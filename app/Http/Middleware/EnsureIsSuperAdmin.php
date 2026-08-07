<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the one thing an Admin cannot do: say who the Admins are.
 *
 * Everything about the family records themselves is guarded by
 * EnsureManagesTree instead, which an Admin passes too.
 */
class EnsureIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_super_admin, 403);

        return $next($request);
    }
}
