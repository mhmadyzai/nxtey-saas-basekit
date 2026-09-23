<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! tenancy()->initialized) {
            abort(404);
        }

        if (! tenant()->hasModule($module)) {
            abort(404);
        }

        return $next($request);
    }
}