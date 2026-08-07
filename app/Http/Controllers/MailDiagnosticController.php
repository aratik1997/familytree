<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * A browser version of `php artisan app:mail-check`, for hosts where there is
 * no shell.
 *
 * The reset page reports success whether or not a message actually left the
 * server, so without this the only way to tell a working mailer from a
 * silently discarded one is to read files over FTP.
 */
class MailDiagnosticController extends Controller
{
    /** The mailer settings actually in force, password reduced to its length. */
    private function settings(): array
    {
        $mailer = config('mail.default');
        $password = config("mail.mailers.{$mailer}.password");

        return [
            'mailer' => $mailer,
            'transport' => config("mail.mailers.{$mailer}.transport"),
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'scheme' => config("mail.mailers.{$mailer}.scheme"),
            'username' => config("mail.mailers.{$mailer}.username"),
            'password_set' => filled($password),
            'password_length' => $password ? strlen($password) : 0,
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
            'log_level' => config('logging.channels.single.level', config('logging.level')),
        ];
    }

    public function show()
    {
        return view('admin.mail-diagnostic', ['settings' => $this->settings()]);
    }

    /**
     * Sends only to the signed-in Super Admin's own address — this must never
     * become something that can be pointed at an arbitrary recipient.
     */
    public function send(Request $request)
    {
        $to = $request->user()->email;

        try {
            Mail::raw(
                "This is a test from The Khandani Legacy.\n\n".
                "If you are reading it, password reset links and account invitations will arrive too.",
                fn ($message) => $message->to($to)->subject('Test message from The Khandani Legacy')
            );
        } catch (Throwable $e) {
            return back()->with('mail_error', $e->getMessage());
        }

        return back()->with('mail_sent', $to);
    }
}
