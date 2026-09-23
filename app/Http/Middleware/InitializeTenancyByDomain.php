<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain as BaseMiddleware;

class InitializeTenancyByDomain extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        // Skip for central domains
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}