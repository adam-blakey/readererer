<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use App\Services\PollererImporter;
use App\Settings\GeneralSettings;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    Http::fake(['*' => Http::response(base64_decode(ONE_PIXEL_PNG), 200, ['Content-Type' => 'image/png'])]);
    config(['app.timezone' => 'Europe/London']);
});

function pollererer_export(?callable $change = null): array
{
    $export = json_decode(file_get_contents(base_path('tests/Fixtures/pollererer-export.json')), true);

    if ($change) {
        $change($export);
    }

    return $export;
}

function import_pollererer(?callable $change = null, ?User $admin = null): array
{
    return (new PollererImporter(pollererer_export($change), $admin ?? make_user(UserRole::Admin)))->import();
}

function pollererer_upload(?callable $change = null): UploadedFile
{
    return UploadedFile::fake()->createWithContent('export.json', json_encode(pollererer_export($change)));
}

function imported_user(string $username): User
{
    return User::withTrashed()->where('username', $username)->firstOrFail();
}

test('ensembles keep their name, slug and visibility, and only seated ensembles get a seating plan', function () {
    import_pollererer();

    $band = Ensemble::where('slug', 'concert-band')->firstOrFail();
    $jazz = Ensemble::where('slug', 'jazz-group')->firstOrFail();

    expect($band->name)->toBe('Concert Band')
        ->and($band->show)->toBeTrue()
        ->and($band->seating_plan_enabled)->toBeTrue()
        ->and($jazz->show)->toBeFalse()
        ->and($jazz->seating_plan_enabled)->toBeFalse();
});

test('ensemble admins match users by email and new addresses become guest users', function () {
    $report = import_pollererer();

    $admins = Ensemble::where('slug', 'concert-band')->firstOrFail()->admins;
    $newAdmin = User::where('email', 'newadmin@example.com')->firstOrFail();

    expect($admins->pluck('id')->sort()->values()->all())->toBe(collect([imported_user('alice.smith')->id, $newAdmin->id])->sort()->values()->all())
        ->and($newAdmin->role)->toBe(UserRole::Guest)
        ->and($newAdmin->username)->toBe('newadmin')
        ->and(Ensemble::where('slug', 'jazz-group')->firstOrFail()->admins)->toBeEmpty()
        ->and($report['counts']['Ensemble admins'])->toBe(2);
});

test('hidden terms are soft-deleted and term images are dropped', function () {
    import_pollererer();

    expect(Term::where('slug', 'autumn-2025')->firstOrFail()->name)->toBe('Autumn 2025')
        ->and(Term::where('slug', 'spring-2024')->exists())->toBeFalse()
        ->and(Term::onlyTrashed()->where('slug', 'spring-2024')->exists())->toBeTrue();
});

test('term dates keep their times in the app timezone, deletions and concert ensembles', function () {
    $report = import_pollererer();

    $dates = TermDate::withTrashed()->with('concert_ensembles')->orderBy('start_datetime')->get()->keyBy(fn ($date) => $date->start_datetime->format('Y-m-d'));

    expect($dates['2025-10-01']->start_datetime->format('H:i'))->toBe('19:00')
        ->and($dates['2025-10-01']->end_datetime->format('H:i'))->toBe('21:00')
        ->and($dates['2025-10-01']->is_concert)->toBeFalse()
        ->and($dates['2025-11-12']->trashed())->toBeTrue()
        ->and($dates['2025-12-06']->concert_ensembles->pluck('slug')->all())->toBe(['concert-band', 'jazz-group'])
        ->and($dates['2025-12-13']->concert_ensembles->pluck('slug')->all())->toBe(['jazz-group'])
        ->and($dates)->toHaveCount(6)
        ->and($report['warnings'])->toContain('Term date 6 was skipped because its term is missing.');
});

