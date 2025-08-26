@props([
    'href' => null,   // Direct URL (string)
    'route' => null,  // Route name (string)
    // 'params' and 'can' are intentionally omitted; they are inferred automatically.
])

@php
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Support\Facades\Route as RouteFacade;
    use Illuminate\Support\Str;

    $baseClasses = trim($attributes->get('class', ''));
    $disabledClasses = trim(($baseClasses ? ($baseClasses . ' ') : '') . 'opacity-50 pointer-events-none cursor-not-allowed');

    // Infer route params based on the route definition and attributes passed to the component
    $inferredParams = [];
    $routeObj = null;
    $paramNames = [];

    if ($route) {
        $routeObj = RouteFacade::getRoutes()->getByName($route);
        if ($routeObj) {
            if (method_exists($routeObj, 'parameterNames')) {
                $paramNames = $routeObj->parameterNames();
            } else {
                // Fallback: parse from URI
                $uri = $routeObj->uri();
                preg_match_all('/\{(.*?)(:.*?)?\}/', $uri, $m);
                $paramNames = array_map(function ($p) { return explode(':', $p)[0]; }, $m[1] ?? []);
            }
            foreach ($paramNames as $p) {
                if ($attributes->has($p)) {
                    $inferredParams[$p] = $attributes->get($p);
                }
            }
            // If exactly one param is required and not provided, try a generic 'model' attribute
            if (count($paramNames) === 1 && !array_key_exists($paramNames[0], $inferredParams) && $attributes->has('model')) {
                $inferredParams[$paramNames[0]] = $attributes->get('model');
            }
        }
    }

    // Attempt to build URL when we have all required params (or none required)
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

    // Infer authorization ability and subject from route name
    $allowed = true;
    if ($route) {
        $ability = null;
        $subject = null;

        // Special-case mappings
        if ($route === 'attendance.poll') {
            $ability = 'view';
            $subject = $inferredParams['ensemble'] ?? null;
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
            // If we couldn't infer, default to allowed to avoid over-restricting
            $allowed = true;
        }
    }
@endphp

@if ($url && $allowed)
    <a href="{{ $url }}" {{ $attributes->merge(['class' => $baseClasses]) }}>{{ $slot }}</a>
@elseif ($url && !$allowed)
    <span aria-disabled="true" {{ $attributes->merge(['class' => $disabledClasses]) }}>{{ $slot }}</span>
@else
    <span {{ $attributes->merge(['class' => $baseClasses]) }}>{{ $slot }}</span>
@endif
