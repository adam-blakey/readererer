<?php

namespace App\Features;

use App\Support\FeatureFlag;

class DashboardAdminCards extends FeatureFlag
{
    public function title(): string
    {
        return __('Management cards on the dashboard');
    }

    public function description(): string
    {
        return __('Shows a row of cards at the top of the dashboard linking to ensembles, setup groups, users, terms and settings for those who can manage them.');
    }
}
