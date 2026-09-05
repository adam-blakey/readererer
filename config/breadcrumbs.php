<?php

return [
    'view' => 'breadcrumbs::tabler',
    'files' => base_path('routes/breadcrumbs.php'),
    'unnamed-route-exception' => true,
    'missing-route-bound-breadcrumb-exception' => true,
    'invalid-named-breadcrumb-exception' => true,
    'manager-class' => Diglactic\Breadcrumbs\Manager::class,
    'generator-class' => Diglactic\Breadcrumbs\Generator::class,
];
