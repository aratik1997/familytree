<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;

/**
 * Appointing the people who look after the family records — the Super Admin's
 * one exclusive job.
 *
 * A moderator is an existing relative who has claimed their account, not a
 * separate login handed out from outside. On a family's own tree the people
 * doing the keeping-up-to-date are family, so this promotes somebody already
 * here rather than inventing an account for them.
 */
class ModeratorController extends Controller
{
    public function index()
    {
        // Only claimed people can be appointed: a moderator has to be able to
        // sign in, and an unclaimed record has no login behind it.
        $candidates = Person::query()
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('full_name')
            ->get();

        return view('admin.moderators', ['candidates' => $candidates]);
    }

    public function promote(User $user)
    {
        $this->guard($user);

        $user->update(['is_moderator' => true]);

        return back()->with('status', 'moderator-added');
    }

    public function demote(User $user)
    {
        $this->guard($user);

        $user->update(['is_moderator' => false]);

        return back()->with('status', 'moderator-removed');
    }

    /**
     * The Super Admin's own role is not editable here. Their standing does not
     * come from the moderator flag, so setting or clearing it would change
     * nothing while looking as though it had.
     */
    private function guard(User $user): void
    {
        abort_if($user->is_super_admin, 403);
    }
}
