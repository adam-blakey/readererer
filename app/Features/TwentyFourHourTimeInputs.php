<?php

namespace App\Features;

use App\Support\FeatureFlag;

class TwentyFourHourTimeInputs extends FeatureFlag
{
    public function title(): string
    {
        return __('24-hour time inputs');
    }

    public function description(): string
    {
        return __('Splits date and time fields into a date picker and a 24-hour time (HH:MM), instead of the browser\'s date and time picker, which shows 12-hour times in some languages.');
    }
}
