<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;

class NotificationController extends Controller
{
    /**
     * Overview of every notification email that has been sent, with its
     * per-recipient delivery status.
     */
    public function index()
    {
        $logs = EmailLog::with(['recipients', 'termDate'])
            ->latest()
            ->paginate(15);

        return view('notifications.index', [
            'page_name' => 'Notifications',
            'logs' => $logs,
        ]);
    }
}
