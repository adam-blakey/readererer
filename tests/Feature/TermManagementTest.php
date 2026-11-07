<?php

use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Mail\AttendanceListMail;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\Location;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;

test('a term can be created with term dates', function () {
    $ensemble = Ensemble::factory()->create();

    $response = $this->actingAs(make_user(UserRole::Admin))->post(route('terms.store'), [
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
                'ensemble_ids' => [$ensemble->id],
            ],
        ],
    ]);

    $term = Term::where('slug', 'spring-2026')->first();
    expect($term)->not->toBeNull();
    $response->assertRedirect(route('terms.show', $term));

    expect($term->term_dates)->toHaveCount(2);
    expect($term->number_of_rehearsals)->toBe(1);
    expect($term->number_of_concerts)->toBe(1);
    expect($term->term_dates->last()->concert_ensembles->modelKeys())->toBe([$ensemble->id]);
});

test('a term date can have its time to be confirmed and a location', function () {
    $location = Location::factory()->create(['name' => 'Town Hall']);

    $default = Location::factory()->create(['name' => 'Church Hall']);

    $this->actingAs(make_user(UserRole::Admin))->post(route('terms.store'), [
        'name' => 'Spring 2026',
        'slug' => 'spring-2026',
        'location_id' => $default->id,
        'term_dates' => [
            ['start_datetime' => '2026-01-05 19:00:00', 'end_datetime' => '2026-01-05 21:00:00', 'time_tbc' => '1', 'location_id' => $location->id],
            ['start_datetime' => '2026-01-12 19:00:00', 'end_datetime' => '2026-01-12 21:00:00'],
        ],
    ]);

    [$tbc, $confirmed] = Term::firstWhere('slug', 'spring-2026')->term_dates->sortBy('start_datetime')->values();
    expect($tbc->time_tbc)->toBeTrue()
        ->and($tbc->location->is($location))->toBeTrue()
        ->and($confirmed->time_tbc)->toBeFalse()
        ->and($confirmed->location_id)->toBeNull()
        ->and($tbc->term->location->is($default))->toBeTrue();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('terms.show', $tbc->term))
        ->assertSeeInOrder(['Church Hall', 'Mon, 5 Jan 2026', 'TBC', 'Town Hall', 'Mon, 12 Jan 2026', '19:00–21:00', 'Church Hall']);
});

test('creating a term requires a name and a unique slug', function () {
    Term::factory()->create(['slug' => 'spring-2026']);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('terms.store'), ['name' => 'Spring 2026', 'slug' => 'spring-2026'])
        ->assertSessionHasErrors('slug');

    $this->actingAs(make_user(UserRole::Admin))
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

    $response = $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), [
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

test('updating a term syncs each date\'s concert ensembles, and none makes it a rehearsal', function () {
    [$wind, $brass, $strings] = Ensemble::factory()->count(3)->create()->all();
    $term = Term::factory()->create();
    $concert = make_term_date($term, $strings);
    $becomesRehearsal = make_term_date($term, $wind);

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), [
        'name' => $term->name,
        'slug' => $term->slug,
        'term_dates' => [
            [
                'id' => $concert->id,
                'start_datetime' => '2026-03-20 19:30:00',
                'end_datetime' => '2026-03-20 22:00:00',
                'ensemble_ids' => [$wind->id, $brass->id],
            ],
            [
                'id' => $becomesRehearsal->id,
                'start_datetime' => '2026-03-27 19:30:00',
                'end_datetime' => '2026-03-27 22:00:00',
            ],
        ],
    ])->assertRedirect(route('terms.show', $term));

    expect($concert->concert_ensembles()->pluck('ensembles.id')->sort()->values()->all())->toBe(collect([$wind->id, $brass->id])->sort()->values()->all());
    expect($becomesRehearsal->fresh()->is_concert)->toBeFalse();
});

test('the same ensemble can play concerts on several dates in a term', function () {
    $wind = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $first = make_term_date($term);
    $second = make_term_date($term);

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), [
        'name' => $term->name,
        'slug' => $term->slug,
        'term_dates' => [
            ['id' => $first->id, 'start_datetime' => '2026-03-20 19:30:00', 'end_datetime' => '2026-03-20 22:00:00', 'ensemble_ids' => [$wind->id]],
            ['id' => $second->id, 'start_datetime' => '2026-03-27 19:30:00', 'end_datetime' => '2026-03-27 22:00:00', 'ensemble_ids' => [$wind->id]],
        ],
    ])->assertSessionHasNoErrors();

    expect($first->concert_ensembles()->pluck('ensembles.id')->all())->toBe([$wind->id])
        ->and($second->concert_ensembles()->pluck('ensembles.id')->all())->toBe([$wind->id]);
});

test('updating a term with no term dates removes them all', function () {
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-01-05 19:00:00',
        'end_datetime' => '2026-01-05 21:00:00',
    ]);

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), [
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
    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('terms.update', $term), [
            'name' => 'Renamed',
            'slug' => $term->slug,
            'term_dates' => [
                ['start_datetime' => '2026-01-06 19:00:00'],
            ],
        ])
        ->assertSessionHasErrors('term_dates.0.end_datetime');

    // Concert ensemble that does not exist.
    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('terms.update', $term), [
            'name' => 'Renamed',
            'slug' => $term->slug,
            'term_dates' => [
                [
                    'start_datetime' => '2026-01-06 19:00:00',
                    'end_datetime' => '2026-01-06 21:00:00',
                    'ensemble_ids' => [999999],
                ],
            ],
        ])
        ->assertSessionHasErrors('term_dates.0.ensemble_ids.0');

    $term->refresh();
    expect($term->name)->toBe('Original');
    expect($term->term_dates->pluck('id')->all())->toBe([$existing->id]);
});

