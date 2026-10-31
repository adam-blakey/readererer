<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Support\Facades\DB;

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

test('a poll submission ignores members, dates and statuses that do not belong to the poll', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $otherTerm = Term::factory()->create();
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $otherConcert = TermDate::forceCreate([
        'term_id' => $term->id,
        'concert_ensemble_id' => $otherEnsemble->id,
        'start_datetime' => now()->addWeeks(2),
        'end_datetime' => now()->addWeeks(2)->addHours(2),
    ]);
    $otherTermDate = TermDate::forceCreate([
        'term_id' => $otherTerm->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, $otherEnsemble);

    $this->actingAs(make_user(UserRole::Admin))->post(
        route('attendance.poll-store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
        [
            "status-t{$rehearsal->id}m{$outsider->id}" => AttendanceStatus::Attending->value,
            "status-t{$otherTermDate->id}m{$member->id}" => AttendanceStatus::Attending->value,
            "status-t{$otherConcert->id}m{$member->id}" => AttendanceStatus::Attending->value,
            "status-t{$rehearsal->id}m{$member->id}" => 7,
            'unexpected' => 'value',
        ]
    )->assertRedirect();

    expect(Attendance::count())->toBe(0);
});

test('the poll and the register agree on a member\'s latest answer for their ensemble', function () {
    $orchestra = Ensemble::factory()->create();
    $choir = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $rehearsal = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $orchestra);
    join_ensemble($member, $choir);
    $admin = make_user(UserRole::Admin);

    $sameSecond = now();
    foreach ([AttendanceStatus::NotAttending, AttendanceStatus::Attending] as $status) {
        Attendance::forceCreate([
            'user_id' => $member->id,
            'term_date_id' => $rehearsal->id,
            'ensemble_id' => $orchestra->id,
            'status' => $status,
            'edit_user_id' => $admin->id,
            'edit_ip' => '127.0.0.1',
            'created_at' => $sameSecond,
            'updated_at' => $sameSecond,
        ]);
    }
    Attendance::forceCreate([
        'user_id' => $member->id,
        'term_date_id' => $rehearsal->id,
        'ensemble_id' => $choir->id,
        'status' => AttendanceStatus::NotAttending,
        'edit_user_id' => $admin->id,
        'edit_ip' => '127.0.0.1',
        'created_at' => $sameSecond->copy()->addMinute(),
        'updated_at' => $sameSecond->copy()->addMinute(),
    ]);

    $poll = $this->actingAs($admin)->get(route('attendance.poll', ['ensemble' => $orchestra->slug, 'term' => $term->slug]));
    $totals = member_status_totals($poll->viewData('members'), $rehearsal, $orchestra);

    $register = $this->actingAs($admin)->get(route('attendance.register.show', ['ensemble' => $orchestra->slug, 'termDate' => $rehearsal]));

    expect($totals['not_attending'])->toBe(0);
    expect($register->viewData('polled_statuses')->get($member->id))->toBe(AttendanceStatus::Attending);
});

test('the poll runs the same number of queries however many members there are', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'week' => 1, 'color' => 'blue']);
    $drivers = collect([make_user(UserRole::Member), make_user(UserRole::Member)]);
    $drivers->each(fn ($driver, $index) => $setupGroup->van_drivers()->attach($driver->id, ['sort' => $index]));
    $drivers->each(fn ($driver) => join_ensemble($driver, $ensemble));
    $dates = collect(range(1, 4))->map(fn ($week) => TermDate::forceCreate([
        'term_id' => $term->id,
        'setup_group_id' => $setupGroup->id,
        'start_datetime' => now()->addWeeks($week),
        'end_datetime' => now()->addWeeks($week)->addHours(2),
    ]));
    $admin = make_user(UserRole::Admin);
    $url = route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $countQueries = function () use ($admin, $url) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($admin)->get($url)->assertOk();
        DB::disableQueryLog();

        return [count(DB::getQueryLog()), $response];
    };

    [$fewMembers] = $countQueries();

    foreach (range(1, 10) as $ignored) {
        $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
        join_ensemble($member, $ensemble);
        Attendance::create([
            'user_id' => $member->id,
            'term_date_id' => $dates->first()->id,
            'ensemble_id' => $ensemble->id,
            'status' => AttendanceStatus::Attending,
            'edit_user_id' => $admin->id,
            'edit_ip' => '127.0.0.1',
        ]);
    }

    [$manyMembers, $response] = $countQueries();

    expect($manyMembers)->toBe($fewMembers);
    expect($response->viewData('van_driver_ids')->values()->all())
        ->toBe([$drivers[0]->id, $drivers[1]->id, $drivers[0]->id, $drivers[1]->id]);
});

test('repeating poll headings start again for each instrument family', function () {
    config(['app.readererer_repeating_headings' => true]);
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    foreach (['Brass', 'Brass', 'Strings'] as $family) {
        join_ensemble(make_user(UserRole::Member), $ensemble, make_instrument_family($family));
    }

    $html = $this->actingAs(make_user(UserRole::Admin))
        ->get(route('attendance.poll', ['ensemble' => $ensemble->slug, 'term' => $term->slug, 'sortby' => 'instrument_family']))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '<thead>'))->toBe(2);
});
