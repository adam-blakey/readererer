<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ThemeSwitch extends FeatureFlag
{
    public function title(): string
    {
        return __('Light and dark mode switch');
    }

    public function description(): string
    {
        return __('Adds a light and dark mode switch to the header. Each browser remembers its choice and follows the device setting until then.');
    }
}
