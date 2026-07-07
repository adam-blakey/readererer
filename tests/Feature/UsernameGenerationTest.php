<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('a username is the dotted slug of the name when free', function () {
    expect(User::generateUniqueUsername('John', 'Smith'))->toBe('john.smith');
});

test('a colliding username gets a numeric suffix', function () {
    User::factory()->create(['username' => 'john.smith']);

    expect(User::generateUniqueUsername('John', 'Smith'))->toBe('john.smith.2');
});

test('suffixes keep counting up as more collisions occur', function () {
    User::factory()->create(['username' => 'john.smith']);
    User::factory()->create(['username' => 'john.smith.2']);

    expect(User::generateUniqueUsername('John', 'Smith'))->toBe('john.smith.3');
});

test('soft-deleted users still reserve their username', function () {
    $user = User::factory()->create(['username' => 'john.smith']);
    // Soft-delete at the database level to sidestep an unrelated model quirk
    // (User declares a `deleted_at` property that shadows the SoftDeletes cast).
    DB::table('users')->where('id', $user->id)->update(['deleted_at' => now()]);

    expect(User::generateUniqueUsername('John', 'Smith'))->toBe('john.smith.2');
});
