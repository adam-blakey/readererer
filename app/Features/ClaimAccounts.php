<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ClaimAccounts extends FeatureFlag
{
    public function title(): string
    {
        return __('Claim accounts');
    }

    public function description(): string
    {
        return __('Invites ensemble logins to set up their own account: members who have never logged in can have a login link emailed to them from a list of the ensemble\'s members.');
    }
}
