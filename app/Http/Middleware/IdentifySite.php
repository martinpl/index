<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifySite
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = Site::findForRequest($request);

        abort_unless($site, 404);

        $site->makeCurrent();

        return $next($request);
    }
}
