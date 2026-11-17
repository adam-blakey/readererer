<?php

namespace App\Features;

use App\Support\FeatureFlag;

class MembermojoImport extends FeatureFlag
{
    public function title(): string
    {
        return __('Membermojo import');
    }

    public function description(): string
    {
        return __('Import members from a Membermojo members download, by upload or a daily scheduled download.');
    }
}
