<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ResendFailedNotifications extends FeatureFlag
{
    public function title(): string
    {
        return __('Resend failed notifications');
    }

    public function description(): string
    {
        return __('Adds a button to the notifications page that resends an email to the recipients it failed to reach.');
    }
}
