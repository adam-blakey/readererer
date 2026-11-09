<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ImageScaling extends FeatureFlag
{
    public function title(): string
    {
        return __('Shrink large images');
    }

    public function description(): string
    {
        return __('Scales uploaded images down in the browser before upload and again on the server when they are saved.');
    }
}
