<?php

use App\Enums\UserRole;
use App\Models\InstrumentFamily;

test('the instrument family index requires the moderator role', function () {
    $this->get(route('instrumentfamilys.index'))->assertRedirect('/login');
    $this->actingAs(make_user(UserRole::Member))->get(route('instrumentfamilys.index'))->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get(route('instrumentfamilys.index'))->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get(route('instrumentfamilys.index'))->assertOk();
});

test('an instrument family can be created with a colour', function () {
    $this->actingAs(make_user(UserRole::Moderator))->get(route('instrumentfamilys.create'))->assertOk();

    $response = $this->actingAs(make_user(UserRole::Moderator))->post(route('instrumentfamilys.store'), [
        'name' => 'Bassoons',
        'color' => 'teal',
    ]);

    $instrumentFamily = InstrumentFamily::where('name', 'Bassoons')->first();
    expect($instrumentFamily)->not->toBeNull();
    expect($instrumentFamily->color)->toBe('teal');
    $response->assertRedirect(route('instrumentfamilys.show', $instrumentFamily));
});

test('creating an instrument family requires a name and colour', function () {
    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('instrumentfamilys.store'), [])
        ->assertSessionHasErrors(['name', 'color']);

    expect(InstrumentFamily::count())->toBe(0);
});

test('creating an instrument family rejects colours outside the allowed palette', function () {
    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('instrumentfamilys.store'), [
            'name' => 'Bassoons',
            'color' => '#123456',
        ])
        ->assertSessionHasErrors(['color']);

    expect(InstrumentFamily::count())->toBe(0);
});

test('members cannot create instrument families', function () {
    $this->actingAs(make_user(UserRole::Member))
        ->post(route('instrumentfamilys.store'), ['name' => 'Bassoons', 'color' => 'teal'])
        ->assertForbidden();

    expect(InstrumentFamily::count())->toBe(0);
});

test('an instrument family can be viewed and updated', function () {
    $instrumentFamily = InstrumentFamily::create(['name' => 'Bassoons', 'color' => 'teal']);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.show', $instrumentFamily))
        ->assertOk()
        ->assertSee('Bassoons')
        ->assertSee('teal');

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.edit', $instrumentFamily))
        ->assertOk();

    $this->actingAs(make_user(UserRole::Moderator))
        ->patch(route('instrumentfamilys.update', $instrumentFamily), ['name' => 'Contrabassoons', 'color' => 'pink'])
        ->assertRedirect(route('instrumentfamilys.show', $instrumentFamily));

    expect($instrumentFamily->fresh()->name)->toBe('Contrabassoons');
    expect($instrumentFamily->fresh()->color)->toBe('pink');
});

test('deleting an instrument family soft deletes it and admins can restore it', function () {
    $instrumentFamily = InstrumentFamily::create(['name' => 'Bassoons']);

    // Deleting is admin only.
    $this->actingAs(make_user(UserRole::Moderator))
        ->delete(route('instrumentfamilys.destroy', $instrumentFamily))
        ->assertForbidden();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('instrumentfamilys.destroy', $instrumentFamily))
        ->assertRedirect();

    expect(InstrumentFamily::find($instrumentFamily->id))->toBeNull();
    expect(InstrumentFamily::withTrashed()->find($instrumentFamily->id))->not->toBeNull();

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('instrumentfamilys.restore', $instrumentFamily->id))
        ->assertRedirect();

    expect($instrumentFamily->fresh()->deleted_at)->toBeNull();
});

test('the instrument family index can include archived records', function () {
    $active = InstrumentFamily::create(['name' => 'Bassoons']);
    $trashed = InstrumentFamily::create(['name' => 'Ophicleides']);
    $trashed->delete();

    $withoutTrashed = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.index'));
    $withoutTrashed->assertOk()->assertSee('Bassoons')->assertDontSee('Ophicleides');

    $withTrashed = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('instrumentfamilys.index', ['with_trashed' => 1]));
    $withTrashed->assertOk()->assertSee('Bassoons')->assertSee('Ophicleides');
});
