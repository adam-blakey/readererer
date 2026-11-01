<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('it creates a user with the given role and prints their login', function () {
    $this->artisan('user:create', ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', '--role' => 'Admin'])
        ->expectsOutputToContain('Username: ada.lovelace')
        ->expectsOutputToContain('Temporary password: ')
        ->assertSuccessful();

    $user = User::where('email', 'ada@example.com')->sole();
    expect($user->role)->toBe(UserRole::Admin)
        ->and($user->username)->toBe('ada.lovelace')
        ->and($user->must_change_password)->toBeTrue();
});

test('the printed password is the stored one', function () {
    $this->withoutMockingConsoleOutput()
        ->artisan('user:create', ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

    preg_match('/Temporary password: (\S+)/', Artisan::output(), $matches);
    $user = User::where('email', 'ada@example.com')->sole();

    expect(Hash::check($matches[1], $user->password))->toBeTrue()
        ->and($user->role)->toBe(UserRole::Member);
});

test('it refuses a taken email or an unknown role', function (array $overrides) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', $overrides + ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'])
        ->assertFailed();

    expect(User::where('first_name', 'Ada')->exists())->toBeFalse();
})->with([
    'taken email' => [['email' => 'taken@example.com']],
    'unknown role' => [['--role' => 'superuser']],
]);
