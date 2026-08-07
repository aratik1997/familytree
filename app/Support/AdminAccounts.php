<?php

namespace App\Support;

use App\Mail\AdminInvite;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creating an Admin and handing them the keys to their own, empty tree.
 *
 * The account and the tree are made together: an Admin with no tree would have
 * nowhere to put anybody, and a tree with no owner would be unreachable.
 */
class AdminAccounts
{
    /**
     * Creates the Admin, their tree, and sends them the link that lets them
     * set a password and start.
     *
     * Their password is random and never shown to anyone — the way in is the
     * emailed link, so the Super Admin never holds another person's password.
     */
    public static function create(string $name, string $email, ?string $treeName = null): User
    {
        $tree = Tree::create([
            'name' => $treeName ?: __(':name\'s family', ['name' => $name]),
        ]);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(64),
            'is_admin' => true,
            'is_super_admin' => false,
            'tree_id' => $tree->id,
        ]);

        $tree->update(['owner_user_id' => $user->id]);

        static::invite($user);

        return $user;
    }

    /**
     * Emails the link that lets an Admin claim their account.
     *
     * Built on the password-reset token rather than the family's claim-invite
     * table, because that table hangs every invitation off a Person and a new
     * Admin has none — their tree is empty, which is the whole point. It also
     * means this rides on the one delivery path already known to work here.
     */
    public static function invite(User $user): void
    {
        $token = Password::broker()->createToken($user);

        try {
            Mail::to($user->email)->send(new AdminInvite($user, $token));
        } catch (Throwable $e) {
            Log::error('Could not email an admin invitation.', [
                'user_id' => $user->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
