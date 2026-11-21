<?php

namespace App\Features;

use App\Support\FeatureFlag;

class UpcomingDatesFirst extends FeatureFlag
{
    public function title(): string
    {
        return __('Upcoming rehearsals first');
    }

    public function description(): string
    {
        return __('Lists only dates that have not ended on the upcoming rehearsals page, soonest first, with a button to view past dates.');
    }
}
