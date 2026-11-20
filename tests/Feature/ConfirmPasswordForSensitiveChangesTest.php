<?php

use App\Enums\UserRole;
use App\Features\ConfirmPasswordOnSave;
use Laravel\Pennant\Feature;

beforeEach(fn () => $this->withSession(['auth.password_confirmed_at' => 0]));

test('sensitive pages ask for the password', function (string $route) {

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route($route))
        ->assertRedirect(route('password.confirm'));
})->with(['settings.edit', 'settings.import', 'feature-flags.edit']);

test('sensitive pages open once the password has been confirmed', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.edit'))
        ->assertOk();
});

test('confirming the password returns to the page that asked for it', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('feature-flags.edit'));

    $this->actingAs($admin)
        ->post(route('password.confirm'), ['password' => 'password'])
        ->assertRedirect(route('feature-flags.edit'));
});

test('editing a user asks a moderator for the password', function () {

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('users.edit', make_user()))
        ->assertRedirect(route('password.confirm'));
});

test('members editing their own profile are not asked for the password', function () {
    $member = make_user();

    $this->actingAs($member)
        ->get(route('users.edit', $member))
        ->assertOk();
});

test('erasing a user asks for the password', function () {
    $user = make_user();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('users.erase', $user), ['confirm_name' => $user->name])
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->username)->not->toBe("erased-{$user->id}");
});

function confirm_password_on_save_flag(): void
{
    config(['app.readererer_feature_flags' => [ConfirmPasswordOnSave::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('with confirm on save, sensitive pages open without the password', function () {
    confirm_password_on_save_flag();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('settings.edit'))
        ->assertOk();
});

test('with confirm on save, saving asks for the password and keeps what was typed', function () {
    confirm_password_on_save_flag();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->from(route('feature-flags.edit'))
        ->put(route('feature-flags.update'), ['typed' => 'kept', 'password' => 'secret'])
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($admin)
        ->post(route('password.confirm'), ['password' => 'password'])
        ->assertRedirect(route('feature-flags.edit'))
        ->assertSessionHasInput('typed', 'kept')
        ->assertSessionMissing('_old_input.password');
});
