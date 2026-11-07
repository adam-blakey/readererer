<?php

namespace App\Notifications;

use App\Enums\EmailLinkType;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailLink extends Notification
{
    public function __construct(public EmailLinkType $type, public string $token) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $replace = [
            'app' => config('app.name'),
            'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
        ];

        [$subject, $intro, $action, $ignore] = match ($this->type) {
            EmailLinkType::ResetPassword => [
                __('Reset Password Request'),
                __('You recently requested to reset a password for your :app account. Use the button below to reset it. This message will expire in :count minutes.', $replace),
                __('Reset password'),
                __('If you did not request a password reset, then please ignore this message.'),
            ],
            EmailLinkType::LoginLink => [
                __('Your login link'),
                __('Use the button below to log in to your :app account. The link works once and expires in :count minutes.', $replace),
                __('Log in'),
                __('If you did not ask for a login link, then please ignore this message.'),
            ],
        };

        return (new MailMessage)
            ->subject(config('app.name').': '.$subject)
            ->greeting(__('Hello!'))
            ->line($intro)
            ->action($action, $this->type->url($this->token, $notifiable->email))
            ->line($ignore)
            ->salutation(__('Regards,')."\r\n\r\n".__(':app Team', ['app' => config('app.name')]));
    }
}
