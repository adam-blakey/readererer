<?php

use App\Enums\UserRole;
use App\Features\EnsembleViewInstrument;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use Laravel\Pennant\Feature;

beforeEach(function () {
    config(['app.readererer_feature_flags' => [EnsembleViewInstrument::class => ['enabled' => true]]]);
    Feature::flushCache();
});

test('a member sees their instrument and seat on the ensemble page', function () {
    $member = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($member, $ensemble, InstrumentFamily::factory()->create(['name' => 'Oboes']), 'B', 3);

    $this->actingAs($member)->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee('Oboes • Row B, seat 3');
});

test('the seat is left out when the member has none', function () {
    $member = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($member, $ensemble, InstrumentFamily::factory()->create(['name' => 'Oboes']));

    $this->actingAs($member)->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertSee('Oboes')
        ->assertDontSee('Oboes •')
        ->assertDontSee('Row ');
});

test('a non-member sees no instrument or seat for the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $other = Ensemble::factory()->create();
    $moderator = make_user(UserRole::Moderator);
    join_ensemble($moderator, $other, InstrumentFamily::factory()->create(['name' => 'Oboes']), 'B', 3);

    $this->actingAs($moderator)->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertViewHas('membership', null)
        ->assertDontSee('Row B, seat 3');
});

test('nothing is shown when the flag is off', function () {
    config(['app.readererer_feature_flags' => [EnsembleViewInstrument::class => ['enabled' => false]]]);
    Feature::flushCache();
    $member = make_user(UserRole::Member);
    $ensemble = Ensemble::factory()->create();
    join_ensemble($member, $ensemble, InstrumentFamily::factory()->create(['name' => 'Oboes']), 'B', 3);

    $this->actingAs($member)->get(route('ensembles.show', $ensemble))
        ->assertOk()
        ->assertViewHas('membership', null)
        ->assertDontSee('Row B, seat 3');
});
