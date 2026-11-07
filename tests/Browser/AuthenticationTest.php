<?php

use App\Models\Ensemble;
use App\Models\Term;
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
    visit('/password')
        ->assertPathIs('/login')
        ->type('username', 'member')
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/password');
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

test('a guest who opens a poll from the home page lands on it after logging in', function () {
    $ensemble = Ensemble::factory()->create(['image' => null]);
    $term = Term::factory()->create(['name' => 'Spring term']);
    make_term_date($term);

    $step = fn (string $name) => fwrite(STDERR, "STEP {$name}\n");
    $step('visit');
    $page = visit('/');
    $step('visited');
    $page->click('Spring term');
    $step('clicked term');
    $page->assertPathIs('/login');
    $step('on login');
    $page->type('username', 'admin')->type('password', 'password');
    $step('typed');
    $page->click('button[type="submit"]');
    $step('submitted');
    $page->assertPathIs(route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term], absolute: false));
    $step('done');
});
