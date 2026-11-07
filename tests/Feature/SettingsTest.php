<?php

use App\Enums\UserRole;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
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
        ]))
        ->assertSessionHasErrors(['name', 'image_max_kilobytes', 'terminology']);

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
