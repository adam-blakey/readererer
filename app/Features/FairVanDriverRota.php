<?php

namespace App\Features;

use App\Support\FeatureFlag;

class FairVanDriverRota extends FeatureFlag
{
    public function title(): string
    {
        return __('Fair van driver rota');
    }

    public function description(): string
    {
        return __('Gives each date without a chosen van driver to the setup group\'s driver who has driven its dates the fewest times, then to whoever drove longest ago. A driver who covers someone else\'s date misses their own next turn, and a driver added to a group joins level with the others. It applies to the whole site, so leave roles and ensembles empty. Switching it on can change the drivers of upcoming dates.');
    }
}
