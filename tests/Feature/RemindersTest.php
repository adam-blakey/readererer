<?php

use App\Contracts\EmailSender;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Mail\AttendanceChangedMail;
use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\Term;
use App\Models\TermDate;
use App\Models\User;
use App\Services\EmailSenders\SmtpEmailSender;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

function make_term_date_in(int $hours, array $attributes = []): TermDate
{
    return TermDate::factory()->create(array_merge([
        'term_id' => Term::factory(),
        'start_datetime' => now()->addHours($hours),
        'end_datetime' => now()->addHours($hours + 2),
    ], $attributes));
}

function post_poll(Ensemble $ensemble, TermDate $termDate, $member, AttendanceStatus $status)
{
    return test()->post(
        route('attendance.poll.store', ['ensemble' => $ensemble->slug, 'term' => $termDate->term->slug]),
        ["status-t{$termDate->id}m{$member->id}" => $status->value]
    );
}

beforeEach(function () {
    Mail::fake();
    config(['app.readererer_reminder_hours' => 24]);
});

test('reminders go out once for dates within the reminder time', function () {
    $ensemble = Ensemble::factory()->create();
    $admin = make_user(UserRole::Member, ['setup_group_id' => null]);
    $ensemble->admins()->attach($admin->id);
    $setupGroup = SetupGroup::factory()->create();
    $setupMember = make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
    $driver = make_user(UserRole::Member, ['setup_group_id' => null]);

    $soon = make_term_date_in(12, ['setup_group_id' => $setupGroup->id, 'van_driver_id' => $driver->id]);
    make_term_date_in(48);

    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful();

    Mail::assertSentCount(3);
    Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->hasTo($admin->email) && $mail->termDate->is($soon));
    Mail::assertSent(SetupReminderMail::class, fn ($mail) => $mail->hasTo($setupMember->email));
    Mail::assertSent(VanDriverReminderMail::class, fn ($mail) => $mail->hasTo($driver->email));
});

test('an attendance list sent by hand before the reminder time does not stop the automatic one', function () {
    $ensemble = Ensemble::factory()->create();
    $ensemble->admins()->attach(make_user(UserRole::Member)->id);
    $termDate = make_term_date_in(12);

    $this->travel(-2)->days();
    $this->actingAs(make_user(UserRole::Admin))->post(route('term-dates.send-attendance-list', $termDate));
    $this->travelBack();

    $this->artisan('reminders:send');

    Mail::assertSentCount(2);
});

test('no reminders are sent when the reminder time is turned off', function () {
    config(['app.readererer_reminder_hours' => 0]);
    Ensemble::factory()->create()->admins()->attach(make_user(UserRole::Member)->id);
    make_term_date_in(1);

    $this->artisan('reminders:send')->assertSuccessful();

    Mail::assertNothingSent();
});

test('a locked poll keeps members from changing replies after the reminder time', function () {
    config(['app.readererer_after_reminders' => 'lock']);
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    $soon = make_term_date_in(12);
    $later = make_term_date_in(48, ['term_id' => $soon->term_id]);

    $this->actingAs($member)
        ->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $soon->term->slug]))
        ->assertSee("Replies close when a date's reminders are sent.")
        ->assertSee('disabled', false);

    post_poll($ensemble, $soon, $member, AttendanceStatus::NotAttending);
    post_poll($ensemble, $later, $member, AttendanceStatus::NotAttending);

    expect(Attendance::pluck('term_date_id')->all())->toBe([$later->id]);

    $this->actingAs(make_user(UserRole::Moderator));
    post_poll($ensemble, $soon, $member, AttendanceStatus::NotAttending);

    expect(Attendance::where('term_date_id', $soon->id)->exists())->toBeTrue();
});

test('changes after the reminder time are emailed to the admins when follow-ups are on', function () {
    config(['app.readererer_after_reminders' => 'follow_up']);
    $ensemble = Ensemble::factory()->create();
    $admin = make_user(UserRole::Member);
    $ensemble->admins()->attach($admin->id);
    $member = make_user(UserRole::Member, ['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    join_ensemble($member, $ensemble);
    $soon = make_term_date_in(12);
    $later = make_term_date_in(48, ['term_id' => $soon->term_id]);

    $this->actingAs($member);
    post_poll($ensemble, $later, $member, AttendanceStatus::NotAttending);
    post_poll($ensemble, $soon, $member, AttendanceStatus::Attending);
    post_poll($ensemble, $soon, $member, AttendanceStatus::Attending);

    Mail::assertSentCount(1);
    Mail::assertSent(AttendanceChangedMail::class, fn ($mail) => $mail->hasTo($admin->email)
        && $mail->termDate->is($soon)
        && $mail->changes === [['name' => $member->name, 'from' => 'Unknown', 'to' => 'Attending']]);
});

test('the attendance changed email renders', function () {
    $termDate = make_term_date_in(12);
    $ensemble = Ensemble::factory()->create(['name' => 'Wind Band']);

    $html = (new AttendanceChangedMail($termDate, $ensemble, [['name' => 'Ada Lovelace', 'from' => 'Attending', 'to' => 'Not attending']]))->render();

    expect($html)->toContain('Wind Band')->toContain('Ada Lovelace')->toContain('Not attending (was Attending)');
});

test('concerts can have their own reminder time', function () {
    config(['app.readererer_concert_reminder_hours' => 72]);
    $ensemble = Ensemble::factory()->create();
    $ensemble->admins()->attach(make_user(UserRole::Member)->id);
    $concert = make_term_date_in(48);
    $concert->concert_ensembles()->attach($ensemble->id);
    make_term_date_in(48);

    $this->artisan('reminders:send')->assertSuccessful();

    Mail::assertSentCount(1);
    Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->termDate->is($concert));
});

test('the attendance list carries the seating plan of each ensemble that has one', function () {
    $seated = Ensemble::factory()->create(['name' => 'Wind Band']);
    join_ensemble(make_user(UserRole::Member), $seated, seatRow: 'A', seatColumn: 1);
    $unseated = Ensemble::factory()->create(['name' => 'Choir']);
    join_ensemble(make_user(UserRole::Member), $unseated);

    $attachments = (new AttendanceListMail(make_term_date_in(12)))->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe('Seating plan - Wind Band.pdf')
        ->and($attachments[0]->mime)->toBe('application/pdf');
});

test('a reminder that failed for every recipient is sent again on the next run', function () {
    Ensemble::factory()->create()->admins()->attach(make_user(UserRole::Member)->id);
    make_term_date_in(12);

    app()->bind(EmailSender::class, fn () => new class implements EmailSender
    {
        public function send(User $recipient, Mailable $mailable): void
        {
            throw new RuntimeException('Mail server unreachable');
        }
    });
    $this->artisan('reminders:send')->assertSuccessful();
    Mail::assertNothingSent();

    app()->bind(EmailSender::class, SmtpEmailSender::class);
    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful();

    Mail::assertSent(AttendanceListMail::class, 1);
});
