<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the pages that change the family records — adding people, editing
 * relationships, sending invitations.
 *
 * Open to an Admin and to the Super Admin alike: within a tree the two have
 * exactly the same powers. Which tree those powers reach is not decided here
 * but by the global tree scope and the policies, so nothing this lets through
 * can touch another family.
 */
class EnsureManagesTree
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->managesTree(), 403);

        return $next($request);
    }
}
