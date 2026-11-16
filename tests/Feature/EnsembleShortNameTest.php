<?php

use App\Enums\UserRole;
use App\Models\Ensemble;

function update_ensemble(Ensemble $ensemble, array $attributes)
{
    return test()->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), $attributes + ['name' => $ensemble->name, 'slug' => $ensemble->slug]);
}

test('an admin can create an ensemble with a short name', function () {
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('ensembles.create'))->assertSee('name="short_name"', false);
    $this->actingAs($admin)->post(route('ensembles.store'), ['name' => 'Symphonic Wind Band', 'short_name' => 'SWB'])->assertRedirect();

    expect(Ensemble::firstWhere('name', 'Symphonic Wind Band')->short_name)->toBe('SWB');
});

test('an admin can set and clear a short name', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('ensembles.edit', $ensemble))->assertSee('name="short_name"', false);

    update_ensemble($ensemble, ['short_name' => 'SWB'])->assertSessionHasNoErrors();
    expect($ensemble->fresh()->short_name)->toBe('SWB');

    update_ensemble($ensemble, ['short_name' => ''])->assertSessionHasNoErrors();
    expect($ensemble->fresh()->short_name)->toBeNull();
});

test('a short name cannot match another ensemble\'s short name or name, even an archived one\'s', function () {
    $archived = Ensemble::factory()->create(['short_name' => 'OLD']);
    $archived->delete();
    Ensemble::factory()->create(['name' => 'Brass']);
    $ensemble = Ensemble::factory()->create(['short_name' => 'SWB']);

    update_ensemble($ensemble, ['short_name' => 'OLD'])->assertSessionHasErrors('short_name');
    update_ensemble($ensemble, ['short_name' => 'Brass'])->assertSessionHasErrors('short_name');
    update_ensemble($ensemble, ['short_name' => 'SWB'])->assertSessionHasNoErrors();
    update_ensemble($ensemble, ['short_name' => str_repeat('a', 21)])->assertSessionHasErrors('short_name');
});

test('a name cannot match another ensemble\'s short name', function () {
    Ensemble::factory()->create(['short_name' => 'SWB']);
    $ensemble = Ensemble::factory()->create();

    update_ensemble($ensemble, ['name' => 'SWB'])->assertSessionHasErrors('name');
    $this->actingAs(make_user(UserRole::Admin))->post(route('ensembles.store'), ['name' => 'SWB'])->assertSessionHasErrors('name');
});

test('tables use short names, falling back to the full name', function () {
    $termDate = make_term_date(null,
        Ensemble::factory()->create(['name' => 'Symphonic Wind Band', 'short_name' => 'SWB']),
        Ensemble::factory()->create(['name' => 'Brass']),
    );

    $this->actingAs(make_user(UserRole::Admin))->get(route('terms.show', $termDate->term))->assertSee('Concert (Brass, SWB)');
});
