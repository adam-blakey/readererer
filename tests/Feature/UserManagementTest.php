<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\User;

test('the user index, create form and show page render', function () {
    $viewer = make_user(UserRole::Moderator);
    $shown = make_user(UserRole::Member);

    $this->actingAs($viewer)->get(route('users.index'))->assertOk();
    $this->actingAs($viewer)->get(route('users.create'))->assertOk();
    $this->actingAs($viewer)->get(route('users.show', $shown))->assertOk();
});

test('a user can be created with a generated username', function () {
    $setupGroup = SetupGroup::factory()->create();

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
    expect($user->setup_group_id)->toBe($setupGroup->id);
    expect($user->must_change_password)->toBeTrue();
    $response->assertRedirect(route('users.show', $user));
});

test('a user can be created without a setup group', function () {
    $response = $this->actingAs(make_user(UserRole::Admin))->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Member->value,
        'setup_group' => '',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->setup_group_id)->toBeNull();
});

test('creating a user requires names and rejects an unknown setup group', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.store'), [
            'email' => 'ada@example.com',
            'setup_group' => 999999,
        ])
        ->assertSessionHasErrors(['first_name', 'last_name', 'role', 'setup_group']);
});

test('creating a user rejects a duplicate email address', function () {
    make_user(UserRole::Member, ['email' => 'ada@example.com']);
    $setupGroup = SetupGroup::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'role' => UserRole::Member->value,
            'setup_group' => $setupGroup->id,
        ])
        ->assertSessionHasErrors('email');

    expect(User::where('email', 'ada@example.com')->count())->toBe(1);
});

test('creating a user rejects a malformed email address and requires one', function () {
    $setupGroup = SetupGroup::factory()->create();
    $admin = make_user(UserRole::Admin);

    $base = [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'role' => UserRole::Member->value,
        'setup_group' => $setupGroup->id,
    ];

    $this->actingAs($admin)
        ->post(route('users.store'), array_merge($base, ['email' => 'not-an-email']))
        ->assertSessionHasErrors('email');

    $this->actingAs($admin)
        ->post(route('users.store'), $base)
        ->assertSessionHasErrors('email');

    expect(User::where('first_name', 'Ada')->exists())->toBeFalse();
});

test('two users with the same name get distinct usernames', function () {
    $setupGroup = SetupGroup::factory()->create();
    $admin = make_user(UserRole::Admin);

    foreach (['ada@example.com', 'ada2@example.com'] as $email) {
        $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $email,
            'role' => UserRole::Member->value,
            'setup_group' => $setupGroup->id,
        ]);
    }

    expect(User::where('email', 'ada@example.com')->first()->username)->toBe('ada.lovelace');
    expect(User::where('email', 'ada2@example.com')->first()->username)->toBe('ada.lovelace.2');
});

test('creating a user rejects an invalid role', function () {
    $setupGroup = SetupGroup::factory()->create();

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
    $setupGroup = SetupGroup::factory()->create();
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

test('a user\'s setup group can be cleared', function () {
    $setupGroup = SetupGroup::factory()->create();
    $user = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role->value,
            'setup_group' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.show', $user));

    expect($user->fresh()->setup_group_id)->toBeNull();
});

test('updating a user rejects another user\'s email address', function () {
    make_user(UserRole::Member, ['email' => 'taken@example.com']);
    $setupGroup = SetupGroup::factory()->create();
    $user = make_user(UserRole::Member, ['email' => 'original@example.com']);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'taken@example.com',
            'role' => UserRole::Member->value,
            'setup_group' => $setupGroup->id,
        ])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->toBe('original@example.com');
});

test('a user may keep their own email address when updated', function () {
    $setupGroup = SetupGroup::factory()->create();
    $user = make_user(UserRole::Member, ['email' => 'ada@example.com']);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'role' => UserRole::Member->value,
            'setup_group' => $setupGroup->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.show', $user));
});

test('an invalid update leaves the user untouched', function () {
    $user = make_user(UserRole::Member, ['first_name' => 'Ada', 'email' => 'ada@example.com']);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'not-an-email',
            'role' => 99,
            'setup_group' => 999999,
        ])
        ->assertSessionHasErrors(['email', 'role', 'setup_group']);

    expect($user->fresh()->first_name)->toBe('Ada');
    expect($user->fresh()->email)->toBe('ada@example.com');
});

test('the show page for a soft-deleted user is not found', function () {
    $user = make_user(UserRole::Member);
    $user->delete();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.show', $user->id))
        ->assertNotFound();
});

test('a user can be soft deleted and restored through the endpoints', function () {
    // Regression: a declared `deleted_at` property shadowed the attribute and broke these endpoints.
    $user = make_user(UserRole::Member);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->delete(route('users.destroy', $user))->assertRedirect();
    expect(User::find($user->id))->toBeNull();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();

    $this->actingAs($admin)->patch(route('users.restore', $user->id))->assertRedirect();
    expect(User::find($user->id))->not->toBeNull();
});

test('the user edit form renders', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk();
});

test('the user edit page renders for a user with a setup group and ensembles', function () {
    $setupGroup = SetupGroup::factory()->create();
    $user = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($user, $ensemble, make_instrument_family('Strings'), 'A', '1');

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('Add to ensemble')
        ->assertSee($ensemble->name)
        ->assertSee('Setup group');
});

