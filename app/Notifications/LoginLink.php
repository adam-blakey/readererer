<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginLink extends Notification
{
    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').': '.__('Your login link'))
            ->greeting(__('Hello!'))
            ->line(__('Use the button below to log in to your :app account. The link works once and expires in :count minutes.', [
                'app' => config('app.name'),
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]))
            ->action(__('Log in'), route('login-link.show', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line(__('If you did not ask for a login link, then please ignore this message.'))
            ->salutation(__('Regards,')."\r\n\r\n".__(':app Team', ['app' => config('app.name')]));
    }
}
