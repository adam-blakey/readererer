<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ConfirmPasswordForSensitiveChanges extends FeatureFlag
{
    public function title(): string
    {
        return __('Confirm password for sensitive changes');
    }

    public function description(): string
    {
        return __('Asks for the password again before the settings, feature flags and Pollererer import pages, editing a user with permission to change their role, and erasing a user. A confirmation lasts three hours.');
    }
}
