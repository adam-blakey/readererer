<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Mail\Mailable;

interface EmailSender
{
    public function send(User $recipient, Mailable $mailable): void;
}
