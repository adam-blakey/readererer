<?php

namespace Tests\Fixtures;

use App\Support\FeatureFlag;

class ExampleFlag extends FeatureFlag
{
    public function title(): string
    {
        return 'Example flag';
    }

    public function description(): string
    {
        return 'A flag used by the tests.';
    }
}
