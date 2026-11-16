<?php

namespace App\Features;

use App\Support\FeatureFlag;

class VanDriverRotaEmails extends FeatureFlag
{
    public function title(): string
    {
        return __('Van driver rota emails');
    }

    public function description(): string
    {
        return __('Emails a van driver their next three van driving dates when a change to a setup group, its van drivers or the dates moves the dates the rota gives them.');
    }
}
