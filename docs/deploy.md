# Deployment

Read this when you are actually deploying. Mirrors `../myfinance/docs/deploy.md` and
`../myfitness/docs/deploy.md` in shape on purpose — same VPS, same conventions, easiest to keep
every runbook on this box in your head at once. **Where it deviates from those two it says so and
why**, because this is the first PHP app on the box and several of their assumptions do not carry
over.

**This is live**, deployed and verified end-to-end (including from off-box) at
**https://jubahpanda.my** (canonical), with `www.jubahpanda.my` and `convo.dhiyadanial.my` both
answering as aliases on the same vhost and certificate. Everything below reflects the actual
running instance.

First deployed 2026-09-08 as `convo.dhiyadanial.my`. Switched to `jubahpanda.my` as canonical on
2026-09-23 — see "Finishing jubahpanda.my" at the bottom for exactly what that switch was and how
to repeat the pattern for a future domain change. Cert (one lineage, three names) expires
2026-12-22; `certbot.timer` is active and renews it.

## SSH access (read this before anything else)

`~/.ssh/config` has the `Host 160.30.5.87` entry (`Port 8080`, `User root`, `IdentityFile` set) —
verified present. Use `ssh 160.30.5.87` and it resolves the port itself.

> The other runbooks say a plain `ssh 160.30.5.87` hangs because sshd is only on 8080. **That is no
> longer true** — sshd now listens on `0.0.0.0:22` *and* `0.0.0.0:8080`. The config entry is still
> the right thing to rely on; just don't be surprised that 22 answers.

## How this app differs from every other app on this box

This is the **first PHP application** deployed here. The Python runbooks' core assumptions do not
apply:

| Their shape | This app |
|---|---|
| uvicorn bound to a loopback port (8081–8084) | **No port.** php-fpm over a unix socket |
| A systemd unit per app | **No unit.** php-fpm is a shared system service |
| Apache `ProxyPass` to `127.0.0.1:<port>` | Apache owns the **DocumentRoot** directly |
| "confirm 808x is free before claiming it" | Not applicable — nothing to claim |

So the recon ritual about port collisions in `myfitness/docs/deploy.md` is irrelevant here. What
replaces it is the PHP version constraint below, which is a much sharper edge.

## PHP 8.4 is required — 8.3 will not work

**Do not install `php8.3-fpm` from Ubuntu's default repos.** Ubuntu 24.04's candidate is 8.3.6, and
while Laravel 13 itself only needs `^8.3`, the resolved `composer.lock` pulls Symfony 8 components
that require **`>=8.4.1`**. `composer install` fails outright on 8.3.

Verify the claim yourself before trusting it:

```bash
php -r '$l=json_decode(file_get_contents("composer.lock"),true);
foreach($l["packages"] as $p){ $v=$p["require"]["php"]??null;
  if($v && preg_match("/8\.[4-9]/",$v)) echo $p["name"]," => ",$v,"\n"; }'
```

PHP 8.4 is not in Ubuntu 24.04's repos, so the `ondrej/php` PPA is needed. **It is not currently on
the box** — adding it is the one third-party repo this deploy introduces, and it is unavoidable.

## The live instance

Same Ubuntu 24.04 VPS as `dhiya_agent`/`myfinance`/`myfitness` (`160.30.5.87`), running as its own
unprivileged system user `runnerconvo` (uid 993), no systemd unit, canonical public hostname
`jubahpanda.my` (with `www.jubahpanda.my` and the original `convo.dhiyadanial.my` both aliased on
the same vhost and cert — see "Domain history" below). PHP 8.4.25 via php-fpm pool `runnerconvo` on
`/run/php/php8.4-fpm-runnerconvo.sock`.

Code at `/opt/runnerconvo/runnerconvo` (a single checkout; the Vite build output in
`public/build/` is synced separately — see below). The VPS clones this repo over SSH using a
**read-only deploy key**, generated on the VPS as the `runnerconvo` user and registered on the
GitHub repo with write access off — mirrors the other three apps' keys exactly, but is its own key,
never shared with any of them.

### The hostname is deliberately not hardcoded

`jubahpanda.my` is canonical now (see "Domain history" below for how the switch was done), but the
principle that motivated keeping it out of application code still holds — the hostname appears in
exactly **two** places, both outside application code:

1. `ServerName`/`ServerAlias` in `deploy/runnerconvo.conf` — the only occurrence in this repo.
2. `APP_URL` in `/opt/runnerconvo/runnerconvo/.env` — VPS only, never in git.

No Blade template or translation string contains it. `config/jubahrunner.php` holds contact details
(WhatsApp, Instagram, registration form, email, pickup address) but no site hostname — its one
domain-shaped value is the `hello@jubahpanda.my` fallback for `JP_EMAIL`, a contact address, not the
URL the app serves itself on. Every value there is `env()`-overridable from `.env`. See "Switching
to the real domain" at the bottom.

### `.env` location — a deliberate deviation

The other three apps keep `.env` at `/opt/<app>/.env`, *outside* the checkout, because systemd
loads it via `EnvironmentFile`. **Laravel reads `.env` from its own base path**, so here it lives at
`/opt/runnerconvo/runnerconvo/.env` — inside the checkout. This is safe: `.env` is gitignored (and
has never been committed — verified), so `git pull` never touches it, and `public/` is the docroot
so the file is not web-reachable. Still `chmod 600`, owned by `runnerconvo`.

### A small SQLite database

