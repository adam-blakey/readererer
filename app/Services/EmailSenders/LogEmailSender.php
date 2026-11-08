<?php

namespace App\Services\EmailSenders;

use App\Contracts\EmailSender;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class LogEmailSender implements EmailSender
{
    public function send(User $recipient, Mailable $mailable): void
    {
        $mailable->to($recipient);

        Log::channel('emails')->info(implode("\n", [
            'To: '.$recipient->name.' <'.$recipient->email.'>',
            'Subject: '.$mailable->envelope()->subject,
            'Mailable: '.$mailable::class,
            '',
            $mailable->render(),
        ]));
    }
}
