<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetBusinessUrlDefaults
{
    /**
     * Set the default URL parameters for business-based routes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($currentBusiness = $request->user()?->currentBusiness) {
            URL::defaults(['business' => $currentBusiness->slug]);
        }

        return $next($request);
    }
}
