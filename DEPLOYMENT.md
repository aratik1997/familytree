# Deploying to khandanilegacy.com

> **The one thing that must be right.** Only the *contents of `public/`* may sit
> in the web root. Everything else — `app/`, `config/`, `storage/`, `vendor/`,
> `.env` — must be somewhere the web server will not serve.
>
> On 27 July 2026 the whole project was uploaded into `public_html` instead.
> The site returned `403 Forbidden` (no `index.php` in the web root), and
> meanwhile `khandanilegacy.com/deploy/khandanilegacy-full.sql` served the
> entire family database — names, dates of birth, email addresses and password
> hashes — to anyone who asked for it, alongside a log file containing 22
> relatives' email addresses. Read step 1 before you upload anything.

Everything you need is staged in `deploy/`, which is git-ignored on purpose
because it holds the live database password and a dump of the family records.

```
deploy/
  .env.production          → becomes .env on the server
  khandanilegacy-full.sql  → import via phpMyAdmin; never upload it
  build/                   → becomes public/build on the server
  verify-live.ps1          → run this when you think you are finished
```

## 1. Arrange the web root

The correct layout puts the application above the directory the web server
serves:

```
/home/sites/…/familytree/        ← app, bootstrap, config, database, resources,
                                   routes, storage, vendor, artisan, .env
/home/sites/…/public_html/       ← the CONTENTS of public/: index.php,
                                   .htaccess, favicon.ico, robots.txt, build/
```

`public/index.php` refers to `__DIR__.'/../vendor/autoload.php'` and
`__DIR__.'/../bootstrap/app.php'`, so as long as `public_html` sits directly
inside the project folder those relative paths keep working unchanged.

If StackCP lets you set the document root per domain, the simpler option is to
upload the project whole and point the domain at its `public/` subdirectory.

**Never upload:** `deploy/`, `node_modules/`, `.env` (your local one), `.git/`,
`tests/`, or `storage/logs/*.log`.

### If you cannot move files above the web root

Upload [`.htaccess`](.htaccess) from the project root. It denies `app/`,
`bootstrap/`, `config/`, `database/`, `deploy/`, `resources/`, `routes/`,
`storage/`, `tests/`, `vendor/` and every dot-file, then routes what remains
into `public/`. It is a stopgap that limits the damage — it is not as good as
getting the document root right, because one misplaced rule re-exposes
everything.

## 2. Dependencies

If the host has Composer:

```bash
composer install --no-dev --optimize-autoloader
```

If it does not, upload your local `vendor/` folder as-is.

There is no Node step on the server. Build locally with `npm run build` and
copy the resulting `public/build` up.

## 3. Environment

Copy `deploy/.env.production` to the project root as `.env`.

It is already set for the live site: `APP_ENV=production`, `APP_DEBUG=false`,
the StackCP database details, and `https://khandanilegacy.com` for both
`APP_URL` and `ASSET_URL`. Two things still need attention — see *Before
inviting anyone*.

`APP_DEBUG=false` is not cosmetic. With debug on, any error page prints the
database credentials, the failing SQL and the session cookie to whoever
triggered it.

## 4. Database

`sdb-56.hosting.stackcp.net` only resolves from inside StackCP's network, so
this cannot be done from a laptop. Use **StackCP → Manage Hosting → MySQL →
phpMyAdmin**, pick the `familytree-…` database, and import
`deploy/khandanilegacy-full.sql` through the *Import* tab.

Upload the file *through phpMyAdmin's form*. Do not copy it onto the server
first — that is exactly how it ended up publicly downloadable.

The dump carries the schema and the current family records, so the live site
starts with everyone already in it. To start empty instead:

```bash
php artisan migrate --force
php artisan app:seed-super-admin
```

## 5. Photographs

Profile photos live in `storage/app/public/profile-photos/` and are served
through a symlink:

```bash
php artisan storage:link
```

If the host blocks symlinks, copy `storage/app/public/` to `public/storage/`
instead. The URLs are the same either way.

## 6. Warm the caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after any `.env` change or the old values stay live.

**Without shell access**, delete `bootstrap/cache/config.php` and
`bootstrap/cache/routes.php` through the file manager instead. Laravel then
reads `.env` and `routes/` directly on each request — slower, but correct. A
stale config cache silently overrides a corrected `.env`, which is a
frustrating thing to debug.

## 7. The daily job

One scheduled task handles 18th birthdays: it emails the account-claim link to
whoever came of age and notifies their parents. Add a StackCP cron job for:

```
php /full/path/to/artisan schedule:run
```

running **every minute**. Laravel decides internally what is actually due.

> **Read this before the first run.** The command invites *every* adult who has
> not yet claimed an account — on the current data that is 14 people at once.
> Check who is about to be contacted:
>
> ```bash
> php artisan tinker --execute="dump(App\Models\Person::whereNull('user_id')->where('claim_status','!=','pending_invite')->whereDate('date_of_birth','<=',now()->subYears(18))->pluck('full_name'));"
> ```

## Before inviting anyone

**Email is switched off.** `.env` ships with `MAIL_MAILER=log`, so invitations
are written to `storage/logs/` and nothing is delivered. Create a mailbox in
StackCP, set `MAIL_MAILER=smtp` with `MAIL_USERNAME` and `MAIL_PASSWORD`, then
re-run `php artisan config:cache`.

Note what that means while it is still `log`: claim links get written to a
file. If that file is web-reachable, so are the links.

**Rotate the database password.** The one in `.env.production` was shared in
plain text, so treat it as known.

## Checking the result

From your machine:

```powershell
.\deploy\verify-live.ps1
```

It asserts that `.env`, `artisan`, `composer.lock`, `vendor/`, the app source,
the logs and the SQL dump are all unreachable, that the site returns 200, and
that error pages carry no stack trace. Everything must pass.

Then by hand:

- Signing in works and the tree draws.
- A profile photo displays, which proves the storage link.
- Fonts and styling load, which proves `ASSET_URL` and `public/build`.
