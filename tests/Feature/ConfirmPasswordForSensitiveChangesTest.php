<?php

use App\Enums\UserRole;

beforeEach(fn () => $this->withSession(['auth.password_confirmed_at' => 0]));

test('sensitive pages open without the password', function (string $route) {

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route($route))
        ->assertOk();
})->with(['settings.edit', 'settings.import', 'feature-flags.edit']);

test('saving sensitive changes asks for the password', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('feature-flags.update'))
        ->assertRedirect(route('password.confirm'));
});

test('saving sensitive changes works once the password has been confirmed', function () {

    $this->actingAs(make_user(UserRole::Admin))
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('feature-flags.update'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('confirming the password returns to the form and keeps what was typed', function () {
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

test('editing a user opens without the password but saving asks a moderator for it', function () {
    $moderator = make_user(UserRole::Moderator);
    $user = make_user();

    $this->actingAs($moderator)
        ->get(route('users.edit', $user))
        ->assertOk();

    $this->actingAs($moderator)
        ->put(route('users.update', $user), ['name' => 'New name'])
        ->assertRedirect(route('password.confirm'));
});

test('members saving their own profile are not asked for the password', function () {
    $member = make_user();

    $this->actingAs($member)
        ->put(route('users.update', $member))
        ->assertSessionHasErrors('email');
});

test('erasing a user asks for the password', function () {
    $user = make_user();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('users.erase', $user), ['confirm_name' => $user->name])
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->username)->not->toBe("erased-{$user->id}");
});
