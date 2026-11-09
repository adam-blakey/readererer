<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

const ROUTES_WITHOUT_ABILITIES = [
    'home', 'health', 'manifest', 'up', 'logout', 'boost.*',
    'login', 'login-link.*', 'google.*',
    'password.*', 'confirm-password', 'verification.*',
    'attendance.poll.view',
];

function route_abilities(RoutingRoute $route): Collection
{
    return collect($route->gatherMiddleware())
        ->filter(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'can:'))
        ->map(fn (string $middleware) => Str::between($middleware, 'can:', ','))
        ->values();
}

function routes_needing_abilities(): Collection
{
    return collect(Route::getRoutes()->getRoutes())
        ->reject(fn (RoutingRoute $route) => Str::is(ROUTES_WITHOUT_ABILITIES, $route->getName() ?? $route->uri()));
}

test('every route declares its authorisation with ->can() on the route', function () {
    $unchecked = routes_needing_abilities()
        ->filter(fn (RoutingRoute $route) => route_abilities($route)->isEmpty())
        ->map(fn (RoutingRoute $route) => $route->getName() ?? $route->uri())
        ->values();

    expect($unchecked->all())->toBe([]);
});

test('every route that changes data checks more than a view ability', function () {
    $view_only = routes_needing_abilities()
        ->reject(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
        ->filter(fn (RoutingRoute $route) => route_abilities($route)->every(fn (string $ability) => str_starts_with($ability, 'view')))
        ->map(fn (RoutingRoute $route) => $route->getName() ?? $route->uri())
        ->values();

    expect($view_only->all())->toBe([]);
});
