<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSwaggerIsVisible
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array(config('app.env'), ['local', 'development', 'testing'], true),
            404,
        );

        return $next($request);
    }
}
