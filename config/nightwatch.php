<?php

return [
    'enabled' => env('NIGHTWATCH_ENABLED', (bool) env('NIGHTWATCH_TOKEN')),
    'base_url' => env('NIGHTWATCH_BASE_URL'),
];
