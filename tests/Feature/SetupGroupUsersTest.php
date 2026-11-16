<?php

use App\Enums\UserRole;
use App\Models\SetupGroup;

function make_setup_group(): SetupGroup
{
    return SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
}

test('the show page lists the users in the group', function () {
    $group = make_setup_group();
    make_user(UserRole::Member, ['first_name' => 'Zelda', 'setup_group_id' => $group->id]);
    make_user(UserRole::Member, ['first_name' => 'Outsider', 'setup_group_id' => null]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('setupgroups.show', $group))
        ->assertOk()
        ->assertSee('Zelda')
        ->assertDontSee('Outsider');
});

test('the edit form offers a users field', function () {
    $group = make_setup_group();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('setupgroups.edit', $group))
        ->assertOk()
        ->assertSee('name="members[]"', false);
});

test('updating adds and removes users', function () {
    $group = make_setup_group();
    $kept = make_user(UserRole::Member, ['setup_group_id' => $group->id]);
    $removed = make_user(UserRole::Member, ['setup_group_id' => $group->id]);
    $added = make_user(UserRole::Member, ['setup_group_id' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('setupgroups.update', $group), [
            'name' => 'Group A', 'week' => 1, 'color' => 'blue',
            'members' => [$kept->id, $added->id],
        ])->assertRedirect();

    expect($kept->fresh()->setup_group_id)->toBe($group->id)
        ->and($added->fresh()->setup_group_id)->toBe($group->id)
        ->and($removed->fresh()->setup_group_id)->toBeNull();
});

test('unknown user ids are rejected', function () {
    $group = make_setup_group();

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('setupgroups.update', $group), [
            'name' => 'Group A', 'color' => 'blue', 'members' => [999999],
        ])->assertSessionHasErrors('members.0');
});

test('only admins can change a group users', function () {
    $group = make_setup_group();
    $user = make_user(UserRole::Member, ['setup_group_id' => null]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->patch(route('setupgroups.update', $group), [
            'name' => 'Group A', 'color' => 'blue', 'members' => [$user->id],
        ])->assertForbidden();

    expect($user->fresh()->setup_group_id)->toBeNull();
});