The app keeps its state in one SQLite file. As of 2026-09-24 that was `runners` (the eight-person
team directory shown on the landing page and edited at `/admin`) plus Laravel's stock `users` table
for the admin login. As of 2026-09-25 it also holds **`bookings`** and **`booking_options`** — the
registration form's data, which is **graduates' personal data** (name, matric number, phone, and a
Kuantan address for COD). See "Personal data" below. SQLite was chosen over the MySQL already
running on the box because a few hundred rows and one writer at a time (WAL and `busy_timeout` are
already on) don't justify standing up user/grant/backup plumbing and a second daemon to babysit;
the whole database is one file. The ceiling is concurrent writes, not row count.

The file lives at `/opt/runnerconvo/runnerconvo/database/database.sqlite` — **inside** the checkout
(same reasoning as `.env`'s location above: Laravel resolves the path relative to its own base path,
not a systemd `EnvironmentFile`), gitignored, and outside `public/` so it is never web-reachable.
Sessions (BM/EN toggle) and cache still go to files and queues still run `sync` — see "Sessions,
cache and queue stay file-backed" below for why that does not change just because a database exists
now.

**This file is the only non-reproducible state on this box.** `git clone` no longer fully
reconstitutes the app on its own — see "Backing up the database" below.

**Do not provision MySQL for this.** MySQL 8.0.46 is running on the box for other apps, but the
registration form was deliberately built on SQLite too. If a launch-day rush ever produced
`database is locked` (the 500 page tells graduates their booking was not saved and to retry),
moving to MySQL is the next step — not something to do preemptively.

## Prerequisites on the box (none of this exists yet)

```bash
# PHP 8.4 — see "PHP 8.4 is required" above for why 8.3 is not an option.
add-apt-repository -y ppa:ondrej/php && apt update
apt install -y php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
               php8.4-zip php8.4-intl php8.4-bcmath php8.4-sqlite3
# php8.4-mysql is still NOT needed — the runner directory, the admin login and the
# registration form all use SQLite (php8.4-sqlite3 above).

# Composer — Ubuntu's package, not the getcomposer.org installer. 2.7.1 is older than
# local (2.10.2) but only ever runs `install` from a committed lock file here, so the
# resolver version is irrelevant. Avoids piping a downloaded script into php as root.
apt install -y composer

# mpm_event is in use, so PHP must go over FastCGI. proxy_fcgi is currently DISABLED.
a2enmod proxy_fcgi && systemctl reload apache2
```

**Node on the VPS is v18.19.1, which cannot build this project** (Vite 8 needs Node ≥20.19). Do not
try to upgrade it — other apps on this box run on that Node. Build locally and copy the output up,
exactly as `myfitness` does with `web/static/`.

## Worked example: initial bootstrap

All run as `root` over SSH unless noted.

```bash
# System user + directory tree.
useradd --system --home-dir /opt/runnerconvo --shell /usr/sbin/nologin runnerconvo
install -d -o runnerconvo -g runnerconvo -m 750 /opt/runnerconvo

# Apache serves static files straight from the docroot, so www-data needs traverse +
# read down the tree. Group membership gives exactly that and nothing more — .env stays
# 600 so www-data still cannot read it, and PHP runs as runnerconvo via its own pool.
usermod -a -G runnerconvo www-data

# A deploy key dedicated to this app, generated ON the VPS as runnerconvo — never reuse
# dhiyaagent's, myfinance's or myfitness's.
install -d -o runnerconvo -g runnerconvo -m 700 /opt/runnerconvo/.ssh
sudo -u runnerconvo ssh-keygen -t ed25519 -f /opt/runnerconvo/.ssh/id_ed25519 -N '' -C 'runnerconvo@vps-deploy' -q
# Register it from a machine with repo admin access (not from the VPS itself):
#   TMPKEY=$(mktemp); ssh 160.30.5.87 "cat /opt/runnerconvo/.ssh/id_ed25519.pub" > "$TMPKEY"
#   gh repo deploy-key add "$TMPKEY" --repo DDhiya/runnerconvo --title "runnerconvo-vps-deploy"
sudo -u runnerconvo bash -c 'ssh-keyscan -t ed25519 github.com >> /opt/runnerconvo/.ssh/known_hosts'
cat > /opt/runnerconvo/.ssh/config <<'EOF'
Host github.com
    IdentityFile /opt/runnerconvo/.ssh/id_ed25519
    IdentitiesOnly yes
EOF
chown runnerconvo:runnerconvo /opt/runnerconvo/.ssh/config
chmod 600 /opt/runnerconvo/.ssh/config /opt/runnerconvo/.ssh/known_hosts

# Clone + production dependencies.
sudo -u runnerconvo git clone git@github.com:DDhiya/runnerconvo.git /opt/runnerconvo/runnerconvo
cd /opt/runnerconvo/runnerconvo
sudo -u runnerconvo composer install --no-dev --optimize-autoloader --no-interaction

# .env — piped, never echoed, so APP_KEY never lands in shell history. Generated fresh
# on the box; never reuse the local dev APP_KEY.
sudo -u runnerconvo tee /opt/runnerconvo/runnerconvo/.env >/dev/null <<'EOF'
APP_NAME=JubahPanda
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://convo.dhiyadanial.my

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=warning

# SQLite holds the `runners` table and the admin login — see "A small SQLite
# database" above. Absolute path on purpose: it removes any question about what
# php-fpm's CWD is, even though SQLiteConnector would also resolve a relative one.
DB_CONNECTION=sqlite
DB_DATABASE=/opt/runnerconvo/runnerconvo/database/database.sqlite

# These stay file/file/sync even though a database now exists — sessions churn on
# every request, and there is no reason to put that write load on the SQLite file
# for a login used by eight people. See "Sessions, cache and queue stay
# file-backed" below.
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync

# Outbound mail via Resend's HTTP API (443, so no SMTP-port blocking to worry
# about). RESEND_API_KEY is the `laravel-prod` key — see "Mail" below. The
# from-address must be on the verified jubahpanda.my domain or Resend rejects it.
MAIL_MAILER=resend
RESEND_API_KEY=
MAIL_FROM_ADDRESS=support@jubahpanda.my
MAIL_FROM_NAME="JubahPanda"

# Contact + pickup details — see README.md. Renamed from JR_* to JP_* on
# 2026-09-23; config/jubahrunner.php reads the JP_ names only, so an .env
# still carrying JR_ names silently falls back to the config defaults.
JP_WHATSAPP_NUMBER=
JP_INSTAGRAM=
JP_EMAIL=support@jubahpanda.my
# Blank = Register buttons open the in-app form at /register. Set to a wa.me link ONLY as
# a kill switch, then config:cache. See "Registration: close, reopen, kill switch" below.
JP_REGISTER_URL=
JP_REGISTRATION_CLOSES_AT="2026-10-21 23:59"
JP_ADDRESS=
JP_MAP_URL=
EOF
chmod 600 /opt/runnerconvo/runnerconvo/.env
sudo -u runnerconvo php artisan key:generate --force

# Writable runtime dirs. Three now, not two — SQLiteConnector THROWS if the
# database file does not exist rather than creating it, so it must be touched
# before the first `migrate`.
sudo -u runnerconvo touch /opt/runnerconvo/runnerconvo/database/database.sqlite
chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/storage \
                                 /opt/runnerconvo/runnerconvo/bootstrap/cache \
                                 /opt/runnerconvo/runnerconvo/database
chmod -R 775 /opt/runnerconvo/runnerconvo/storage \
             /opt/runnerconvo/runnerconvo/bootstrap/cache
# 750 on database/, not 775: php-fpm (runnerconvo) needs to create the -wal/-shm
# siblings, but www-data only needs to traverse past it to reach public/, never
# read inside it.
chmod 750 /opt/runnerconvo/runnerconvo/database
chmod 600 /opt/runnerconvo/runnerconvo/database/database.sqlite

sudo -u runnerconvo php artisan migrate --force
sudo -u runnerconvo php artisan db:seed --class=RunnerSeeder --force
```

