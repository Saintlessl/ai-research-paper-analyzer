<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $hasRequiredRole = $request->user()?->roles()
            ->whereIn('name', $roles)
            ->exists() ?? false;

        abort_unless($hasRequiredRole, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
