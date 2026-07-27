<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Answers "is this deployment actually correct?" from inside the server,
 * which is the half deploy/verify-live.ps1 cannot see. That script probes
 * the site from outside and proves what is *exposed*; this proves what is
 * *configured*. Run both.
 */
class DeployCheck extends Command
{
    protected $signature = 'app:deploy-check';

    protected $description = 'Check that this installation is configured correctly for the live site';

    private int $failures = 0;

    private int $warnings = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Environment</>');
        $this->checkEnvironment();

        $this->newLine();
        $this->line('  <options=bold>Database</>');
        $this->checkDatabase();

        $this->newLine();
        $this->line('  <options=bold>Files</>');
        $this->checkFiles();

        $this->newLine();
        $this->line('  <options=bold>Mail</>');
        $this->checkMail();

        $this->newLine();

        if ($this->failures > 0) {
            $this->error("  {$this->failures} problem(s) must be fixed before this site is used.");

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->warn("  Ready, with {$this->warnings} thing(s) worth a look.");

            return self::SUCCESS;
        }

        $this->info('  Everything checks out.');

        return self::SUCCESS;
    }

    private function checkEnvironment(): void
    {
        $this->assert(
            ! config('app.debug'),
            'APP_DEBUG is off',
            'APP_DEBUG is ON. Error pages will print the database password, the failing SQL and session cookies to whoever triggers them.',
        );

        $this->assert(
            app()->environment('production'),
            'APP_ENV is production',
            'APP_ENV is "'.app()->environment().'", not production.',
        );

        $this->assert(
            ! empty(config('app.key')),
            'APP_KEY is set',
            'APP_KEY is empty. Sessions and encrypted cookies cannot work. Run: php artisan key:generate',
        );

        $url = (string) config('app.url');

        $this->assert(
            str_starts_with($url, 'https://'),
            'APP_URL is https',
            "APP_URL is \"{$url}\". URL generation is pinned to this value, so every generated link will use the wrong scheme.",
        );

        $this->assert(
            ! str_contains($url, 'localhost') && ! str_contains($url, '127.0.0.1'),
            'APP_URL is not a local address',
            "APP_URL is \"{$url}\", which looks like a development value.",
        );

        $this->assert(
            config('session.secure') === true,
            'The session cookie is HTTPS-only',
            'SESSION_SECURE_COOKIE is not true, so the login cookie may be sent over plain HTTP.',
        );
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $host = config('database.connections.'.config('database.default').'.host');
            $name = config('database.connections.'.config('database.default').'.database');

            $this->reportFail("Cannot connect to the database (host {$host}, database {$name}).");
            $this->line('        '.$e->getMessage());
            $this->line('        A host of 127.0.0.1 here usually means the development .env is still in place.');

            return;
        }

        $this->reportPass('The database accepts connections');

        // Sessions live in the database, so a missing table takes the whole
        // site down on the very first request rather than degrading quietly.
        foreach (['users', 'people', 'sessions', 'cache', 'claim_invites'] as $table) {
            $this->assert(
                Schema::hasTable($table),
                "Table `{$table}` exists",
                "Table `{$table}` is missing. Import the database dump, or run: php artisan migrate --force",
            );
        }

        try {
            if (Schema::hasTable('users') && DB::table('users')->where('is_super_admin', true)->doesntExist()) {
                $this->reportWarn('No Super Admin account exists. Run: php artisan app:seed-super-admin');
            }
        } catch (Throwable) {
            // A missing column is already reported by the table checks above.
        }
    }

    private function checkFiles(): void
    {
        $this->assert(
            is_writable(storage_path('logs')),
            'storage/logs is writable',
            'storage/logs is not writable, so nothing can be logged.',
        );

        $this->assert(
            is_writable(storage_path('framework/views')),
            'storage/framework/views is writable',
            'storage/framework/views is not writable, so no Blade template can be compiled.',
        );

        $this->assert(
            file_exists(public_path('build/manifest.json')),
            'The compiled assets are present',
            'public/build/manifest.json is missing. Build locally with "npm run build" and upload public/build.',
        );

        $this->assert(
            file_exists(public_path('storage')),
            'The storage link exists',
            'public/storage is missing, so no profile photo will load. Run: php artisan storage:link',
        );

        // The failure that caused the July 2026 outage: the whole project
        // sitting inside the web root, which puts .env one rule away from
        // being downloadable.
        if (file_exists(public_path('../artisan')) && file_exists(public_path('index.php'))) {
            $projectRoot = realpath(base_path());
            $docRootGuess = realpath(public_path());

            if ($projectRoot && $docRootGuess && str_contains((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), $projectRoot)
                && ($_SERVER['DOCUMENT_ROOT'] ?? '') !== $docRootGuess) {
                $this->reportWarn('The web root appears to be the project root rather than public/. See DEPLOYMENT.md step 1.');
            }
        }
    }

    private function checkMail(): void
    {
        if (config('mail.default') === 'log') {
            $this->reportWarn('MAIL_MAILER is "log": invitations are written to storage/logs and never delivered. Claim links end up in a file.');

            return;
        }

        $this->reportPass('Mail is configured to send');
    }

    private function assert(bool $condition, string $passMessage, string $failMessage): void
    {
        $condition ? $this->reportPass($passMessage) : $this->reportFail($failMessage);
    }

    private function reportPass(string $message): void
    {
        $this->line("  <fg=green>PASS</>  {$message}");
    }

    private function reportFail(string $message): void
    {
        $this->failures++;
        $this->line("  <fg=red>FAIL</>  {$message}");
    }

    private function reportWarn(string $message): void
    {
        $this->warnings++;
        $this->line("  <fg=yellow>WARN</>  {$message}");
    }
}
