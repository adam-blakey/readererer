<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ConcertDetails extends FeatureFlag
{
    public function title(): string
    {
        return __('Concert details');
    }

    public function description(): string
    {
        return __('Adds a description and a reusable dress code to each date, shows them on the date\'s page, and makes the poll\'s date headings link to that page.');
    }
}
