<?php

use App\Models\User;
use Database\Seeders\SetupGroupSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed([SetupGroupSeeder::class, UserSeeder::class]);
});

test('the default admin account can log in through the login page', function () {
    visit('/login')
        ->assertSee('Login to your account')
        ->assertVisible('#username')
        ->assertVisible('#password')
        ->type('username', 'admin')
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs(User::where('username', 'admin')->first());
});

test('a wrong password keeps the user on the login page and tells them why', function () {
    visit('/login')
        ->type('username', 'admin')
        ->type('password', 'not-the-password')
        ->click('button[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records.')
        ->assertValue('#username', 'admin');

    $this->assertGuest();
});

test('too many failed logins lock the account and tell the user to wait', function () {
    $page = visit('/login');

    foreach (range(1, 5) as $attempt) {
        $page->type('username', 'admin')
            ->type('password', 'not-the-password')
            ->click('button[type="submit"]')
            ->assertSee('These credentials do not match our records.');
    }

    // Even the right password is refused until the lockout expires.
    $page->type('username', 'admin')
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee('Too many login attempts.');

    $this->assertGuest();
});

test('a guest opening a members-only page logs in and is taken back to it', function () {
    visit('/settings')
        ->assertPathIs('/login')
        ->type('username', 'member')
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/settings');
});

test('a logged-in user can log out from the user menu', function () {
    visit('/login')
        ->type('username', 'member')
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard')
        ->click('[aria-label="Open user menu"]')
        ->click('Logout')
        ->assertPathIs('/')
        ->assertVisible('.navbar a[href$="/login"]');

    $this->assertGuest();
});
