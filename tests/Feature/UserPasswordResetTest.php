<?php

use App\Enums\EmailLinkType;
use App\Enums\UserRole;
use App\Notifications\EmailLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('an admin can email a user a password reset link', function () {
    Notification::fake();
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->from(route('users.show', $user))
        ->post(route('users.password-reset-link', $user))
        ->assertRedirect(route('users.show', $user))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, EmailLink::class, fn (EmailLink $notification) => $notification->type === EmailLinkType::ResetPassword);
});

test('a reset link cannot be sent to a user without an email address', function () {
    Notification::fake();
    $user = make_user(UserRole::Member, ['email' => null]);
    make_user(UserRole::Member, ['email' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.password-reset-link', $user))
        ->assertStatus(422);

    Notification::assertNothingSent();
});

test('an admin can set a user\'s password', function (bool $mustChange) {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->from(route('users.show', $user))
        ->put(route('users.password', $user), array_filter([
            'password' => 'a-new-Password-123',
            'password_confirmation' => 'a-new-Password-123',
            'must_change_password' => $mustChange ? '1' : null,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect(Hash::check('a-new-Password-123', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBe($mustChange);
})->with([true, false]);

test('a set password must be confirmed', function () {
    $user = make_user(UserRole::Member);
    $password = $user->password;

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'different'])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->password)->toBe($password);
});

test('only admins can reset another user\'s password', function (UserRole $role) {
    Notification::fake();
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user($role))->post(route('users.password-reset-link', $user))->assertForbidden();
    $this->actingAs(make_user($role))->put(route('users.password', $user), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])->assertForbidden();
})->with([UserRole::Moderator, UserRole::Member]);

test('admins cannot reset their own password from the user page', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->put(route('users.password', $admin), ['password' => 'a-new-Password-123', 'password_confirmation' => 'a-new-Password-123'])->assertForbidden();
});

test('the reset password button only shows to admins', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))->get(route('users.show', $user))->assertSee('modal-reset-password');
    $this->actingAs(make_user(UserRole::Moderator))->get(route('users.show', $user))->assertDontSee('modal-reset-password');
});
