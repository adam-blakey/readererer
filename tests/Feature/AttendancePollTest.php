<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Location;
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
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]));

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
        route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
        ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value]
    );

    $response->assertRedirect(route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]));

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
        route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
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
        ->get(route('attendance.poll.show', ['ensemble' => 'no-such-ensemble', 'term' => $term->slug]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => 'no-such-term']))
        ->assertNotFound();
});

test('an empty poll submission records nothing', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), [])
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

    $url = route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);
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
    $otherConcert = tap(TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeeks(2),
        'end_datetime' => now()->addWeeks(2)->addHours(2),
    ]), fn (TermDate $termDate) => $termDate->concert_ensembles()->attach($otherEnsemble));
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
        route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]),
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

    $poll = $this->actingAs($admin)->get(route('attendance.poll.show', ['ensemble' => $orchestra->slug, 'term' => $term->slug]));
    $totals = member_status_totals($poll->viewData('members'), $rehearsal, $orchestra);

    $register = $this->actingAs($admin)->get(route('attendance.register.show', ['ensemble' => $orchestra->slug, 'termDate' => $rehearsal]));

    expect($totals['not_attending'])->toBe(0);
    expect($register->viewData('polled_statuses')->get($member->id))->toBe(AttendanceStatus::Attending);
});

test('the poll runs the same number of queries however many members there are', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
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
    $url = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

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
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug, 'sortby' => 'instrument_family']))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, '<thead>'))->toBe(2);
});

test('a member can switch the poll to show only their own dates, and it stays switched', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = make_term_date($term);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $other = make_user(UserRole::Member);
    join_ensemble($other, $ensemble);

    $url = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->actingAs($member)->get($url)
        ->assertSee(__('Only show me'))
        ->assertSee("status-t{$termDate->id}m{$other->id}", false);

    $this->actingAs($member)->from($url)
        ->put(route('attendance.poll.view'), ['own' => '1'])
        ->assertRedirect($url);

    expect($member->fresh()->setting('poll_own_view'))->toBeTrue();

    $this->actingAs($member->fresh())->get($url)
        ->assertSee("status-t{$termDate->id}m{$member->id}", false)
        ->assertDontSee("status-t{$termDate->id}m{$other->id}", false);

    $this->actingAs($member)->from($url)->put(route('attendance.poll.view'), ['own' => '0']);

    expect($member->fresh()->setting('poll_own_view'))->toBeFalse();
});

test('the own-dates toggle is hidden from shared ensemble logins and non-members', function (UserRole $role, bool $joins) {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    make_term_date($term);

    $user = make_user($role);
    if ($joins) {
        join_ensemble($user, $ensemble);
    }

    $this->actingAs($user)
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))
        ->assertOk()
        ->assertDontSee(__('Only show me'));
})->with([
    'shared ensemble login' => [UserRole::Ensemble, true],
    'admin outside the ensemble' => [UserRole::Admin, false],
]);

test('the own-dates view marks the dates the member sets up and drives the van', function () {
    config(['app.readererer_setup_groups' => true, 'app.readererer_van_drivers' => true]);
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id, 'settings' => ['poll_own_view' => true]]);
    join_ensemble($member, $ensemble);
    $setupGroup->van_drivers()->attach($member->id, ['sort' => 0]);
    TermDate::forceCreate([
        'term_id' => $term->id,
        'setup_group_id' => $setupGroup->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $url = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $pills = fn () => substr_count($this->actingAs($member)->get($url)->getContent(), 'badge rounded-pill');

    expect($pills())->toBe(2);

    config(['app.readererer_setup_groups' => false, 'app.readererer_van_drivers' => false]);

    expect($pills())->toBe(0);
});

test('the poll names the term\'s location and marks dates held elsewhere with a symbol', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create(['location_id' => Location::factory()->create(['name' => 'Church Hall'])->id]);
    make_term_date($term);
    $away = make_term_date($term);
    $away->update(['location_id' => Location::factory()->create(['name' => 'Town Hall'])->id]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $html = $this->actingAs($member)
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]))
        ->assertOk()
        ->assertSeeInOrder(['Locations', 'Church Hall', '*', 'Town Hall'])
        ->getContent();

    expect(substr_count($html, '<strong>*</strong>'))->toBe(2); // the legend and the away date's heading
});

test('with depping on, a member can send a dep and name them', function () {
    config(['app.readererer_depping' => true]);

    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), [
        "status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Depping->value,
        "dep-t{$termDate->id}m{$member->id}" => 'Jane Dep',
    ])->assertRedirect();

    expect(Attendance::sole())
        ->status->toBe(AttendanceStatus::Depping)
        ->dep_name->toBe('Jane Dep')
        ->status_text->toBe('Sending a dep: Jane Dep');

    $this->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))
        ->assertOk()
        ->assertSee('data-original-value="3"', false)
        ->assertSee('value="Jane Dep"', false);

    $this->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), [
        "status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value,
        "dep-t{$termDate->id}m{$member->id}" => 'Jane Dep',
    ]);

    expect(Attendance::latest('id')->first()->dep_name)->toBeNull();
});

test('with depping off, a dep reply is ignored and earlier deps show as attending', function () {
    config(['app.readererer_depping' => false]);

    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs($member)->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), [
        "status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Depping->value,
    ])->assertRedirect();

    expect(Attendance::count())->toBe(0);

    Attendance::create(['user_id' => $member->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => AttendanceStatus::Depping, 'dep_name' => 'Jane Dep', 'edit_user_id' => $member->id, 'edit_ip' => '127.0.0.1']);

    expect(Attendance::sole()->status_text)->toBe('Attending');

    $this->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))
        ->assertOk()
        ->assertDontSee('dep-name', false)
        ->assertSee('data-original-value="1"', false);
});

