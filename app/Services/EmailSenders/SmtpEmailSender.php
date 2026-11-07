<?php

namespace App\Services\EmailSenders;

use App\Contracts\EmailSender;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SmtpEmailSender implements EmailSender
{
    public function send(User $recipient, Mailable $mailable): void
    {
        Mail::to($recipient)->send($mailable);
    }
}
