<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\UserEnsemble;

test('an admin can create an ensemble and the slug is generated from the name', function () {
    $admin = make_user(UserRole::Admin);

    $response = $this->actingAs($admin)->post(route('ensembles.store'), ['name' => 'Wind Band']);

    $ensemble = Ensemble::where('name', 'Wind Band')->first();
    expect($ensemble)->not->toBeNull();
    expect($ensemble->slug)->toBe('wind_band');
    $response->assertRedirect(route('ensembles.show', $ensemble));
});

test('creating an ensemble requires a name', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.store'), [])
        ->assertSessionHasErrors('name');

    expect(Ensemble::count())->toBe(0);
});

test('creating an ensemble rejects an overlong name', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.store'), ['name' => str_repeat('a', 256)])
        ->assertSessionHasErrors('name');

    expect(Ensemble::count())->toBe(0);
});

test('non-admins cannot create ensembles', function () {
    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('ensembles.store'), ['name' => 'Wind Band'])
        ->assertForbidden();
});

test('the ensemble create form renders', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.create'))
        ->assertOk();
});

test('an admin can soft delete an ensemble', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->delete(route('ensembles.destroy', $ensemble))
        ->assertRedirect();

    expect(Ensemble::find($ensemble->id))->toBeNull();
    expect(Ensemble::withTrashed()->find($ensemble->id))->not->toBeNull();
});

test('non-admins cannot delete ensembles', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Moderator))
        ->delete(route('ensembles.destroy', $ensemble))
        ->assertForbidden();

    expect(Ensemble::find($ensemble->id))->not->toBeNull();
});

test('a soft-deleted ensemble can be restored', function () {
    $ensemble = Ensemble::factory()->create();
    $ensemble->delete();

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('ensembles.restore', $ensemble->id))
        ->assertRedirect();

    expect(Ensemble::find($ensemble->id))->not->toBeNull();
});

test('the ensembles index can include soft-deleted records with with_trashed', function () {
    $kept = Ensemble::factory()->create();
    $deleted = Ensemble::factory()->create();
    $deleted->delete();

    $admin = make_user(UserRole::Admin);

    $default = $this->actingAs($admin)->get(route('ensembles.index'));
    expect($default->viewData('entities')->pluck('id')->all())->toBe([$kept->id]);

    $withTrashed = $this->actingAs($admin)->get(route('ensembles.index', ['with_trashed' => 1]));
    expect($withTrashed->viewData('entities')->pluck('id')->sort()->values()->all())
        ->toBe(collect([$kept->id, $deleted->id])->sort()->values()->all());
});

test('a user can be added to an ensemble with an instrument family and seat', function () {
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => true]);
    $member = make_user(UserRole::Member);
    $instrumentFamily = make_instrument_family('Percussion');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.add_user', $ensemble), [
            'user_id' => $member->id,
            'instrument_family_id' => $instrumentFamily->id,
            'seat_row' => '2',
            'seat_column' => 'C',
        ])
        ->assertRedirect();

    $pivot = UserEnsemble::where('user_id', $member->id)->where('ensemble_id', $ensemble->id)->first();
    expect($pivot)->not->toBeNull();
    expect($pivot->instrument_family_id)->toBe($instrumentFamily->id);
    expect($pivot->seat)->toBe('C2');
});

test('adding a user to a seating-plan-disabled ensemble ignores the seat', function () {
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => false]);
    $member = make_user(UserRole::Member);
    $instrumentFamily = make_instrument_family('Percussion');

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.add_user', $ensemble), [
            'user_id' => $member->id,
            'instrument_family_id' => $instrumentFamily->id,
            'seat_row' => '2',
            'seat_column' => 'C',
        ])
        ->assertRedirect();

    $pivot = UserEnsemble::where('user_id', $member->id)->where('ensemble_id', $ensemble->id)->first();
    expect($pivot->instrument_family_id)->toBe($instrumentFamily->id);
    expect($pivot->seat_row)->toBeNull();
    expect($pivot->seat_column)->toBeNull();
});

