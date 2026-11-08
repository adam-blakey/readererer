<?php

return [
    'enabled' => env('NIGHTWATCH_ENABLED', (bool) env('NIGHTWATCH_TOKEN')),
];
