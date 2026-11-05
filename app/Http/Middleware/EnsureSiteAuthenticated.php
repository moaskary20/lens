<?php

namespace App\Http\Middleware;

use App\Support\SiteAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteAuth::user($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('site.login'));
        }

        SiteAuth::applyClientHeader($request);

        return $next($request);
    }
}
