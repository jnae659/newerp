<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BypassLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Define allowed domains - bypass licensing for all domains
        $allowedDomains = [
            'mohasabhfirm.com',
            'localhost',
            '127.0.0.1',
            'localhost:8000',
            '127.0.0.1:8000',
            // Add any other domains you want to allow
        ];

        $currentDomain = $request->getHost();

        // Check if current domain is allowed
        if (in_array($currentDomain, $allowedDomains) ||
            strpos($currentDomain, 'localhost') !== false ||
            filter_var($currentDomain, FILTER_VALIDATE_IP)) {
            // Domain is allowed, proceed
            return $next($request);
        }

        // For other domains, still allow access (bypass licensing)
        // This effectively removes the licensing restriction
        return $next($request);
    }
}
