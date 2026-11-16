<?php

namespace App\Features;

use App\Support\FeatureFlag;

class HiddenEnsemblesGetNoEmails extends FeatureFlag
{
    public function title(): string
    {
        return __('No emails for hidden ensembles');
    }

    public function description(): string
    {
        return __('Leaves hidden ensembles out of attendance lists and reminders, so only the admins and members of shown ensembles get or appear in them, and sends nothing for a concert whose ensembles are all hidden. It applies to the whole site, so leave roles and ensembles empty.');
    }
}
