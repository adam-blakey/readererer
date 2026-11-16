<?php

use App\Enums\AttendanceStatus;
use App\Enums\Color;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\InstrumentFamily;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;

test('the seating plan page requires permission to update the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $url = route('ensembles.seating-plan.show', $ensemble);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $this->actingAs($member)->get($url)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the seating plan groups members by seat row', function () {
    $ensemble = Ensemble::factory()->create();
    $seated = make_user(UserRole::Member);
    $unseated = make_user(UserRole::Member);
    join_ensemble($seated, $ensemble, null, 'A', 2);
    join_ensemble($unseated, $ensemble);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $grouped = $response->viewData('grouped_users');
    expect($grouped->has('unassigned'))->toBeFalse();
    expect($grouped->get('A')->pluck('id')->all())->toBe([$seated->id]);
    expect($response->viewData('unassigned_users')->flatten(1)->pluck('id')->all())->toBe([$unseated->id]);
});

test('unassigned members are split up by instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $flautist = make_user(UserRole::Member);
    $trumpeter = make_user(UserRole::Member);
    $unknown = make_user(UserRole::Member);
    join_ensemble($flautist, $ensemble, make_instrument_family('Flutes'));
    join_ensemble($trumpeter, $ensemble, make_instrument_family('Trumpets'));
    join_ensemble_without_instrument($unknown, $ensemble);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $unassigned = $response->viewData('unassigned_users');
    expect($unassigned->get('Flutes')->pluck('id')->all())->toBe([$flautist->id]);
    expect($unassigned->get('Trumpets')->pluck('id')->all())->toBe([$trumpeter->id]);
    expect($unassigned->get('No instrument')->pluck('id')->all())->toBe([$unknown->id]);

    expect($unassigned->get('Flutes')->first()->instrument_color)->toBe(Color::Blue);
    expect($unassigned->get('No instrument')->first()->instrument_color)->toBeNull();
});

test('the seating plan page offers downloads for upcoming rehearsals and own concerts', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $pastRehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->subWeek(),
        'end_datetime' => now()->subWeek()->addHours(2),
    ]);
    $upcomingRehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $ownConcert = tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(2),
        'end_datetime' => now()->addWeeks(2)->addHours(2),
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($ensemble));
    $otherConcert = tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(3),
        'end_datetime' => now()->addWeeks(3)->addHours(2),
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($otherEnsemble));

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $dates = $response->viewData('upcoming_term_dates');
    expect($dates->pluck('id')->all())->toBe([$upcomingRehearsal->id, $ownConcert->id]);
    $response->assertSee(route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $upcomingRehearsal]));

    // Past dates are offered separately so the dropdown can filter them.
    $pastDates = $response->viewData('past_term_dates');
    expect($pastDates->pluck('id')->all())->toBe([$pastRehearsal->id]);
    $response->assertSee(route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $pastRehearsal]));
});

test('updating the seating plan stores seat rows and columns on the pivot', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.seating-plan.update', $ensemble), [
            'seating_plan' => json_encode([
                'A' => [['id' => $member->id, 'column' => 2]],
            ]),
        ])
        ->assertRedirect(route('ensembles.seating-plan.show', $ensemble));

    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toEqual('A');
    expect($pivot->seat_column)->toEqual(2);
});

test('the seating plan lists every row past Z, plus one empty row', function () {
    $ensemble = Ensemble::factory()->create();
    $back = make_user(UserRole::Member);
    $oddRow = make_user(UserRole::Member);
    join_ensemble($back, $ensemble, null, 'AB', 1);
    join_ensemble($oddRow, $ensemble, null, '3', 1);

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.show', $ensemble));

    $response->assertOk();
    $grouped = $response->viewData('grouped_users');
    expect($grouped->keys()->all())->toHaveCount(29)
        ->and($grouped->keys()->slice(-4)->values()->all())->toBe(['Z', 'AA', 'AB', 'AC'])
        ->and($grouped->get('AB')->pluck('id')->all())->toBe([$back->id])
        ->and($response->viewData('unassigned_users')->flatten(1)->pluck('id')->all())->toBe([$oddRow->id]);
});

