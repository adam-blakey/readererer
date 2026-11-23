<?php

namespace App\Features;

use App\Support\FeatureFlag;

class CompactTermForm extends FeatureFlag
{
    public function title(): string
    {
        return __('Compact term form');
    }

    public function description(): string
    {
        return __('Shows each date on the term form as a one-line summary that opens to edit, keeps Save in view, picks concert ensembles with tick-boxes, lets a removed date be restored before saving and adds a run of repeating dates in one go.');
    }
}
