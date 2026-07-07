<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

test('the poll lists ensemble members who have an instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    // The poll view requires the term to have at least one date.
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
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

    $response->assertOk();
    expect($response->viewData('members')->pluck('id')->all())->toBe([$playingMember->id]);
});

test('submitting the poll records attendance for each member and term date', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $admin = make_user(UserRole::Admin);

    $response = $this->actingAs($admin)->post(
        route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
        ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value]
    );

    $response->assertRedirect(route('attendance.poll', ['ensemble' => $ensemble, 'term' => $term]));

    $attendance = Attendance::first();
    expect($attendance)->not->toBeNull();
    expect($attendance->user_id)->toBe($member->id);
    expect($attendance->term_date_id)->toBe($termDate->id);
    expect($attendance->ensemble_id)->toBe($ensemble->id);
    expect($attendance->status)->toBe(AttendanceStatus::Attending);
    expect($attendance->edit_user_id)->toBe($admin->id);
    expect($attendance->edit_ip)->not->toBeNull();
});

test('a poll submission can update several members at once', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $memberOne = make_user(UserRole::Member);
    $memberTwo = make_user(UserRole::Member);
    join_ensemble($memberOne, $ensemble);
    join_ensemble($memberTwo, $ensemble);

    $this->actingAs(make_user(UserRole::Admin))->post(
        route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
        [
            "status-t{$termDate->id}m{$memberOne->id}" => AttendanceStatus::Attending->value,
            "status-t{$termDate->id}m{$memberTwo->id}" => AttendanceStatus::NotAttending->value,
        ]
    );

    expect(Attendance::count())->toBe(2);
    expect(Attendance::where('user_id', $memberOne->id)->first()->status)->toBe(AttendanceStatus::Attending);
    expect(Attendance::where('user_id', $memberTwo->id)->first()->status)->toBe(AttendanceStatus::NotAttending);
});

test('the poll page is not found for unknown ensembles or terms', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)
        ->get(route('attendance.poll', ['ensemble' => 'no-such-ensemble', 'term' => $term->slug]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => 'no-such-term']))
        ->assertNotFound();
});

test('an empty poll submission records nothing', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), [])
        ->assertRedirect();

    expect(Attendance::count())->toBe(0);
});

test('repeated poll submissions append new attendance records rather than replacing them', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $url = route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->post($url, ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value]);
    $this->actingAs($admin)->post($url, ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::NotAttending->value]);

    // Attendance history is append-only; the latest record wins when totals are computed.
    expect(Attendance::where('user_id', $member->id)->count())->toBe(2);
});
