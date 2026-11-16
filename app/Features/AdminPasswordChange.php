<?php

namespace App\Features;

use App\Support\FeatureFlag;

class AdminPasswordChange extends FeatureFlag
{
    public function title(): string
    {
        return __('Password buttons on the user page');
    }

    public function description(): string
    {
        return __('Replaces the reset password dialogue on a user\'s page with a button that emails them a reset link and a button that opens a page to set their password.');
    }
}
