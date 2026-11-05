<?php

namespace App\Http\Middleware;

use App\Support\SiteAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSiteAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SiteAuth::user($request)) {
            return redirect()->route('site.account');
        }

        return $next($request);
    }
}
