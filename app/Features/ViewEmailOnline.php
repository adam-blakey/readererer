<?php

namespace App\Features;

use App\Support\FeatureFlag;

class ViewEmailOnline extends FeatureFlag
{
    public function title(): string
    {
        return __('View emails online');
    }

    public function description(): string
    {
        return __('Keeps a copy of each notification email for 90 days and adds a "View online" link to it that opens the copy in a browser. Login and password reset emails are never kept.');
    }
}
