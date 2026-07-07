<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

test('a term can be created with term dates', function () {
    $ensemble = Ensemble::factory()->create();

    $response = $this->actingAs(make_user(UserRole::Moderator))->post(route('terms.store'), [
        'name' => 'Spring 2026',
        'slug' => 'spring-2026',
        'term_dates' => [
            [
                'start_datetime' => '2026-01-05 19:00:00',
                'end_datetime' => '2026-01-05 21:00:00',
            ],
            [
                'start_datetime' => '2026-03-20 19:30:00',
                'end_datetime' => '2026-03-20 22:00:00',
                'ensemble_id' => $ensemble->id,
            ],
        ],
    ]);

    $term = Term::where('slug', 'spring-2026')->first();
    expect($term)->not->toBeNull();
    $response->assertRedirect(route('terms.show', $term));

    expect($term->term_dates)->toHaveCount(2);
    expect($term->number_of_rehearsals)->toBe(1);
    expect($term->number_of_concerts)->toBe(1);
    expect($term->term_dates->last()->concert_ensemble_id)->toBe($ensemble->id);
});

test('creating a term requires a name and a unique slug', function () {
    Term::factory()->create(['slug' => 'spring-2026']);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('terms.store'), ['name' => 'Spring 2026', 'slug' => 'spring-2026'])
        ->assertSessionHasErrors('slug');

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('terms.store'), [])
        ->assertSessionHasErrors(['name', 'slug']);

    expect(Term::count())->toBe(1);
});

test('updating a term syncs its term dates', function () {
    $term = Term::factory()->create();
    $kept = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);
    $removed = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-12 19:00:00',
        'end_datetime' => '2026-01-12 21:00:00',
    ]);

    $response = $this->actingAs(make_user(UserRole::Moderator))->patch(route('terms.update', $term), [
        'name' => 'Renamed Term',
        'slug' => $term->slug,
        'term_dates' => [
            [
                'id' => $kept->id,
                'start_datetime' => '2026-01-06 19:00:00',
                'end_datetime' => '2026-01-06 21:00:00',
            ],
            [
                'start_datetime' => '2026-02-02 19:00:00',
                'end_datetime' => '2026-02-02 21:00:00',
            ],
        ],
    ]);

    $response->assertRedirect(route('terms.show', $term));

    $term->refresh();
    expect($term->name)->toBe('Renamed Term');
    expect($term->term_dates->pluck('id'))->not->toContain($removed->id);
    expect($kept->fresh()->start_datetime->format('Y-m-d'))->toBe('2026-01-06');
    expect($term->term_dates)->toHaveCount(2);
});

test('updating a term with no term dates removes them all', function () {
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);

    $this->actingAs(make_user(UserRole::Moderator))->patch(route('terms.update', $term), [
        'name' => $term->name,
        'slug' => $term->slug,
    ]);

    expect($term->fresh()->term_dates)->toHaveCount(0);
});

test('updating a term rejects term dates with missing or invalid fields', function () {
    $term = Term::factory()->create(['name' => 'Original']);
    $existing = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);

    // Missing end_datetime.
    $this->actingAs(make_user(UserRole::Moderator))
        ->patch(route('terms.update', $term), [
            'name' => 'Renamed',
            'slug' => $term->slug,
            'term_dates' => [
                ['start_datetime' => '2026-01-06 19:00:00'],
            ],
        ])
        ->assertSessionHasErrors('term_dates.0.end_datetime');

    // Concert ensemble that does not exist.
    $this->actingAs(make_user(UserRole::Moderator))
        ->patch(route('terms.update', $term), [
            'name' => 'Renamed',
            'slug' => $term->slug,
            'term_dates' => [
                [
                    'start_datetime' => '2026-01-06 19:00:00',
                    'end_datetime' => '2026-01-06 21:00:00',
                    'ensemble_id' => 999999,
                ],
            ],
        ])
        ->assertSessionHasErrors('term_dates.0.ensemble_id');

    // Nothing changed on either failed attempt.
    $term->refresh();
    expect($term->name)->toBe('Original');
    expect($term->term_dates->pluck('id')->all())->toBe([$existing->id]);
});

test('updating a term requires the moderator role', function () {
    $term = Term::factory()->create();

    $this->actingAs(make_user(UserRole::Member))
        ->patch(route('terms.update', $term), ['name' => 'New Name', 'slug' => $term->slug])
        ->assertForbidden();
});

test('a term can be soft deleted and restored', function () {
    $term = Term::factory()->create();
    $user = make_user(UserRole::Admin);

    $this->actingAs($user)->delete(route('terms.destroy', $term))->assertRedirect();
    expect(Term::find($term->id))->toBeNull();

    $this->actingAs($user)->patch(route('terms.restore', $term->id))->assertRedirect();
    expect(Term::find($term->id))->not->toBeNull();
});

test('the terms index shows the friendly column labels', function () {
    Term::factory()->create();

    $response = $this->actingAs(make_user(UserRole::Member))->get(route('terms.index'));

    $response->assertOk();
    $response->assertSee('Rehearsals');
    $response->assertSee('Concerts');
    $response->assertSee('First date');
    $response->assertSee('Last date');
});

test('the term show page renders with its dates', function () {
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);

    $this->actingAs(make_user(UserRole::Member))
        ->get(route('terms.show', $term))
        ->assertOk();
});
