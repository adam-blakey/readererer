<?php

use App\Enums\UserRole;
use App\Features\ConfirmPasswordForSensitiveChanges;
use Laravel\Pennant\Feature;

function enable_password_confirmation(): void
{
    config(['app.readererer_feature_flags' => [ConfirmPasswordForSensitiveChanges::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('sensitive pages ask for the password when the flag is on', function (string $route) {
    enable_password_confirmation();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route($route))
        ->assertRedirect(route('password.confirm'));
})->with(['settings.edit', 'settings.import', 'feature-flags.edit']);

test('sensitive pages open without asking when the flag is off', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.edit'))
        ->assertOk();
});

test('sensitive pages open once the password has been confirmed', function () {
    enable_password_confirmation();

    $this->actingAs(make_user(UserRole::Admin))
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.edit'))
        ->assertOk();
});

test('confirming the password returns to the page that asked for it', function () {
    enable_password_confirmation();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('feature-flags.edit'));

    $this->actingAs($admin)
        ->post(route('password.confirm'), ['password' => 'password'])
        ->assertRedirect(route('feature-flags.edit'));
});

test('editing a user asks a moderator for the password', function () {
    enable_password_confirmation();

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('users.edit', make_user()))
        ->assertRedirect(route('password.confirm'));
});

test('members editing their own profile are not asked for the password', function () {
    enable_password_confirmation();
    $member = make_user();

    $this->actingAs($member)
        ->get(route('users.edit', $member))
        ->assertOk();
});

test('erasing a user asks for the password', function () {
    enable_password_confirmation();
    $user = make_user();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('users.erase', $user), ['confirm_name' => $user->name])
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->username)->not->toBe("erased-{$user->id}");
});