Then create the admin login — this needs a TTY for the password prompt, so `ssh -t`, not the plain
`ssh` used elsewhere in this file:

```bash
ssh -t 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo php artisan jubahpanda:admin you@example.com --name='Your Name'"
```

There is no `ADMIN_PASSWORD` env var and no admin-seeding step in the `.env` heredoc above, on
purpose: `config:cache` (run right after this, below) makes Laravel skip `.env` entirely, so an
env-driven password would read as `null` in production. `jubahpanda:admin` prompts instead, and
doubles as the password-reset path later.

Then, **from your local machine** — `public/build/` is gitignored, so it is never in the clone:

```bash
npm run build
scp -r public/build 160.30.5.87:/tmp/rc-build
ssh 160.30.5.87 "rm -rf /opt/runnerconvo/runnerconvo/public/build \
  && mv /tmp/rc-build /opt/runnerconvo/runnerconvo/public/build \
  && chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/public/build \
  && chmod -R go+rX /opt/runnerconvo/runnerconvo/public/build"
```

The `chmod go+rX` matters: `scp` preserves your local mode bits, and Apache serves these files as
`www-data` (via the `runnerconvo` group). Without it the CSS and JS 403 and the page renders
unstyled.

Back on the VPS as `root` — php-fpm pool, caches, vhost, TLS:

```bash
# php-fpm pool.
scp deploy/runnerconvo-fpm.conf 160.30.5.87:/tmp/runnerconvo-fpm.conf
ssh 160.30.5.87 "mv /tmp/runnerconvo-fpm.conf /etc/php/8.4/fpm/pool.d/runnerconvo.conf \
  && systemctl restart php8.4-fpm && systemctl status php8.4-fpm --no-pager"

# Laravel's production caches. Run AFTER .env is final — config:cache freezes it, and
# env() returns null everywhere outside config/ once cached.
cd /opt/runnerconvo/runnerconvo
sudo -u runnerconvo php artisan config:cache
sudo -u runnerconvo php artisan route:cache
sudo -u runnerconvo php artisan view:cache

# Apache + TLS. Plain :80 vhost first, then certbot rewrites it in place.
scp deploy/runnerconvo.conf 160.30.5.87:/tmp/runnerconvo.conf
ssh 160.30.5.87 "mv /tmp/runnerconvo.conf /etc/apache2/sites-available/runnerconvo.conf \
  && a2ensite runnerconvo && apache2ctl configtest && systemctl reload apache2"
ssh 160.30.5.87 "certbot --apache -d convo.dhiyadanial.my \
  --non-interactive --agree-tos --register-unsafely-without-email --redirect"
```

`--redirect` is what adds the `:80 -> :443` rewrite to `runnerconvo.conf` and generates
`runnerconvo-le-ssl.conf`. Note that Cloudflare already terminates TLS at its edge, so the site
answered on `https://` before certbot ran — the origin cert is what lets Cloudflare's SSL mode be
Full (strict) rather than Flexible, matching every other site on this box.

### DNS is already in place

`convo.dhiyadanial.my` resolves to `104.21.10.71` / `172.67.144.229` — Cloudflare, **the same pair
that `finance`, `fitness` and `bowling` resolve to**. The zone is on Cloudflare nameservers
(`jermaine`/`kimora.ns.cloudflare.com`) and every working site on this box is proxied the same way,
so `certbot --apache` HTTP-01 succeeds through the proxy exactly as it did for those. No
grey-clouding needed, and nothing to change before running certbot.

