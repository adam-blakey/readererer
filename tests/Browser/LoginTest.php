<?php

use App\Models\User;
use Database\Seeders\SetupGroupSeeder;
use Database\Seeders\UserSeeder;

test('the default admin account can log in through the login page', function () {
    $this->seed([SetupGroupSeeder::class, UserSeeder::class]);

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
