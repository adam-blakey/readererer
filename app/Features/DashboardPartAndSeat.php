<?php

namespace App\Features;

use App\Support\FeatureFlag;

class DashboardPartAndSeat extends FeatureFlag
{
    public function title(): string
    {
        return __('Part and seat on the dashboard');
    }

    public function description(): string
    {
        return __('Shows each member\'s part and seat position under their ensembles on the dashboard.');
    }
}