## The standard deploy

Content or Blade change, no new dependency, no `.env` change, no asset change:

```bash
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo git pull \
  && sudo -u runnerconvo php artisan config:cache \
  && sudo -u runnerconvo php artisan route:cache \
  && sudo -u runnerconvo php artisan view:cache"
```

No php-fpm reload is needed for a Blade or translation change — `view:cache` rewrites the compiled
templates and FPM picks them up per request. **Editing copy in `lang/{en,ms}/landing.php` still
needs `config:cache`/`view:cache`** to take effect, because translations are compiled into the view
cache.

A deploy that includes a new file under `database/migrations/` is not "the standard deploy" — see
the **New migration** bullet below. Run `migrate --force` between `config:cache` and `route:cache`.

## When that is not enough

- **New migration** (a new file under `database/migrations/`):
  `sudo -u runnerconvo php artisan migrate --force` — run it **after** `config:cache` (so the command
  sees the current `DB_DATABASE`) and **before** `route:cache`/`view:cache`. `--force` is required
  because `APP_ENV=production` makes `migrate` prompt for confirmation otherwise. Never run
  `migrate:fresh` or `migrate:refresh` here — see "Do not" below.
- **New Composer dependency** (`composer.lock` changed):
  `sudo -u runnerconvo composer install --no-dev --optimize-autoloader` before the cache commands,
  then `systemctl reload php8.4-fpm` to clear opcache.
- **Frontend change** (anything under `resources/css`, `resources/js`, or any Blade file that
  introduces a new Tailwind class): rebuild locally and re-sync, using the `npm run build` + `scp`
  block from the bootstrap above. Tailwind scans Blade files, so **a Blade-only change can still
  require a rebuild** — if a class you just used renders unstyled in production, this is why.
- **`.env` change**: edit it by hand over SSH — `git pull` never touches it — then
  `php artisan config:cache` again. Skipping that is the single most common way a config change
  appears to do nothing.
- **Registration: close, reopen, kill switch** — all `.env` edits plus `config:cache`, no deploy:
  - *Close on a date:* `JP_REGISTRATION_CLOSES_AT="2026-10-21 23:59"` (Kuala Lumpur time). After it,
    `/register` shows a "closed" card and rejects POSTs. A past time closes it immediately; blank
    means open indefinitely.
  - *Kill switch:* `JP_REGISTER_URL=https://wa.me/<number>` points every Register button back at
    WhatsApp instantly. It does **not** hide `/register` — anyone holding the direct link can still
    register until the closing date passes, so set that to a past time as well if that matters.
  - *"Opens soon":* `/register` also shows this until faculties, robe sizes and convocation
    sessions each have at least one active entry (`/admin/options/...`).
- **Vhost changed** (`deploy/runnerconvo.conf`): careful — certbot has rewritten the installed copy
  and generated `runnerconvo-le-ssl.conf` beside it. Re-copying the repo version **discards
  certbot's redirect block**. Apply the change by hand to both installed files instead, or re-copy
  and re-run `certbot --apache`.
- **php-fpm pool changed** (`deploy/runnerconvo-fpm.conf`): copy to
  `/etc/php/8.4/fpm/pool.d/runnerconvo.conf` and `systemctl restart php8.4-fpm`.

## Sanity-check after deploying

```bash
ssh 160.30.5.87 "systemctl status php8.4-fpm apache2 --no-pager | head -20"
ssh 160.30.5.87 "ls -l /run/php/php8.4-fpm-runnerconvo.sock"   # srw-rw---- www-data www-data
curl -sI https://jubahpanda.my/                                 # 200, after certbot
curl -s  https://jubahpanda.my/ | grep -o '<html lang="[^"]*"'   # lang="en" (default; see SetLocale)
curl -s  https://jubahpanda.my/lang/ms -o /dev/null -w '%{http_code}\n'  # 302

# Runner directory + admin (after the SQLite deploy below).
curl -s  https://jubahpanda.my/ | grep -c 'wa.me/60'                     # >= 8
curl -s  https://jubahpanda.my/ | grep -o 'id="runners"'                 # present
curl -sI https://jubahpanda.my/database/database.sqlite | head -1        # 404 — docroot is public/
curl -sI https://jubahpanda.my/admin | head -1                           # 302 to /admin/login, NOT 401

# Registration form (after the bookings deploy below).
curl -sI https://jubahpanda.my/register | head -1                        # 200
curl -s  https://jubahpanda.my/register | grep -c 'contact_me_by_fax_only'   # 1 (the honeypot) - only once options exist; until then the page says "opens soon"
curl -s  -o /dev/null -w '%{http_code}\n' -X POST https://jubahpanda.my/register   # 302 back to /register (no CSRF token: the expired-token handler)
curl -sI https://jubahpanda.my/register/done | grep -i location          # -> /register (no session)
curl -sI https://jubahpanda.my/admin/bookings | head -1                  # 302 to /admin/login
curl -sI https://jubahpanda.my/privacy | head -1                         # 200
```

A bare `401` on that last check means the `redirectGuestsTo`/`redirectUsersTo` configuration in
`bootstrap/app.php`'s `withMiddleware()` callback did not take effect — check that first.

Also check `www.jubahpanda.my` and `convo.dhiyadanial.my` the same way — all three are aliases on
one vhost and cert, so a break in the vhost config affects all three at once even though only one
is canonical.

Then check in a real browser, not just curl:

- The page renders **styled** — unstyled means `public/build/` did not sync.
- The **BM/EN toggle** flips the page and the choice survives a refresh (this is the only thing on
  the site that needs working sessions and a writable `storage/`).
