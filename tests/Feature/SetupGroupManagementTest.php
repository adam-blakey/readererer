<?php

use App\Enums\UserRole;
use App\Models\SetupGroup;

test('a setup group can be created with van drivers', function () {
    $driverOne = make_user();
    $driverTwo = make_user();

    $response = $this->actingAs(make_user(UserRole::Admin))->post(route('setup-groups.store'), [
        'name' => 'Group A',
        'week' => 2,
        'color' => 'blue',
        'van_drivers' => [$driverOne->id, $driverTwo->id],
    ]);

    $setupGroup = SetupGroup::where('name', 'Group A')->first();
    expect($setupGroup)->not->toBeNull();
    expect($setupGroup->week)->toBe(2);
    expect($setupGroup->color)->toBe('blue');
    expect($setupGroup->van_drivers->pluck('id')->sort()->values()->all())
        ->toBe(collect([$driverOne->id, $driverTwo->id])->sort()->values()->all());
    $response->assertRedirect(route('setup-groups.show', $setupGroup));
});

test('a setup group can be created without van drivers', function () {
    $this->actingAs(make_user(UserRole::Admin))->post(route('setup-groups.store'), [
        'name' => 'Group B',
        'color' => 'red',
    ])->assertRedirect();

    $setupGroup = SetupGroup::where('name', 'Group B')->first();
    expect($setupGroup)->not->toBeNull();
    expect($setupGroup->van_drivers)->toHaveCount(0);
});

test('creating a setup group requires a name and colour', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('setup-groups.store'), [])
        ->assertSessionHasErrors(['name', 'color']);

    expect(SetupGroup::count())->toBe(0);
});

test('creating a setup group rejects an out-of-range week', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('setup-groups.store'), [
            'name' => 'Group A',
            'week' => 11,
            'color' => 'blue',
        ])
        ->assertSessionHasErrors('week');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('setup-groups.store'), [
            'name' => 'Group A',
            'week' => -1,
            'color' => 'blue',
        ])
        ->assertSessionHasErrors('week');

    expect(SetupGroup::count())->toBe(0);
});

test('creating a setup group rejects van drivers who are not users', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('setup-groups.store'), [
            'name' => 'Group A',
            'color' => 'blue',
            'van_drivers' => [999999],
        ])
        ->assertSessionHasErrors('van_drivers.0');

    expect(SetupGroup::count())->toBe(0);
});

test('updating a setup group replaces its attributes and van drivers', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $oldDriver = make_user();
    $newDriver = make_user();
    $setupGroup->van_drivers()->attach($oldDriver->id, ['sort' => 0]);

    $response = $this->actingAs(make_user(UserRole::Admin))->patch(route('setup-groups.update', $setupGroup), [
        'name' => 'Group Z',
        'week' => 3,
        'color' => 'green',
        'van_drivers' => [$newDriver->id],
    ]);

    $response->assertRedirect(route('setup-groups.show', $setupGroup));

    $setupGroup->refresh();
    expect($setupGroup->name)->toBe('Group Z');
    expect($setupGroup->week)->toBe(3);
    expect($setupGroup->color)->toBe('green');
    expect($setupGroup->van_drivers->pluck('id')->all())->toBe([$newDriver->id]);
});

test('updating a setup group without van drivers removes them all', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $driver = make_user();
    $setupGroup->van_drivers()->attach($driver->id, ['sort' => 0]);

    $this->actingAs(make_user(UserRole::Admin))->patch(route('setup-groups.update', $setupGroup), [
        'name' => 'Group A',
        'week' => 1,
        'color' => 'blue',
    ]);

    expect($setupGroup->fresh()->van_drivers)->toHaveCount(0);
});

test('an invalid update leaves the setup group untouched', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $driver = make_user();
    $setupGroup->van_drivers()->attach($driver->id, ['sort' => 0]);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('setup-groups.update', $setupGroup), [
            'name' => 'Group Z',
            'week' => 99,
            'color' => 'green',
            'van_drivers' => [999999],
        ])
        ->assertSessionHasErrors(['week', 'van_drivers.0']);

    $setupGroup->refresh();
    expect($setupGroup->name)->toBe('Group A');
    expect($setupGroup->van_drivers->pluck('id')->all())->toBe([$driver->id]);
});

test('a setup group can be soft deleted and restored through the endpoints', function () {
    // Regression test: SetupGroup used to declare `created_at`/`updated_at`
    // properties that shadowed the Eloquent attributes, making the destroy
    // endpoint error.
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->delete(route('setup-groups.destroy', $setupGroup))->assertRedirect();
    expect(SetupGroup::find($setupGroup->id))->toBeNull();
    expect(SetupGroup::withTrashed()->find($setupGroup->id))->not->toBeNull();

    $this->actingAs($admin)->patch(route('setup-groups.restore', $setupGroup->id))->assertRedirect();
    expect(SetupGroup::find($setupGroup->id))->not->toBeNull();
});

test('guests cannot create setup groups', function () {
    $this->post(route('setup-groups.store'), [
        'name' => 'Group A',
        'color' => 'blue',
    ])->assertRedirect('/login');

    expect(SetupGroup::count())->toBe(0);
});

test('the setup group show and create pages render', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $user = make_user();

    $this->actingAs($user)->get(route('setup-groups.show', $setupGroup))->assertOk();
    $this->actingAs($user)->get(route('setup-groups.create'))->assertOk();
    $this->actingAs($user)->get(route('setup-groups.edit', $setupGroup))->assertOk();
});
