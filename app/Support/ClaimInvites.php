<?php

namespace App\Support;

use App\Mail\AccountClaimInvite;
use App\Models\ClaimInvite;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Issuing and emailing the link that lets somebody claim their own account.
 *
 * This used to live inline in the resend-invite action, which meant that was
 * the only place an invitation was ever sent — adding a relative to the tree
 * created their record and then said nothing to them. Every path that puts a
 * new adult into the family now comes through here instead.
 */
class ClaimInvites
{
    /** How long a link stays good for. */
    public const DAYS_VALID = 7;

    /**
     * Who an invitation makes sense for: somebody who has not already claimed
     * their account, has an address to send to, and is old enough to look
     * after their own profile. A child's profile belongs to their parent until
     * they turn 18, and ProcessAdultTransitions invites them on the day.
     */
    public static function eligible(Person $person): bool
    {
        return ! $person->isClaimed()
            && filled($person->email)
            && ! $person->isMinor();
    }

    /**
     * Issues a fresh link and emails it.
     *
     * Returns the plain link on success and null if the person was not
     * eligible. A send that fails still returns the link: the record and the
     * token are both good, and the link can be passed on by hand — losing the
     * email is not a reason to lose the invitation.
     */
    public static function send(Person $person, string $type = 'manual_invite', ?Person $invitedBy = null): ?string
    {
        if (! static::eligible($person)) {
            return null;
        }

        $plainToken = Str::random(64);

        DB::transaction(function () use ($person, $plainToken, $type, $invitedBy) {
            // Nobody should ever hold two live tokens at once.
            $person->invites()->whereNull('used_at')->update(['used_at' => now()]);

            ClaimInvite::create([
                'person_id' => $person->id,
                'token' => hash('sha256', $plainToken),
                'type' => $type,
                'expires_at' => now()->addDays(static::DAYS_VALID),
                'invited_by_person_id' => $invitedBy?->id,
            ]);

            $person->update(['claim_status' => 'pending_invite', 'invited_at' => now()]);
        });

        static::mail($person, $plainToken);

        return route('claim.show', $plainToken);
    }

    /**
     * Sent during the request rather than queued.
     *
     * The mailable used to be queued, which on a host with no worker process
     * depends entirely on QUEUE_CONNECTION being "sync" — and Laravel's own
     * default is "database", where the message would sit unsent forever with
     * nothing to show for it. Sending here costs the admin a second and cannot
     * silently stop working.
     *
     * A failure is logged and swallowed: the invitation itself is already
     * recorded, the link is handed back on screen, and losing the record of a
     * new relative because their mail server was briefly down would be worse
     * than an email that has to be resent.
     */
    private static function mail(Person $person, string $plainToken): void
    {
        try {
            Mail::to($person->email)->send(new AccountClaimInvite($person, $plainToken));
        } catch (Throwable $e) {
            Log::error('Could not email a claim invitation.', [
                'person_id' => $person->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
