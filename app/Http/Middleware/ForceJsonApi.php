<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures every API request is treated as expecting JSON. Without this, an
 * unauthenticated request that omits the Accept header makes Laravel try to
 * redirect to a (non-existent) 'login' route and 500s instead of returning a
 * clean 401 JSON response.
 */
class ForceJsonApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
