<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function user_who_must_change_password(): User
{
    $user = make_user(UserRole::Admin);
    $user->forceFill(['must_change_password' => true])->save();

    return $user;
}

test('a flagged user is sent to the change password page from anywhere', function (string $route) {
    $this->actingAs(user_who_must_change_password())
        ->get(route($route))
        ->assertRedirect(route('password.edit'));
})->with(['home', 'dashboard', 'attendance.index', 'users.index']);

test('a flagged user gets a 403 from JSON requests', function () {
    $this->actingAs(user_who_must_change_password())
        ->getJson(route('dashboard'))
        ->assertForbidden();
});

test('a flagged user can still log out', function () {
    $this->actingAs(user_who_must_change_password())
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});

test('the forced change page hides the menu and does not ask for the current password', function () {
    $this->actingAs(user_who_must_change_password())
        ->get(route('password.edit'))
        ->assertOk()
        ->assertDontSee('name="current_password"', false)
        ->assertDontSee('href="'.route('settings.edit').'"', false);
});

test('changing the password clears the flag and returns to the page first asked for', function () {
    $user = user_who_must_change_password();

    $this->actingAs($user)->get(route('users.index'));

    $this->put(route('password.update'), [
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('a-new-password', $user->password))->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
});

test('the temporary password cannot be kept', function () {
    $this->actingAs(user_who_must_change_password())
        ->put(route('password.update'), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');
});

test('users who are not flagged are not redirected', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('dashboard'))
        ->assertOk();
});
