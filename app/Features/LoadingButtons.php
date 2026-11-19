<?php

namespace App\Features;

use App\Support\FeatureFlag;

class LoadingButtons extends FeatureFlag
{
    public function title(): string
    {
        return __('Loading buttons');
    }

    public function description(): string
    {
        return __('Disables buttons that send emails or import data once they are pressed and shows a spinner, so slow actions do not look stuck or get submitted twice.');
    }
}
