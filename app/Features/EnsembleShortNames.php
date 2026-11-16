<?php

namespace App\Features;

use App\Support\FeatureFlag;

class EnsembleShortNames extends FeatureFlag
{
    public function title(): string
    {
        return __('Ensemble short names');
    }

    public function description(): string
    {
        return __('Shows each ensemble\'s short name, where it has one, in tables and badges such as the term dates and registers.');
    }
}
