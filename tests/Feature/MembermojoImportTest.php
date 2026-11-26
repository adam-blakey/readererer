<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\SetupGroup;
use App\Models\User;
use App\Services\MembermojoImporter;
use App\Settings\GeneralSettings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    Http::preventStrayRequests();
});

function membermojo_csv(): string
{
    return file_get_contents(base_path('tests/Fixtures/membermojo-members.csv'));
}

function import_membermojo(bool $overwrite = true, bool $archiveLeavers = false, ?string $csv = null): array
{
    return (new MembermojoImporter(MembermojoImporter::parse($csv ?? membermojo_csv()), $overwrite, $archiveLeavers))->import();
}

function membermojo_user(int $id): User
{
    return User::withTrashed()->where('membermojo_id', $id)->firstOrFail();
}

function configure_membermojo_download(): void
{
    $settings = app(GeneralSettings::class);
    $settings->membermojo_shortname = 'band';
    $settings->membermojo_email = 'robot@example.com';
    $settings->membermojo_password = 'secret';
    $settings->save();
    GeneralSettings::applyToConfig();
}

test('active members become users with their details, ensemble, instrument and setup group', function () {
    $orchestra = Ensemble::factory()->create(['name' => 'Orchestra']);
    $group = SetupGroup::factory()->create(['name' => 'Setup group 3']);
    $flute = make_instrument_family('Flute');

    $report = import_membermojo();

    $user = membermojo_user(788240);
    expect($user->only(['first_name', 'last_name', 'email', 'phone_number', 'address_line1', 'address_line2', 'address_city', 'address_post_code', 'emergency_contact_name', 'emergency_contact_number', 'setup_group_id']))->toBe([
        'first_name' => 'Person',
        'last_name' => 'Surname',
        'email' => 'email@example.com',
        'phone_number' => '0123456789',
        'address_line1' => '123 Some Street',
        'address_line2' => 'Nottinghamshire',
        'address_city' => 'Nottingham',
        'address_post_code' => 'NG1 1AA',
        'emergency_contact_name' => 'Emergency Person',
        'emergency_contact_number' => '07123456789',
        'setup_group_id' => $group->id,
    ])
        ->and($user->role)->toBe(UserRole::Member)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->is_gift_aiding_subs)->toBeTrue()
        ->and($user->has_photo_permission)->toBeTrue()
        ->and($user->member_since->toDateString())->toBe('2018-06-24')
        ->and($user->membership_expires_on->toDateString())->toBe('2027-08-31')
        ->and($user->ensembles->pluck('id')->all())->toBe([$orchestra->id])
        ->and(InstrumentFamily::find($user->ensembles->first()->pivot->instrument_family_id)->name)->toBe('Clarinet');

    $second = membermojo_user(5295467);
    expect($second->phone_number)->toBe('07987654321')
        ->and($second->has_photo_permission)->toBeFalse()
        ->and($second->ensembles->first()->pivot->instrument_family_id)->toBe($flute->id);

    expect(membermojo_user(1173588)->role)->toBe(UserRole::Member)
        ->and(membermojo_user(1173588)->ensembles)->toBeEmpty()
        ->and(User::where('membermojo_id', 999)->exists())->toBeFalse()
        ->and($report['counts'])->toMatchArray(['Users created' => 3, 'Memberships added' => 2, 'Instrument families created' => 1, 'Rows left out as not active' => 1])
        ->and($report['warnings'])->toContain('No ensemble is called Helper, so 1 member was not added to one.', 'No setup group matches Group 4, so members in it kept the setup group they had.');
});

test('a member is matched by email first and by Membermojo ID afterwards, keeping their role and password', function () {
    $existing = make_user(UserRole::Admin, ['email' => 'Helper@Example.com', 'phone_number' => '111']);
    $password = $existing->password;

    import_membermojo();

    expect($existing->refresh()->membermojo_id)->toBe(1173588)
        ->and($existing->role)->toBe(UserRole::Admin)
        ->and($existing->password)->toBe($password)
        ->and($existing->phone_number)->toBe('+447123456789');

    $report = import_membermojo(csv: str_replace('helper@example.com', 'moved@example.com', membermojo_csv()));

    expect($existing->refresh()->email)->toBe('moved@example.com')
        ->and(User::count())->toBe(3)
        ->and($report['counts'])->toMatchArray(['Users created' => 0, 'Users updated' => 1, 'Users unchanged' => 2]);
});

test('with overwrite off only empty details are filled in', function () {
    $existing = make_user(UserRole::Member, ['email' => 'email@example.com', 'first_name' => 'Kept', 'phone_number' => null, 'address_line1' => null]);

    import_membermojo(overwrite: false);

    expect($existing->refresh()->first_name)->toBe('Kept')
        ->and($existing->phone_number)->toBe('0123456789')
        ->and($existing->address_line1)->toBe('123 Some Street')
        ->and($existing->membermojo_id)->toBe(788240);
});

