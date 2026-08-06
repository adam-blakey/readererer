<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('login screen tabs the forgotten password link last, after login with Google', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);

    // The link stays reachable by keyboard, but is numbered after every other
    // focusable element on the page rather than sitting beside the password field.
    $response->assertSeeInOrder([
        'href="'.route('password.request').'"',
        'tabindex="7"',
        'I forgot password',
    ], false);
    $response->assertDontSee('tabindex="9999"', false);

    // Every other focusable element is numbered so the ordering above holds:
    // a positive tabindex is always traversed before elements in natural order.
    $response->assertSee('name="username"', false);
    $response->assertSeeInOrder(['name="username"', 'tabindex="2"'], false);
    $response->assertSeeInOrder(['name="password"', 'tabindex="3"'], false);
    $response->assertSeeInOrder(['name="remember_me"', 'tabindex="4"'], false);
    $response->assertSeeInOrder(['tabindex="5"', 'Login'], false);
    $response->assertSeeInOrder(['tabindex="6"', 'Login with Google'], false);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
