<?php

use App\Enums\UserRole;
use App\Models\SetupGroup;
use App\Models\User;

test('name combines first and last name', function () {
    $user = User::factory()->make(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    expect($user->name)->toBe('Ada Lovelace');
});

test('initials come from the first and last name', function () {
    $user = User::factory()->make(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    expect($user->initials)->toBe('AL');
    expect($user->first_name_initial)->toBe('A');
    expect($user->last_name_initial)->toBe('L');
});

test('role description matches the role', function (UserRole $role, string $description) {
    $user = User::factory()->make(['role' => $role]);

    expect($user->role_description)->toBe($description);
})->with([
    [UserRole::Guest, 'Guest'],
    [UserRole::Ensemble, 'Ensemble'],
    [UserRole::Member, 'Member'],
    [UserRole::Moderator, 'Moderator'],
    [UserRole::Admin, 'Admin'],
]);

test('full address joins the populated address lines', function () {
    $user = User::factory()->make([
        'address_line1' => '1 Main Street',
        'address_line2' => 'Flat 2',
        'address_city' => 'Leeds',
        'address_post_code' => 'LS1 1AA',
    ]);

    expect($user->full_address)->toBe('1 Main Street, Flat 2, Leeds, LS1 1AA');
});

test('full address skips missing address lines', function () {
    $user = User::factory()->make([
        'address_line1' => '1 Main Street',
        'address_line2' => null,
        'address_city' => 'Leeds',
        'address_post_code' => null,
    ]);

    expect($user->full_address)->toBe('1 Main Street, Leeds');
});

test('emergency contact details join the populated fields', function () {
    $user = User::factory()->make([
        'emergency_contact_name' => 'Grace Hopper',
        'emergency_contact_number' => '01234 567890',
        'emergency_contact_relationship' => 'Friend',
        'emergency_contact_address_line1' => null,
        'emergency_contact_address_line2' => null,
        'emergency_contact_address_city' => null,
        'emergency_contact_address_post_code' => null,
    ]);

    expect($user->emergency_contact_details)->toBe('Grace Hopper, 01234 567890, Friend');
});

test('is_over_18 is false without a date of birth', function () {
    $user = User::factory()->make(['date_of_birth' => null]);

    expect($user->is_over_18)->toBeFalse();
});

test('is_over_18 is false for someone under 18', function () {
    $user = User::factory()->make(['date_of_birth' => now()->subYears(17)]);

    expect($user->is_over_18)->toBeFalse();
});

test('is_over_18 is true for someone 18 or older', function () {
    $user = User::factory()->make(['date_of_birth' => now()->subYears(18)->subDay()]);

    expect($user->is_over_18)->toBeTrue();
});

test('users belong to a setup group', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $user = User::factory()->create(['setup_group_id' => $setupGroup->id]);

    expect($user->setup_group->name)->toBe('Group A');
});

test('users are soft deleted and can be restored', function () {
    $user = User::factory()->create();

    $user->delete();
    expect(User::find($user->id))->toBeNull();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();

    $user->restore();
    expect(User::find($user->id))->not->toBeNull();
});
