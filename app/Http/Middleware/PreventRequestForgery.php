<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware
{
    // Forms post the _token field and no script sends X-XSRF-TOKEN, so the cookie, which must be readable by JavaScript, is not needed.
    protected $addHttpCookie = false;
}
