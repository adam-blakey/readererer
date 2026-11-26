<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;

function single_concert_term_dates(?Ensemble $concertEnsemble = null): array
{
    return [
        ['start_datetime' => '2026-01-05 19:00:00', 'end_datetime' => '2026-01-05 21:00:00'],
        ['start_datetime' => '2026-03-20 19:30:00', 'end_datetime' => '2026-03-20 22:00:00', 'ensemble_ids' => $concertEnsemble ? [$concertEnsemble->id] : []],
    ];
}

test('in single-ensemble mode the term form asks only whether a date is a concert', function (bool $single) {
    config(['app.readererer_single_ensemble' => $single]);
    $ensemble = Ensemble::factory()->create(['name' => 'Wind Band']);
    $termDate = make_term_date(null, $ensemble);

    $response = $this->actingAs(make_user(UserRole::Admin))->get(route('terms.edit', $termDate->term));

    $checkbox = 'class="form-check-input" name="term_dates[0][ensemble_ids][]" type="checkbox" value="'.$ensemble->id.'" data-initial="1"';
    $single
        ? $response->assertSee($checkbox, false)->assertDontSee('Concert ensembles')
        : $response->assertDontSee($checkbox, false)->assertSee('Concert ensembles');
})->with(['on' => true, 'off' => false]);

test('in single-ensemble mode ticking concert plays the ensemble and unticking makes a rehearsal', function () {
    config(['app.readererer_single_ensemble' => true]);
    $ensemble = Ensemble::factory()->create();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->post(route('terms.store'), ['name' => 'Spring', 'slug' => 'spring', 'term_dates' => single_concert_term_dates($ensemble)])
        ->assertSessionHasNoErrors();
    $term = Term::firstWhere('slug', 'spring');
    expect($term->number_of_concerts)->toBe(1);

    $rows = $term->term_dates->sortBy('start_datetime')->values()->map(fn ($date, $i) => ['id' => $date->id] + single_concert_term_dates()[$i])->all();
    $this->actingAs($admin)->patch(route('terms.update', $term), ['name' => 'Spring', 'slug' => 'spring', 'term_dates' => $rows])
        ->assertSessionHasNoErrors();
    expect($term->fresh()->number_of_concerts)->toBe(0);
});

test('in single-ensemble mode a concert cannot name an archived ensemble', function () {
    config(['app.readererer_single_ensemble' => true]);
    $archived = Ensemble::factory()->create();
    $archived->delete();
    Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('terms.store'), ['name' => 'Spring', 'slug' => 'spring', 'term_dates' => single_concert_term_dates($archived)])
        ->assertSessionHasErrors('term_dates.1.ensemble_ids.0');
});

test('in single-ensemble mode tables label a concert without naming the ensemble', function (bool $single, string $label) {
    config(['app.readererer_single_ensemble' => $single]);
    $termDate = make_term_date(null, Ensemble::factory()->create(['name' => 'Wind Band']));
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('terms.show', $termDate->term))->assertSee($label);
    $this->actingAs($admin)->get(route('attendance.register.index'))->assertSee($label);
    expect($termDate->concert_label)->toBe($label);
})->with([
    'on' => [true, 'Concert'],
    'off' => [false, 'Concert (Wind Band)'],
]);
