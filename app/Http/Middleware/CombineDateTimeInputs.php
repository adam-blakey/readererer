<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Joins the date and time halves posted by <x-forms.datetime-input> into one
 * Y-m-d\TH:i value, turning a 12-hour time into 24 hours, so validation and
 * controllers see what datetime-local sent.
 */
class CombineDateTimeInputs
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->request->replace($this->combine($request->request->all()));

        return $next($request);
    }

    private function combine(array $input): array
    {
        foreach ($input as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $input[$key] = array_keys($value) === ['date', 'time']
                ? (filled($value['date']) || filled($value['time']) ? $value['date'].'T'.normalise_time($value['time']) : null)
                : $this->combine($value);
        }

        return $input;
    }
}