test('an empty Membermojo value does not wipe what Readererer holds', function () {
    $existing = make_user(UserRole::Member, ['email' => 'another@example.com', 'emergency_contact_address_line1' => '1 Kept Road']);

    import_membermojo();

    expect($existing->refresh()->emergency_contact_address_line1)->toBe('1 Kept Road');
});

test('members who have left are listed, and archived only when asked, never admins or unlinked users', function () {
    $leaver = make_user(UserRole::Member, ['membermojo_id' => 999, 'first_name' => 'Lapsed', 'last_name' => 'Member']);
    $admin = make_user(UserRole::Admin, ['membermojo_id' => 12345]);
    $unlinked = make_user();

    $report = import_membermojo();

    expect($report['leavers'])->toContain('Lapsed Member')
        ->and($report['counts']['Members who have left'])->toBe(2)
        ->and($leaver->refresh()->trashed())->toBeFalse();

    $report = import_membermojo(archiveLeavers: true);

    expect($leaver->refresh()->trashed())->toBeTrue()
        ->and($admin->refresh()->trashed())->toBeFalse()
        ->and($unlinked->refresh()->trashed())->toBeFalse()
        ->and($report['counts']['Users archived'])->toBe(1);
});

test('an archived user is left alone and a clashing email address is not imported', function () {
    $archived = make_user(UserRole::Member, ['email' => 'email@example.com', 'first_name' => 'Archived']);
    $archived->delete();
    make_user(UserRole::Member, ['email' => 'another@example.com', 'membermojo_id' => 4]);

    $report = import_membermojo();

    expect($archived->refresh()->first_name)->toBe('Archived')
        ->and($archived->trashed())->toBeTrue()
        ->and(membermojo_user(5295467)->email)->toBeNull()
        ->and($report['warnings'])->toContain(
            'Person Surname is archived in Readererer, so they were left alone. Unarchive them to import their details.',
            'Another Person has the same email address as another user, so their email address was not imported.',
        );
});

test('the preview saves nothing', function () {
    Ensemble::factory()->create(['name' => 'Orchestra']);
    make_user(UserRole::Member, ['membermojo_id' => 999]);

    $report = (new MembermojoImporter(MembermojoImporter::parse(membermojo_csv()), true, true))->preview();

    expect($report['counts'])->toMatchArray(['Users created' => 3, 'Users archived' => 1])
        ->and(User::withTrashed()->count())->toBe(1)
        ->and(User::count())->toBe(1)
        ->and(InstrumentFamily::count())->toBe(0);
});

test('a file that is not a members download, or has no active members, is refused', function (string $csv) {
    expect(fn () => MembermojoImporter::parse($csv))->toThrow(ValidationException::class);
})->with([
    'html' => '<html><body>Sign in</body></html>',
    'no active members' => "\"First name\",\"Last name\",Email,\"Membership state\",\"membermojo ID\"\nA,B,a@example.com,Expired,1\n",
]);

test('the page needs an admin', function () {
    $this->actingAs(make_user(UserRole::Moderator))->get('/settings/membermojo')->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get('/settings/membermojo')->assertOk()->assertSee('Scheduled download');
    $this->actingAs(make_user(UserRole::Admin))->get('/settings')->assertSee(route('settings.membermojo'));

});

test('an upload is previewed, then imported on confirmation and deleted', function () {
    $admin = make_user(UserRole::Admin);
    $upload = UploadedFile::fake()->createWithContent('members.csv', membermojo_csv());

    $this->actingAs($admin)->post('/settings/membermojo/preview', ['export' => $upload, 'overwrite' => '1'])->assertRedirect('/settings/membermojo');

    expect(User::count())->toBe(1)->and(Storage::disk('local')->files('imports'))->toHaveCount(1);
    $this->actingAs($admin)->get('/settings/membermojo')->assertSee('Preview')->assertSee('Existing details will be overwritten.');

    $this->actingAs($admin)->post('/settings/membermojo')->assertRedirect('/settings/membermojo');

    expect(User::count())->toBe(4)->and(Storage::disk('local')->files('imports'))->toBeEmpty();
    $this->actingAs($admin)->post('/settings/membermojo')->assertSessionHasErrors('export');
});

