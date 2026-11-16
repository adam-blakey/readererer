<?php

namespace App\Features;

use App\Support\FeatureFlag;

class EnsembleViewInstrument extends FeatureFlag
{
    public function title(): string
    {
        return __('Part and seat on the ensemble page');
    }

    public function description(): string
    {
        return __('Shows a member\'s own part and seat position on the page of an ensemble they belong to.');
    }
}
