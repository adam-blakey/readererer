<?php

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function settings_payload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Riverside Band',
        'image_max_kilobytes' => 4096,
        'assume_attending' => '1',
        'allow_change_to_unknown' => '0',
        'repeating_headings' => '1',
        'reminder_hours' => '48',
        'after_reminders' => 'lock',
        'terminology' => ['rehearsal' => 'practice', 'rehearsals' => 'practices', 'concert' => ''],
    ], $overrides);
}

function reload_settings(): void
{
    Setting::applyToConfig();
    app('translator')->setLoaded([]);
}

test('an admin can save the settings and they override the config', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings.edit'));

    reload_settings();

    expect(config('app.name'))->toBe('Riverside Band')
        ->and(config('mail.from.name'))->toBe('Riverside Band')
        ->and(config('app.readererer_image_max_kilobytes'))->toBe(4096)
        ->and(config('app.readererer_assume_attending'))->toBeTrue()
        ->and(config('app.readererer_allow_change_to_unknown'))->toBeFalse()
        ->and(config('app.readererer_repeating_headings'))->toBeTrue()
        ->and(config('app.readererer_reminder_hours'))->toBe(48)
        ->and(config('app.readererer_after_reminders'))->toBe('lock')
        ->and(config('app.readererer_terminology'))->toBe(['rehearsal' => 'practice', 'rehearsals' => 'practices']);
});

test('saved settings show on the settings page', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->put(route('settings.update'), settings_payload());

    reload_settings();

    $this->actingAs($admin)->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('value="Riverside Band"', false)
        ->assertSee('value="practices"', false);
});

test('the wording setting renames words in translated text', function () {
    Setting::put('terminology', ['rehearsal' => 'practice', 'rehearsals' => 'practices', 'member' => 'player', 'concert' => 'gig']);
    reload_settings();

    expect(__('Next rehearsal'))->toBe('Next practice')
        ->and(__('Upcoming rehearsals and concerts'))->toBe('Upcoming practices and concerts')
        ->and(__('Remove :member from :ensemble?', ['member' => 'Alice', 'ensemble' => 'Concert Band']))
        ->toBe('Remove Alice from Concert Band?');
});

test('an admin can upload, replace and remove the logo', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put(route('settings.update'), settings_payload(['logo' => UploadedFile::fake()->image('first.png')]))
        ->assertSessionHasNoErrors();
    $first = Setting::where('key', 'logo')->value('value');
    Storage::disk('public')->assertExists($first);

    reload_settings();
    expect(logo_url())->toEndWith('/storage/'.$first);

    $this->actingAs($admin)->put(route('settings.update'), settings_payload(['logo' => UploadedFile::fake()->image('second.png')]));
    $second = Setting::where('key', 'logo')->value('value');
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($admin)->put(route('settings.update'), settings_payload(['remove_logo' => '1']));
    Storage::disk('public')->assertMissing($second);
    expect(Setting::where('key', 'logo')->exists())->toBeFalse();
});

test('settings reject unknown words and invalid values', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload([
            'name' => '',
            'image_max_kilobytes' => 0,
            'terminology' => ['piece' => 'tune'],
            'primary_color' => 'magenta',
        ]))
        ->assertSessionHasErrors(['name', 'image_max_kilobytes', 'terminology', 'primary_color']);

    expect(Setting::count())->toBe(0);
});

test('moderators and admins see the settings link', function () {
    $this->actingAs(make_user(UserRole::Member))->get(route('dashboard'))
        ->assertDontSee('href="'.route('settings.edit').'"', false);
    $this->actingAs(make_user(UserRole::Moderator))->get(route('dashboard'))
        ->assertSee('href="'.route('settings.edit').'"', false);
    $this->actingAs(make_user(UserRole::Admin))->get(route('dashboard'))
        ->assertSee('href="'.route('settings.edit').'"', false);
});

test('the settings page links to ensembles, instrument families, terms and setup groups', function () {
    $response = $this->actingAs(make_user(UserRole::Moderator))->get(route('settings.edit'))->assertOk();

    foreach (['ensembles.index', 'instrumentfamilys.index', 'terms.index', 'setupgroups.index'] as $route) {
        $response->assertSee('href="'.route($route).'"', false);
    }
});

test('moderators see the settings read-only', function () {
    $this->actingAs(make_user(UserRole::Moderator))->get(route('settings.edit'))
        ->assertSee('<fieldset class="card-body" disabled', false)
        ->assertDontSee('Save settings')
        ->assertDontSee('Import from Pollererer');

    $this->actingAs(make_user(UserRole::Admin))->get(route('settings.edit'))
        ->assertDontSee('<fieldset class="card-body" disabled', false)
        ->assertSee('Save settings');
});

test('deploys link public/storage to the public disk', function () {
    expect(file_get_contents(base_path('.cpanel.yml')))->toContain('ln -sfn ../storage/app/public public/storage')
        ->and(file_get_contents(base_path('.github/actions/deploy-ssh/action.yml')))->toContain('ln -sfn ../storage/app/public public/storage');
});

test('every member detail is shown until the setting is saved', function () {
    expect(make_user()->getVisible())->toContain('phone_number', 'emergency_contact_name', 'is_over_18');
});

