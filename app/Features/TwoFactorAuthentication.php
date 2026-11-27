<?php

namespace App\Features;

use App\Support\FeatureFlag;

class TwoFactorAuthentication extends FeatureFlag
{
    public function title(): string
    {
        return __('Two-factor authentication');
    }

    public function description(): string
    {
        return __('Lets members turn on two-factor authentication, so logging in also needs a code from an authenticator app. Members who have turned it on are still asked for a code if this is turned off.');
    }
}
