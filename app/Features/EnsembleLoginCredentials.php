<?php

namespace App\Features;

use App\Support\FeatureFlag;

class EnsembleLoginCredentials extends FeatureFlag
{
    public function title(): string
    {
        return __('Shared login on the ensemble page');
    }

    public function description(): string
    {
        return __('Lets admins set the username, email address and password of an ensemble\'s shared login from the ensemble\'s page.');
    }
}
