<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;

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
}
