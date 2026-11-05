<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfComingSoon
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('lens.coming_soon')) {
            return $next($request);
        }

        if ($request->routeIs('site.home', 'site.marketplace', 'site.newsletter', 'site.coming-soon', 'site.help', 'site.help.report')) {
            return $next($request);
        }

        if ($request->routeIs('site.*')) {
            return redirect()->route('site.home');
        }

        return $next($request);
    }
}
