<?php

namespace App\Services;

use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Imports the members.csv download of a Membermojo site, matching rows to users by Membermojo ID and then by email.
 */
class MembermojoImporter
{
    public const ID = 'membermojo ID';

    private const REQUIRED = [self::ID, 'First name', 'Last name', 'Email'];

    /** Custom form questions whose wording can change, by the text their heading starts with. */
    private const HEADING_PREFIXES = [
        'Please treat my membership subscriptions as Gift Aid' => 'Gift Aid',
        'Emergency Contact Address' => 'Emergency Contact Address',
    ];

    private const TEXT_COLUMNS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'address_line1' => 'House name/number and street',
        'address_line2' => 'County',
        'address_city' => 'Town',
        'address_post_code' => 'Postcode',
        'emergency_contact_name' => 'Emergency Contact Name',
        'emergency_contact_address_line1' => 'Emergency Contact Address',
    ];

    private const DATE_COLUMNS = [
        'member_since' => 'Member since',
        'membership_expires_on' => 'Expires on',
    ];

    private array $counts = [
        'Users created' => 0,
        'Users updated' => 0,
        'Users unchanged' => 0,
        'Memberships added' => 0,
        'Instrument families created' => 0,
        'Rows left out as not active' => 0,
        'Members who have left' => 0,
        'Users archived' => 0,
    ];

    private array $warnings = [];

    private array $unmatchedGroups = [];

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    public function __construct(private array $rows, private bool $overwrite = true, private bool $archiveLeavers = false) {}

    /**
     * Reads the CSV into rows keyed by heading, throwing a validation error on the "export" field when it is not a members download.
     *
     * @return array<int, array<string, string>>
     */
    public static function parse(string $csv): array
    {
        if (! mb_check_encoding($csv, 'UTF-8')) {
            $csv = mb_convert_encoding($csv, 'UTF-8', 'Windows-1252');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $csv));
        rewind($stream);

        $headings = array_map(function ($heading) {
            $heading = trim((string) $heading);

            foreach (self::HEADING_PREFIXES as $prefix => $name) {
                if (str_starts_with($heading, $prefix)) {
                    return $name;
                }
            }

            return $heading;
        }, fgetcsv($stream, null, ',', '"', '') ?: []);

        if (array_diff(self::REQUIRED, $headings)) {
            throw ValidationException::withMessages(['export' => __('The file is not a Membermojo members download.')]);
        }

        $rows = [];

        while (($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
            if (count($values) === count($headings)) {
                $rows[] = array_map(fn ($value) => trim((string) $value), array_combine($headings, $values));
            }
        }

        fclose($stream);

        if (! array_filter($rows, fn ($row) => self::isActive($row))) {
            throw ValidationException::withMessages(['export' => __('The Membermojo download has no active members.')]);
        }

        return $rows;
    }

    /** Runs the whole import and rolls it back, so nothing is saved. */
    public function preview(): array
    {
        return $this->run(false);
    }

    public function import(): array
    {
        return $this->run(true);
    }

    private static function isActive(array $row): bool
    {
        return ($row['Membership state'] ?? 'Active') === 'Active';
    }

    private function run(bool $commit): array
    {
        $leavers = [];

        Model::withoutEvents(function () use ($commit, &$leavers) {
            DB::beginTransaction();

            try {
                $current = [];

                foreach ($this->rows as $row) {
                    if (! self::isActive($row)) {
                        $this->counts['Rows left out as not active']++;
                    } elseif (ctype_digit($row[self::ID])) {
                        $current[] = (int) $row[self::ID];
                        $this->importRow($row);
                    }
                }

                foreach ($this->unmatchedGroups as $group => $count) {
                    $this->warnings[] = trans_choice('No ensemble is called :group, so :count member was not added to one.|No ensemble is called :group, so :count members were not added to one.', $count, ['group' => $group]);
                }

                $leavers = $this->leavers($current);
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }

            $commit ? DB::commit() : DB::rollBack();
        });

        return [
            'counts' => $this->counts,
            'warnings' => array_values(array_unique($this->warnings)),
            'leavers' => $leavers,
        ];
    }

    private function importRow(array $row): void
    {
        $id = (int) $row[self::ID];
        $name = trim($row['First name'].' '.$row['Last name']);
        $email = filter_var($row['Email'], FILTER_VALIDATE_EMAIL) ?: null;

        $user = User::withTrashed()->where('membermojo_id', $id)->first();

        if (! $user && $email) {
            $user = User::withTrashed()
                ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
                ->whereNull('membermojo_id')
                ->where('role', '!=', UserRole::Ensemble)
                ->first();
        }

        if ($user?->trashed()) {
            $this->warnings[] = __(':name is archived in Readererer, so they were left alone. Unarchive them to import their details.', ['name' => $name]);

            return;
        }

        if ($name === '') {
            $this->warnings[] = __('Membermojo member :id has no name, so it was left out.', ['id' => $id]);

            return;
        }

        if ($email && User::withTrashed()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->when($user, fn ($query) => $query->whereKeyNot($user->id))->exists()) {
            $this->warnings[] = __(':name has the same email address as another user, so their email address was not imported.', ['name' => $name]);
            $email = null;
        }

        $attributes = array_filter($this->attributes($row) + ['email' => $email], fn ($value) => $value !== null);

        if ($this->hasColumn($row, 'Setup group') && config('app.readererer_setup_groups')) {
            $attributes += $this->setupGroup($row['Setup group']);
        }

        if (! $user) {
            $user = User::forceCreate($attributes + [
                'membermojo_id' => $id,
                'username' => User::generateUniqueUsername($row['First name'], $row['Last name']),
                'password' => Hash::make(Str::password(64)),
                'role' => UserRole::Member,
                'must_change_password' => true,
            ]);
            $this->counts['Users created']++;
        } else {
            if (! $this->overwrite) {
                $attributes = array_filter($attributes, fn ($key) => blank($user->getAttribute($key)), ARRAY_FILTER_USE_KEY);
            }

            $user->forceFill($attributes + ['membermojo_id' => $id]);
            $this->counts[$user->isDirty() ? 'Users updated' : 'Users unchanged']++;
            $user->save();
        }

        $this->importMembership($user, $row);
    }

    private function hasColumn(array $row, string $heading): bool
    {
        return array_key_exists($heading, $row);
    }

    /** The user attributes a row carries; a column missing from the download is left out, and so is an empty value, so neither wipes what Readererer holds. */
    private function attributes(array $row): array
    {
        $attributes = [];

        foreach (self::TEXT_COLUMNS as $attribute => $heading) {
            $attributes[$attribute] = ($row[$heading] ?? '') ?: null;
        }

        foreach (self::DATE_COLUMNS as $attribute => $heading) {
            $date = $row[$heading] ?? '';
            $attributes[$attribute] = Carbon::canBeCreatedFromFormat($date, 'Y-m-d') ? $date : null;
        }

        $attributes['phone_number'] = $this->phone($row['Mobile number'] ?? '') ?? $this->phone($row['Home Telephone Number'] ?? '');
        $attributes['emergency_contact_number'] = $this->phone($row['Emergency Contact Telephone Number'] ?? '');

        if ($this->hasColumn($row, 'Gift Aid')) {
            $attributes['is_gift_aiding_subs'] = strtolower($row['Gift Aid']) === 'yes';
        }

        if ($this->hasColumn($row, 'Photographs')) {
            $attributes['has_photo_permission'] = str_starts_with(strtolower($row['Photographs']), 'i have no objection');
        }

        return $attributes;
    }

    /** Membermojo prefixes mobile numbers with "t:". */
    private function phone(string $value): ?string
    {
        return trim(preg_replace('/^t:/i', '', $value)) ?: null;
    }

    /** @return array{setup_group_id?: ?int} */
    private function setupGroup(string $value): array
    {
        if ($value === '' || strtolower($value) === 'none') {
            return ['setup_group_id' => null];
        }

        $number = fn (string $name) => preg_match('/(\d+)\s*$/', $name, $match) ? (int) $match[1] : null;

        $group = SetupGroup::all()->first(fn (SetupGroup $group) => strcasecmp($group->name, $value) === 0)
            ?? ($number($value) === null ? null : SetupGroup::all()->first(fn (SetupGroup $group) => $number($group->name) === $number($value)));

        if (! $group) {
            $this->warnings[] = __('No setup group matches :group, so members in it kept the setup group they had.', ['group' => $value]);

            return [];
        }

        return ['setup_group_id' => $group->id];
    }

    private function importMembership(User $user, array $row): void
    {
        $group = $row['Which Group'] ?? '';

        if ($group === '') {
            return;
        }

        $ensemble = Ensemble::all()->first(fn (Ensemble $ensemble) => in_array(mb_strtolower($group), [mb_strtolower($ensemble->name), mb_strtolower((string) $ensemble->short_name)], true));

        if (! $ensemble) {
            $this->unmatchedGroups[$group] = ($this->unmatchedGroups[$group] ?? 0) + 1;

            return;
        }

        $family = $this->instrumentFamily($row['Instrument'] ?? '');

        if (! $user->ensembles()->whereKey($ensemble->id)->exists()) {
            $user->ensembles()->attach($ensemble->id, ['instrument_family_id' => $family]);
            $this->counts['Memberships added']++;
        } elseif ($this->overwrite && $family) {
            $user->ensembles()->updateExistingPivot($ensemble->id, ['instrument_family_id' => $family]);
        }
    }

    private function instrumentFamily(string $instrument): ?int
    {
        if ($instrument === '' || strtolower($instrument) === 'n/a') {
            return null;
        }

        $family = InstrumentFamily::all()->first(fn (InstrumentFamily $family) => strcasecmp($family->name, $instrument) === 0);

        if (! $family) {
            $colors = Color::cases();
            $family = InstrumentFamily::forceCreate(['name' => $instrument, 'color' => $colors[InstrumentFamily::count() % count($colors)]]);
            $this->counts['Instrument families created']++;
        }

        return $family->id;
    }

    /**
     * Users linked to Membermojo who are no longer active members there.
     *
     * @param  array<int, int>  $current
     * @return array<int, string>
     */
    private function leavers(array $current): array
    {
        $leavers = User::whereNotNull('membermojo_id')->whereNotIn('membermojo_id', $current)->orderBy('last_name')->get();
        $this->counts['Members who have left'] = $leavers->count();

        if ($this->archiveLeavers) {
            foreach ($leavers as $leaver) {
                if ($leaver->role === UserRole::Admin) {
                    $this->warnings[] = __(':name is an admin, so they were not archived.', ['name' => $leaver->name]);

                    continue;
                }

                $leaver->delete();
                $this->counts['Users archived']++;
            }
        }

        return $leavers->pluck('name')->all();
    }
}