test('a seating plan with a malformed row or seat is refused', function (mixed $plan) {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble, null, 'A', 1);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.seating-plan.update', $ensemble), ['seating_plan' => $plan])
        ->assertSessionHasErrors();

    expect($ensemble->users()->first()->pivot->seat_row)->toBe('A');
})->with([
    'missing' => null,
    'not json' => '{',
    'lower-case row' => fn () => json_encode(['b' => [['id' => 1, 'column' => 1]]]),
    'column not a number' => fn () => json_encode(['B' => [['id' => 1, 'column' => 'x']]]),
]);

test('updating the seating plan is forbidden without permission to update the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble, null, 'A', 1);

    $payload = [
        'seating_plan' => json_encode([
            'B' => [['id' => $member->id, 'column' => 5]],
        ]),
    ];

    $this->actingAs($member)
        ->put(route('ensembles.seating-plan.update', $ensemble), $payload)
        ->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))
        ->put(route('ensembles.seating-plan.update', $ensemble), $payload)
        ->assertForbidden();

    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toEqual('A');
    expect($pivot->seat_column)->toEqual(1);
});

test('moving a member to unassigned clears their seat', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble, null, 1, 'A');

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.seating-plan.update', $ensemble), [
            'seating_plan' => json_encode([
                'unassigned' => [['id' => $member->id]],
            ]),
        ])
        ->assertRedirect();

    $pivot = $ensemble->users()->first()->pivot;
    expect($pivot->seat_row)->toBeNull();
    expect($pivot->seat_column)->toBeNull();
});

test('the seating plan pdf downloads for authorised users', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member, ['image' => null]);
    join_ensemble($member, $ensemble, null, 1, 'A');

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $termDate->id]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('the seating plan pdf is forbidden without permission to view the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member);

    $this->actingAs($member)
        ->get(route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $termDate->id]))
        ->assertForbidden();
});

test('the seating plan pdf view groups members by row and marks who is not attending', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $frontRow = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Front']);
    $backRow = make_user(UserRole::Member, ['first_name' => 'Bea', 'last_name' => 'Back']);
    join_ensemble($frontRow, $ensemble, null, 'A', 1);
    join_ensemble($backRow, $ensemble, null, 'B', 1);
    Attendance::create([
        'user_id' => $backRow->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::NotAttending,
        'edit_user_id' => $frontRow->id,
        'edit_ip' => '127.0.0.1',
    ]);
    $members = $ensemble->users()->wherePivotNotNull('instrument_family_id')->with('attendances')->get();
    $instrumentFamilies = InstrumentFamily::all()->keyBy('id');

    $render = fn () => view('mail.seating-plan-pdf', ['ensemble' => $ensemble, 'termDate' => $termDate, 'members' => $members, 'instrumentFamilies' => $instrumentFamilies])->render();
    $render();
    $html = $render();

    expect($html)->toContain('Row A')->toContain('Row B');
    expect($html)->toMatch('/<s [^>]*>\s*'.$backRow->name.'\s*<\/s>/');
    expect($html)->not->toMatch('/<s [^>]*>\s*'.$frontRow->name.'\s*<\/s>/');
});

test('the seating plan pdf shows rows past Z and leaves out members with no seat', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date();
    $back = make_user(UserRole::Member, ['first_name' => 'Bea', 'last_name' => 'Back']);
    $unseated = make_user(UserRole::Member, ['first_name' => 'Una', 'last_name' => 'Seated']);
    join_ensemble($back, $ensemble, null, 'AA', 1);
    join_ensemble($unseated, $ensemble);

    $html = view('mail.seating-plan-pdf', [
        'ensemble' => $ensemble,
        'termDate' => $termDate,
        'members' => $ensemble->users()->with('attendances')->get(),
        'instrumentFamilies' => InstrumentFamily::all()->keyBy('id'),
    ])->render();

    expect($html)->toContain('Row AA')->toContain($back->name)
        ->not->toContain('Row A<')->not->toContain($unseated->name);
});

