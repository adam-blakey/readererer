<?php

namespace App\Features;

use App\Support\FeatureFlag;

class TableSize extends FeatureFlag
{
    public function title(): string
    {
        return __('Configurable table size');
    }

    public function description(): string
    {
        return __('Shows a selector beside each table\'s pagination so users can choose how many rows a page shows, remembered for each user and page.');
    }
}
