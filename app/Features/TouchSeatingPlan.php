<?php

namespace App\Features;

use App\Support\FeatureFlag;

class TouchSeatingPlan extends FeatureFlag
{
    public function title(): string
    {
        return __('Touchscreen seating plan');
    }

    public function description(): string
    {
        return __('Adds a grab handle to each member on the seating plan, so seats can be moved on phones and tablets by dragging the handle.');
    }
}
