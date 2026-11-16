<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Mail\AttendanceListMail;
use App\Models\Attendance;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\SetupGroup;

test('members cannot see the overview', function () {

    $this->actingAs(make_user(UserRole::Member))->get(route('term-dates.show', make_term_date()))->assertForbidden();
});

test('the overview shows attendance, the setup group, the van driver, seating plans and emails', function () {
    config(['app.readererer_setup_groups' => true, 'app.readererer_van_drivers' => true, 'app.readererer_assume_attending' => false]);

    $ensemble = Ensemble::factory()->create(['seating_plan_enabled' => true]);
    $other = Ensemble::factory()->create();
    $attending = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Attends']);
    $silent = make_user(UserRole::Member, ['first_name' => 'Sam', 'last_name' => 'Silent']);
    join_ensemble($attending, $ensemble);
    join_ensemble($silent, $ensemble);

    $setupGroup = SetupGroup::factory()->create();
    $setupMember = make_user(UserRole::Member, ['first_name' => 'Seth', 'last_name' => 'Setup', 'setup_group_id' => $setupGroup->id]);
    $driver = make_user(UserRole::Member, ['first_name' => 'Vera', 'last_name' => 'Van']);

    $termDate = make_term_date(null, $ensemble);
    $termDate->forceFill(['setup_group_id' => $setupGroup->id, 'van_driver_id' => $driver->id])->save();

    Attendance::create(['user_id' => $attending->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => AttendanceStatus::Attending, 'edit_user_id' => $attending->id, 'edit_ip' => '127.0.0.1']);
    EmailLog::create(['term_date_id' => $termDate->id, 'mailable_class' => AttendanceListMail::class, 'subject' => 'Attendance list subject', 'status' => EmailStatus::Sent])
        ->recipients()->create(['name' => 'Ada Attends', 'email' => 'ada@example.com', 'status' => EmailStatus::Sent]);

    $this->actingAs(make_user(UserRole::Admin))->get(route('term-dates.show', $termDate))
        ->assertOk()
        ->assertSee($ensemble->name)
        ->assertDontSee($other->name)
        ->assertSee('Ada Attends')
        ->assertSee('Sam Silent')
        ->assertSee(__(':count attending', ['count' => 1]))
        ->assertSee(__(':count unknown', ['count' => 1]))
        ->assertSee('Seth Setup')
        ->assertSee('Vera Van')
        ->assertSee(route('ensembles.seating-plan.download', ['ensemble' => $ensemble, 'termDate' => $termDate]))
        ->assertSee('Attendance list subject')
        ->assertSee(__('Resend attendance list (:count previous)', ['count' => 1]));
});

test('the term page links each date to its overview', function () {
    $termDate = make_term_date();

    $this->actingAs(make_user(UserRole::Moderator))->get(route('terms.show', $termDate->term))
        ->assertSee(route('term-dates.show', $termDate));
});