test('the ensemble edit page renders the seating plan toggle', function () {
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => true]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.edit', $ensemble))
        ->assertOk()
        ->assertSee('Seating plan enabled')
        ->assertSee('name="seating_plan_enabled"', false)
        ->assertSee('form="ensemble-edit-form"', false);
});

test('saving the ensemble edit page persists the seating plan toggle', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'seating_plan_enabled' => true]);

    // Unchecked checkbox submits no value.
    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), ['name' => 'Strings'])
        ->assertRedirect(route('ensembles.show', $ensemble));
    expect($ensemble->fresh()->seating_plan_enabled)->toBeFalse();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), ['name' => 'Renamed Strings', 'seating_plan_enabled' => '1'])
        ->assertRedirect();

    $ensemble->refresh();
    expect($ensemble->seating_plan_enabled)->toBeTrue();
    expect($ensemble->name)->toBe('Renamed Strings');
});

test('updating an ensemble requires a name', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [])
        ->assertSessionHasErrors('name');
});

test('updating an ensemble requires the update ability', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Member))
        ->put(route('ensembles.update', $ensemble), ['name' => 'Nope'])
        ->assertForbidden();
});

test('adding a user to an ensemble validates the user and instrument family', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.add_user', $ensemble), [
            'user_id' => 999999,
            'instrument_family_id' => 999999,
        ])
        ->assertSessionHasErrors(['user_id', 'instrument_family_id']);

    expect(UserEnsemble::count())->toBe(0);
});

test('a user can be removed from an ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.remove_user', [$ensemble, $member]))
        ->assertRedirect();

    expect(UserEnsemble::where('user_id', $member->id)->where('ensemble_id', $ensemble->id)->exists())
        ->toBeFalse();
});

test('restoring an ensemble that does not exist returns not found', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('ensembles.restore', 999999))
        ->assertNotFound();
});

test('guests cannot add users to an ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);

    $this->post(route('ensembles.add_user', $ensemble), [
        'user_id' => $member->id,
        'instrument_family_id' => make_instrument_family()->id,
    ])->assertRedirect('/login');

    expect(UserEnsemble::count())->toBe(0);
});

test('removing a user who is not in the ensemble returns not found', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('ensembles.remove_user', [$ensemble, $member]))
        ->assertNotFound();
});

test('the ensemble show page renders with management buttons for moderators', function () {
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => true]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee('Seating plan')
        ->assertSee('Edit');
});

test('the ensemble show page hides the seating plan button when seating is disabled', function () {
    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => false]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertDontSee('Seating plan')
        ->assertSee('Edit');
});

test('the ensemble show page hides management buttons from ordinary members', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee("You're a member!", false)
        ->assertDontSee('Seating plan');
});

test('the ensemble show page hides polls until the ensemble has members', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee('Add members before polls become available');
});

test('the ensemble show page shows polls once the ensemble has members', function () {
    $ensemble = Ensemble::factory()->create();
    join_ensemble(make_user(UserRole::Member), $ensemble);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertDontSee('Add members before polls become available');
});

test('the ensemble members table lists members with their instrument and seat', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $instrumentFamily = make_instrument_family('Strings');
    join_ensemble($member, $ensemble, $instrumentFamily, '2', 'C');

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('ensembles.members', $ensemble))
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('Strings')
        ->assertSee('C2');
});

test('a member of an ensemble can view its members table', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)
        ->get(route('ensembles.members', $ensemble))
        ->assertOk();
});

test('a non-member without a management role cannot view an ensemble members table', function () {
    $ensemble = Ensemble::factory()->create();
    $outsider = make_user(UserRole::Member);

    $this->actingAs($outsider)
        ->get(route('ensembles.members', $ensemble))
        ->assertForbidden();
});

test('the ensemble members table hides removal controls from ordinary members', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)
        ->get(route('ensembles.members', $ensemble))
        ->assertOk()
        ->assertDontSee('Add user');
});
