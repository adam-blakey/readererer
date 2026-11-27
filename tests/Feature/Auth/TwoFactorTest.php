<?php

use App\Enums\UserRole;
use App\Features\TwoFactorAuthentication;
use App\Models\User;
use Laravel\Pennant\Feature;
use PragmaRX\Google2FA\Google2FA;

function enable_two_factor_flag(): void
{
    config(['app.readererer_feature_flags' => [TwoFactorAuthentication::class => ['enabled' => true]]]);
    Feature::flushCache();
}

function current_code(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

function make_two_factor_user(UserRole $role = UserRole::Member): User
{
    return make_user($role, [
        'two_factor_secret' => app(Google2FA::class)->generateSecretKey(32),
        'two_factor_recovery_codes' => ['aaaaa-bbbbb', 'ccccc-ddddd'],
    ]);
}

test('the setup page is off until the flag is on', function () {
    $user = make_user();

    $this->actingAs($user)->get(route('two-factor.edit'))->assertNotFound();
    $this->actingAs($user)->post(route('two-factor.store'), ['code' => '123456'])->assertBadRequest();
});

test('a member turns on two-factor authentication with a code from their app', function () {
    enable_two_factor_flag();
    $user = make_user();

    $this->actingAs($user)->get(route('two-factor.edit'))->assertOk()->assertSee('<svg', false);
    $secret = session('two_factor.setup_secret');

    $this->post(route('two-factor.store'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasTwoFactor())->toBeFalse();

    $this->post(route('two-factor.store'), ['code' => current_code($secret)])
        ->assertRedirect(route('two-factor.edit'));

    $user->refresh();
    expect($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_recovery_codes)->toHaveCount(8)
        ->and($user->getRawOriginal('two_factor_secret'))->not->toBe($secret);

    $this->get(route('two-factor.edit'))->assertSee($user->two_factor_recovery_codes[0]);
});

test('shared ensemble logins cannot use two-factor authentication', function () {
    enable_two_factor_flag();

    $this->actingAs(make_user(UserRole::Ensemble))->get(route('two-factor.edit'))->assertForbidden();
});

test('logging in asks for a code before any other page', function () {
    $user = make_two_factor_user();

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->assertAuthenticatedAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
    $this->get(route('users.show', $user))->assertRedirect(route('two-factor.challenge'));
    $this->get(route('two-factor.challenge'))->assertOk();

    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));

    $this->post(route('two-factor.verify'), ['code' => current_code($user->two_factor_secret)])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->get(route('dashboard'))->assertOk();
});

test('a recovery code logs in once', function () {
    $user = make_two_factor_user();

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->post(route('two-factor.verify'), ['code' => 'AAAAA-BBBBB'])->assertSessionHasNoErrors();
    expect($user->fresh()->two_factor_recovery_codes)->toBe(['ccccc-ddddd']);

    $this->post('/logout');
    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->post(route('two-factor.verify'), ['code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('code');
});

test('users without two-factor authentication are not asked for a code', function () {
    $user = make_user();

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);

    $this->get(route('dashboard'))->assertOk();
});

test('turning it off needs a code, even once the flag is off', function () {
    $user = make_two_factor_user();

    $this->actingAs($user)->get(route('two-factor.edit'))->assertOk();
    $this->delete(route('two-factor.destroy'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasTwoFactor())->toBeTrue();

    $this->delete(route('two-factor.destroy'), ['code' => current_code($user->two_factor_secret)]);
    expect($user->fresh()->hasTwoFactor())->toBeFalse();
});

test('an admin can turn off another user\'s two-factor authentication', function () {
    $user = make_two_factor_user();

    $this->actingAs(make_user(UserRole::Moderator))->delete(route('users.two-factor.destroy', $user))->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->delete(route('users.two-factor.destroy', $user))
        ->assertRedirect(route('users.show', $user));

    expect($user->fresh()->hasTwoFactor())->toBeFalse();
});
