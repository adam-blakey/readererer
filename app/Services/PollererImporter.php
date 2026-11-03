<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Setting;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

/** Imports the JSON file that Pollererer's admin export downloads into an empty site. */
class PollererImporter
{
    public const COLUMNS = [
        'ensembles' => ['ID', 'safe_name', 'name', 'admin_email', 'image', 'hidden'],
        'members' => ['ID', 'first_name', 'last_name', 'instrument', 'row', 'seat', 'user_level', 'setup_group', 'deleted'],
        'members-ensembles' => ['ID', 'member_ID', 'ensemble_ID'],
        'logins' => ['ID', 'email', 'password'],
        'terms' => ['ID', 'safe_name', 'name', 'hidden'],
        'term_dates' => ['ID', 'datetime', 'datetime_end', 'is_featured', 'term_ID', 'setup_group', 'deleted'],
        'attendance' => ['ID', 'member_ID', 'edit_datetime', 'edit_member_ID', 'term_dates_ID', 'ensemble_ID', 'IP', 'status'],
    ];

    private const REQUIRED_INTEGERS = ['ID', 'member_ID', 'ensemble_ID', 'term_ID', 'term_dates_ID', 'datetime', 'datetime_end', 'edit_datetime'];

    private const INTEGERS = ['edit_member_ID', 'is_featured', 'hidden', 'deleted', 'user_level', 'status', 'row', 'seat'];

    private const SETUP_GROUP_COLORS = [1 => Color::Azure, 2 => Color::Teal, 3 => Color::Orange, 4 => Color::Purple];

    private const TAXONOMY = [
        'taxonomy_rehearsal' => 'rehearsal',
        'taxonomy_rehearsals' => 'rehearsals',
        'taxonomy_concert' => 'concert',
        'taxonomy_ensemble' => 'ensemble',
        'taxonomy_ensembles' => 'ensembles',
    ];

    private const BOOLEAN_SETTINGS = ['assume_attending' => 'assume_attending', 'repeat_headings' => 'repeating_headings'];

    private const IMAGE_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    private array $counts;

    private array $warnings = [];

    /** @var array<int, array{url: string, ensemble: ?int, name: string}> */
    private array $images = [];

    private array $ensembles = [];

    private array $users = [];

    private array $ensembleLogins = [];

    private array $terms = [];

    private array $termDates = [];

    private array $setupGroups;

    private array $instrumentFamilies;

    private string $unusablePassword;

    public function __construct(private array $export, private User $admin, private bool $keepClockTimes = false)
    {
        $this->counts = array_fill_keys([
            'Ensembles', 'Ensemble admins', 'Users created', 'Existing users linked', 'Memberships',
            'Instrument families', 'Setup groups', 'Terms', 'Term dates', 'Attendance replies', 'Settings',
        ], 0);
    }

    /** Decodes and checks an export, throwing a validation error on the "export" field when it cannot be imported. */
    public static function parse(string $json): array
    {
        try {
            $export = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['export' => __('The file is not valid JSON.')]);
        }

        if (! is_array($export) || ($export['format'] ?? null) !== 'pollererer-export') {
            throw ValidationException::withMessages(['export' => __('The file is not a Pollererer export.')]);
        }

        if (($export['version'] ?? null) !== 1) {
            throw ValidationException::withMessages(['export' => __('Only version 1 Pollererer exports can be imported.')]);
        }

        $rules = [
            'timezone' => ['required', 'timezone:all_with_bc'],
            'settings' => ['present', 'array'],
            'settings.group_name' => ['nullable', 'string', 'max:255'],
            'settings.logo_url' => ['nullable', 'string'],
            'tables' => ['required', 'array'],
        ];

        foreach (array_keys(self::TAXONOMY) as $key) {
            $rules['settings.'.$key] = ['nullable', 'string', 'max:50'];
        }

        foreach (array_keys(self::COLUMNS) as $table) {
            $rules['tables.'.$table] = ['present', 'list'];
        }

