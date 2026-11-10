<?php

namespace App\Http\Controllers;

use App\Features\ResendFailedNotifications;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailDispatcher;
use Illuminate\Http\RedirectResponse;
use Laravel\Pennant\Feature;

class NotificationController extends Controller
{
    public function index()
    {
        $logs = EmailLog::with(['recipients', 'termDate'])
            ->latest()
            ->paginate(15);

        return view('notifications.index', [
            'page_name' => __('Notifications'),
            'logs' => $logs,
        ]);
    }

    /** Resend a logged email to the recipients it failed to reach, as a new log. */
    public function resend(EmailLog $emailLog, EmailDispatcher $dispatcher): RedirectResponse
    {
        abort_unless(Feature::active(ResendFailedNotifications::class), 404);

        $recipients = User::whereIn('id', $emailLog->failedRecipients()->pluck('user_id'))
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty() || $emailLog->resendableMailable() === null) {
            return back()->with('status', __('This notification cannot be resent.'));
        }

        $dispatcher->send($emailLog->termDate, $recipients, fn (?User $recipient = null) => $emailLog->resendableMailable($recipient));

        return back()->with('status', trans_choice('Notification resent to :count recipient.|Notification resent to :count recipients.', $recipients->count()));
    }
}
