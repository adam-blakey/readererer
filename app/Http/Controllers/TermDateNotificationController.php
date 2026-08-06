<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Services\EmailDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class TermDateNotificationController extends Controller
{
    public function __construct(private EmailDispatcher $dispatcher) {}

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

        $this->dispatcher->send($termDate, $recipients, fn () => new AttendanceListMail($termDate));

        return back()->with('status', 'Attendance list sent to '.$recipients->count().' recipient(s).');
    }

    /**
     * Email a setup reminder to the members and van drivers of the term
     * date's setup group.
     */
    public function sendSetupReminder(TermDate $termDate): RedirectResponse
    {
        $this->authorize('sendNotifications', $termDate);

        $setupGroup = $termDate->setup_group;

        if ($setupGroup === null) {
            return back()->with('status', 'This date has no setup group, so there is nobody to remind.');
        }

        $recipients = $setupGroup->members
            ->merge($setupGroup->van_drivers)
            ->filter(fn ($member) => filled($member->email))
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return back()->with('status', 'The setup group has no members or van drivers with an email address.');
        }

        $this->dispatcher->send($termDate, $recipients, fn () => new SetupReminderMail($termDate, $setupGroup));

        return back()->with('status', 'Setup reminder sent to '.$recipients->count().' recipient(s).');
    }

    /**
     * Email a reminder to the van driver responsible for the term date
     * (explicitly assigned, or inferred from the setup group's rotation).
     */
    public function sendVanDriverReminder(TermDate $termDate): RedirectResponse
    {
        $this->authorize('sendNotifications', $termDate);

        $driver = $termDate->inferred_van_driver;

        if ($driver === null) {
            return back()->with('status', 'This date has no van driver to remind.');
        }

        if (blank($driver->email)) {
            return back()->with('status', 'The van driver has no email address.');
        }

        $this->dispatcher->send($termDate, collect([$driver]), fn () => new VanDriverReminderMail($termDate, $driver));

        return back()->with('status', 'Van driver reminder sent to '.$driver->name.'.');
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
}
