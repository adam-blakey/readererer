<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\User;
use App\Services\EmailDispatcher;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function index()
    {
        $logs = EmailLog::with(['recipients', 'termDate'])
            ->newestOrSorted()
            ->paginate(per_page(15))
            ->withQueryString();

        return view('notifications.index', [
            'page_name' => __('Notifications'),
            'logs' => $logs,
        ]);
    }

    /** Resend a logged email to the recipients it failed to reach, as a new log. */
    public function resend(EmailLog $emailLog, EmailDispatcher $dispatcher): RedirectResponse
    {
        $recipients = User::whereIn('id', $emailLog->failedRecipients()->pluck('user_id'))
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty() || $emailLog->resendableMailable() === null
            || ! $dispatcher->send($emailLog->termDate, $recipients, fn (?User $recipient = null) => $emailLog->resendableMailable($recipient))) {
            return back()->with('status', __('This notification cannot be resent.'));
        }

        return back()->with('status', trans_choice('Notification resent to :count recipient.|Notification resent to :count recipients.', $recipients->count()));
    }
}