test('a dep reply needs the dep\'s name, and nothing is saved without it', function (?string $name) {
    config(['app.readererer_depping' => true]);

    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $poll = route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);

    $this->actingAs($member)->from($poll)->post(route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]), array_filter([
        "status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Depping->value,
        "dep-t{$termDate->id}m{$member->id}" => $name,
    ], fn ($value) => $value !== null))
        ->assertRedirect($poll)
        ->assertSessionHasErrors(["dep-t{$termDate->id}m{$member->id}" => 'Enter the name of each dep.']);

    expect(Attendance::count())->toBe(0);

    $this->get($poll)->assertSee('Enter the name of each dep.');
})->with(['missing' => [null], 'blank' => ['   ']]);

function poll_url(Ensemble $ensemble, Term $term, array $query = []): string
{
    return route('attendance.poll.show', array_merge(['ensemble' => $ensemble->slug, 'term' => $term->slug], $query));
}

function poll_date(Term $term, string $start): TermDate
{
    return TermDate::forceCreate([
        'term_id' => $term->id,
        'start_datetime' => $start,
        'end_datetime' => $start.' +2 hours',
    ]);
}

test('the poll sorts members by setup group in either direction', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    poll_date($term, '+1 week');
    $first = SetupGroup::factory()->create(['name' => 'Early crew', 'week' => 1]);
    $second = SetupGroup::factory()->create(['name' => 'Late crew', 'week' => 2]);
    $zoe = make_user(UserRole::Member, ['first_name' => 'Zoe', 'setup_group_id' => $first->id]);
    $amy = make_user(UserRole::Member, ['first_name' => 'Amy', 'setup_group_id' => $second->id]);
    $bob = make_user(UserRole::Member, ['first_name' => 'Bob', 'setup_group_id' => null]);
    foreach ([$zoe, $amy, $bob] as $member) {
        join_ensemble($member, $ensemble);
    }
    config(['app.readererer_repeating_headings' => true]);
    $this->actingAs(make_user(UserRole::Admin));

    $this->get(poll_url($ensemble, $term, ['sortby' => 'setup_group']))
        ->assertOk()
        ->assertSeeInOrder(['Early crew', 'Zoe', 'Late crew', 'Amy', 'No setup group', 'Bob']);

    $this->get(poll_url($ensemble, $term, ['sortby' => 'setup_group', 'sortdir' => 'desc']))
        ->assertSeeInOrder(['No setup group', 'Bob', 'Late crew', 'Amy', 'Early crew', 'Zoe']);
});

test('the poll totals each date at the top and bottom', function () {
    config(['app.readererer_assume_attending' => false]);
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = poll_date($term, '+1 week');
    foreach ([AttendanceStatus::Attending, AttendanceStatus::Attending, AttendanceStatus::NotAttending, null] as $status) {
        $member = make_user(UserRole::Member);
        join_ensemble($member, $ensemble);
        if ($status) {
            Attendance::create(['user_id' => $member->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => $status, 'edit_user_id' => $member->id, 'edit_ip' => '127.0.0.1']);
        }
    }

    $html = $this->actingAs(make_user(UserRole::Admin))->get(poll_url($ensemble, $term))->assertOk()->getContent();

    expect(substr_count($html, 'Attending (no reply)'))->toBe(2)
        ->and(preg_match_all('/\s2\s*<span class="text-secondary fw-normal">\(1\)<\/span>/', $html))->toBe(2);
});

test('replies to dates that have started are closed to members but not moderators', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $past = poll_date($term, '-1 hour');
    $future = poll_date($term, '+1 week');
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $store = route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $term->slug]);
    $replies = [
        "status-t{$past->id}m{$member->id}" => AttendanceStatus::NotAttending->value,
        "status-t{$future->id}m{$member->id}" => AttendanceStatus::NotAttending->value,
    ];

    $this->actingAs($member)->post($store, $replies);

    expect(Attendance::pluck('term_date_id')->all())->toBe([$future->id]);

    $this->actingAs(make_user(UserRole::Moderator))->post($store, $replies);

    expect(Attendance::where('term_date_id', $past->id)->count())->toBe(1);
});

test('past dates can be left out of the poll', function () {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $past = poll_date($term, '-1 week');
    $future = poll_date($term, '+1 week');
    join_ensemble(make_user(UserRole::Member), $ensemble);
    $this->actingAs(make_user(UserRole::Admin));

    expect($this->get(poll_url($ensemble, $term))->viewData('term_dates')->pluck('id')->all())->toBe([$past->id, $future->id]);

    config(['app.readererer_hide_past_dates' => true]);

    expect($this->get(poll_url($ensemble, $term))->viewData('term_dates')->pluck('id')->all())->toBe([$future->id]);
});

test('a poll whose dates have all passed points to the next term', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $oldTerm = Term::factory()->create();
    poll_date($oldTerm, '-1 week');
    $nextTerm = Term::factory()->create(['name' => 'Next season', 'slug' => 'next-season']);
    poll_date($nextTerm, '+1 week');
    $hiddenTerm = Term::factory()->create(['name' => 'Draft season', 'slug' => 'draft-season', 'hidden' => true]);
    poll_date($hiddenTerm, '+1 day');

    $this->actingAs($member)->get(poll_url($ensemble, $oldTerm))
        ->assertOk()
        ->assertSee('This poll has ended.')
        ->assertSee('Go to the Next season poll')
        ->assertSee(poll_url($ensemble, $nextTerm), false);

    $this->get(poll_url($ensemble, $nextTerm))->assertDontSee('This poll has ended.');
});
