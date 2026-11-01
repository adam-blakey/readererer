<?php

use App\Enums\AttendanceStatus;
use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\Term;

test('an admin can mark a member as attending on the poll and save it', function () {
    config(['app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    $termDate = make_term_date($term);

    $member = make_user(UserRole::Member, ['first_name' => 'Jo', 'last_name' => 'Bloggs']);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Admin));

    $pollPath = route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term], absolute: false);

    visit($pollPath)
        ->assertSee('Jo Bloggs')
        ->assertButtonDisabled('Save')
        ->click('.three-state-checkbox')
        ->assertPresent('.three-state-checkbox-attending')
        ->assertButtonEnabled('Save')
        ->press('Save')
        ->assertPathIs($pollPath)
        ->assertPresent('.three-state-checkbox-attending')
        ->assertNoJavaScriptErrors();

    $attendance = Attendance::sole();
    expect($attendance->user_id)->toBe($member->id)
        ->and($attendance->term_date_id)->toBe($termDate->id)
        ->and($attendance->status)->toBe(AttendanceStatus::Attending);
});

test('a moderator can mark everyone present on the register and save it', function () {
    $ensemble = Ensemble::factory()->create();
    $termDate = make_term_date();

    foreach (range(1, 2) as $i) {
        join_ensemble(make_user(UserRole::Member), $ensemble);
    }

    $this->actingAs(make_user(UserRole::Moderator));

    visit(route('attendance.register.show', ['ensemble' => $ensemble, 'termDate' => $termDate], absolute: false))
        ->assertSee('This register has not been taken yet.')
        ->assertButtonDisabled('Save register')
        ->click('All present')
        ->press('Save register')
        ->assertSee('Register saved.')
        ->assertSee('Present: 2')
        ->assertNoJavaScriptErrors();

    expect(RegisterEntry::where('status', RegisterStatus::Present)->count())->toBe(2);
});