- `https://jubahpanda.my/.env` returns **404**, not the file. If it ever returns content, the
  DocumentRoot is wrong — it must point at `public/`, not the repo root.
- `APP_DEBUG=false` is doing its job: a deliberate 404 shows Laravel's plain error page, not a
  stack trace.
- Log in at `/admin`, rename a runner, and confirm the landing page shows the new name immediately —
  there is no caching layer between the `runners` table and the page, by design.

Confirm the four existing services are completely unaffected — this deploy touches Apache's global
config (`a2enmod proxy_fcgi`) and adds a PPA, which are the only two things here with any blast
radius beyond this app:

```bash
ssh 160.30.5.87 "systemctl status myfinance-api myfinance-web myfitness-web dhiyaagent --no-pager | grep -E 'Active:|●'"
curl -sI https://finance.dhiyadanial.my/ | head -1
curl -sI https://fitness.dhiyadanial.my/ | head -1
```

## Adding the runner database and admin (2026-09-24)

The box was already live with no database (see "A small SQLite database" above for the end state).
This is the one-time deploy that got it there, done in **two passes** rather than one — the read
path (SQLite + the public "Our runners" section) proven working before the write path (the `/admin`
login) went anywhere near a public-facing box:

**Pass 1 — read path:**

```bash
# 1. SQLite PDO driver.
ssh 160.30.5.87 "apt install -y php8.4-sqlite3 && systemctl restart php8.4-fpm"
ssh 160.30.5.87 "php8.4 -m | grep -iE 'pdo_sqlite|sqlite3'"          # both must print

# 2. Create the file, set ownership/permissions — see "Writable runtime dirs" above
#    for why (SQLiteConnector throws rather than creating it).
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo touch database/database.sqlite \
  && chmod 750 database && chmod 600 database/database.sqlite"

# 3. Add DB_CONNECTION/DB_DATABASE to .env by hand — see the heredoc above.

# 4. Pull, re-cache config FIRST so migrate sees the new DB config, then migrate + seed.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo git pull \
  && sudo -u runnerconvo php artisan config:cache \
  && sudo -u runnerconvo php artisan migrate --force \
  && sudo -u runnerconvo php artisan db:seed --class=RunnerSeeder --force"

# 5. Assets — LOCAL machine, same npm run build + scp block as the bootstrap above.
#    The new section's Tailwind classes (lg:grid-cols-4, h-14 w-14, …) are not in
#    the previously-synced public/build/.

# 6. Remaining caches.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo php artisan route:cache && sudo -u runnerconvo php artisan view:cache"
```

Verify pass 1 with the runner-directory curl checks in "Sanity-check after deploying" above before
touching pass 2.

**Pass 2 — write path (the admin):** ordinary `git pull` + cache-rebuild (`## The standard deploy`)
covers the new routes and views. A fresh `npm run build` + `scp` is needed again — the admin views
pull in a different set of Tailwind utilities (tables, form inputs) than pass 1's public section.
Then create the admin account:

```bash
ssh -t 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo php artisan jubahpanda:admin you@example.com --name='Your Name'"
```

## Adding the registration form (2026-09-25)

One code deploy that ships the form **unlinked**, so it can be tested live before any button points
at it. Nothing public changes until the flip. Full reasoning: `docs/registration-form-plan.md`.

```bash
# 0. Pre-flight: is mod_remoteip already rewriting REMOTE_ADDR? Either answer is fine
#    (the app trusts Cloudflare's ranges itself); just know which it is.
ssh 160.30.5.87 "apache2ctl -M 2>/dev/null | grep -i remoteip || echo 'not loaded'"

# 1. Backup NOW, before the first migration that touches data worth keeping.
ssh 160.30.5.87 "apt install -y sqlite3 && install -d -o runnerconvo -g runnerconvo -m 700 /var/backups/jubahpanda \
  && sudo -u runnerconvo sqlite3 /opt/runnerconvo/runnerconvo/database/database.sqlite \
     \".backup /var/backups/jubahpanda/pre-bookings.sqlite\""

# 2. .env by hand: pin the Register buttons to their CURRENT target so this deploy changes
#    nothing publicly, and set the closing date and contact address.
#      JP_REGISTER_URL=https://wa.me/<JP_WHATSAPP_NUMBER>
#      JP_REGISTRATION_CLOSES_AT="2026-10-21 23:59"
#      JP_EMAIL=support@jubahpanda.my

# 3. Pull, config FIRST so migrate sees current config, migrate, then the other caches.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo git pull \
  && sudo -u runnerconvo php artisan config:cache \
  && sudo -u runnerconvo php artisan migrate --force \
  && sudo -u runnerconvo php artisan route:cache \
  && sudo -u runnerconvo php artisan view:cache"

# 4. Assets — LOCAL machine. The form, radio cards, admin tables and nav are all new
#    Tailwind utilities; skipping this renders /register unstyled. Same block as the bootstrap.

# 5. Cron: nightly backup + the one-year retention purge.
scp deploy/jubahpanda.cron 160.30.5.87:/tmp/jubahpanda.cron
ssh 160.30.5.87 "install -m 644 -o root -g root /tmp/jubahpanda.cron /etc/cron.d/jubahpanda && rm /tmp/jubahpanda.cron"
```

**Enter the option lists.** Log in and fill in `/admin/options/faculty`, `robe_size` and
`convocation_session` in both languages. Until all three have an active entry `/register` shows
"opens soon" — intended, and it means this can happen before or after the steps above.

**Test live while unlinked.** Open `https://jubahpanda.my/register` on a phone, in both languages.
Submit a booking with an obviously fake matric that still fits the format (`ZZ99001`), find it in
`/admin/bookings`, walk it through every status, export a CSV, then **delete it**. Also try deleting
an option that booking used *before* deleting the booking: it should refuse.

