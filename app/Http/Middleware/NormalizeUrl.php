<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pathInfo = rawurldecode($request->getPathInfo());
        $path = mb_strtolower(rtrim($pathInfo, '/')) ?: '/';

        if ($path !== $pathInfo && $request->isMethodSafe()) {
            $query = $request->getQueryString();

            return redirect(url($path).($query ? "?$query" : ''), 301);
        }

        return $next($request);
    }
}
