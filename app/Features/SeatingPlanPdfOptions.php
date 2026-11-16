<?php

namespace App\Features;

use App\Support\FeatureFlag;

class SeatingPlanPdfOptions extends FeatureFlag
{
    public function title(): string
    {
        return __('Seating plan PDF options');
    }

    public function description(): string
    {
        return __('Adds a generic seating plan PDF with no attendance, shortens surnames in seating plan PDFs when the site setting is on, and shows each dep on their own line under the member they replace.');
    }
}
