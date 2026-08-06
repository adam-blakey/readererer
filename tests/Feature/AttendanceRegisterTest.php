<?php

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\Term;

test('the register index requires moderator or above', function () {
    $url = route('attendance.register.index');

    $this->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Ensemble))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Member))->get($url)->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the register for a date requires moderator or above', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());
    $url = route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]);

    $this->get($url)->assertForbidden();

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $this->actingAs($member)->get($url)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get($url)->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get($url)->assertOk();
});

test('the register lists ensemble members who have an instrument family', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $playingMember = make_user(UserRole::Member);
    join_ensemble($playingMember, $ensemble);

    $memberWithoutInstrument = make_user(UserRole::Member);
    join_ensemble_without_instrument($memberWithoutInstrument, $ensemble);

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());

    $response = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]));

    $response->assertOk();
    expect($response->viewData('members')->pluck('id')->all())->toBe([$playingMember->id]);
});

test('another ensemble concert has no register for this ensemble', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $concert = make_term_date($term, $otherEnsemble);
    $rehearsal = make_term_date($term);
    $ownConcert = make_term_date($term, $ensemble);

    $moderator = make_user(UserRole::Moderator);

    $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $concert->id]))
        ->assertNotFound();

    // Rehearsals belong to every ensemble, and an ensemble's own concert to it.
    $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $rehearsal->id]))
        ->assertOk();
    $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $ownConcert->id]))
        ->assertOk();
});

test('taking the register records who was present', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $present = make_user(UserRole::Member);
    $absent = make_user(UserRole::Member);
    join_ensemble($present, $ensemble);
    join_ensemble($absent, $ensemble);

    $moderator = make_user(UserRole::Moderator);

    $response = $this->actingAs($moderator)->post(
        route('attendance.register.store', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]),
        [
            'status' => [
                $present->id => RegisterStatus::Present->value,
                $absent->id => RegisterStatus::Absent->value,
            ],
            'notes' => [
                $absent->id => 'Sent apologies',
            ],
        ]
    );

    $response->assertRedirect();

    expect(RegisterEntry::count())->toBe(2);

    $presentEntry = RegisterEntry::where('user_id', $present->id)->first();
    expect($presentEntry->status)->toBe(RegisterStatus::Present);
    expect($presentEntry->term_date_id)->toBe($termDate->id);
    expect($presentEntry->ensemble_id)->toBe($ensemble->id);
    expect($presentEntry->marked_by_user_id)->toBe($moderator->id);
    expect($presentEntry->notes)->toBeNull();

    $absentEntry = RegisterEntry::where('user_id', $absent->id)->first();
    expect($absentEntry->status)->toBe(RegisterStatus::Absent);
    expect($absentEntry->notes)->toBe('Sent apologies');
});

test('re-taking the register updates the existing entry rather than adding another', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $url = route('attendance.register.store', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]);
    $moderator = make_user(UserRole::Moderator);

    $this->actingAs($moderator)->post($url, ['status' => [$member->id => RegisterStatus::Absent->value]]);
    $this->actingAs($moderator)->post($url, ['status' => [$member->id => RegisterStatus::Late->value]]);

    expect(RegisterEntry::count())->toBe(1);
    expect(RegisterEntry::first()->status)->toBe(RegisterStatus::Late);
});

test('clearing a member back to unmarked removes their entry', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $url = route('attendance.register.store', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]);
    $moderator = make_user(UserRole::Moderator);

    $this->actingAs($moderator)->post($url, ['status' => [$member->id => RegisterStatus::Present->value]]);
    expect(RegisterEntry::count())->toBe(1);

    $this->actingAs($moderator)->post($url, ['status' => [$member->id => RegisterStatus::Unmarked->value]]);
    expect(RegisterEntry::count())->toBe(0);

    // A note on its own is still worth keeping.
    $this->actingAs($moderator)->post($url, [
        'status' => [$member->id => RegisterStatus::Unmarked->value],
        'notes' => [$member->id => 'Arrived after the break'],
    ]);
    expect(RegisterEntry::count())->toBe(1);
    expect(RegisterEntry::first()->notes)->toBe('Arrived after the break');
});

test('statuses submitted for someone outside the ensemble are ignored', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $outsider = make_user(UserRole::Member);
    join_ensemble($outsider, Ensemble::factory()->create());

    $this->actingAs(make_user(UserRole::Moderator))->post(
        route('attendance.register.store', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]),
        ['status' => [$outsider->id => RegisterStatus::Present->value]]
    );

    expect(RegisterEntry::count())->toBe(0);
});

test('an unknown status is rejected', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(
            route('attendance.register.store', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]),
            ['status' => [$member->id => 99]]
        )
        ->assertSessionHasErrors('status.'.$member->id);

    expect(RegisterEntry::count())->toBe(0);
});

test('the index lists each date with a link to the registers that apply to it', function () {
    $ensemble = Ensemble::factory()->create();
    $otherEnsemble = Ensemble::factory()->create();
    $term = Term::factory()->create();

    $rehearsal = make_term_date($term);
    $concert = make_term_date($term, $ensemble);

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    RegisterEntry::create([
        'user_id' => $member->id,
        'term_date_id' => $rehearsal->id,
        'ensemble_id' => $ensemble->id,
        'status' => RegisterStatus::Present,
        'marked_by_user_id' => make_user(UserRole::Moderator)->id,
    ]);

    $response = $this->actingAs(make_user(UserRole::Admin))->get(route('attendance.register.index'));

    $response->assertOk();
    // A rehearsal offers a register for every ensemble; a concert only for the
    // ensemble playing it.
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $rehearsal]), false);
    $response->assertSee(route('attendance.register.show', ['ensemble' => $otherEnsemble, 'termDate' => $rehearsal]), false);
    $response->assertSee(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $concert]), false);
    $response->assertDontSee(route('attendance.register.show', ['ensemble' => $otherEnsemble, 'termDate' => $concert]), false);
    $response->assertSee('1 present');
});

test('a register that has been taken shows its members and their notes', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $moderator = make_user(UserRole::Moderator);

    RegisterEntry::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => RegisterStatus::Late,
        'notes' => 'Held up in traffic',
        'marked_by_user_id' => $moderator->id,
    ]);

    $response = $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]));

    $response->assertOk();
    $response->assertSee($member->name);
    $response->assertSee('Held up in traffic');
    $response->assertSee('Late: 1');
    $response->assertSee('Present: 0');
});

test('the register shows what each member said on the poll', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date(Term::factory()->create());

    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $moderator = make_user(UserRole::Moderator);

    Attendance::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::NotAttending,
        'edit_user_id' => $moderator->id,
        'edit_ip' => '127.0.0.1',
    ]);

    // The most recent poll answer is the one that counts.
    Attendance::create([
        'user_id' => $member->id,
        'term_date_id' => $termDate->id,
        'ensemble_id' => $ensemble->id,
        'status' => AttendanceStatus::Attending,
        'edit_user_id' => $moderator->id,
        'edit_ip' => '127.0.0.1',
    ]);

    $response = $this->actingAs($moderator)
        ->get(route('attendance.register.show', ['ensemble' => $ensemble->slug, 'termDate' => $termDate->id]));

    $response->assertOk();
    expect($response->viewData('polled_statuses')->get($member->id))->toBe(AttendanceStatus::Attending);
});
