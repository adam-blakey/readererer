@props(['href' => null, 'route' => null, 'tabindex' => null])

@php
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Support\Facades\Route as RouteFacade;
    use Illuminate\Support\Str;

    $baseClasses = trim($attributes->get('class', ''));
    $disabledClasses = trim(($baseClasses ? ($baseClasses . ' ') : '') . 'opacity-50 pointer-events-none cursor-not-allowed');

    $inferredParams = [];
    $routeObj = null;
    $paramNames = [];

    if ($route) {
        $routeObj = RouteFacade::getRoutes()->getByName($route);
        if ($routeObj) {
            if (method_exists($routeObj, 'parameterNames')) {
                $paramNames = $routeObj->parameterNames();
            } else {
                $uri = $routeObj->uri();
                preg_match_all('/\{(.*?)(:.*?)?\}/', $uri, $m);
                $paramNames = array_map(function ($p) { return explode(':', $p)[0]; }, $m[1] ?? []);
            }
            foreach ($paramNames as $p) {
                if ($attributes->has($p)) {
                    $inferredParams[$p] = $attributes->get($p);
                }
            }
            // A lone route parameter may be passed as `model`.
            if (count($paramNames) === 1 && !array_key_exists($paramNames[0], $inferredParams) && $attributes->has('model')) {
                $inferredParams[$paramNames[0]] = $attributes->get('model');
            }
        }
    }

    $canBuild = true;
    foreach ($paramNames as $p) {
        if (!array_key_exists($p, $inferredParams)) { $canBuild = false; break; }
    }
    if ($href != null) {
        $url = $href;
    }
    else if ($route && $canBuild) {
        try {
            $url = route($route, $inferredParams);
        }
        catch (Exception) {
            $url = null;
        }
    }
    else {
        $url = null;
    }

    $allowed = true;
    if ($route) {
        $ability = null;
        $subject = null;

        // Special-case mappings
        if ($route === 'attendance.poll.show') {
            ;
        }
        elseif (Str::startsWith($route, 'setupgroup')) {
            ;
        } elseif (Str::endsWith($route, '.index')) {
            $ability = 'viewAny';
            $resource = Str::beforeLast($route, '.index');
            $class = 'App\\Models\\' . Str::studly(Str::singular(Str::afterLast($resource, '.')));
            if (class_exists($class)) { $subject = $class; }
        } elseif (Str::endsWith($route, '.show')) {
            $ability = 'view';
            $resource = Str::beforeLast($route, '.show');
            $param = Str::singular(Str::afterLast($resource, '.'));
            $subject = $inferredParams[$param] ?? null;
        } elseif (Str::endsWith($route, '.edit') || Str::endsWith($route, '.update')) {
            $ability = 'update';
            $resource = Str::beforeLast($route, '.') ;
            $param = Str::singular(Str::afterLast($resource, '.'));
            $subject = $inferredParams[$param] ?? null;
        } elseif (Str::endsWith($route, '.create') || Str::endsWith($route, '.store')) {
            $ability = 'create';
            $resource = Str::beforeLast($route, '.');
            $class = 'App\\Models\\' . Str::studly(Str::singular(Str::afterLast($resource, '.')));
            if (class_exists($class)) { $subject = $class; }
        }

        if ($ability && $subject) {
            $allowed = Gate::allows($ability, $subject);
        } else {
            // Nothing to check here; the policy still guards the route itself.
            $allowed = true;
        }
    }
@endphp

@if ($url && $allowed)
    <a href="{{ $url }}" {{ $attributes->merge(['class' => $baseClasses]) }} @isset($tabindex) tabindex="{{ $tabindex }}" @endisset>{{ $slot }}</a>
@elseif ($url && !$allowed)
    <span aria-disabled="true" {{ $attributes->merge(['class' => $disabledClasses]) }} @isset($tabindex) tabindex="{{ $tabindex }}" @endisset>{{ $slot }}</span>
@else
    <span {{ $attributes->merge(['class' => $baseClasses]) }} @isset($tabindex) tabindex="{{ $tabindex }}" @endisset>{{ $slot }}</span>
@endif
