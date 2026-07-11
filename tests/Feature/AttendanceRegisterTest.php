<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

test('the register lists ensemble members who have an instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $playingMember = make_user(UserRole::Member);
    join_ensemble($playingMember, $ensemble);

    $memberWithoutInstrument = make_user(UserRole::Member);
    join_ensemble_without_instrument($memberWithoutInstrument, $ensemble);

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());

    $response = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('attendance.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

    $response->assertOk();
    $response->assertViewIs('attendances.show');
    expect($response->viewData('members')->pluck('id')->all())->toBe([$playingMember->id]);
});

test('the register shows the latest attendance statuses and totals', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $attending = make_user(UserRole::Member);
    $absent = make_user(UserRole::Member);
    join_ensemble($attending, $ensemble);
    join_ensemble($absent, $ensemble);

    $admin = make_user(UserRole::Admin);
    Attendance::create([
        'user_id' => $attending->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::Attending,
        'edit_user_id' => $admin->id,
        'edit_ip' => '127.0.0.1',
    ]);
    Attendance::create([
        'user_id' => $absent->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::NotAttending,
        'edit_user_id' => $admin->id,
        'edit_ip' => '127.0.0.1',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('attendance.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

    $response->assertOk();
    $response->assertSee('Attending');
    $response->assertSee('Not attending');
    // Totals row: one of two members attending.
    $response->assertSee('1&hairsp;/&hairsp;2', false);
});

test('the register page requires permission to view the ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $url = route('attendance.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->get($url)->assertForbidden();

    // A member of another ensemble may not view this ensemble's register.
    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());
    $this->actingAs($outsider)->get($url)->assertForbidden();

    // A member of the ensemble may.
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $this->actingAs($member)->get($url)->assertOk();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the register page is not found for unknown ensembles or terms', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('attendance.show', ['ensemble' => 'no-such-ensemble', 'term' => $term->slug]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('attendance.show', ['ensemble' => $ensemble->slug, 'term' => 'no-such-term']))
        ->assertNotFound();
});

test('the term page shows real attendance totals for each date', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $attending = make_user(UserRole::Member);
    $absent = make_user(UserRole::Member);
    $unknown = make_user(UserRole::Member);
    join_ensemble($attending, $ensemble);
    join_ensemble($absent, $ensemble);
    join_ensemble($unknown, $ensemble);

    $admin = make_user(UserRole::Admin);
    Attendance::create([
        'user_id' => $attending->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::Attending,
        'edit_user_id' => $admin->id,
        'edit_ip' => '127.0.0.1',
    ]);
    Attendance::create([
        'user_id' => $absent->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::NotAttending,
        'edit_user_id' => $admin->id,
        'edit_ip' => '127.0.0.1',
    ]);

    $response = $this->actingAs($admin)->get(route('terms.show', $term));

    $response->assertOk();
    $totals = $response->viewData('attendance_totals')[$termDate->id];
    expect($totals['attending'])->toBe(1);
    expect($totals['not_attending'])->toBe(1);
    expect($totals['unknown'])->toBe(1);
});

test('concert dates only count the concert ensemble\'s members in the term totals', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $concert = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'concert_ensemble_id' => $ensemble->id,
    ]);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, $otherEnsemble);

    $response = $this->actingAs(make_user(UserRole::Admin))->get(route('terms.show', $term));

    $response->assertOk();
    $totals = $response->viewData('attendance_totals')[$concert->id];
    expect($totals['unknown'])->toBe(1);
});
