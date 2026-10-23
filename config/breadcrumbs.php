<?php

use Diglactic\Breadcrumbs\Generator;
use Diglactic\Breadcrumbs\Manager;

return [
    'view' => 'breadcrumbs::tabler',
    'files' => base_path('routes/breadcrumbs.php'),
    'unnamed-route-exception' => true,
    'missing-route-bound-breadcrumb-exception' => true,
    'invalid-named-breadcrumb-exception' => true,
    'manager-class' => Manager::class,
    'generator-class' => Generator::class,
];
