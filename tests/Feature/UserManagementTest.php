<?php

use App\Enums\UserRole;
use App\Models\SetupGroup;
use App\Models\User;

function make_setup_group_for_users(): SetupGroup
{
    return SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
}

test('the user index, create form and show page render', function () {
    $viewer = make_user(UserRole::Member);
    $shown = make_user(UserRole::Member);

    $this->actingAs($viewer)->get(route('users.index'))->assertOk();
    $this->actingAs($viewer)->get(route('users.create'))->assertOk();
    $this->actingAs($viewer)->get(route('users.show', $shown))->assertOk();
});

test('a user can be created with a generated username', function () {
    $setupGroup = make_setup_group_for_users();

    $response = $this->actingAs(make_user(UserRole::Admin))->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Member->value,
        'setup_group' => $setupGroup->id,
    ]);

    $user = User::where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->username)->toBe('ada.lovelace');
    expect($user->role)->toBe(UserRole::Member);
    $response->assertRedirect(route('users.show', $user));
});

test('creating a user requires names and an existing setup group', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.store'), [
            'email' => 'ada@example.com',
            'setup_group' => 999999,
        ])
        ->assertSessionHasErrors(['first_name', 'last_name', 'setup_group']);
});

test('creating a user rejects an invalid role', function () {
    $setupGroup = make_setup_group_for_users();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'role' => 99,
            'setup_group' => $setupGroup->id,
        ])
        ->assertSessionHasErrors('role');
});

test('a user can be updated including their setup group', function () {
    $setupGroup = make_setup_group_for_users();
    $user = make_user(UserRole::Member);

    $response = $this->actingAs(make_user(UserRole::Admin))->patch(route('users.update', $user), [
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'role' => UserRole::Moderator->value,
        'setup_group' => $setupGroup->id,
    ]);

    $response->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect($user->first_name)->toBe('Grace');
    expect($user->last_name)->toBe('Hopper');
    expect($user->email)->toBe('grace@example.com');
    expect($user->role)->toBe(UserRole::Moderator);
    expect($user->setup_group_id)->toBe($setupGroup->id);
});

test('the user edit form renders', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk();
});