test('the user edit page renders when the user has no setup group or ensembles', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertSee('not a member of any ensembles');
});

test('the user edit page offers an empty setup group option', function () {
    $setupGroup = SetupGroup::factory()->create();
    $user = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);

    $html = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('users.edit', $user))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('No setup group')
        ->toMatch('/<select name="setup_group"(?![^>]*\brequired\b)[^>]*>/');
});

test('a user can be added to an ensemble from the edit page', function () {
    $user = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => true]);
    $instrumentFamily = make_instrument_family('Brass');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.ensembles.store', $user), [
            'ensemble_id' => $ensemble->id,
            'instrument_family_id' => $instrumentFamily->id,
            'seat_row' => 'B',
            'seat_column' => '2',
        ])
        ->assertRedirect();

    $pivot = $user->fresh()->ensembles->firstWhere('id', $ensemble->id)?->pivot;
    expect($pivot)->not->toBeNull();
    expect($pivot->instrument_family_id)->toBe($instrumentFamily->id);
    expect($pivot->seat_row)->toBe('B');
    expect($pivot->seat_column)->toBe('2');
});

test('adding a user to a seating-plan-disabled ensemble from the edit page ignores the seat', function () {
    $user = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => false]);
    $instrumentFamily = make_instrument_family('Brass');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.ensembles.store', $user), [
            'ensemble_id' => $ensemble->id,
            'instrument_family_id' => $instrumentFamily->id,
            'seat_row' => 'B',
            'seat_column' => '2',
        ])
        ->assertRedirect();

    $pivot = $user->fresh()->ensembles->firstWhere('id', $ensemble->id)?->pivot;
    expect($pivot->instrument_family_id)->toBe($instrumentFamily->id);
    expect($pivot->seat_row)->toBeNull();
    expect($pivot->seat_column)->toBeNull();
});

test('adding a user to an ensemble they already belong to does not duplicate the membership', function () {
    $user = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    $instrumentFamily = make_instrument_family('Woodwind');
    join_ensemble($user, $ensemble, $instrumentFamily);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.ensembles.store', $user), [
            'ensemble_id' => $ensemble->id,
            'instrument_family_id' => $instrumentFamily->id,
        ])
        ->assertRedirect();

    expect($user->fresh()->ensembles()->where('ensembles.id', $ensemble->id)->count())->toBe(1);
});

test('adding a user to an ensemble validates the input', function () {
    $user = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('users.ensembles.store', $user), [
            'ensemble_id' => 999999,
            'instrument_family_id' => 999999,
        ])
        ->assertSessionHasErrors(['ensemble_id', 'instrument_family_id']);
});

test('a user can be removed from an ensemble from the edit page', function () {
    $user = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($user, $ensemble, make_instrument_family('Percussion'));

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('users.ensembles.destroy', ['user' => $user, 'ensemble' => $ensemble]))
        ->assertRedirect();

    expect($user->fresh()->ensembles->contains('id', $ensemble->id))->toBeFalse();
});

test('ensemble logins and members cannot manage users', function (UserRole $role) {
    $actor = make_user($role);
    $target = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($target, $ensemble);
    $payload = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'role' => UserRole::Admin->value];

    $this->actingAs($actor)->get(route('users.index'))->assertForbidden();
    $this->actingAs($actor)->post(route('users.store'), $payload)->assertForbidden();
    $this->actingAs($actor)->patch(route('users.update', $actor), $payload)->assertForbidden();
    $this->actingAs($actor)->delete(route('users.destroy', $target))->assertForbidden();
    $this->actingAs($actor)->delete(route('users.ensembles.destroy', [$target, $ensemble]))->assertForbidden();

    expect($actor->fresh()->role)->toBe($role);
    expect($target->fresh()->trashed())->toBeFalse();
    expect($target->fresh()->ensembles)->toHaveCount(1);
})->with([UserRole::Ensemble, UserRole::Member]);

test('moderators cannot grant a role above their own or edit an admin', function () {
    $moderator = make_user(UserRole::Moderator);
    $member = make_user(UserRole::Member);
    $admin = make_user(UserRole::Admin);
    $payload = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com'];

    $this->actingAs($moderator)
        ->patch(route('users.update', $member), $payload + ['role' => UserRole::Admin->value])
        ->assertSessionHasErrors('role');
    $this->actingAs($moderator)
        ->patch(route('users.update', $admin), $payload + ['role' => UserRole::Member->value])
        ->assertForbidden();
    $this->actingAs($moderator)->delete(route('users.destroy', $admin))->assertForbidden();

    expect($member->fresh()->role)->toBe(UserRole::Member);
    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

test('creating a user retries with a fresh username if the generated one is taken meanwhile', function () {
    $raced = false;
    User::creating(function (User $user) use (&$raced) {
        if (! $raced && $user->username === 'ada.lovelace') {
            $raced = true;
            make_user(UserRole::Member, ['username' => 'ada.lovelace']);
        }
    });

    $this->actingAs(make_user(UserRole::Admin))->post(route('users.store'), [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'role' => UserRole::Member->value,
    ])->assertRedirect();

    expect($raced)->toBeTrue();
    expect(User::where('email', 'ada@example.com')->value('username'))->toBe('ada.lovelace.2');
});
