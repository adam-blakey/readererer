<?php

namespace App\Features;

use App\Support\FeatureFlag;

class NotificationsMenuItem extends FeatureFlag
{
    public function title(): string
    {
        return __('Notifications in the menu');
    }

    public function description(): string
    {
        return __('Adds a link to the notifications page in the main menu.');
    }
}
