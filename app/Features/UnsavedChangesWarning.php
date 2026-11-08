<?php

namespace App\Features;

use App\Support\FeatureFlag;

class UnsavedChangesWarning extends FeatureFlag
{
    public function title(): string
    {
        return __('Unsaved changes warning');
    }

    public function description(): string
    {
        return __('Asks signed-in users to confirm before leaving a page with a form they have changed but not saved, such as a poll.');
    }
}
