<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isSuperAdmin() && ! $user->hasRole($roles))) {
            abort(403, __('You do not have the required role to access this section.'));
        }

        return $next($request);
    }
}
