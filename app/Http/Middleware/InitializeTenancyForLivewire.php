<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyForLivewire
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for central domains — no tenancy needed
        if (in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        // Tenant domain: initialize tenancy so the correct DB is used
        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}