test('unticked member details are left out of the member pages and keep their saved values', function () {
    $admin = make_user(UserRole::Admin);
    $member = make_user(UserRole::Member, ['phone_number' => '01234 567890', 'date_of_birth' => '1990-01-01']);

    $this->actingAs($admin)
        ->put(route('settings.update'), settings_payload(['shown_user_fields' => ['address', 'emergency_contact', 'has_photo_permission', 'is_gift_aiding_subs']]))
        ->assertSessionHasNoErrors();
    reload_settings();

    expect($member->getVisible())->toContain('address_line1')->not->toContain('phone_number', 'is_over_18');

    $this->actingAs($admin)->get(route('users.show', $member))
        ->assertOk()
        ->assertDontSee('01234 567890')
        ->assertDontSee(__('Over 18'))
        ->assertSee(__('Address'))
        ->assertDontSee(__('No additional info available'));

    foreach ([route('users.create'), route('users.edit', $member)] as $url) {
        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertDontSee('name="phone_number"', false)
            ->assertDontSee('name="date_of_birth"', false)
            ->assertSee('name="address_line1"', false);
    }

    $this->actingAs($admin)->patch(route('users.update', $member), [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'email' => $member->email,
        'role' => $member->role->value,
    ])->assertSessionHasNoErrors();

    expect($member->fresh()->phone_number)->toBe('01234 567890');
});

test('unticking every member detail hides them all', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)->put(route('settings.update'), settings_payload())->assertSessionHasNoErrors();
    reload_settings();

    expect(User::shownFields())->toBe([]);

    $member = make_user();
    $this->actingAs($member)->get(route('users.show', $member))
        ->assertOk()
        ->assertSee(__('No additional info available'));
});

test('only known member details can be shown', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload(['shown_user_fields' => ['password']]))
        ->assertSessionHasErrors('shown_user_fields.0');
});

test('the email logging switch is saved to the config', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload(['log_emails' => '1']))
        ->assertSessionHasNoErrors();

    reload_settings();

    expect(config('app.readererer_log_emails'))->toBeTrue();
});

test('the featured colour themes every page and the maintenance illustration', function () {
    $admin = make_user(UserRole::Admin);
    $this->actingAs($admin)
        ->put(route('settings.update'), settings_payload(['primary_color' => 'red']))
        ->assertSessionHasNoErrors();

    reload_settings();

    expect(config('app.readererer_primary_color'))->toBe('red');
    $this->actingAs($admin)->get(route('settings.edit'))
        ->assertSee('data-bs-theme-primary="red"', false)
        ->assertSee('name="primary_color" type="radio" value="red" checked', false);

    Route::get('/test-unavailable', fn () => abort(503));
    $this->get('/test-unavailable')
        ->assertStatus(503)
        ->assertSee('data-bs-theme-primary="red"', false)
        ->assertSee('var(--tblr-primary', false);
});

test('the Google login settings are saved, with the client secret encrypted', function () {
    $admin = make_user(UserRole::Admin);
    $google = [
        'google_login' => '1',
        'google_client_id' => 'client-id',
        'google_client_secret' => 'client-secret',
        'google_register_domain' => 'Example.org',
        'google_register_role' => (string) UserRole::Moderator->value,
    ];

    $this->actingAs($admin)->put(route('settings.update'), settings_payload($google))->assertSessionHasNoErrors();

    $stored = Setting::where('key', 'google_client_secret')->value('value');
    reload_settings();

    expect($stored)->not->toContain('client-secret')
        ->and(config('app.readererer_google_login'))->toBeTrue()
        ->and(config('services.google.client_id'))->toBe('client-id')
        ->and(config('services.google.client_secret'))->toBe('client-secret')
        ->and(config('app.readererer_google_register_domain'))->toBe('example.org')
        ->and(config('app.readererer_google_register_role'))->toBe(UserRole::Moderator->value);

    $this->actingAs($admin)->get(route('settings.edit'))->assertDontSee('client-secret');

    $this->actingAs($admin)
        ->put(route('settings.update'), settings_payload(['google_client_secret' => ''] + $google))
        ->assertSessionHasNoErrors();
    reload_settings();

    expect(config('services.google.client_secret'))->toBe('client-secret');
});

test('Google login needs a client ID and secret, and registration needs both a domain and a role', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload(['google_login' => '1', 'google_register_domain' => 'example.org']))
        ->assertSessionHasErrors(['google_client_id', 'google_client_secret', 'google_register_role']);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('settings.update'), settings_payload(['google_register_domain' => '@example.org', 'google_register_role' => (string) UserRole::Ensemble->value]))
        ->assertSessionHasErrors(['google_register_domain', 'google_register_role']);
});

test('the Google login section links to the Google Cloud console pages for OAuth clients', function () {
    $this->actingAs(make_user(UserRole::Admin))->get(route('settings.edit'))
        ->assertSee('href="https://console.cloud.google.com/auth/clients/create"', false)
        ->assertSee('href="https://console.cloud.google.com/auth/clients"', false)
        ->assertSee('<code>'.route('google.callback').'</code>', false);
});

test('an admin can upload and remove the favicon', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put(route('settings.update'), settings_payload(['favicon' => UploadedFile::fake()->image('icon.png')]))
        ->assertSessionHasNoErrors();
    $path = Setting::where('key', 'favicon')->value('value');
    Storage::disk('public')->assertExists($path);

    reload_settings();
    expect(favicon_url())->toEndWith('/storage/'.$path);

    $this->actingAs($admin)->put(route('settings.update'), settings_payload(['remove_favicon' => '1']));
    Storage::disk('public')->assertMissing($path);
    expect(Setting::where('key', 'favicon')->exists())->toBeFalse();
});