test('dates and members share setup groups numbered by their first character', function () {
    import_pollererer();

    $groups = SetupGroup::orderBy('week')->get();

    expect($groups->pluck('week')->all())->toBe([1, 2])
        ->and($groups->pluck('color')->all())->toBe([Color::Azure, Color::Teal])
        ->and(imported_user('alice.smith')->setup_group_id)->toBe($groups[0]->id)
        ->and(imported_user('bob.jones')->setup_group_id)->toBe($groups[1]->id)
        ->and(imported_user('carol.white')->setup_group_id)->toBeNull()
        ->and(TermDate::whereDate('start_datetime', '2025-11-05')->value('setup_group_id'))->toBe($groups[1]->id);
});

test('members get roles, usernames, emails and deletions', function () {
    import_pollererer();

    $alice = imported_user('alice.smith');
    $login = imported_user('concertband');

    expect($alice->role)->toBe(UserRole::Admin)
        ->and($alice->email)->toBe('alice@example.com')
        ->and($alice->must_change_password)->toBeFalse()
        ->and($alice->image)->toBeNull()
        ->and(imported_user('bob.jones')->role)->toBe(UserRole::Moderator)
        ->and(imported_user('carol.white')->role)->toBe(UserRole::Member)
        ->and(imported_user('carol.white')->email)->toBeNull()
        ->and(imported_user('dan.brown')->role)->toBe(UserRole::Member)
        ->and(imported_user('dan.brown')->trashed())->toBeTrue()
        ->and($login->role)->toBe(UserRole::Ensemble)
        ->and($login->email)->toBeNull()
        ->and($login->ensembles->pluck('slug')->all())->toBe(['concert-band'])
        ->and(User::where('first_name', 'Guest')->exists())->toBeFalse();
});

test('the original Pollererer passwords still log in', function () {
    import_pollererer();

    expect(Auth::attempt(['email' => 'alice@example.com', 'password' => 'alice-password']))->toBeTrue()
        ->and(Auth::attempt(['username' => 'concertband', 'password' => 'band-password']))->toBeTrue()
        ->and(Auth::attempt(['email' => 'bob@example.com', 'password' => 'wrong']))->toBeFalse();
});

test('a login email that matches an existing user links to that user', function () {
    $existing = make_user(UserRole::Member, ['email' => 'Bob@Example.com', 'username' => 'bobby']);

    $report = import_pollererer();

    expect(User::where('first_name', 'Bob')->where('last_name', 'Jones')->exists())->toBeFalse()
        ->and($existing->ensembles()->pluck('slug')->all())->toBe(['concert-band'])
        ->and($report['counts']['Existing users linked'])->toBe(1);
});

test('memberships carry instrument families and seats', function () {
    import_pollererer();

    $flute = InstrumentFamily::where('name', 'Flute')->firstOrFail();
    $pivot = fn (string $username, string $slug) => imported_user($username)->ensembles->firstWhere('slug', $slug)->pivot;

    expect(InstrumentFamily::count())->toBe(2)
        ->and($pivot('alice.smith', 'concert-band')->instrument_family_id)->toBe($flute->id)
        ->and($pivot('alice.smith', 'concert-band')->seat_row)->toBe('A')
        ->and($pivot('alice.smith', 'concert-band')->seat_column)->toBe('2')
        ->and($pivot('bob.jones', 'concert-band')->instrument_family_id)->toBe($flute->id)
        ->and($pivot('carol.white', 'jazz-group')->instrument_family_id)->toBeNull()
        ->and($pivot('carol.white', 'jazz-group')->seat_row)->toBeNull()
        ->and($pivot('carol.white', 'jazz-group')->seat_column)->toBeNull();
});

test('seat rows become letters', function (string $row, ?string $letter) {
    import_pollererer(function (&$export) use ($row) {
        $export['tables']['members'][2]['row'] = $row;
    });

    expect(imported_user('alice.smith')->ensembles->firstWhere('slug', 'concert-band')->pivot->seat_row)->toBe($letter);
})->with([
    'first row' => ['1', 'A'],
    'second row' => ['2', 'B'],
    'last single letter' => ['26', 'Z'],
    'past Z' => ['27', 'AA'],
    'unseated' => ['0', null],
]);