test('the scheduled download signs in, keeps the cookies and imports the members', function () {
    configure_membermojo_download();
    Http::fake([
        'membermojo.co.uk/band/signin_password' => Http::response('', 200, ['Set-Cookie' => 'session=abc; Path=/; Secure']),
        'membermojo.co.uk/band/membership/download_members' => Http::response(membermojo_csv()),
    ]);

    $this->artisan('membermojo:sync')->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/signin_password') && $request['email'] === 'robot@example.com' && $request['password'] === 'secret');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/download_members') && $request->header('Cookie') === ['session=abc']);

    $settings = app(GeneralSettings::class)->refresh();
    expect(User::count())->toBe(3)
        ->and($settings->membermojo_status['ok'])->toBeTrue()
        ->and($settings->membermojo_status['counts']['Users created'])->toBe(3)
        ->and($settings->membermojo_cookies[0]['Value'])->toBe('abc')
        ->and(DB::table('settings')->where('name', 'membermojo_password')->value('payload'))->not->toContain('secret');
});

test('a download that returns a sign-in page is recorded as failed and imports nothing', function () {
    configure_membermojo_download();
    Http::fake(['*' => Http::response('<html>Verify your browser</html>')]);

    $this->artisan('membermojo:sync')->assertFailed();

    expect(User::count())->toBe(0)->and(app(GeneralSettings::class)->refresh()->membermojo_status['ok'])->toBeFalse();
    $this->actingAs(make_user(UserRole::Admin))->get('/settings/membermojo')->assertSee('Last download failed')->assertSee('Verification link');
});

test('nothing is requested until the download is set up', function () {
    Http::fake();

    $this->artisan('membermojo:sync')->assertSuccessful();

    Http::assertNothingSent();
});

test('a verification link is posted with the CSRF token and then the members are downloaded', function () {
    configure_membermojo_download();
    Http::fake([
        'membermojo.co.uk/verify/*' => Http::response('', 200, ['Set-Cookie' => 'verified=yes; Path=/']),
        'membermojo.co.uk/band/signin_password' => Http::response(''),
        'membermojo.co.uk/band/membership/download_members' => Http::response(membermojo_csv()),
        'membermojo.co.uk' => Http::response('<script>{"csrf_token":"tok123"}</script>'),
    ]);

    $this->actingAs(make_user(UserRole::Admin))
        ->post('/settings/membermojo/verify', ['verification_link' => 'https://membermojo.co.uk/verify/xyz'])
        ->assertRedirect('/settings/membermojo');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://membermojo.co.uk/verify/xyz' && $request['csrf_token'] === 'tok123');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/download_members') && $request->header('Cookie') === ['verified=yes']);
    expect(User::where('membermojo_id', 788240)->exists())->toBeTrue();
});

test('a verification link to another site is refused', function () {
    configure_membermojo_download();
    Http::fake();

    $this->actingAs(make_user(UserRole::Admin))
        ->post('/settings/membermojo/verify', ['verification_link' => 'https://membermojo.co.uk.example.com/verify'])
        ->assertSessionHasErrors('verification_link');

    Http::assertNothingSent();
});

test('saving the download settings keeps a saved password and forgets the cookies when the account changes', function () {
    configure_membermojo_download();
    $settings = app(GeneralSettings::class);
    $settings->membermojo_cookies = [['Name' => 'session', 'Value' => 'abc', 'Domain' => 'membermojo.co.uk']];
    $settings->save();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put('/settings/membermojo/download', ['membermojo_shortname' => 'band', 'membermojo_email' => 'robot@example.com', 'membermojo_password' => ''])->assertSessionHasNoErrors();

    expect($settings->refresh()->membermojo_password)->toBe('secret')
        ->and($settings->membermojo_cookies)->not->toBeNull()
        ->and($settings->membermojo_overwrite)->toBeFalse();

    $this->actingAs($admin)->put('/settings/membermojo/download', ['membermojo_shortname' => 'band', 'membermojo_email' => 'other@example.com', 'membermojo_overwrite' => '1']);

    expect($settings->refresh()->membermojo_cookies)->toBeNull()->and($settings->membermojo_overwrite)->toBeTrue();

    $this->actingAs($admin)->put('/settings/membermojo/download', ['membermojo_shortname' => '../evil', 'membermojo_email' => 'other@example.com'])->assertSessionHasErrors('membermojo_shortname');
});

test('the scheduled download runs at the saved time', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put('/settings/membermojo/download', ['membermojo_time' => '25:00'])->assertSessionHasErrors('membermojo_time');
    $this->actingAs($admin)->put('/settings/membermojo/download', ['membermojo_time' => '6:05pm'])->assertSessionHasNoErrors();
    GeneralSettings::applyToConfig();
    require base_path('routes/console.php');

    $event = collect(app(Schedule::class)->events())->last(fn ($event) => str_contains($event->command, 'membermojo:sync'));
    expect(app(GeneralSettings::class)->refresh()->membermojo_time)->toBe('18:05')
        ->and($event->expression)->toBe('5 18 * * *');
});