**Flip.** Blank `JP_REGISTER_URL=` in `.env` and `config:cache`. The five Register buttons now open
`/register`. **Undo** is the same edit in reverse (see "Registration: close, reopen, kill switch").

The `support@jubahpanda.my` mailbox must work before the flip: the privacy notice names it as a
contact for access and correction requests.

### Sessions, cache and queue stay file-backed

Explicitly **do not** switch `SESSION_DRIVER` to `database` just because a database now exists. The
`web` guard's session payload stores only a user id — the storage backend is irrelevant to whether
login works — and `storage/framework/sessions` is already writable and already exercised by the
BM/EN toggle. Moving sessions onto SQLite would put per-request write load on a file that otherwise
sees only the occasional admin edit, for zero benefit to a login used by one team of eight.

### FPM's `disable_functions` is irrelevant here

`deploy/runnerconvo-fpm.conf` blocks `exec,passthru,shell_exec,system,proc_open,popen`. None of this
affects SQLite: PDO SQLite is a native extension, not a shell-out, and every `php artisan` command
above runs under the CLI SAPI (`/etc/php/8.4/cli/php.ini`), which that pool config does not touch. It
does matter for the backup method below — hence backing up from a root shell, not from PHP.

## Backing up the database

`database/database.sqlite` is the only state on this box that `git pull` cannot reconstitute (see "A
small SQLite database" above). **Backups are no longer optional**: the file holds every graduate's
booking, and a lost database is a lost intake.

**Nightly, on the box:** `deploy/jubahpanda.cron` (installed as `/etc/cron.d/jubahpanda`) takes an
atomic `.backup` at 03:15 into `/var/backups/jubahpanda/`, keeps 14 days, and runs the retention
purge at 03:30. It runs as `runnerconvo`, **not root** — a root `sqlite3` on a WAL database can create
root-owned `-wal`/`-shm` files, after which php-fpm fails every write with "attempt to write a
readonly database".

```bash
# One-time.
ssh 160.30.5.87 "apt install -y sqlite3 && install -d -o runnerconvo -g runnerconvo -m 700 /var/backups/jubahpanda"
scp deploy/jubahpanda.cron 160.30.5.87:/tmp/jubahpanda.cron
ssh 160.30.5.87 "install -m 644 -o root -g root /tmp/jubahpanda.cron /etc/cron.d/jubahpanda && rm /tmp/jubahpanda.cron"

# By hand, before anything risky (a migration, a bulk edit). .backup is atomic even if a write
# is in progress; a plain `cp` can copy a torn file mid-write and is not safe here.
ssh 160.30.5.87 "sudo -u runnerconvo sqlite3 /opt/runnerconvo/runnerconvo/database/database.sqlite \
  \".backup /var/backups/jubahpanda/manual-\$(date +%F-%H%M).sqlite\""
```

**Off-box copy — and where NOT to put it.** A copy on the same VPS doesn't survive losing the VPS,
so pull one to another machine now and then. **Do not copy it into a OneDrive-synced folder.** This
repo's working copy lives under `OneDrive - UMPSA\...`, so the old `scp ... ./backups/` would sync
graduates' matric and phone numbers into the university's OneDrive tenant. (`*.sqlite` is
gitignored, but that doesn't stop OneDrive.) Use a local, non-synced path:

```bash
scp 160.30.5.87:/var/backups/jubahpanda/jubahpanda-2026-10-01.sqlite "C:/Users/<you>/Backups/"
```

**Restore drill** — do it once, before you need it, on a copy:

```bash
sqlite3 copy.sqlite "PRAGMA integrity_check; SELECT count(*) FROM bookings;"     # ok, then a count
```

To restore for real: stop writes (`php artisan down`), replace `database.sqlite` with the copy
(owner `runnerconvo`, mode 600, delete any stale `-wal`/`-shm`), then `php artisan up`.

## Personal data

`bookings` holds names, matric numbers, WhatsApp numbers and (for COD) Kuantan addresses — personal
data under Malaysia's PDPA 2010. No IC numbers, no uploaded documents: the authorisation letter goes
over WhatsApp, never to this box. The notice graduates agree to is `/privacy` (both languages);
each booking records `consented_at` and the `privacy_version` they agreed to. **Bump
`jubahrunner.privacy_version`** whenever `lang/*/privacy.php` changes materially.

- **Who can see it:** every admin account sees every booking — there are no roles. Keep the number
  of accounts to the people who actually handle bookings.
- **Removing someone's access:** `sudo -u runnerconvo php artisan tinker --execute="App\Models\User::where('email','x@y.z')->delete();"`
- **Who took a copy:** every CSV export writes `bookings.exported` (user, filters, row count — no
  personal data) to `storage/logs/laravel.log`: `grep bookings.exported storage/logs/laravel*.log`.
- **Erasure / consent withdrawal:** `/admin/bookings/<reference>` → "Delete permanently". Cancelling
  keeps the row; only deleting erases it. Backups may hold a copy for up to 14 more days (the
  notice says so).
- **Retention:** one year from registration (`jubahrunner.retention_days`), enforced nightly by
  `php artisan jubahpanda:purge-bookings --force` from cron. Check what it would do with
  `--dry-run`. The first real deletions happen in October 2027 — the cron job exists from now so
  nobody has to remember.
- **If the database leaks:** the 2024 PDPA amendments (in force 2025) require notifying the Personal
  Data Protection Commissioner within 72 hours, and affected individuals without undue delay where
  significant harm is likely. Check the Commissioner's current guidelines at the time. Start with
  the export log and Apache's access log to establish what left and when, and rotate every admin
  password with `jubahpanda:admin`.

## Mail (2026-09-25)

There is no mail server on this box and there should never be one. Mail is two hosted services,
both configured in dashboards rather than in this repo:

| Direction | Service | What it does |
|---|---|---|
| Inbound | **Cloudflare Email Routing** (free) | Forwards `support@jubahpanda.my` to the team's Gmail inbox. No mailbox of its own. |
| Outbound | **Resend** (free tier, ~3,000/month, 100/day) | Sends as `support@` for both the Laravel app and Gmail "Send mail as". |

**DNS, all in the Cloudflare zone.** Each service owns its own names, so neither can break the other:

| Name | Type | Owner |
|---|---|---|
| `@` | 3× MX `route{1,2,3}.mx.cloudflare.net` | Email Routing — added by the dashboard, do not hand-edit |
| `@` | TXT `v=spf1 include:_spf.mx.cloudflare.net ~all` | Email Routing |
| `cf2024-1._domainkey` | TXT (DKIM) | Email Routing |
| `send` | MX + TXT `v=spf1 include:amazonses.com ~all` | Resend (bounce/return-path) |
| `resend._domainkey` | TXT (DKIM) | Resend — this is what makes outbound pass DMARC |
| `_dmarc` | TXT `v=DMARC1; p=none; …` | Us. Move to `p=quarantine` once a few weeks of reports show only passing mail. |

**Routing rules** live at Cloudflare → jubahpanda.my → Email → Email Routing. Delivery attempts show
up in its Activity log — check there first if mail "didn't arrive". Test from an address *other*
than the destination Gmail: Gmail silently hides mail you sent to yourself that comes back through
forwarding, so a self-test looks like a failure when it isn't.

**Two Resend API keys, both "Sending access" scoped to jubahpanda.my**, so either can be revoked
without breaking the other:

- `gmail-send-as` — typed into Gmail → Settings → Accounts → "Send mail as" (`smtp.resend.com`,
  port 465, username `resend`, password = the key). Never in any `.env`.
- `laravel-prod` — `RESEND_API_KEY` in the VPS `.env` only (see the heredoc above), read through
  `config/services.php`. The `resend` transport needs `resend/resend-php`, already in
  `composer.json`. Local `.env` stays `MAIL_MAILER=log` with no key.

Changing any of the `MAIL_*`/`RESEND_*` values is an ordinary `.env` change: edit, then
`config:cache`. To prove the app can send:

```bash
ssh -t 160.30.5.87
cd /opt/runnerconvo/runnerconvo && sudo -u runnerconvo php artisan tinker
>>> Mail::raw('Resend test', fn ($m) => $m->to('you@example.com')->subject('Test'));
```

As of 2026-09-25 no app code sends mail — that works, but nothing uses it yet. The first user will
be a booking confirmation (README, "Next steps").

**Resend suspended the account minutes after signup** — an automated check on new accounts. It was
reinstated after the review form was filled in honestly (low volume, support replies + transactional
only, no marketing, no lists). If it ever happens again and review fails, **Brevo** or **Amazon SES**
drop in the same way: records on a subdomain plus a DKIM name, then a new SMTP host / key in Gmail
and `.env`. Inbound is unaffected either way.

**Moving to a real mailbox later** (Zoho, Google Workspace) means turning Email Routing *off* first —
it owns the root MX records, and the two cannot share them.

## Domain history: convo.dhiyadanial.my → jubahpanda.my

The real domain is **jubahpanda.my** (not `jubahrunner.my`, which this file used to guess at and
was never registered). Registered at Namecheap, zone on Cloudflare — `jermaine`/`kimora.ns`, the
same pair every other site here uses.

This happened in two steps, a few hours apart on 2026-09-23, because DNS propagation outran the
plan: `jubahpanda.my` started resolving through Cloudflare to this box's IP *before* Apache knew
about it, which meant it was being served by **the admin app** — Apache's default vhost here is
`admin.dhiyadanial.my` (it sorts first in `sites-enabled`), and any hostname pointed at this IP
without a matching `ServerName`/`ServerAlias` falls through to whatever `apache2ctl -S`'s first
`default server` line names. So step 1 was purely defensive — claim the hostname immediately, even
before TLS or canonical status were sorted out:

```bash
# Alias it on :80 first — no cert needed for that, and it stops the admin app leaking
# the wrong content the moment DNS resolves.
ssh 160.30.5.87 "sed -i '/ServerName convo.dhiyadanial.my/a\\    ServerAlias jubahpanda.my www.jubahpanda.my' \
  /etc/apache2/sites-available/runnerconvo.conf && apache2ctl configtest && systemctl reload apache2"

# Expand the existing cert lineage to cover all three names. --expand replaces the
# lineage only on success, so a failure leaves the working convo cert untouched.
ssh 160.30.5.87 "certbot certonly --apache --cert-name convo.dhiyadanial.my \
  -d convo.dhiyadanial.my -d jubahpanda.my -d www.jubahpanda.my \
  --expand --non-interactive --agree-tos"

# Alias :443 too, only after the cert covers the name — doing this first would have
# Cloudflare Full (strict) hit a cert that doesn't cover the hostname and serve a 526.
ssh 160.30.5.87 "sed -i '/ServerName convo.dhiyadanial.my/a\\    ServerAlias jubahpanda.my www.jubahpanda.my' \
  /etc/apache2/sites-available/runnerconvo-le-ssl.conf \
  && apache2ctl configtest && systemctl reload apache2"
```

Step 2, once `jubahpanda.my` was confirmed working as an alias, made it canonical — swap
`ServerName` and `ServerAlias` in **both** vhosts (`convo.dhiyadanial.my` becomes the alias),
point `APP_URL` at it, and re-cache:

```bash
ssh 160.30.5.87 "sed -i \
  -e 's/ServerName convo.dhiyadanial.my/ServerName jubahpanda.my/' \
  -e 's/ServerAlias jubahpanda.my www.jubahpanda.my/ServerAlias www.jubahpanda.my convo.dhiyadanial.my/' \
  /etc/apache2/sites-available/runnerconvo.conf \
  /etc/apache2/sites-available/runnerconvo-le-ssl.conf \
  && apache2ctl configtest && systemctl reload apache2"

ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo sed -i 's|^APP_URL=.*|APP_URL=https://jubahpanda.my|' .env \
  && sudo -u runnerconvo php artisan config:cache"
```

`APP_URL` is less load-bearing than it looks — canonical/`og:url` come from the request host, so
every alias already self-describes correctly regardless of `APP_URL`. It matters for URLs generated
off-request (CLI, mail), and for which name Apache's `:80` redirect sends *unmatched* requests to
(anything hitting this vhost by IP with no recognized `Host` falls back to `ServerName`).

**One thing step 2 broke that is easy to miss**: certbot's original `--redirect` (run back when
`convo.dhiyadanial.my` was the only name) wrote an HTTP→HTTPS `RewriteCond` matching that one
hostname by name, not "whatever `ServerName` currently is". Swapping `ServerName` did not update
it, so plain `http://jubahpanda.my` stopped redirecting to HTTPS at the origin — invisible if
Cloudflare's SSL/TLS mode is Full (strict), since Cloudflare then always speaks HTTPS to the
origin regardless of what the visitor requested, but wrong at the origin all the same. Fixed by
replacing the single `RewriteCond` with an OR chain covering all three names:

```apache
RewriteEngine on
RewriteCond %{SERVER_NAME} =jubahpanda.my [OR]
RewriteCond %{SERVER_NAME} =www.jubahpanda.my [OR]
RewriteCond %{SERVER_NAME} =convo.dhiyadanial.my
RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
```

This block is certbot-managed (see the note at the top of `deploy/runnerconvo.conf`) and is not
part of the committed vhost, so nothing here needed a repo change — but re-check it by hand after
any future `ServerName` swap; certbot will not do it for you.

Verify a domain switch the same way this one was checked, from off-box (a plain `curl` only proves
Cloudflare answered) and from the origin directly:

```bash
for h in jubahpanda.my www.jubahpanda.my convo.dhiyadanial.my; do
  curl -sI https://$h/ | head -1                                              # 200, not 526
  curl -sI http://$h/  | grep -i location                                     # https://$h/, not another host
  ssh 160.30.5.87 "curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: $h' http://127.0.0.1/"
done
curl -s https://jubahpanda.my/ | grep -oE '<link rel="canonical"[^>]*>|<meta property="og:url"[^>]*>'
```

`deploy/runnerconvo.conf` in this repo carries the canonical `ServerName`/`ServerAlias`, kept in
sync with the installed copy by hand — **no application code changes**, which is the entire point
of keeping the hostname out of the codebase.

Keep `convo.dhiyadanial.my` resolving and aliased indefinitely; anything already shared in a
WhatsApp group still points there, and it costs nothing to keep answering on the same cert.

## Do not

- **Do not point DocumentRoot at the repo root.** Only `public/` is web-facing. Getting this wrong
  exposes `.env`.
- **Do not commit `.env` or `public/build/`.** Both are gitignored; `.env` has never been in this
  repo's history and must stay that way.
- **Do not run `composer install` without `--no-dev`** in production — it pulls PHPUnit and friends
  onto a public-facing box for no reason.
- **Do not `git push --force`** or otherwise break the VPS's read-only deploy key's ability to keep
  pulling cleanly — same reasoning the other three runbooks give.
- **Do not upgrade the box's Node** to make the build work on the VPS. Other apps depend on v18.
  Build locally and `scp`.
- **Do not install `php8.3-*`.** See the top of this file.
- **Do not commit `database/database.sqlite`.** Gitignored at both the root (`*.sqlite`) and
  `database/.gitignore` (`*.sqlite*`) — belt and braces on purpose.
- **Do not run `migrate:fresh` or `migrate:refresh` in production.** Both drop the `runners` table
  and the admin `users` row along with it.
- **Do not copy the database or a CSV export into OneDrive, WhatsApp, email or a shared drive.** They
  contain graduates' personal data. See "Backing up the database" and "Personal data".
- **Do not run `sqlite3` against the live file as root** (or `sudo sqlite3` without `-u runnerconvo`):
  it can leave root-owned `-wal`/`-shm` files that break every write from php-fpm.
- **Do not delete `/etc/cron.d/jubahpanda` to "clean up".** It is the nightly backup and the
  retention purge, and nothing else runs either of them.
- **Do not try to delete a booking option that bookings use** — deactivate it instead (the admin
  refuses the delete anyway). Renaming is fine and shows up on every booking that uses it.
- **Do not run `db:seed` without `--class=`.** The bare `DatabaseSeeder` used to create a
  `test@example.com` user with a known factory password; it no longer does (see `DatabaseSeeder`),
  but always name the seeder explicitly on a production box regardless.
- **Do not hand-edit the root MX or SPF records** on jubahpanda.my. Email Routing owns them; Resend's
  records live on `send.` and `resend._domainkey` precisely so they don't need to. See "Mail".
- **Do not put `RESEND_API_KEY` in the local `.env` or `.env.example`.** It lives in the VPS `.env`
  only, and local development stays on `MAIL_MAILER=log`.