test('null names are imported as empty strings', function () {
    import_pollererer(function (&$export) {
        $export['tables']['members'][2]['last_name'] = null;
        $export['tables']['terms'][0]['name'] = null;
        $export['tables']['ensembles'][1]['name'] = null;
    });

    expect(User::where('email', 'alice@example.com')->value('last_name'))->toBe('')
        ->and(Term::where('slug', 'autumn-2025')->value('name'))->toBe('')
        ->and(Ensemble::where('slug', 'jazz-group')->value('name'))->toBe('');
});

test('warnings name a member with no name by their ID', function () {
    $report = import_pollererer(function (&$export) {
        $export['tables']['members'][2]['first_name'] = null;
        $export['tables']['members'][2]['last_name'] = null;
        $export['tables']['logins'][1]['password'] = null;
    });

    expect($report['warnings'])->toContain('The password for Member 1 is not a bcrypt hash, so they will need to reset it.');
});

test('a missing safe name falls back to the name, then to the ID', function () {
    import_pollererer(function (&$export) {
        $export['tables']['ensembles'][0]['safe_name'] = null;
        $export['tables']['ensembles'][1]['safe_name'] = null;
        $export['tables']['ensembles'][1]['name'] = null;
        $export['tables']['terms'][0]['safe_name'] = null;
        $export['tables']['terms'][0]['name'] = null;
    });

    expect(Ensemble::pluck('slug')->sort()->values()->all())->toBe(['concert-band', 'ensemble-2'])
        ->and(Term::withTrashed()->pluck('slug')->sort()->values()->all())->toBe(['spring-2024', 'term-1']);
});

test('a safe name that falls back onto another\'s is rejected', function (string $table, string $error) {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('settings.import.preview'), ['export' => pollererer_upload(function (&$export) use ($table) {
            $export['tables'][$table][1]['safe_name'] = null;
            $export['tables'][$table][1]['name'] = $export['tables'][$table][0]['safe_name'];
        })])
        ->assertSessionHasErrors(['export' => $error]);

    expect(Ensemble::count())->toBe(0);
})->with([
    'ensembles' => ['ensembles', 'Two ensembles have the same safe name.'],
    'terms' => ['terms', 'Two terms have the same safe name.'],
]);

test('null settings keep the site defaults', function () {
    import_pollererer(function (&$export) {
        $export['settings']['assume_attending'] = null;
        $export['settings']['repeat_headings'] = null;
    });

    expect(app(GeneralSettings::class)->toCollection()->only('assume_attending', 'repeating_headings')->all())->toBe(['assume_attending' => null, 'repeating_headings' => null]);
});

test('the preview leaves out the export date when it has none', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->post(route('settings.import.preview'), ['export' => pollererer_upload(function (&$export) {
        $export['exported_at'] = null;
    })]);

    $this->actingAs($admin)->get(route('settings.import'))->assertOk()->assertDontSee('Exported');
});

test('attendance updates leave out replies for dates in a hidden term', function () {
    $admin = make_user(UserRole::Admin);
    import_pollererer(function (&$export) {
        $export['tables']['attendance'][] = ['ID' => '7', 'member_ID' => '1', 'edit_datetime' => '1707000000', 'edit_member_ID' => '1', 'term_dates_ID' => '7', 'ensemble_ID' => '1', 'IP' => '', 'status' => '1'];
    }, $admin);

    $hidden = Attendance::whereRelation('term_date', 'term_id', Term::onlyTrashed()->value('id'))->firstOrFail();

    expect($hidden->name)->toBe('Concert Band: ');
    $this->actingAs($admin)->get(route('attendance.index'))
        ->assertOk()
        ->assertSee('Concert Band: Autumn 2025');
});

