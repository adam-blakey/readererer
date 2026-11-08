<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Settings\GeneralSettings;
use Laravel\Pennant\Feature;
use Tests\Fixtures\ExampleFlag;

beforeEach(function () {
    Feature::define(ExampleFlag::class);
});

function set_example_flag(array $options): void
{
    config(['app.readererer_feature_flags' => [ExampleFlag::class => $options]]);
    Feature::flushCache();
}

test('only admins can open the feature flags page and see its tab', function () {
    $this->get('/feature-flags')->assertRedirectContains('/login?redirect=');
    $this->actingAs(make_user(UserRole::Moderator))->get('/feature-flags')->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->put('/feature-flags')->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get('/dashboard')->assertDontSee(route('feature-flags.edit'));

    $this->actingAs(make_user(UserRole::Admin))->get('/dashboard')->assertSee(route('feature-flags.edit'));
    $this->actingAs(make_user(UserRole::Admin))->get('/feature-flags')
        ->assertOk()
        ->assertSee('Example flag')
        ->assertSee('A flag used by the tests.');
});

test('saving the page stores each flag\'s options', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->put('/feature-flags', ['flags' => [ExampleFlag::class => [
            'enabled' => '1',
            'roles' => [(string) UserRole::Member->value],
            'ensembles' => [(string) $ensemble->id],
            'starts_at' => '2026-12-01T09:00',
            'from_version' => 'v1.5.0',
        ]]])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/feature-flags');

    expect(app(GeneralSettings::class)->feature_flags)->toBe([ExampleFlag::class => [
        'enabled' => true,
        'roles' => [UserRole::Member->value],
        'ensembles' => [$ensemble->id],
        'starts_at' => '2026-12-01T09:00',
        'from_version' => 'v1.5.0',
    ]]);
});

test('invalid options are rejected', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->put('/feature-flags', ['flags' => [ExampleFlag::class => ['roles' => ['9'], 'starts_at' => 'soon', 'from_version' => 'latest']]])
        ->assertSessionHasErrors(['flags.'.ExampleFlag::class.'.roles.0', 'flags.'.ExampleFlag::class.'.starts_at', 'flags.'.ExampleFlag::class.'.from_version']);
});

test('a flag is off until it is turned on', function () {
    $user = make_user(UserRole::Member);

    expect(Feature::for($user)->active(ExampleFlag::class))->toBeFalse();

    set_example_flag(['enabled' => true]);

    expect(Feature::for($user)->active(ExampleFlag::class))->toBeTrue()
        ->and(Feature::for(null)->active(ExampleFlag::class))->toBeTrue();
});

test('a flag can be limited to roles and ensembles', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $admin = make_user(UserRole::Admin);
    join_ensemble($admin, $ensemble);

    set_example_flag(['enabled' => true, 'roles' => [UserRole::Admin->value]]);
    expect(Feature::for($admin)->active(ExampleFlag::class))->toBeTrue()
        ->and(Feature::for($member)->active(ExampleFlag::class))->toBeFalse()
        ->and(Feature::for(null)->active(ExampleFlag::class))->toBeFalse();

    set_example_flag(['enabled' => true, 'ensembles' => [$ensemble->id]]);
    expect(Feature::for($admin)->active(ExampleFlag::class))->toBeTrue()
        ->and(Feature::for($member)->active(ExampleFlag::class))->toBeFalse()
        ->and(Feature::for(null)->active(ExampleFlag::class))->toBeFalse();
});

test('a flag can roll out at a time', function () {
    $this->travelTo('2026-11-30 12:00');
    set_example_flag(['enabled' => true, 'starts_at' => '2026-12-01T09:00']);
    expect(Feature::active(ExampleFlag::class))->toBeFalse();

    $this->travelTo('2026-12-01 09:00');
    Feature::flushCache();
    expect(Feature::active(ExampleFlag::class))->toBeTrue();
});

test('a flag can roll out with a release', function (string $version, bool $active) {
    config(['app.version' => $version]);
    set_example_flag(['enabled' => true, 'from_version' => 'v1.5.0']);

    expect(Feature::active(ExampleFlag::class))->toBe($active);
})->with([
    'an earlier release' => ['v1.4.9', false],
    'a dev build of an earlier release' => ['v1.4.9-3-gabc1234+pr62', false],
    'that release' => ['v1.5.0', true],
    'a later release' => ['v1.10.0', true],
    'an unreleased checkout' => ['0.0.0', false],
]);
