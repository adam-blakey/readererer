<?php

use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Mail\AttendanceListMail;
use App\Mail\RosterChangedMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Support\Facades\Mail;

function make_notification_term_date(array $attributes = []): TermDate
{
    $term = Term::factory()->create();

    return TermDate::forceCreate(array_merge([
        'term_id' => $term->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
    ], $attributes));
}

test('a moderator can send the attendance list for a concert date to the ensemble admins', function () {
    Mail::fake();

    $ensemble = Ensemble::factory()->create();
    $admin = make_user(UserRole::Member);
    $ensemble->admins()->attach($admin->id);

    $termDate = make_notification_term_date(['concert_ensemble_id' => $ensemble->id]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-attendance-list', $termDate))
        ->assertRedirect();

    Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->hasTo($admin->email));

    $log = EmailLog::where('term_date_id', $termDate->id)->first();
    expect($log)->not->toBeNull();
    expect($log->mailable_class)->toBe(AttendanceListMail::class);
    expect($log->status)->toBe(EmailStatus::Sent);
    expect($log->recipients)->toHaveCount(1);
    expect($log->recipients->first()->email)->toBe($admin->email);
});

test('sending the attendance list with no ensemble admins is a no-op with a message', function () {
    Mail::fake();

    $ensemble = Ensemble::factory()->create();
    $termDate = make_notification_term_date(['concert_ensemble_id' => $ensemble->id]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-attendance-list', $termDate))
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertNothingSent();
    expect(EmailLog::count())->toBe(0);
});

test('a moderator can send a setup reminder to the setup group van drivers', function () {
    Mail::fake();

    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    // The factory assigns new users to a random setup group, so opt out explicitly.
    $driver = make_user(UserRole::Member, ['setup_group_id' => null]);
    $setupGroup->van_drivers()->attach($driver->id);

    $termDate = make_notification_term_date(['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Moderator, ['setup_group_id' => null]))
        ->post(route('term-dates.send-setup-reminder', $termDate))
        ->assertRedirect();

    Mail::assertSent(SetupReminderMail::class, fn ($mail) => $mail->hasTo($driver->email));

    $log = EmailLog::where('term_date_id', $termDate->id)->first();
    expect($log)->not->toBeNull();
    expect($log->mailable_class)->toBe(SetupReminderMail::class);
    expect($log->recipients)->toHaveCount(1);
});

test('a setup reminder for a date with no setup group is a no-op', function () {
    Mail::fake();

    $termDate = make_notification_term_date();

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-setup-reminder', $termDate))
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertNothingSent();
    expect(EmailLog::count())->toBe(0);
});

test('ordinary members cannot send notifications', function () {
    Mail::fake();

    $ensemble = Ensemble::factory()->create();
    $termDate = make_notification_term_date(['concert_ensemble_id' => $ensemble->id]);

    $this->actingAs(make_user(UserRole::Member))
        ->post(route('term-dates.send-attendance-list', $termDate))
        ->assertForbidden();

    Mail::assertNothingSent();
});

test('guests are redirected to login when sending notifications', function () {
    $termDate = make_notification_term_date();

    $this->post(route('term-dates.send-setup-reminder', $termDate))
        ->assertRedirect('/login');
});

test('the term show page lists sent emails in the history', function () {
    $termDate = make_notification_term_date();
    $log = EmailLog::create([
        'term_date_id' => $termDate->id,
        'mailable_class' => AttendanceListMail::class,
        'subject' => 'Attendance list — a rehearsal',
        'status' => EmailStatus::Sent,
    ]);
    $log->recipients()->create(['name' => 'Jane', 'email' => 'jane@example.com', 'status' => EmailStatus::Sent]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('terms.show', $termDate->term))
        ->assertOk()
        ->assertSee('Attendance list — a rehearsal')
        ->assertSee('Send attendance list now');
});

test('the attendance list email renders', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    $termDate = make_notification_term_date(['concert_ensemble_id' => $ensemble->id]);

    $html = (new AttendanceListMail($termDate))->render();

    expect($html)->toContain('Attendance list');
    expect($html)->toContain($member->name);
});

test('the setup reminder email renders', function () {
    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    $termDate = make_notification_term_date(['setup_group_id' => $setupGroup->id]);

    $html = (new SetupReminderMail($termDate, $setupGroup))->render();

    expect($html)->toContain('Setup reminder');
    expect($html)->toContain('Group A');
});

test('a moderator can send a van driver reminder to the assigned driver', function () {
    Mail::fake();

    $driver = make_user(UserRole::Member);
    $termDate = make_notification_term_date(['van_driver_id' => $driver->id]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-van-driver-reminder', $termDate))
        ->assertRedirect();

    Mail::assertSent(VanDriverReminderMail::class, fn ($mail) => $mail->hasTo($driver->email));

    $log = EmailLog::where('term_date_id', $termDate->id)->first();
    expect($log)->not->toBeNull();
    expect($log->mailable_class)->toBe(VanDriverReminderMail::class);
    expect($log->status)->toBe(EmailStatus::Sent);
    expect($log->recipients)->toHaveCount(1);
    expect($log->recipients->first()->email)->toBe($driver->email);
});

test('a van driver reminder goes to the driver inferred from the setup group rotation', function () {
    Mail::fake();

    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    $driver = make_user(UserRole::Member);
    $setupGroup->van_drivers()->attach($driver->id);

    $termDate = make_notification_term_date(['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-van-driver-reminder', $termDate))
        ->assertRedirect();

    Mail::assertSent(VanDriverReminderMail::class, fn ($mail) => $mail->hasTo($driver->email));
});

test('a van driver reminder for a date with no driver is a no-op', function () {
    Mail::fake();

    $termDate = make_notification_term_date();

    $this->actingAs(make_user(UserRole::Moderator))
        ->post(route('term-dates.send-van-driver-reminder', $termDate))
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertNothingSent();
    expect(EmailLog::count())->toBe(0);
});

test('a setup reminder also goes to the members of the setup group', function () {
    Mail::fake();

    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $driver = make_user(UserRole::Member, ['setup_group_id' => null]);
    $setupGroup->van_drivers()->attach($driver->id);

    $termDate = make_notification_term_date(['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Moderator, ['setup_group_id' => null]))
        ->post(route('term-dates.send-setup-reminder', $termDate))
        ->assertRedirect();

    Mail::assertSent(SetupReminderMail::class, fn ($mail) => $mail->hasTo($member->email));
    Mail::assertSent(SetupReminderMail::class, fn ($mail) => $mail->hasTo($driver->email));

    expect(EmailLog::first()->recipients)->toHaveCount(2);
});

test('changing the setup group of an upcoming date alerts the old and new groups', function () {
    Mail::fake();

    $oldGroup = SetupGroup::create(['name' => 'Old group', 'color' => 'blue']);
    $newGroup = SetupGroup::create(['name' => 'New group', 'color' => 'red']);
    $oldMember = make_user(UserRole::Member, ['setup_group_id' => $oldGroup->id]);
    $newMember = make_user(UserRole::Member, ['setup_group_id' => $newGroup->id]);

    $termDate = make_notification_term_date([
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'setup_group_id' => $oldGroup->id,
    ]);

    $termDate->update(['setup_group_id' => $newGroup->id]);

    Mail::assertSent(RosterChangedMail::class, fn ($mail) => $mail->hasTo($oldMember->email));
    Mail::assertSent(RosterChangedMail::class, fn ($mail) => $mail->hasTo($newMember->email));

    $log = EmailLog::where('term_date_id', $termDate->id)->first();
    expect($log)->not->toBeNull();
    expect($log->mailable_class)->toBe(RosterChangedMail::class);
    expect($log->recipients)->toHaveCount(2);
});

test('changing the van driver of an upcoming date alerts the old and new drivers', function () {
    Mail::fake();

    $oldDriver = make_user(UserRole::Member);
    $newDriver = make_user(UserRole::Member);

    $termDate = make_notification_term_date([
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
        'van_driver_id' => $oldDriver->id,
    ]);

    $termDate->update(['van_driver_id' => $newDriver->id]);

    Mail::assertSent(RosterChangedMail::class, fn ($mail) => $mail->hasTo($oldDriver->email));
    Mail::assertSent(RosterChangedMail::class, fn ($mail) => $mail->hasTo($newDriver->email));
});

test('changing the roster of a past date does not send alerts', function () {
    Mail::fake();

    $driver = make_user(UserRole::Member);
    $termDate = make_notification_term_date(); // 2026-06-01 is in the past.

    $termDate->update(['van_driver_id' => $driver->id]);

    Mail::assertNothingSent();
    expect(EmailLog::count())->toBe(0);
});

test('updating a date without touching the roster does not send alerts', function () {
    Mail::fake();

    $termDate = make_notification_term_date([
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);

    $termDate->update(['end_datetime' => now()->addWeek()->addHours(3)]);

    Mail::assertNothingSent();
});

test('editing a term through the form sends roster change alerts', function () {
    Mail::fake();

    $setupGroup = SetupGroup::create(['name' => 'Group A', 'color' => 'blue']);
    $member = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);

    $termDate = make_notification_term_date([
        'start_datetime' => now()->addWeek(),
        'end_datetime' => now()->addWeek()->addHours(2),
    ]);
    $term = $termDate->term;

    $this->actingAs(make_user(UserRole::Moderator))
        ->patch(route('terms.update', $term), [
            'name' => $term->name,
            'slug' => $term->slug,
            'term_dates' => [
                [
                    'id' => $termDate->id,
                    'start_datetime' => $termDate->start_datetime->format('Y-m-d H:i:s'),
                    'end_datetime' => $termDate->end_datetime->format('Y-m-d H:i:s'),
                    'setup_group_id' => $setupGroup->id,
                ],
            ],
        ])
        ->assertRedirect();

    Mail::assertSent(RosterChangedMail::class, fn ($mail) => $mail->hasTo($member->email));
});

test('a moderator can view the notifications overview', function () {
    $termDate = make_notification_term_date();
    $log = EmailLog::create([
        'term_date_id' => $termDate->id,
        'mailable_class' => VanDriverReminderMail::class,
        'subject' => 'Van driver reminder — a rehearsal',
        'status' => EmailStatus::Sent,
    ]);
    $log->recipients()->create(['name' => 'Jane', 'email' => 'jane@example.com', 'status' => EmailStatus::Sent]);

    $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Van driver reminder — a rehearsal')
        ->assertSee('Van-driver reminder')
        ->assertSee('Jane');
});

test('ordinary members cannot view the notifications overview', function () {
    $this->actingAs(make_user(UserRole::Member))
        ->get(route('notifications.index'))
        ->assertForbidden();
});

test('guests are redirected to login from the notifications overview', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect('/login');
});

test('the van driver reminder email renders', function () {
    $driver = make_user(UserRole::Member);
    $termDate = make_notification_term_date(['van_driver_id' => $driver->id]);

    $html = (new VanDriverReminderMail($termDate, $driver))->render();

    expect($html)->toContain('Van driver reminder');
    expect($html)->toContain($driver->first_name);
});

test('the roster changed email renders', function () {
    $termDate = make_notification_term_date();

    $html = (new RosterChangedMail($termDate, ['The setup group is now Group B (was Group A).']))->render();

    expect($html)->toContain('changed');
    expect($html)->toContain('Group B');
});