test('attendance keeps every reply with its time, editor and address', function () {
    $admin = make_user(UserRole::Admin);
    $report = import_pollererer(admin: $admin);

    $alice = imported_user('alice.smith');
    $replies = Attendance::where('user_id', $alice->id)->orderBy('created_at')->get();
    $guestEdit = Attendance::where('user_id', imported_user('bob.jones')->id)->firstOrFail();
    $unknownEnsembleEdit = Attendance::where('user_id', imported_user('carol.white')->id)->firstOrFail();

    expect($replies->pluck('status')->all())->toBe([AttendanceStatus::Attending, AttendanceStatus::NotAttending])
        ->and($replies[1]->created_at->format('Y-m-d H:i'))->toBe('2025-09-25 10:00')
        ->and($replies[1]->updated_at->format('Y-m-d H:i'))->toBe('2025-09-25 10:00')
        ->and($replies[1]->edit_user_id)->toBe($alice->id)
        ->and($replies[1]->edit_ip)->toBe('192.0.2.1')
        ->and($guestEdit->edit_user_id)->toBe(imported_user('concertband')->id)
        ->and($guestEdit->edit_ip)->toBe('')
        ->and($unknownEnsembleEdit->edit_user_id)->toBe($admin->id)
        ->and($report['counts']['Attendance replies'])->toBe(4)
        ->and($report['warnings'])->toContain('2 attendance replies were skipped because their member, date, ensemble or status is missing.')
        ->and(collect($report['warnings'])->contains(fn ($warning) => str_contains($warning, 'made by the guest')))->toBeTrue();
});

test('settings map onto the settings page and unknown ones are listed', function () {
    $report = import_pollererer();

    expect(array_filter(app(GeneralSettings::class)->toCollection()->except('logo')->all(), fn ($value) => $value !== null))->toEqual([
        'name' => 'North Band',
        'assume_attending' => true,
        'repeating_headings' => false,
        'terminology' => ['rehearsal' => 'practice', 'rehearsals' => 'practices'],
    ])
        ->and($report['ignored_settings'])->toBe(['software_name', 'hide_past_dates']);
});

test('images are downloaded to the public disk', function () {
    import_pollererer();

    $image = Ensemble::where('slug', 'concert-band')->value('image');
    $logo = app(GeneralSettings::class)->logo;

    Storage::disk('public')->assertExists($image);
    Storage::disk('public')->assertExists($logo);
    expect($image)->toStartWith('images/ensembles/')->toEndWith('.png')
        ->and($logo)->toStartWith('images/settings/');
});

test('a relative image path is found on the logo\'s site', function (string $image) {
    import_pollererer(function (&$export) use ($image) {
        $export['settings']['logo_url'] = 'https://polls.example.org:8443/uploads/logo.png';
        $export['tables']['ensembles'][1]['image'] = $image;
    });

    Http::assertSent(fn ($request) => $request->url() === 'https://polls.example.org:8443/uploads/ensemble-logos/jazz.png');
    expect(Ensemble::where('slug', 'jazz-group')->value('image'))->toStartWith('images/ensembles/');
})->with(['/uploads/ensemble-logos/jazz.png', 'uploads/ensemble-logos/jazz.png']);

test('an image address with spaces is not treated as a relative path', function () {
    $report = import_pollererer(function (&$export) {
        $export['settings']['logo_url'] = 'https://polls.example.org/uploads/logo.png';
        $export['tables']['ensembles'][1]['image'] = 'https://polls.example.org/uploads/ensemble-logos/jazz/Jazz icon RGB-16.jpg';
    });

    expect($report['images'])->toContain('https://polls.example.org/uploads/ensemble-logos/jazz/Jazz icon RGB-16.jpg');
    Http::assertSent(fn ($request) => $request->url() === 'https://polls.example.org/uploads/ensemble-logos/jazz/Jazz%20icon%20RGB-16.jpg');
});

test('a relative image path is left out when the logo is not a web address', function () {
    $report = import_pollererer(function (&$export) {
        $export['settings']['logo_url'] = '';
        $export['tables']['ensembles'][1]['image'] = 'uploads/jazz.png';
    });

    expect(Ensemble::where('slug', 'jazz-group')->value('image'))->toBeNull()
        ->and($report['warnings'])->toContain('The image for Jazz Group is not a web address and the logo gives no address to find it on, so it was left out.');
});

