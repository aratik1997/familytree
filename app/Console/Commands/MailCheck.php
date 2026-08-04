<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one plain message and reports exactly why it failed.
 *
 * Password reset links, claim invites and the "child turned 18" notice all go
 * out through the same mailer, and when it is misconfigured the app carries on
 * as though nothing is wrong — the reset page still says "we have emailed
 * you". This turns that silence into an error message.
 */
class MailCheck extends Command
{
    protected $signature = 'app:mail-check {to : Where to send the test message}';

    protected $description = 'Send a test email and report the exact failure if it does not go out';

    public function handle(): int
    {
        $to = $this->argument('to');

        $mailer = config('mail.default');
        $transport = config("mail.mailers.{$mailer}.transport");
        $password = config("mail.mailers.{$mailer}.password");

        $this->line('');
        $this->line('  <fg=gray>Mailer   </> '.$mailer.' ('.$transport.')');
        $this->line('  <fg=gray>Host     </> '.(config("mail.mailers.{$mailer}.host") ?: '—'));
        $this->line('  <fg=gray>Port     </> '.(config("mail.mailers.{$mailer}.port") ?: '—'));
        $this->line('  <fg=gray>Scheme   </> '.(config("mail.mailers.{$mailer}.scheme") ?: '—'));
        $this->line('  <fg=gray>Username </> '.(config("mail.mailers.{$mailer}.username") ?: '—'));
        $this->line('  <fg=gray>Password </> '.($password ? str_repeat('•', 8).' ('.strlen($password).' chars)' : '<fg=red>not set</>'));
        $this->line('  <fg=gray>From     </> '.config('mail.from.address').' ('.config('mail.from.name').')');
        $this->line('');

        if ($transport === 'log') {
            $this->warn('  MAIL_MAILER is "log": nothing is delivered, messages are written to storage/logs.');
            $this->line('  Set MAIL_MAILER=smtp, then run: php artisan config:cache');

            return self::FAILURE;
        }

        // StackMail refuses a From address that is not the mailbox it just
        // authenticated, and does it quietly.
        $username = config("mail.mailers.{$mailer}.username");
        if ($username && strcasecmp($username, config('mail.from.address')) !== 0) {
            $this->warn('  MAIL_FROM_ADDRESS differs from MAIL_USERNAME.');
            $this->line('  Most providers reject that, often without a visible error.');
            $this->line('');
        }

        $this->line('  Sending to '.$to.' …');

        try {
            Mail::raw(
                "This is a test from The Khandani Legacy.\n\n".
                "If you are reading it, password reset links and account invitations will arrive too.",
                fn ($message) => $message->to($to)->subject('Test message from The Khandani Legacy')
            );
        } catch (Throwable $e) {
            $this->line('');
            $this->error('  Not sent.');
            $this->line('  <fg=gray>'.$e->getMessage().'</>');
            $this->line('');
            $this->line('  Common causes:');
            $this->line('   • Wrong mailbox password — this is not the database password.');
            $this->line('   • Port 465 blocked; try MAIL_PORT=587 with MAIL_SCHEME=smtp.');
            $this->line('   • The mailbox does not exist yet in StackCP → Email.');
            $this->line('   • A stale config cache; run php artisan config:cache after editing .env.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info('  Accepted by the mail server.');
        $this->line('  If it still does not arrive, check the spam folder, then the domain\'s SPF record.');

        return self::SUCCESS;
    }
}
