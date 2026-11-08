<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceListMail;
use App\Mail\SetupReminderMail;
use App\Mail\VanDriverReminderMail;
use App\Models\TermDate;
use App\Services\EmailDispatcher;
use Illuminate\Http\RedirectResponse;

class TermDateNotificationController extends Controller
{
    public function __construct(private EmailDispatcher $dispatcher) {}

    /** Email the attendance list to the concert ensemble's admins, or every ensemble's admins for a rehearsal. */
    public function sendAttendanceList(TermDate $termDate): RedirectResponse
    {
        $recipients = $termDate->attendanceListRecipients();

        if ($recipients->isEmpty()) {
            return back()->with('status', __('There are no ensemble admins with an email address to send the attendance list to.'));
        }

        $this->dispatcher->send($termDate, $recipients, fn () => new AttendanceListMail($termDate));

        return back()->with('status', trans_choice('Attendance list sent to :count recipient.|Attendance list sent to :count recipients.', $recipients->count()));
    }

    /** Email a setup reminder to the members and van drivers of the date's setup group. */
    public function sendSetupReminder(TermDate $termDate): RedirectResponse
    {
        abort_unless(config('app.readererer_setup_groups'), 404);

        $setupGroup = $termDate->setup_group;

        if ($setupGroup === null) {
            return back()->with('status', __('This date has no setup group, so there is nobody to remind.'));
        }

        $recipients = $termDate->setupReminderRecipients();

        if ($recipients->isEmpty()) {
            return back()->with('status', __('The setup group has no members or van drivers with an email address.'));
        }

        $this->dispatcher->send($termDate, $recipients, fn () => new SetupReminderMail($termDate, $setupGroup));

        return back()->with('status', trans_choice('Setup reminder sent to :count recipient.|Setup reminder sent to :count recipients.', $recipients->count()));
    }

    /** Email the date's van driver: the one assigned, or inferred from the setup group's rotation. */
    public function sendVanDriverReminder(TermDate $termDate): RedirectResponse
    {
        abort_unless(config('app.readererer_van_drivers'), 404);

        $driver = $termDate->inferred_van_driver;

        if ($driver === null) {
            return back()->with('status', __('This date has no van driver to remind.'));
        }

        if (blank($driver->email)) {
            return back()->with('status', __('The van driver has no email address.'));
        }

        $this->dispatcher->send($termDate, collect([$driver]), fn () => new VanDriverReminderMail($termDate, $driver));

        return back()->with('status', __('Van driver reminder sent to :name.', ['name' => $driver->name]));
    }
}
