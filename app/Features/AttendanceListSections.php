<?php

namespace App\Features;

use App\Support\FeatureFlag;

class AttendanceListSections extends FeatureFlag
{
    public function title(): string
    {
        return __('Attendance list email sections');
    }

    public function description(): string
    {
        return __('Gives the attendance list email a bar of the attendance totals, the time it was sent, the number of deps, and separate attending, not attending and not yet responded sections, with each dep shown beside the member they replace.');
    }
}