test('a failed image download keeps the address and warns', function () {
    Http::swap(new Factory);
    Http::fake(['*' => Http::response('Not found', 404)]);

    $report = import_pollererer();

    expect(Ensemble::where('slug', 'concert-band')->value('image'))->toBe('https://example.com/band.png')
        ->and(app(GeneralSettings::class)->logo)->toBeNull()
        ->and($report['warnings'])->toContain('The image for Concert Band could not be downloaded from https://example.com/band.png, so it links to that address instead.');
});

test('a download that is not an image is refused', function () {
    Http::swap(new Factory);
    Http::fake(['*' => Http::response('<svg></svg>', 200, ['Content-Type' => 'image/png'])]);

    import_pollererer();

    expect(Ensemble::where('slug', 'concert-band')->value('image'))->toBe('https://example.com/band.png');
});

test('previewing counts everything but saves and downloads nothing', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->post(route('settings.import.preview'), ['export' => pollererer_upload()])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.import'));

    expect(Ensemble::withTrashed()->count())->toBe(0)
        ->and(TermDate::withTrashed()->count())->toBe(0)
        ->and(Attendance::count())->toBe(0)
        ->and(User::count())->toBe(1)
        ->and(DB::table('settings')->where('payload', '!=', 'null')->count())->toBe(0)
        ->and(DB::table('user_ensemble')->count())->toBe(0);
    Http::assertNothingSent();

    $this->actingAs($admin)->get(route('settings.import'))
        ->assertOk()
        ->assertSee('https://example.com/band.png')
        ->assertSee('software_name')
        ->assertSee('Term date 6 was skipped because its term is missing.')
        ->assertSeeInOrder(['Attendance replies', '4']);
});

test('confirming imports the previewed file and deletes it', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->post(route('settings.import.preview'), ['export' => pollererer_upload()]);
    $path = session('pollererer_import.path');
    Storage::disk('local')->assertExists($path);
    expect($path)->toStartWith('imports/');

    $this->actingAs($admin)->post(route('settings.import.store'))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.import'));

    Storage::disk('local')->assertMissing($path);
    expect(Ensemble::count())->toBe(2)
        ->and(session('pollererer_import'))->toBeNull();

    $this->actingAs($admin)->get(route('settings.import'))
        ->assertSee('The Pollererer data has been imported.');
});

test('the import page is for admins only', function () {
    $this->get(route('settings.import'))->assertRedirectContains('/login?redirect=');

    foreach ([UserRole::Guest, UserRole::Ensemble, UserRole::Member, UserRole::Moderator] as $role) {
        $user = make_user($role);
        $this->actingAs($user)->get(route('settings.import'))->assertForbidden();
        $this->actingAs($user)->post(route('settings.import.preview'), ['export' => pollererer_upload()])->assertForbidden();
        $this->actingAs($user)->post(route('settings.import.store'))->assertForbidden();
    }

    $this->actingAs(make_user(UserRole::Admin))->get(route('settings.import'))->assertOk();
});

test('the settings page links to the import page', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(route('settings.edit'))
        ->assertSee(route('settings.import'));
});

test('malformed exports are rejected', function (string $content, string $error) {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('settings.import.preview'), ['export' => UploadedFile::fake()->createWithContent('export.json', $content)])
        ->assertSessionHasErrors(['export' => $error]);

    expect(session('pollererer_import'))->toBeNull();
})->with([
    'not JSON' => ['{nope', 'The file is not valid JSON.'],
    'another format' => [json_encode(['format' => 'something-else', 'version' => 1]), 'The file is not a Pollererer export.'],
    'wrong version' => [fn () => json_encode(pollererer_export(fn (&$e) => $e['version'] = 2)), 'Only version 1 Pollererer exports can be imported.'],
    'version as a string' => [fn () => json_encode(pollererer_export(fn (&$e) => $e['version'] = '1')), 'Only version 1 Pollererer exports can be imported.'],
    'missing table' => [fn () => json_encode(pollererer_export(function (&$e) {
        unset($e['tables']['attendance']);
    })), 'The tables.attendance field must be present.'],
    'missing column' => [fn () => json_encode(pollererer_export(function (&$e) {
        unset($e['tables']['members'][2]['user_level']);
    })), 'Row 3 of members is missing user_level.'],
    'non-numeric ID' => [fn () => json_encode(pollererer_export(fn (&$e) => $e['tables']['term_dates'][0]['term_ID'] = 'one')), 'Row 1 of term_dates has an invalid term_ID.'],
    'duplicate IDs' => [fn () => json_encode(pollererer_export(fn (&$e) => $e['tables']['terms'][1]['ID'] = '1')), 'The terms table has duplicate IDs.'],
    'bad timezone' => [fn () => json_encode(pollererer_export(fn (&$e) => $e['timezone'] = 'Mars/Olympus')), 'The timezone field must be a valid timezone.'],
]);

