<?php

namespace App\Http\Controllers;

use App\Enums\EmailStatus;
use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\TermDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TermDateNotificationController extends Controller
{
    /**
     * Email the current attendance list for a term date to the relevant
     * ensemble admins.
     */
    public function sendAttendanceList(TermDate $termDate): RedirectResponse
    {
        $this->authorize('sendNotifications', $termDate);

        $recipients = $this->attendanceListRecipients($termDate);

        if ($recipients->isEmpty()) {
            return back()->with('status', 'There are no ensemble admins with an email address to send the attendance list to.');
        }

        $this->dispatchToRecipients($termDate, $recipients, fn () => new AttendanceListMail($termDate));

        return back()->with('status', 'Attendance list sent to '.$recipients->count().' recipient(s).');
    }

    /**
     * Email a setup reminder to the van drivers of the term date's setup group.
     */
    public function sendSetupReminder(TermDate $termDate): RedirectResponse
    {
        $this->authorize('sendNotifications', $termDate);

        $setupGroup = $termDate->setup_group;

        if ($setupGroup === null) {
            return back()->with('status', 'This date has no setup group, so there is nobody to remind.');
        }

        $recipients = $setupGroup->van_drivers
            ->filter(fn ($driver) => filled($driver->email))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return back()->with('status', 'The setup group has no van drivers with an email address.');
        }

        $this->dispatchToRecipients($termDate, $recipients, fn () => new SetupReminderMail($termDate, $setupGroup));

        return back()->with('status', 'Setup reminder sent to '.$recipients->count().' recipient(s).');
    }

    /**
     * The admins who should receive the attendance list: the concert
     * ensemble's admins for a concert, or every ensemble's admins for a
     * shared rehearsal.
     */
    private function attendanceListRecipients(TermDate $termDate): Collection
    {
        if ($termDate->concert_ensemble_id !== null) {
            $admins = $termDate->concert_ensemble?->admins ?? collect();
        } else {
            $admins = Ensemble::with('admins')->get()->flatMap->admins;
        }

        return $admins
            ->filter(fn ($admin) => filled($admin->email))
            ->unique('id')
            ->values();
    }

    /**
     * Send a mailable to each recipient, recording the send in an EmailLog so
     * it shows up in the term date's email history. A fresh mailable is built
     * per recipient so addresses are not accumulated across sends.
     *
     * @param  callable(): \Illuminate\Mail\Mailable  $mailableFactory
     */
    private function dispatchToRecipients(TermDate $termDate, Collection $recipients, callable $mailableFactory): void
    {
        $sample = $mailableFactory();

        $log = EmailLog::create([
            'term_date_id' => $termDate->id,
            'mailable_class' => $sample::class,
            'subject' => $sample->envelope()->subject,
            'status' => EmailStatus::Pending,
        ]);

        $anyFailed = false;

        foreach ($recipients as $recipient) {
            $status = EmailStatus::Sent;
            $error = null;

            try {
                Mail::to($recipient)->send($mailableFactory());
            } catch (Throwable $exception) {
                $status = EmailStatus::Failed;
                $error = $exception->getMessage();
                $anyFailed = true;
            }

            $log->recipients()->create([
                'user_id' => $recipient->id,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'status' => $status,
                'error_message' => $error,
            ]);
        }

        $log->update(['status' => $anyFailed ? EmailStatus::Failed : EmailStatus::Sent]);
    }
}
