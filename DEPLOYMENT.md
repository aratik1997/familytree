# Deploying to khandanilegacy.com

Everything you need to upload is staged in `deploy/` — that folder is
git-ignored on purpose, because it holds the live database password, a dump of
the family records and the photographs. **Never commit it or attach it
anywhere public.**

```
deploy/
  .env.production          → becomes .env on the server
  khandanilegacy-full.sql  → import once, via phpMyAdmin
  build/                   → becomes public/build on the server
```

## 1. Upload the code

Upload the whole project **except** `node_modules/`, `deploy/`, `.env`, and
your local `public/build`.

On most StackCP plans the web root is `public_html`. Laravel expects the web
root to be the `public/` directory only — everything else must sit *above* it.
The usual arrangement is:

```
/home/sites/…/           ← app, config, routes, resources, storage, vendor …
/home/sites/…/public_html/   ← contents of the project's public/ folder
```

If you cannot move files above the web root, point the domain's document root
at `public/` in StackCP instead. Do not simply drop the whole project inside
`public_html` — that exposes `.env` to the internet.

## 2. Dependencies

If the host has Composer:

```bash
composer install --no-dev --optimize-autoloader
```

If it does not, upload your local `vendor/` folder as-is.

There is no Node step on the server: the compiled assets are already built in
`deploy/build/`. Copy that to `public/build`.

## 3. Environment

Copy `deploy/.env.production` to the project root and rename it to `.env`.

It is already set for the live site: production mode, debug off, the database
details, and `https://khandanilegacy.com` as the app URL. Two things still
need your attention — see *Before inviting anyone* below.

## 4. Database

The database host `sdb-56.hosting.stackcp.net` only resolves from inside
StackCP's network, so this cannot be done from a laptop. Use **StackCP →
Manage Hosting → MySQL → phpMyAdmin**, choose the `familytree-…` database, open
the *Import* tab and upload `deploy/khandanilegacy-full.sql`.

That file contains the schema **and** the current family records, so the live
site starts with everyone already in it.

If you would rather start empty, skip the import and run this on the server
instead:

```bash
php artisan migrate --force
php artisan app:seed-super-admin      # creates your login
```

## 5. Photographs

Profile photos live in `storage/app/public/profile-photos/` and are served
through a symlink. Upload that folder, then create the link:

```bash
php artisan storage:link
```

If the host blocks symlinks, copy `storage/app/public/` to `public/storage/`
instead — the URLs are the same either way.

## 6. Warm the caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after any future `.env` change, or the old values stay live.

## 7. The daily job

One scheduled task handles 18th birthdays: it emails the account-claim link to
whoever came of age and notifies their parents. Add a StackCP cron job for:

```
php /full/path/to/artisan schedule:run
```

running **every minute**. Laravel decides internally what is actually due.

> **Read this before the first run.** The command invites *every* adult who has
> not yet claimed an account — on the current data that is 14 people in one go.
> Check who is about to be contacted first:
>
> ```bash
> php artisan tinker --execute="dump(App\Models\Person::whereNull('user_id')->where('claim_status','!=','pending_invite')->whereDate('date_of_birth','<=',now()->subYears(18))->pluck('full_name'));"
> ```

## Before inviting anyone

**Email is switched off.** `.env` ships with `MAIL_MAILER=log`, so invitations
and notifications are written to `storage/logs/` and nothing is delivered.
Create a mailbox in StackCP and fill in `MAIL_MAILER=smtp`, `MAIL_USERNAME` and
`MAIL_PASSWORD`, then re-run `php artisan config:cache`.

**Change the database password.** The one in `.env.production` was shared in
plain text, so treat it as known. Rotate it in StackCP and update `.env`.

## Checks after going live

- `https://khandanilegacy.com` loads the sign-in page with fonts and styling.
- Signing in works and the tree draws.
- A profile photo displays (proves the storage link).
- Force an error and confirm you get a plain error page, **not** a stack trace.
  A trace would mean `APP_DEBUG` is still on, which prints the database
  password and session cookies to anyone who sees it.
