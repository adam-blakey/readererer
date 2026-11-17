<?php

namespace App\Features;

use App\Support\FeatureFlag;

class AddUsersToEnsembles extends FeatureFlag
{
    public function title(): string
    {
        return __('Add users to ensembles on creation');
    }

    public function description(): string
    {
        return __('Lets the create user form add the new user to an ensemble, with the instrument family they play and, where the ensemble has a seating plan, their seat.');
    }
}