function render_seating_plan_pdf(Ensemble $ensemble, ?TermDate $termDate): string
{
    return view('mail.seating-plan-pdf', [
        'ensemble' => $ensemble,
        'termDate' => $termDate,
        'members' => $ensemble->users()->with('attendances')->get(),
        'instrumentFamilies' => InstrumentFamily::all()->keyBy('id'),
    ])->render();
}

function seating_plan_reply(User $user, Ensemble $ensemble, TermDate $termDate, AttendanceStatus $status, ?string $depName = null): void
{
    Attendance::create([
        'user_id' => $user->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => $status,
        'dep_name' => $depName,
        'edit_user_id' => $user->id,
        'edit_ip' => '127.0.0.1',
    ]);
}

test('the seating plan pdf row headers use the primary colour', function () {
    config(['app.readererer_primary_color' => 'purple']);
    $ensemble = Ensemble::factory()->create();
    join_ensemble(make_user(UserRole::Member), $ensemble, null, 'A', 1);

    expect(render_seating_plan_pdf($ensemble, make_term_date()))->toContain('background-color: '.Color::Purple->hex());
});

test('the generic seating plan pdf downloads', function () {
    $ensemble = Ensemble::factory()->create();
    join_ensemble(make_user(UserRole::Member, ['image' => null]), $ensemble, null, 'A', 1);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('ensembles.seating-plan.show', $ensemble))
        ->assertSee(route('ensembles.seating-plan.download-generic', $ensemble));
    $this->actingAs($admin)
        ->get(route('ensembles.seating-plan.download-generic', $ensemble))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('the generic seating plan pdf is forbidden without permission to view the ensemble', function () {

    $this->actingAs(make_user(UserRole::Member))
        ->get(route('ensembles.seating-plan.download-generic', Ensemble::factory()->create()))
        ->assertForbidden();
});

test('the generic seating plan pdf shows no attendance', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date();
    $absent = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Absent']);
    join_ensemble($absent, $ensemble, null, 'A', 1);
    seating_plan_reply($absent, $ensemble, $termDate, AttendanceStatus::NotAttending);

    expect(render_seating_plan_pdf($ensemble, null))->toContain('Row A')->toContain('Ada Absent')
        ->not->toContain('<s ')->not->toContain('Not attending')->not->toContain('Attendees');
});

test('seating plan pdfs shorten surnames when the setting is on', function () {
    config(['app.readererer_abbreviate_surnames' => true]);
    $ensemble = Ensemble::factory()->create();
    join_ensemble(make_user(UserRole::Member, ['first_name' => 'Adam', 'last_name' => 'Blakey']), $ensemble, null, 'A', 1);

    expect(render_seating_plan_pdf($ensemble, null))->toContain('Adam B')->not->toContain('Adam Blakey');
});

test('the seating plan pdf shows a dep above the member they replace', function () {
    config(['app.readererer_depping' => true]);
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date();
    $member = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    join_ensemble($member, $ensemble, null, 'A', 1);
    seating_plan_reply($member, $ensemble, $termDate, AttendanceStatus::Depping, 'Charles Babbage');

    expect(render_seating_plan_pdf($ensemble, $termDate))->toMatch('/<div>Charles Babbage<\/div>\s*<div [^>]*>Dep for Ada Lovelace<\/div>/');
});

test('the seating plan pdf shows a dep as the member attending while depping is off', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date();
    $member = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    join_ensemble($member, $ensemble, null, 'A', 1);
    seating_plan_reply($member, $ensemble, $termDate, AttendanceStatus::Depping, 'Charles Babbage');

    expect(render_seating_plan_pdf($ensemble, $termDate))->toContain('Ada Lovelace')->not->toContain('Charles Babbage');
});
