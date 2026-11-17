<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Location;
use App\Models\RegisterEntry;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\User;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Database\Seeders\EmailLogSeeder;
use Database\Seeders\InstrumentFamilySeeder;
use Database\Seeders\SetupGroupSeeder;
use Illuminate\Support\Facades\Storage;

// Run after `php artisan migrate:fresh` with `php artisan tinker docs/moderator-guide/sample-data.php`.

fake()->seed(2026);
mt_srand(2026);
Carbon::setTestNow(); // dates are relative to today

(new SetupGroupSeeder)->run();
(new InstrumentFamilySeeder)->run();

Storage::disk('public')->put('images/settings/nsw-logo.png', file_get_contents(__DIR__.'/nsw-logo.png'));
$settings = app(GeneralSettings::class);
$settings->name = 'Nottingham Symphonic Winds';
$settings->logo = 'images/settings/nsw-logo.png';
$settings->primary_color = Color::Red->value;
$settings->setup_groups = true;
$settings->van_drivers = true;
$settings->depping = true;
$settings->single_ensemble = false;
$settings->save();

$rehearsalVenue = Location::create(['name' => 'Rehearsal Hall', 'address' => 'Nottingham']);
$concertVenue = Location::create(['name' => 'Concert Hall', 'address' => 'Nottingham']);

$nsw = Ensemble::factory()->create(['name' => 'Nottingham Symphonic Winds', 'short_name' => 'NSW', 'slug' => 'nsw', 'image' => null]);
$nwe = Ensemble::factory()->create(['name' => 'Nottingham Wind Ensemble', 'short_name' => 'NWE', 'slug' => 'nwe', 'image' => null]);

$account = fn (string $first, string $last, UserRole $role) => User::factory()->create([
    'first_name' => $first,
    'last_name' => $last,
    'username' => strtolower($first),
    'email' => strtolower($first).'@example.com',
    'password' => 'password',
    'role' => $role,
    'image' => null,
    'must_change_password' => false,
]);

$admin = $account('Alex', 'Admin', UserRole::Admin);
$moderator = $account('Morgan', 'Moderator', UserRole::Moderator);

$families = InstrumentFamily::where('name', '!=', 'Conductor')->get();
$groups = SetupGroup::all();
$members = collect(range(1, 28))->map(fn ($i) => User::factory()->create([
    'role' => UserRole::Member,
    'image' => null,
    'setup_group_id' => $groups[$i % $groups->count()]->id,
    'must_change_password' => false,
    'last_login_at' => $i % 5 ? now()->subDays($i) : null,
]));
$members->push($moderator);

foreach ($members as $i => $member) {
    $family = $families[$i % $families->count()];
    $nsw->users()->attach($member, ['instrument_family_id' => $family->id, 'seat_row' => chr(65 + $i % 4), 'seat_column' => intdiv($i, 4) + 1]);
    if ($i % 3 === 0) {
        $nwe->users()->attach($member, ['instrument_family_id' => $family->id, 'seat_row' => chr(65 + $i % 3), 'seat_column' => intdiv($i, 3) + 1]);
    }
}
$nsw->admins()->attach($admin);
$nwe->admins()->attach($admin);

foreach ([$nsw, $nwe] as $ensemble) {
    User::create([
        'first_name' => $ensemble->name,
        'last_name' => 'Ensemble',
        'username' => $ensemble->slug,
        'password' => 'password',
        'role' => UserRole::Ensemble,
    ])->ensembles()->attach($ensemble);
}

foreach ($groups as $i => $group) {
    $group->van_drivers()->attach($members[$i * 2]->id, ['sort' => 1]);
    $group->van_drivers()->attach($members[$i * 2 + 1]->id, ['sort' => 2]);
}

$makeTerm = function (string $name, Carbon $first, int $weeks, array $concerts) use ($rehearsalVenue, $concertVenue, $groups) {
    $term = Term::create(['name' => $name, 'slug' => Str::slug($name), 'location_id' => $rehearsalVenue->id]);
    for ($w = 0; $w < $weeks; $w++) {
        $start = $first->copy()->addWeeks($w)->setTime(19, 30);
        $term->term_dates()->create([
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->setTime(21, 30),
            'setup_group_id' => $groups[$w % $groups->count()]->id,
        ]);
    }
    foreach ($concerts as [$date, $ensemble]) {
        $term->term_dates()->create([
            'start_datetime' => $date->copy()->setTime(19, 30),
            'end_datetime' => $date->copy()->setTime(21, 30),
            'location_id' => $concertVenue->id,
        ])->concert_ensembles()->attach($ensemble);
    }

    return $term;
};

$autumnStart = Carbon::parse('first Tuesday of September '.now()->year);
$autumn = $makeTerm('Autumn '.now()->year, $autumnStart, 14, [[$autumnStart->copy()->addWeeks(14)->next(Carbon::SATURDAY), $nsw], [$autumnStart->copy()->addWeeks(14)->next(Carbon::SUNDAY), $nwe]]);
$springStart = Carbon::parse('first Tuesday of January '.(now()->year + 1));
$makeTerm('Spring '.(now()->year + 1), $springStart, 11, [[$springStart->copy()->addWeeks(11)->next(Carbon::SATURDAY), $nsw]]);

$statuses = [AttendanceStatus::Attending, AttendanceStatus::Attending, AttendanceStatus::Attending, AttendanceStatus::Attending, AttendanceStatus::NotAttending, AttendanceStatus::Depping];
foreach ($autumn->term_dates as $date) {
    foreach ([$nsw, $nwe] as $ensemble) {
        if (! $date->appliesToEnsemble($ensemble)) {
            continue;
        }
        foreach ($ensemble->users()->where('role', '!=', UserRole::Ensemble)->get() as $member) {
            if (mt_rand(1, 10) > 8) {
                continue; // some members have not replied yet
            }
            $status = $statuses[array_rand($statuses)];
            Attendance::create([
                'user_id' => $member->id,
                'edit_user_id' => $member->id,
                'term_date_id' => $date->id,
                'ensemble_id' => $ensemble->id,
                'status' => $status,
                'dep_name' => $status === AttendanceStatus::Depping ? fake()->name() : null,
                'edit_ip' => '127.0.0.1',
            ]);
            if ($date->start_datetime->isPast()) {
                RegisterEntry::create([
                    'user_id' => $member->id,
                    'term_date_id' => $date->id,
                    'ensemble_id' => $ensemble->id,
                    'status' => $status === AttendanceStatus::NotAttending ? RegisterStatus::Absent : (mt_rand(1, 8) === 1 ? RegisterStatus::Late : RegisterStatus::Present),
                    'marked_by_user_id' => $moderator->id,
                ]);
            }
        }
    }
}

(new EmailLogSeeder)->run();

echo "Sample data ready: log in as morgan / password (Moderator) or alex / password (Admin).\n";
