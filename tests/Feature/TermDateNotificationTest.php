<?php

use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
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
    $driver = make_user(UserRole::Member);
    $setupGroup->van_drivers()->attach($driver->id);

    $termDate = make_notification_term_date(['setup_group_id' => $setupGroup->id]);

    $this->actingAs(make_user(UserRole::Moderator))
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
