<?php

namespace App\Features;

use App\Support\FeatureFlag;

class SetupGroupUsers extends FeatureFlag
{
    public function title(): string
    {
        return __('Setup group users');
    }

    public function description(): string
    {
        return __('Shows the users in a setup group on its page and lets them be chosen when creating or editing the setup group.');
    }
}
