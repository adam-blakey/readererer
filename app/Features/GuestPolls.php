<?php

namespace App\Features;

use App\Support\FeatureFlag;

class GuestPolls extends FeatureFlag
{
    public function title(): string
    {
        return __('Guest polls');
    }

    public function description(): string
    {
        return __('Lets guests open the polls of the ensembles they play in, where they see and change only their own replies.');
    }
}
