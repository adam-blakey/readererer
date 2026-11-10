<?php

namespace App\Console\Commands;

use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\TermDate;
use App\Services\EmailDispatcher;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send the attendance list and setup and van driver reminders for dates whose reminder time has come';

    public function handle(EmailDispatcher $dispatcher): int
    {
        $minutes = max((int) config('app.readererer_reminder_minutes'), (int) config('app.readererer_concert_reminder_minutes'));

        if ($minutes <= 0) {
            return self::SUCCESS;
        }

        $termDates = TermDate::with('email_logs.recipients')
            ->whereBetween('start_datetime', [now(), now()->addMinutes($minutes)])
            ->get()
            ->filter->isPastReminderTime();

        foreach ($termDates as $termDate) {
            $sent = $termDate->email_logs
                ->where('created_at', '>=', $termDate->remindersDueAt())
                ->filter->wasDelivered()
                ->pluck('mailable_class');

            if (! $sent->contains(AttendanceListMail::class) && ($recipients = $termDate->attendanceListRecipients())->isNotEmpty()) {
                $dispatcher->send($termDate, $recipients, fn () => new AttendanceListMail($termDate));
            }

            if (config('app.readererer_setup_groups') && ! $sent->contains(SetupReminderMail::class)
                && ($recipients = $termDate->setupReminderRecipients())->isNotEmpty()) {
                $dispatcher->send($termDate, $recipients, fn () => new SetupReminderMail($termDate, $termDate->setup_group));
            }

            $driver = config('app.readererer_van_drivers') ? $termDate->inferred_van_driver : null;

            if (filled($driver?->email) && ! $sent->contains(VanDriverReminderMail::class)) {
                $dispatcher->send($termDate, collect([$driver]), fn () => new VanDriverReminderMail($termDate, $driver));
            }
        }

        return self::SUCCESS;
    }
}