        $validator = Validator::make($export, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['export' => $validator->errors()->all()]);
        }

        $errors = self::rowErrors($export['tables']);

        if ($errors) {
            throw ValidationException::withMessages(['export' => array_slice($errors, 0, 10)]);
        }

        return $export;
    }

    private static function rowErrors(array $tables): array
    {
        $errors = [];

        foreach (self::COLUMNS as $table => $columns) {
            $ids = [];

            foreach ($tables[$table] as $index => $row) {
                $missing = is_array($row) ? array_diff($columns, array_keys($row)) : $columns;

                if ($missing) {
                    $errors[] = __('Row :row of :table is missing :columns.', ['row' => $index + 1, 'table' => $table, 'columns' => implode(', ', $missing)]);

                    continue;
                }

                foreach ($columns as $column) {
                    $value = $row[$column];
                    $valid = match (true) {
                        in_array($column, self::REQUIRED_INTEGERS, true) => self::isInteger($value),
                        in_array($column, self::INTEGERS, true) => $value === null || self::isInteger($value),
                        default => $value === null || is_scalar($value),
                    };

                    if (! $valid) {
                        $errors[] = __('Row :row of :table has an invalid :column.', ['row' => $index + 1, 'table' => $table, 'column' => $column]);
                    }
                }

                $ids[] = (int) $row['ID'];
            }

            if (count($ids) !== count(array_unique($ids))) {
                $errors[] = __('The :table table has duplicate IDs.', ['table' => $table]);
            }
        }

        if ($errors) {
            return $errors;
        }

        $slugs = array_map(fn ($row) => self::slug($row, 'ensemble'), $tables['ensembles']);

        if (count($slugs) !== count(array_unique($slugs))) {
            $errors[] = __('Two ensembles have the same safe name.');
        }

        $slugs = array_map(fn ($row) => self::slug($row, 'term'), $tables['terms']);

        if (count($slugs) !== count(array_unique($slugs))) {
            $errors[] = __('Two terms have the same safe name.');
        }

        return $errors;
    }

    private static function slug(array $row, string $fallback): string
    {
        $slug = filled($row['safe_name']) ? (string) $row['safe_name'] : Str::slug((string) $row['name']);

        return $slug === '' ? $fallback.'-'.(int) $row['ID'] : $slug;
    }

    private static function isInteger(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1);
    }

    /** Ensembles, terms, term dates and attendance, including soft-deleted ones; users may already exist. */
    public static function siteIsEmpty(): bool
    {
        return ! Ensemble::withTrashed()->exists()
            && ! Term::withTrashed()->exists()
            && ! TermDate::withTrashed()->exists()
            && ! Attendance::exists();
    }

    /** Runs the whole import and rolls it back, so nothing is saved or downloaded. */
    public function preview(): array
    {
        return $this->run(false);
    }

    public function import(): array
    {
        return $this->run(true);
    }

    private function run(bool $commit): array
    {
        Model::withoutEvents(function () use ($commit) {
            DB::beginTransaction();

            try {
                $this->importRows();
            } catch (Throwable $exception) {
                DB::rollBack();

                throw $exception;
            }

            $commit ? DB::commit() : DB::rollBack();
        });

        if ($commit) {
            $this->downloadImages();
        }

        $handled = array_merge(['group_name', 'logo_url'], array_keys(self::BOOLEAN_SETTINGS), array_keys(self::TAXONOMY));

        return [
            'counts' => $this->counts,
            'warnings' => array_values($this->warnings),
            'images' => array_column($this->images, 'url'),
            'ignored_settings' => array_values(array_diff(array_keys($this->export['settings']), $handled)),
            'timezone_warning' => $this->timezoneWarning(),
            'exported_at' => $this->export['exported_at'] ?? null,
            'anonymised' => (bool) ($this->export['anonymised'] ?? false),
        ];
    }

    private function importRows(): void
    {
        $this->unusablePassword = Hash::make(Str::password(64));
        $this->setupGroups = SetupGroup::pluck('id', 'week')->all();
        $this->instrumentFamilies = InstrumentFamily::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id])->all();

        $this->importEnsembles();
        $this->importUsers();
        $this->importMemberships();
        $this->importEnsembleAdmins();
        $this->importTerms();
        $this->importTermDates();
        $this->importAttendance();
        $this->importSettings();
    }

    private function table(string $name): array
    {
        return $this->export['tables'][$name];
    }

    private function members(): array
    {
        return array_column($this->table('members'), null, 'ID');
    }

    private function importEnsembles(): void
    {
        $members = $this->members();
        $seated = [];

        foreach ($this->table('members-ensembles') as $row) {
            $member = $members[$row['member_ID']] ?? null;

            if ($member && ! (int) $member['deleted'] && ((int) $member['row'] || (int) $member['seat'])) {
                $seated[(int) $row['ensemble_ID']] = true;
            }
        }

        foreach ($this->table('ensembles') as $row) {
            $image = trim((string) $row['image']);
            $url = $this->imageUrl($image);

            $ensemble = Ensemble::forceCreate([
                'name' => (string) $row['name'],
                'slug' => self::slug($row, 'ensemble'),
                'show' => ! (int) $row['hidden'],
                'seating_plan_enabled' => isset($seated[(int) $row['ID']]),
                'image' => $url,
            ]);

            if ($url) {
                $this->images[] = ['url' => $url, 'ensemble' => $ensemble->id, 'name' => $ensemble->name];
            } elseif ($image !== '') {
                $this->warnings[] = __('The image for :ensemble is not a web address and the logo gives no address to find it on, so it was left out.', ['ensemble' => $ensemble->name]);
            }

            $this->ensembles[(int) $row['ID']] = $ensemble->id;
            $this->counts['Ensembles']++;
        }
    }

    private function imageUrl(string $image): ?string
    {
        if ($image === '' || Str::isUrl($image, ['http', 'https'])) {
            return $image === '' ? null : $image;
        }

        $logo = parse_url(trim((string) ($this->export['settings']['logo_url'] ?? '')));

        if (! isset($logo['scheme'], $logo['host']) || ! in_array($logo['scheme'], ['http', 'https'], true)) {
            return null;
        }

        return $logo['scheme'].'://'.$logo['host'].(isset($logo['port']) ? ':'.$logo['port'] : '').'/'.ltrim($image, '/');
    }

    private function importUsers(): void
    {
        $logins = array_column($this->table('logins'), null, 'ID');

        foreach ($this->table('members') as $row) {
            $id = (int) $row['ID'];

            if ($id === 0) {
                continue;
            }

            $login = $logins[$id] ?? null;
            $loginName = trim((string) ($login['email'] ?? ''));
            $email = str_contains($loginName, '@') ? $loginName : null;
            $name = trim($row['first_name'].' '.$row['last_name']) ?: __('Member :id', ['id' => $id]);

            if ($email && $existing = $this->findUserByEmail($email)) {
                if (! in_array($existing->id, $this->users, true)) {
                    $this->users[$id] = $existing->id;
                    $this->counts['Existing users linked']++;

                    continue;
                }

                $this->warnings[] = __(':name has the same email address as another member, so it was left out.', ['name' => $name]);
                $email = null;
            }

            $ensemble = $id < 0 ? ($this->ensembles[-(int) $row['user_level']] ?? null) : null;

            if ($id < 0 && ! $ensemble) {
                $this->warnings[] = __('The ensemble login :name belongs to no imported ensemble.', ['name' => $name]);
            }

            $username = $email === null && $loginName !== '' ? $loginName : null;

            if ($username !== null && User::withTrashed()->where('username', $username)->exists()) {
                $this->warnings[] = __('The username :username is already taken, so :name was given another.', ['username' => $username, 'name' => $name]);
                $username = null;
            }

            $user = User::forceCreate([
                'first_name' => trim((string) $row['first_name']),
                'last_name' => trim((string) $row['last_name']),
                'username' => $username ?? $this->generateUsername((string) $row['first_name'], (string) $row['last_name'], $id),
                'email' => $email,
                'password' => $this->unusablePassword,
                'role' => $this->role($row),
                'setup_group_id' => $this->setupGroup($row['setup_group']),
                'must_change_password' => false,
                'deleted_at' => (int) $row['deleted'] ? now() : null,
            ]);

            if ($login && password_get_info((string) $login['password'])['algoName'] === 'bcrypt') {
                // Written directly: the hashed cast refuses a bcrypt hash whose cost differs from the configured rounds.
                DB::table('users')->where('id', $user->id)->update(['password' => $login['password']]);
            } elseif ($login) {
                $this->warnings[] = __('The password for :name is not a bcrypt hash, so they will need to reset it.', ['name' => $name]);
            }

            if ($ensemble) {
                $this->ensembleLogins[-(int) $row['user_level']] = $user->id;
            }

            $this->users[$id] = $user->id;
            $this->counts['Users created']++;
        }
    }

    private function role(array $member): UserRole
    {
        if ((int) $member['ID'] < 0) {
            return UserRole::Ensemble;
        }

        return match ((int) $member['user_level']) {
            2 => UserRole::Moderator,
            3 => UserRole::Admin,
            default => UserRole::Member,
        };
    }

    private function generateUsername(string $firstName, string $lastName, int $id): string
    {
        return Str::slug($firstName.' '.$lastName) === ''
            ? User::generateUniqueUsername('member', (string) abs($id))
            : User::generateUniqueUsername($firstName, $lastName);
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::withTrashed()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();
    }

    /** Pollererer numbers setup groups by the first character, e.g. "2" or "2.1" are both group 2. */
    private function setupGroup(?string $value): ?int
    {
        $number = (int) substr(trim((string) $value), 0, 1);

        if ($number === 0) {
            return null;
        }

        if (! isset($this->setupGroups[$number])) {
            $this->setupGroups[$number] = SetupGroup::forceCreate([
                'name' => 'Setup group '.$number,
                'week' => $number,
                'color' => self::SETUP_GROUP_COLORS[$number] ?? Color::Blue,
            ])->id;
            $this->counts['Setup groups']++;
        }

        return $this->setupGroups[$number];
    }

    private function instrumentFamily(?string $instrument): ?int
    {
        $name = trim((string) $instrument);

        if ($name === '') {
            return null;
        }

        $key = mb_strtolower($name);

        if (! isset($this->instrumentFamilies[$key])) {
            $colors = Color::cases();
            $this->instrumentFamilies[$key] = InstrumentFamily::forceCreate([
                'name' => $name,
                'color' => $colors[$this->counts['Instrument families'] % count($colors)],
            ])->id;
            $this->counts['Instrument families']++;
        }

        return $this->instrumentFamilies[$key];
    }

    private function importMemberships(): void
    {
        $members = $this->members();
        $memberships = [];

        foreach ($this->table('members-ensembles') as $row) {
            if ((int) $row['member_ID'] === 0) {
                continue;
            }

            $user = $this->users[(int) $row['member_ID']] ?? null;
            $ensemble = $this->ensembles[(int) $row['ensemble_ID']] ?? null;

            if (! $user || ! $ensemble) {
                $this->warnings[] = __('Membership :id was skipped because its member or ensemble is missing.', ['id' => $row['ID']]);

                continue;
            }

            $member = $members[$row['member_ID']];
            $memberships[$user.'-'.$ensemble] ??= [
                'user_id' => $user,
                'ensemble_id' => $ensemble,
                'instrument_family_id' => $this->instrumentFamily($member['instrument']),
                'seat_row' => (int) $member['row'] > 0 ? $this->rowLetter((int) $member['row']) : null,
                'seat_column' => (int) $member['seat'] ? (string) (int) $member['seat'] : null,
            ];
        }

        foreach ($this->ensembleLogins as $ensemble => $user) {
            $memberships[$user.'-'.$this->ensembles[$ensemble]] ??= [
                'user_id' => $user,
                'ensemble_id' => $this->ensembles[$ensemble],
                'instrument_family_id' => null,
                'seat_row' => null,
                'seat_column' => null,
            ];
        }

        $now = now();

        foreach (array_chunk($memberships, 500) as $chunk) {
            DB::table('user_ensemble')->insert(array_map(fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now], $chunk));
        }

        $this->counts['Memberships'] = count($memberships);
    }

    private function rowLetter(int $row): string
    {
        $letter = 'A';

        for ($i = 1; $i < $row; $i++) {
            $letter = str_increment($letter);
        }

        return $letter;
    }

    private function importEnsembleAdmins(): void
    {
        foreach ($this->table('ensembles') as $row) {
            $emails = array_unique(array_map('mb_strtolower', array_filter(array_map('trim', explode(',', (string) $row['admin_email'])))));

            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $this->warnings[] = __('The admin address :email for :ensemble is not an email address, so it was left out.', ['email' => $email, 'ensemble' => $row['name']]);

                    continue;
                }

                $user = $this->findUserByEmail($email) ?? $this->createAdminUser($email);

                DB::table('ensemble_admins')->insertOrIgnore(['ensemble_id' => $this->ensembles[(int) $row['ID']], 'admin_id' => $user->id]);
                $this->counts['Ensemble admins']++;
            }
        }
    }

    private function createAdminUser(string $email): User
    {
        $name = Str::before($email, '@');

        $this->counts['Users created']++;

        return User::forceCreate([
            'first_name' => $name,
            'last_name' => '',
            'username' => User::generateUniqueUsername($name, ''),
            'email' => $email,
            'password' => $this->unusablePassword,
            'role' => UserRole::Guest,
            'must_change_password' => false,
        ]);
    }

    private function importTerms(): void
    {
        foreach ($this->table('terms') as $row) {
            $this->terms[(int) $row['ID']] = Term::forceCreate([
                'name' => (string) $row['name'],
                'slug' => self::slug($row, 'term'),
                'deleted_at' => (int) $row['hidden'] ? now() : null,
            ])->id;
            $this->counts['Terms']++;
        }
    }

    private function time(int $timestamp): Carbon
    {
        return $this->keepClockTimes
            ? Carbon::createFromTimestamp($timestamp, $this->export['timezone'])->shiftTimezone(config('app.timezone'))
            : Carbon::createFromTimestamp($timestamp, config('app.timezone'));
    }

    private function importTermDates(): void
    {
        foreach ($this->table('term_dates') as $row) {
            $term = $this->terms[(int) $row['term_ID']] ?? null;

            if (! $term) {
                $this->warnings[] = __('Term date :id was skipped because its term is missing.', ['id' => $row['ID']]);

                continue;
            }

            $termDate = TermDate::forceCreate([
                'term_id' => $term,
                'start_datetime' => $this->time((int) $row['datetime']),
                'end_datetime' => $this->time((int) $row['datetime_end']),
                'setup_group_id' => $this->setupGroup($row['setup_group']),
                'deleted_at' => (int) $row['deleted'] ? now() : null,
            ]);

            $featured = (int) $row['is_featured'];
            $concertEnsembles = match (true) {
                $featured > 0 => array_values($this->ensembles),
                $featured < 0 => array_filter([$this->ensembles[-$featured] ?? null]),
                default => [],
            };

            if ($featured < 0 && ! $concertEnsembles) {
                $this->warnings[] = __('Term date :id is a concert for a missing ensemble, so it was imported as a rehearsal.', ['id' => $row['ID']]);
            }

            $termDate->concert_ensembles()->attach($concertEnsembles);

            $this->termDates[(int) $row['ID']] = $termDate->id;
            $this->counts['Term dates']++;
        }
    }

    private function importAttendance(): void
    {
        $skipped = 0;
        $rows = [];

        foreach ($this->table('attendance') as $row) {
            $user = $this->users[(int) $row['member_ID']] ?? null;
            $termDate = $this->termDates[(int) $row['term_dates_ID']] ?? null;
            $ensemble = $this->ensembles[(int) $row['ensemble_ID']] ?? null;
            $status = match ((string) $row['status']) {
                '1' => AttendanceStatus::Attending,
                '0' => AttendanceStatus::NotAttending,
                default => null,
            };

            if (! $user || ! $termDate || ! $ensemble || ! $status) {
                $skipped++;

                continue;
            }

            $editedAt = $this->time((int) $row['edit_datetime'])->toDateTimeString();

            $rows[] = [
                'user_id' => $user,
                'term_date_id' => $termDate,
                'ensemble_id' => $ensemble,
                'status' => $status->value,
                'edit_user_id' => $this->editor((int) $row['edit_member_ID'], (int) $row['ensemble_ID']),
                'edit_ip' => (string) $row['IP'],
                'created_at' => $editedAt,
                'updated_at' => $editedAt,
            ];

            if (count($rows) === 500) {
                DB::table('attendances')->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table('attendances')->insert($rows);
        }

        $this->counts['Attendance replies'] = count($this->table('attendance')) - $skipped;

        if ($skipped) {
            $this->warnings[] = trans_choice(
                ':count attendance reply was skipped because its member, date, ensemble or status is missing.|:count attendance replies were skipped because their member, date, ensemble or status is missing.',
                $skipped
            );
        }
    }

    /** Pollererer records poll edits made without logging in against the guest, member 0. */
    private function editor(int $member, int $ensemble): int
    {
        if ($member === 0) {
            if (isset($this->ensembleLogins[$ensemble])) {
                return $this->ensembleLogins[$ensemble];
            }

            $this->warnings['guest-'.$ensemble] = __('Some replies for ensemble :id were made by the guest, but that ensemble has no login, so they are recorded as edited by you.', ['id' => $ensemble]);

            return $this->admin->id;
        }

        if (isset($this->users[$member])) {
            return $this->users[$member];
        }

        $this->warnings['unknown-editor'] = __('Some replies were edited by members missing from the export, so they are recorded as edited by you.');

        return $this->admin->id;
    }

    private function importSettings(): void
    {
        $settings = $this->export['settings'];

        if (filled($settings['group_name'] ?? null)) {
            Setting::put('name', $settings['group_name']);
            $this->counts['Settings']++;
        }

        foreach (self::BOOLEAN_SETTINGS as $from => $to) {
            if (isset($settings[$from])) {
                Setting::put($to, filter_var($settings[$from], FILTER_VALIDATE_BOOLEAN));
                $this->counts['Settings']++;
            }
        }

        $terminology = [];

        foreach (self::TAXONOMY as $key => $word) {
            if (filled($settings[$key] ?? null)) {
                $terminology[$word] = $settings[$key];
            }
        }

        if ($terminology) {
            Setting::put('terminology', $terminology);
            $this->counts['Settings']++;
        }

        $logo = trim((string) ($settings['logo_url'] ?? ''));

        if (Str::isUrl($logo, ['http', 'https'])) {
            $this->images[] = ['url' => $logo, 'ensemble' => null, 'name' => ''];
        } elseif ($logo !== '') {
            $this->warnings[] = __('The logo is not a web address, so it was left out.');
        }
    }

    private function downloadImages(): void
    {
        foreach ($this->images as ['url' => $url, 'ensemble' => $ensemble, 'name' => $name]) {
            $path = $this->download($url, $ensemble ? 'images/ensembles' : 'images/settings');

            if ($ensemble && $path) {
                Ensemble::whereKey($ensemble)->update(['image' => $path]);
            } elseif ($ensemble) {
                $this->warnings[] = __('The image for :ensemble could not be downloaded from :url, so it links to that address instead.', ['ensemble' => $name, 'url' => $url]);
            } elseif ($path) {
                Setting::put('logo', $path);
                $this->counts['Settings']++;
            } else {
                $this->warnings[] = __('The logo could not be downloaded from :url, so it was left out.', ['url' => $url]);
            }
        }
    }

    private function download(string $url, string $directory): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
        } catch (Throwable) {
            return null;
        }

        $type = strtolower(trim(Str::before((string) $response->header('Content-Type'), ';')));
        $body = $response->body();

        if (! $response->successful()
            || ! isset(self::IMAGE_EXTENSIONS[$type])
            || strlen($body) > config('app.readererer_image_max_kilobytes') * 1024
            || @getimagesizefromstring($body) === false) {
            return null;
        }

        $path = $directory.'/'.Str::random(40).'.'.self::IMAGE_EXTENSIONS[$type];
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /** Rehearsals that start at one local time all year shift by an hour in the site's timezone when the two differ. */
    private function timezoneWarning(): ?string
    {
        $source = new DateTimeZone($this->export['timezone']);
        $site = new DateTimeZone(config('app.timezone'));
        $times = [];

        foreach ($this->table('term_dates') as $row) {
            if ((int) $row['is_featured'] !== 0 || (int) $row['deleted']) {
                continue;
            }

            $start = new DateTime('@'.(int) $row['datetime']);
            $season = $start->setTimezone($source)->format('I') ? 'summer' : 'winter';
            $time = $start->setTimezone($site)->format('H:i');
            $times[$season][$time] = ($times[$season][$time] ?? 0) + 1;
        }

        if (count($times) < 2) {
            return null;
        }

        arsort($times['winter']);
        arsort($times['summer']);
        $winter = array_key_first($times['winter']);
        $summer = array_key_first($times['summer']);

        if ($winter === $summer) {
            return null;
        }

        return __('Rehearsals mostly start at :winter in winter but :summer in summer. The site\'s timezone (:site) probably doesn\'t match Pollererer\'s (:source). Check APP_TIMEZONE before importing.', [
            'winter' => $winter,
            'summer' => $summer,
            'site' => $site->getName(),
            'source' => $source->getName(),
        ]);
    }
}
