<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // How long until another link may be requested, so the form can count
        // it down instead of just refusing. Only shown for the two outcomes
        // that already confirm the address is in use — telling a stranger to
        // "wait 43 seconds" for an address that has no account would give away
        // that the account exists.
        $waitFor = in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)
            ? $this->secondsUntilNextLink($request->input('email'))
            : 0;

        $response = $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);

        return $response
            ->with('retry_after', $waitFor)
            ->with('retry_email', $waitFor > 0 ? $request->input('email') : null);
    }

    /**
     * Seconds left on the reset throttle for this address, or 0 if a new link
     * can be requested right now.
     *
     * Laravel keeps one row per address and refuses a second link until
     * `throttle` seconds after it was written, but doesn't expose the time
     * remaining, so it is worked out from that row here.
     */
    private function secondsUntilNextLink(string $email): int
    {
        $table = config('auth.passwords.users.table', 'password_reset_tokens');
        $throttle = (int) config('auth.passwords.users.throttle', 60);

        $createdAt = DB::table($table)->where('email', $email)->value('created_at');

        if (! $createdAt) {
            return 0;
        }

        // Compared as timestamps: the sign convention of Carbon's diff helpers
        // has changed between major versions, this has not.
        $canRetryAt = Carbon::parse($createdAt)->addSeconds($throttle)->getTimestamp();

        return max(0, $canRetryAt - now()->getTimestamp());
    }
}
