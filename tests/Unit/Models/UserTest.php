<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
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

test('the abbreviated name keeps the first name and the initials of the surname', function (string $lastName, string $abbreviated) {
    $user = User::factory()->make(['first_name' => 'Adam', 'last_name' => $lastName]);

    expect($user->abbreviated_name)->toBe($abbreviated);
})->with([
    ['Blakey', 'Adam B'],
    ['van der Berg', 'Adam v d B'],
    ['Smith-Jones', 'Adam S-J'],
    ['Ødegaard', 'Adam Ø'],
    ['', 'Adam'],
]);

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
    $setupGroup = SetupGroup::factory()->create(['name' => 'Group A']);
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

test('the latest attendance is found per date and ensemble, and follows newly loaded replies', function () {
    $user = User::factory()->create();
    $termDate = make_term_date();
    [$band, $choir] = Ensemble::factory()->count(2)->create();
    $reply = fn (Ensemble $ensemble, AttendanceStatus $status, string $at) => Attendance::factory()->create([
        'user_id' => $user->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => $status,
        'dep_name' => null,
        'created_at' => $at,
    ]);
    $reply($band, AttendanceStatus::Attending, '2026-01-01 10:00:00');
    $reply($band, AttendanceStatus::NotAttending, '2026-01-02 10:00:00');
    $reply($choir, AttendanceStatus::Attending, '2026-01-03 10:00:00');

    expect($user->latestAttendanceFor($termDate, $band)->status)->toBe(AttendanceStatus::NotAttending)
        ->and($user->latestAttendanceFor($termDate)->ensemble_id)->toBe($choir->id)
        ->and($user->lastAttendanceUpdateIn($band)->toDateTimeString())->toBe('2026-01-02 10:00:00');

    $reply($band, AttendanceStatus::Attending, '2026-01-04 10:00:00');
    $user->load('attendances');

    expect($user->latestAttendanceFor($termDate, $band)->status)->toBe(AttendanceStatus::Attending);
});
