<?php

namespace App\Features;

use App\Support\FeatureFlag;

class HealthAndSecurityChecks extends FeatureFlag
{
    public function title(): string
    {
        return __('Health and security checks');
    }

    public function description(): string
    {
        return __('Shows admins a list of server health and security checks at the top of the settings page, with how to fix any that fail.');
    }
}
