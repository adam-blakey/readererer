<?php

use App\Enums\EmailStatus;
use App\Enums\UserRole;
use App\Features\ResendFailedNotifications;
use App\Mail\VanDriverReminderMail;
use App\Models\EmailLog;
use App\Models\Term;
use App\Models\TermDate;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

function failed_van_driver_log(): EmailLog
{
    $driver = make_user(UserRole::Member);
    $termDate = TermDate::forceCreate([
        'term_id' => Term::factory()->create()->id,
        'start_datetime' => '2026-06-01 19:00:00',
        'end_datetime' => '2026-06-01 21:00:00',
        'van_driver_id' => $driver->id,
    ]);

    $log = EmailLog::create([
        'term_date_id' => $termDate->id,
        'mailable_class' => VanDriverReminderMail::class,
        'subject' => 'Van',
        'status' => EmailStatus::Failed,
    ]);
    $log->recipients()->create(['user_id' => $driver->id, 'name' => $driver->name, 'email' => $driver->email, 'status' => EmailStatus::Failed]);

    return $log;
}

function enable_resend_flag(): void
{
    config(['app.readererer_feature_flags' => [ResendFailedNotifications::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('the resend flag is off by default, so resending is a 404 and the button is hidden', function () {
    config(['app.readererer_van_drivers' => true]);
    $log = failed_van_driver_log();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->post(route('notifications.resend', $log))->assertNotFound();
    $this->actingAs($admin)->get(route('notifications.index'))->assertDontSee(__('Resend to failed recipients'));
});

test('an admin resends a failed notification to its failed recipients as a new log', function () {
    Mail::fake();
    config(['app.readererer_van_drivers' => true]);
    enable_resend_flag();
    $log = failed_van_driver_log();
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('notifications.index'))->assertSee(__('Resend to failed recipients'));

    $this->actingAs($admin)->post(route('notifications.resend', $log))->assertRedirect()->assertSessionHas('status');

    Mail::assertSent(VanDriverReminderMail::class, 1);
    $new = EmailLog::where('id', '!=', $log->id)->sole();
    expect($new->status)->toBe(EmailStatus::Sent)
        ->and($new->term_date_id)->toBe($log->term_date_id);
});

test('only admins can resend notifications', function () {
    enable_resend_flag();
    $log = failed_van_driver_log();

    $this->actingAs(make_user(UserRole::Moderator))->post(route('notifications.resend', $log))->assertForbidden();
});
