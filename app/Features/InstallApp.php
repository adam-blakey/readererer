<?php

namespace App\Features;

use App\Support\FeatureFlag;

class InstallApp extends FeatureFlag
{
    public function title(): string
    {
        return __('Install app');
    }

    public function description(): string
    {
        return __('Adds an "Install app" option to the user menu in browsers that can install the site as an app, and tells iPhone and iPad users how to add it to their home screen.');
    }
}
