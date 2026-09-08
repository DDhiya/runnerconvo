# Deployment

Read this when you are actually deploying. Mirrors `../myfinance/docs/deploy.md` and
`../myfitness/docs/deploy.md` in shape on purpose — same VPS, same conventions, easiest to keep
every runbook on this box in your head at once. **Where it deviates from those two it says so and
why**, because this is the first PHP app on the box and several of their assumptions do not carry
over.

**Not yet deployed.** Everything below is the plan to execute on first deploy, not a description of
a running instance — update this file's language once it actually is live (see
`../myfinance/docs/deploy.md`'s "This is live" framing for what that should look like afterward).

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

## The live instance (once deployed)

Same Ubuntu 24.04 VPS as `dhiya_agent`/`myfinance`/`myfitness` (`160.30.5.87`), running as its own
unprivileged system user `runnerconvo`, no systemd unit, temporary public hostname
`convo.dhiyadanial.my`.

Code at `/opt/runnerconvo/runnerconvo` (a single checkout; the Vite build output in
`public/build/` is synced separately — see below). The VPS clones this repo over SSH using a
**read-only deploy key**, generated on the VPS as the `runnerconvo` user and registered on the
GitHub repo with write access off — mirrors the other three apps' keys exactly, but is its own key,
never shared with any of them.

### The hostname is deliberately not hardcoded

`convo.dhiyadanial.my` is temporary; the team moves to a root domain (probably `jubahrunner.my`)
later. The hostname therefore appears in exactly **two** places, both outside application code:

1. `ServerName` in `deploy/runnerconvo.conf` — the only occurrence in this repo.
2. `APP_URL` in `/opt/runnerconvo/runnerconvo/.env` — VPS only, never in git.

No Blade template or translation string contains it. `config/jubahrunner.php` holds contact details
(WhatsApp, Instagram, registration form, email) but no site hostname — its one domain-shaped value
is the `hello@jubahrunner.my` fallback for `JR_EMAIL`, a contact address, not the URL the app serves
itself on. Every value there is `env()`-overridable from `.env`. See "Switching to the real domain"
at the bottom.

### `.env` location — a deliberate deviation

The other three apps keep `.env` at `/opt/<app>/.env`, *outside* the checkout, because systemd
loads it via `EnvironmentFile`. **Laravel reads `.env` from its own base path**, so here it lives at
`/opt/runnerconvo/runnerconvo/.env` — inside the checkout. This is safe: `.env` is gitignored (and
has never been committed — verified), so `git pull` never touches it, and `public/` is the docroot
so the file is not web-reachable. Still `chmod 600`, owned by `runnerconvo`.

### No database

The landing page reads and writes nothing. Sessions (used only by the BM/EN language toggle) and
cache go to files; queues run `sync`. **Do not provision MySQL for this.** MySQL 8.0.46 is already
running on the box for when the registration form lands — that is a future deploy, not this one.

## Prerequisites on the box (none of this exists yet)

```bash
# PHP 8.4 — see "PHP 8.4 is required" above for why 8.3 is not an option.
add-apt-repository -y ppa:ondrej/php && apt update
apt install -y php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
               php8.4-zip php8.4-intl php8.4-bcmath
# php8.4-mysql is NOT needed yet — add it when the registration form lands.

# Composer.
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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
APP_NAME=JubahRunner
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://convo.dhiyadanial.my

APP_LOCALE=ms
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=warning

# No database. Sessions back the BM/EN toggle only.
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync

# TODO before launch — see README.md
JR_WHATSAPP_NUMBER=
JR_INSTAGRAM=
JR_EMAIL=
JR_REGISTER_URL=
EOF
chmod 600 /opt/runnerconvo/runnerconvo/.env
sudo -u runnerconvo php artisan key:generate --force

# Writable runtime dirs (the only two Laravel needs).
chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/storage \
                                 /opt/runnerconvo/runnerconvo/bootstrap/cache
chmod -R 775 /opt/runnerconvo/runnerconvo/storage \
             /opt/runnerconvo/runnerconvo/bootstrap/cache
```

Then, **from your local machine** — `public/build/` is gitignored, so it is never in the clone:

```bash
npm run build
scp -r public/build 160.30.5.87:/tmp/rc-build
ssh 160.30.5.87 "rm -rf /opt/runnerconvo/runnerconvo/public/build \
  && mv /tmp/rc-build /opt/runnerconvo/runnerconvo/public/build \
  && chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/public/build"
```

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
ssh 160.30.5.87 "certbot --apache -d convo.dhiyadanial.my"
```

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

## When that is not enough

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
curl -sI https://convo.dhiyadanial.my/                          # 200, after certbot
curl -s  https://convo.dhiyadanial.my/ | grep -o '<html lang="[^"]*"'   # lang="ms"
curl -s  https://convo.dhiyadanial.my/lang/en -o /dev/null -w '%{http_code}\n'  # 302
```

Then check in a real browser, not just curl:

- The page renders **styled** — unstyled means `public/build/` did not sync.
- The **BM/EN toggle** flips the page and the choice survives a refresh (this is the only thing on
  the site that needs working sessions and a writable `storage/`).
- `https://convo.dhiyadanial.my/.env` returns **404**, not the file. If it ever returns content, the
  DocumentRoot is wrong — it must point at `public/`, not the repo root.
- `APP_DEBUG=false` is doing its job: a deliberate 404 shows Laravel's plain error page, not a
  stack trace.

Confirm the four existing services are completely unaffected — this deploy touches Apache's global
config (`a2enmod proxy_fcgi`) and adds a PPA, which are the only two things here with any blast
radius beyond this app:

```bash
ssh 160.30.5.87 "systemctl status myfinance-api myfinance-web myfitness-web dhiyaagent --no-pager | grep -E 'Active:|●'"
curl -sI https://finance.dhiyadanial.my/ | head -1
curl -sI https://fitness.dhiyadanial.my/ | head -1
```

## Switching to the real domain (jubahrunner.my)

`jubahrunner.my` does not resolve yet. When it does, the whole switch is:

```bash
# 1. Point the domain at Cloudflare, add an A record for 160.30.5.87 (proxied, same as
#    every other site on this box). Confirm before touching the VPS:
dig +short A jubahrunner.my

# 2. One-line ServerName change in the installed vhost AND its -le-ssl twin.
ssh 160.30.5.87 "sed -i 's/convo\.dhiyadanial\.my/jubahrunner.my/' \
  /etc/apache2/sites-available/runnerconvo.conf \
  /etc/apache2/sites-available/runnerconvo-le-ssl.conf && apache2ctl configtest"

# 3. New cert covering both apex and www.
ssh 160.30.5.87 "certbot --apache -d jubahrunner.my -d www.jubahrunner.my"

# 4. APP_URL, then re-cache.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo sed -i 's|^APP_URL=.*|APP_URL=https://jubahrunner.my|' .env \
  && sudo -u runnerconvo php artisan config:cache"
```

Also update `ServerName` in this repo's `deploy/runnerconvo.conf` so the committed copy matches
reality. **No application code changes** — that is the entire point of keeping the hostname out of
the codebase.

Keep `convo.dhiyadanial.my` resolving and its cert alive for a while after the switch; anything
already shared in a WhatsApp group still points there.

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
