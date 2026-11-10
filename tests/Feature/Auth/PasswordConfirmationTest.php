<?php

use App\Enums\UserRole;
use App\Models\User;

test('confirm password screen can be rendered', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Member, 'must_change_password' => false]))
        ->get('/confirm-password')
        ->assertOk()
        ->assertSee(__('Please confirm your password before continuing.'));
});

test('password can be confirmed', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Member, 'must_change_password' => false]))
        ->post('/confirm-password', ['password' => 'password'])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('auth.password_confirmed_at');
});

test('password is not confirmed with an invalid password', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Member, 'must_change_password' => false]))
        ->post('/confirm-password', ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');
});
