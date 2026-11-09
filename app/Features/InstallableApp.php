<?php

namespace App\Features;

use App\Support\FeatureFlag;

class InstallableApp extends FeatureFlag
{
    public function title(): string
    {
        return __('Installable app');
    }

    public function description(): string
    {
        return __('Lets members add the site to their home screen, through a web app manifest and a service worker that never caches pages.');
    }
}