test('a site with existing data is refused', function () {
    Term::factory()->create()->delete();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('settings.import.preview'), ['export' => pollererer_upload()])
        ->assertSessionHasErrors(['export' => 'Pollererer data can only be imported into a site with no ensembles, terms, dates or attendance.']);
});

test('the daylight saving check warns when rehearsal times shift in summer', function () {
    config(['app.timezone' => 'UTC']);

    $report = (new PollererImporter(pollererer_export(), make_user(UserRole::Admin)))->preview();

    expect($report['timezone_warning'])->toBe('Rehearsals mostly start at 19:00 in winter but 18:00 in summer. The site\'s timezone (UTC) probably doesn\'t match Pollererer\'s (Europe/London). Check APP_TIMEZONE before importing.');
});

test('the daylight saving check stays quiet when the timezones match', function () {
    $report = (new PollererImporter(pollererer_export(), make_user(UserRole::Admin)))->preview();

    expect($report['timezone_warning'])->toBeNull();
});

test('confirming needs a choice of times when the check warns', function () {
    config(['app.timezone' => 'UTC']);
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->post(route('settings.import.preview'), ['export' => pollererer_upload()]);

    $this->actingAs($admin)->get(route('settings.import'))->assertSee('name="keep_clock_times"', false);

    $this->actingAs($admin)->post(route('settings.import.store'))->assertSessionHasErrors(['keep_clock_times' => 'Choose how to import the times.']);
    expect(Ensemble::count())->toBe(0);

    $this->actingAs($admin)->post(route('settings.import.store'), ['keep_clock_times' => '0'])->assertSessionHasNoErrors();
    expect(Ensemble::count())->toBe(2)
        ->and(TermDate::whereDate('start_datetime', '2025-10-01')->firstOrFail()->start_datetime->format('H:i'))->toBe('18:00');
});

test('keeping clock times moves summer times to winter time', function () {
    config(['app.timezone' => 'UTC']);
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->post(route('settings.import.preview'), ['export' => pollererer_upload()]);

    $this->actingAs($admin)->post(route('settings.import.store'), ['keep_clock_times' => '1'])->assertSessionHasNoErrors();

    $summer = TermDate::whereDate('start_datetime', '2025-10-01')->firstOrFail();
    $winter = TermDate::whereDate('start_datetime', '2025-11-05')->firstOrFail();

    expect($summer->start_datetime->format('H:i'))->toBe('19:00')
        ->and($summer->end_datetime->format('H:i'))->toBe('21:00')
        ->and($winter->start_datetime->format('H:i'))->toBe('19:00')
        ->and(Attendance::orderBy('created_at')->firstOrFail()->created_at->format('Y-m-d H:i'))->toBe('2025-09-20 10:00');
});

test('keeping clock times changes nothing when the timezones match', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->post(route('settings.import.preview'), ['export' => pollererer_upload()]);

    $this->actingAs($admin)->post(route('settings.import.store'), ['keep_clock_times' => '1'])->assertSessionHasNoErrors();

    expect(TermDate::whereDate('start_datetime', '2025-10-01')->firstOrFail()->start_datetime->format('H:i'))->toBe('19:00');
});
