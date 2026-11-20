<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ConfirmPasswordOnSave extends FeatureFlag
{
    public function title(): string
    {
        return __('Confirm password on save');
    }

    public function description(): string
    {
        return __('Asks for the password only when sensitive changes are saved, not when their pages are opened. What was typed is kept while the password is confirmed.');
    }
}