test('updating a term requires the admin role', function (UserRole $role) {
    $term = Term::factory()->create();

    $this->actingAs(make_user($role))
        ->patch(route('terms.update', $term), ['name' => 'New Name', 'slug' => $term->slug])
        ->assertForbidden();
})->with([UserRole::Member, UserRole::Moderator]);

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

    $response = $this->actingAs(make_user(UserRole::Admin))->get(route('terms.index'));

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

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('terms.show', $term))
        ->assertOk();
});

test('the term show page lists its dates in a table', function () {
    $ensemble = Ensemble::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
    $term = Term::factory()->create();

    tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-05-01 19:00:00',
        'end_datetime' => '2026-05-01 21:00:00',
        'setup_group_id' => $setupGroup->id,
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($ensemble));
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => '2026-05-08 19:00:00',
        'end_datetime' => '2026-05-08 21:00:00',
    ]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('terms.show', $term))
        ->assertOk()
        ->assertSee('card-table', false)
        ->assertSee('<th>Date</th>', false)
        ->assertSee('<th>Time</th>', false)
        ->assertSee('Setup group')
        ->assertSee('Van driver')
        ->assertSee('Concert')
        ->assertSee('Rehearsal')
        ->assertSee('19:00–21:00')
        ->assertDontSee('Send attendance list now');
});

test('the upcoming rehearsals page collapses all but the latest email into an accordion', function () {
    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-05-01 19:00:00',
        'end_datetime' => '2026-05-01 21:00:00',
    ]);

    // An older log and a newer one; email_logs() returns latest first.
    $older = EmailLog::create([
        'term_date_id' => $termDate->id,
        'mailable_class' => AttendanceListMail::class,
        'subject' => 'Older attendance list',
        'status' => EmailStatus::Sent,
    ]);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    EmailLog::create([
        'term_date_id' => $termDate->id,
        'mailable_class' => AttendanceListMail::class,
        'subject' => 'Newer attendance list',
        'status' => EmailStatus::Sent,
    ]);

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('attendance.register.index'))
        ->assertOk()
        ->assertSee('Newer attendance list')
        ->assertSee('Older attendance list')
        ->assertSee('Show 1 earlier')
        ->assertSee('email-history-'.$termDate->id, false);
});

test('creating a term persists the setup group and van driver on its dates', function () {
    $setupGroup = SetupGroup::factory()->create();
    $driver = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))->post(route('terms.store'), [
        'name' => 'Autumn 2026',
        'slug' => 'autumn-2026',
        'term_dates' => [
            [
                'start_datetime' => '2026-09-05 19:00:00',
                'end_datetime' => '2026-09-05 21:00:00',
                'setup_group_id' => $setupGroup->id,
                'van_driver_id' => $driver->id,
            ],
        ],
    ])->assertRedirect();

    $termDate = Term::where('slug', 'autumn-2026')->first()->term_dates->first();
    expect($termDate->setup_group_id)->toBe($setupGroup->id);
    expect($termDate->van_driver_id)->toBe($driver->id);
});

test('updating a term persists the setup group and van driver on its dates', function () {
    $term = Term::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
    $driver = make_user(UserRole::Member);

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), [
        'name' => $term->name,
        'slug' => $term->slug,
        'term_dates' => [
            [
                'start_datetime' => '2026-09-05 19:00:00',
                'end_datetime' => '2026-09-05 21:00:00',
                'setup_group_id' => $setupGroup->id,
                'van_driver_id' => $driver->id,
            ],
        ],
    ])->assertRedirect();

    $termDate = $term->fresh()->term_dates->first();
    expect($termDate->setup_group_id)->toBe($setupGroup->id);
    expect($termDate->van_driver_id)->toBe($driver->id);
});

test('creating a term rejects an unknown setup group or van driver', function () {
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('terms.store'), [
            'name' => 'Bad Term',
            'slug' => 'bad-term',
            'term_dates' => [
                [
                    'start_datetime' => '2026-09-05 19:00:00',
                    'end_datetime' => '2026-09-05 21:00:00',
                    'setup_group_id' => 999999,
                    'van_driver_id' => 999999,
                ],
            ],
        ])
        ->assertSessionHasErrors(['term_dates.0.setup_group_id', 'term_dates.0.van_driver_id']);

    expect(Term::where('slug', 'bad-term')->exists())->toBeFalse();
});

test('the term edit form renders the setup group, van driver and row template', function () {
    $term = Term::factory()->create();
    SetupGroup::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->get(route('terms.edit', $term))
        ->assertOk()
        ->assertSee('Setup group')
        ->assertSee('Van driver override')
        ->assertSee('term-date-row-template', false);
});

test('a term cannot be renamed to another term\'s slug but may keep its own', function () {
    Term::factory()->create(['slug' => 'autumn-2026']);
    $term = Term::factory()->create(['slug' => 'spring-2027']);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->patch(route('terms.update', $term), ['name' => 'Spring', 'slug' => 'autumn-2026'])
        ->assertSessionHasErrors('slug');
    expect($term->fresh()->slug)->toBe('spring-2027');

    $this->actingAs($admin)
        ->patch(route('terms.update', $term), ['name' => 'Spring', 'slug' => 'spring-2027'])
        ->assertSessionHasNoErrors();
});
