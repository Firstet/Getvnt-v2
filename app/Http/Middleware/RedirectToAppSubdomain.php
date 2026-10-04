<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToAppSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($appHost && str_starts_with($appHost, 'app.')) {
            $host = $request->getHost();
            if (config('app.is_testing') || config('app.env') === 'local' || ! config('app.hosted') || str_contains($host, 'sslip.io') || filter_var($host, FILTER_VALIDATE_IP)) {
                return $next($request);
            }

            if (! str_starts_with($host, 'app.')) {
                return redirect(app_url($request->getRequestUri()), 302);
            }
        }

        return $next($request);
    }
}
