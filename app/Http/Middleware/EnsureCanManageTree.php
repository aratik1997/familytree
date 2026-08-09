<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the pages that change the family records — adding people, editing
 * relationships, sending invitations.
 *
 * A moderator passes here as readily as the Super Admin: looking after the
 * records is exactly what the role is for. Only appointing the moderators
 * themselves is kept back, and that is guarded by EnsureIsSuperAdmin.
 */
class EnsureCanManageTree
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canManageTree(), 403);

        return $next($request);
    }
}
