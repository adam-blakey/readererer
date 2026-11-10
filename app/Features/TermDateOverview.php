<?php

namespace App\Features;

use App\Support\FeatureFlag;

class TermDateOverview extends FeatureFlag
{
    public function title(): string
    {
        return __('Term date overview page');
    }

    public function description(): string
    {
        return __('Adds a page for each rehearsal or concert showing who is attending, the setup group, the van driver, seating plans and the emails sent, linked from the term, notification and rehearsal lists.');
    }
}
