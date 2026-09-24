# Runner directory: SQLite, a public "Our runners" section, and a small admin

> Implementation plan. Written 2026-09-23 against commit `12eb667`. Nothing in this plan has been
> implemented yet — every file listed as "New" still needs creating.

## Context

JubahPanda's landing page is currently a pure static marketing page with **one** hardcoded contact
person. `config/jubahrunner.php` reads `JP_WHATSAPP_NUMBER` from `.env` and ten Blade call sites turn
it into `wa.me` links. There is no database in use anywhere, no auth, no admin, no controllers.

The team is actually eight people. We want all eight listed on the site, each reachable on WhatsApp,
and we want to change a name or number later without a git commit and a deploy. That means real
persistence and an edit UI — so this change introduces the project's first database, first
authentication endpoint, and first migration.

On the `.env` question that prompted this: **`.env` is already gitignored** (`.gitignore:3`; `git ls-files`
confirms it was never committed) and `database/.gitignore` already contains `*.sqlite*`. SQLite also has
no credentials — only a file path. So that part is largely settled; the plan covers the real gaps
instead (prod `.env` DB keys, `.env.example`, a defence-in-depth root ignore, and backups).

**Decisions already made:** new "Our runners" section on the landing page; password-protected admin
page for editing; full production rollout included; **split into two deploys**; **nav + footer link added**.

---

## Verified constraints that shape the work

Four things checked against the vendor tree and the runbook — each would cost an hour if found late:

1. **`ApplicationBuilder::withMiddleware()` sets a default `redirectGuestsTo(route('login'))`** — a route
   this app doesn't have. **Implemented fix differs from the original plan**: this must be overridden
   inside `bootstrap/app.php`'s own `withMiddleware()` callback (`$middleware->redirectGuestsTo(...)` /
   `redirectUsersTo(...)`), **not** in `AppServiceProvider::boot()`. Both the default and any override set
   from a service provider write to the same static property on `Authenticate`/`RedirectIfAuthenticated`,
   but the *order* of "default vs. provider boot" differs between a real request (the HTTP kernel is
   resolved before providers boot, so a provider override wins) and the test environment
   (`createApplication()` bootstraps providers via the **console** kernel first, then the HTTP kernel is
   resolved lazily on the first `$this->get()`, re-applying the framework default and silently clobbering
   the provider's override). Setting it inside `bootstrap/app.php`'s `withMiddleware()` closure runs in
   the same `afterResolving(HttpKernel)` callback as the default, in a fixed order, in both environments.
   This was caught by `AdminAuthTest::test_guests_are_redirected_to_the_login_page`, which failed with
   `RouteNotFoundException: Route [login] not defined` under the original provider-based approach.
2. ~~`RedirectIfAuthenticated::defaultRedirectUri()` scans for `dashboard` then `home`~~ — same root cause
   and same fix as #1: `redirectUsersTo()` in `bootstrap/app.php` covers this directly.
3. **`app/Console/Commands/` is not auto-discovered here.** `bootstrap/app.php:12` passes
   `commands: routes/console.php` — a *file*, which registers as a command *route* path, not a command
   *directory*. A new command class silently will not exist until `->withCommands()` is added.
4. **`config:cache` makes Laravel skip `.env` entirely**, and `docs/deploy.md` runs it on every deploy.
   So `env()` in a seeder returns `null` in prod — which rules out an env-driven admin password.

Good news, confirmed: `phpunit.xml:26-31` already sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
`SESSION_DRIVER=array`, `CACHE_STORE=array` — **no phpunit config changes needed**. And `User`'s
`hashed` cast guards with `Hash::isHashed()`, so passing a plaintext password to `User::create()` hashes
exactly once.

---

## Phase 1 — Data layer

**New** `database/migrations/2026_09_23_000000_create_runners_table.php`

```php
Schema::create('runners', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    // Digits only, international, no "+" — same convention as JP_WHATSAPP_NUMBER.
    $table->string('phone', 20)->unique();
    // Display order. Deliberately NOT unique: reordering is a two-row swap, and a
    // unique index would force a temporary sentinel value to dodge a collision.
    $table->unsignedSmallInteger('position')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['is_active', 'position']);
});
```

`phone` is a string, not an integer — it is an opaque identifier, and a string makes the `unique` rule
and the digits-only invariant natural. No `SoftDeletes`: `is_active` is the deactivate we want, and a
soft-deleted row would still hold its number under the unique index, which reads as a bug.

**New** `app/Models/Runner.php` — match the `#[Fillable]` attribute idiom already used in
`app/Models/User.php:13`.

- `#[Fillable(['name', 'phone', 'position', 'is_active'])]`, `casts()` for `is_active`/`position`.
- `#[Scope] active()` → `where('is_active', true)`.
- `#[Scope] ordered()` → `orderBy('position')->orderBy('id')` (the id tiebreaker keeps order
  deterministic; SQLite gives no stable order otherwise).
- `whatsappUrl` accessor → `'https://wa.me/'.$this->phone`. Keeps the one URL string in one place
  and is directly testable — do not build it in Blade.
- `public static function normalisePhone(string $value): string` — strip non-digits, then map a
  leading `0` to `60`. So `+60 14-533 2637` and `014-533 2637` both become `60145332637`.
- A `phone` mutator calling `normalisePhone` as a backstop for seeder/tinker/tests.

**New** `database/factories/RunnerFactory.php` with an `inactive()` state — needed by the tests.

**New** `database/seeders/RunnerSeeder.php` — the eight, in order, stored digits-only:

| # | Name | Stored phone |
|---|---------|--------------|
| 1 | Hanizam | 60106554842 |
| 2 | Auni | 60133561949 |
| 3 | Dhiya | 60145332637 |
| 4 | Fazil | 60199215166 |
| 5 | Ezdie | 601121996364 |
| 6 | Farah | 60136969366 |
| 7 | Jue | 601165099515 |
| 8 | Azwarie | 60164475909 |

Use `Runner::updateOrCreate(['phone' => $phone], [...])` so re-running is safe. **Comment that this
overwrites a name edited in /admin** — that is the price of idempotency and nobody should be surprised.

Register it in `DatabaseSeeder` — and **remove the stock `User::factory()->create(['email' => 'test@example.com'])`**
from `DatabaseSeeder::run()`. Left in place, a stray `db:seed` on the VPS creates a real login account
with the factory's known password.

**Also:** add `'busy_timeout' => 5000` and `'journal_mode' => 'WAL'` to the sqlite block in
`config/database.php` (both keys already exist as `null`). Cheap insurance against `database is locked`.
WAL's `-wal`/`-shm` siblings are already covered by `database/.gitignore`.

---

## Phase 2 — Public section

**Edit** `routes/web.php:7-9` — replace the closure with a single-action controller. A closure that
touches Eloquent widens what gets serialised into the route cache for no benefit, and `phpunit.xml`'s
`<source>` only covers `app/`, so closure logic is invisible to coverage.

**New** `app/Http/Controllers/LandingController.php`:

```php
public function __invoke(): View
{
    // rescue(): a missing or corrupt SQLite file would otherwise 500 the ENTIRE
    // marketing page. Degrading to "no runners section" keeps every config-driven
    // CTA working, which is the property the page has today.
    $runners = rescue(fn () => Runner::query()->active()->ordered()->get(), collect(), report: true);

    return view('landing', ['runners' => $runners]);
}
```

**New** `resources/views/partials/runners.blade.php`, included between `pickup` and `faq` in
`resources/views/landing.blade.php:8-9`, with the variable passed explicitly
(`@include('partials.runners', ['runners' => $runners])`) since every other partial takes no data.

Structure, reusing the existing idiom exactly:
- `<section id="runners" class="section">` — plain `.section`, same as pricing/pickup. FAQ next wraps
  `.section` in `.section-tint`, so the tint rhythm is preserved.
- Header: the `reveal mx-auto max-w-2xl text-center` + `.eyebrow` + `.section-title` + `mt-4 text-ink-soft`
  block from `resources/views/partials/pickup.blade.php:9-13`.
- Grid of `.glass-card` cells, `sm:grid-cols-2 lg:grid-cols-4`, staggered via
  `style="transition-delay: {{ $i * 60 }}ms"` (the idiom from `steps.blade.php:14`).
- Gradient initial-avatar from `steps.blade.php:17-20`.
- Guard the whole section with `@if ($runners->isNotEmpty())`.
- Each link gets `aria-label="{{ __('landing.runners.chat_with', ['name' => $runner->name]) }}"` —
  eight links all reading "Chat on WhatsApp" are indistinguishable in a screen reader's link list.

**Extract** `resources/views/components/whatsapp-icon.blade.php` (anonymous component, `$attributes->merge`)
and update `resources/views/partials/cta.blade.php:22-24` to use it, rather than duplicating ~800
characters of SVG path data. This is the repo's first Blade component.

**Lang keys** — add a `'runners'` block between `'pickup'` and `'faq'` in **both** `lang/en/landing.php`
and `lang/ms/landing.php` (key order mirrors section order in both files today):

```php
// en
'runners' => [
    'eyebrow' => 'Our runners',
    'title' => 'The people handling your robe',
    'subtitle' => 'Message any of us directly on WhatsApp. Whoever replies first will take care of you.',
    'chat' => 'Chat on WhatsApp',
    'chat_with' => 'Chat with :name on WhatsApp',
],
// ms
'runners' => [
    'eyebrow' => 'Runner kami',
    'title' => 'Orang yang uruskan jubah anda',
    'subtitle' => 'Mesej sesiapa sahaja antara kami terus di WhatsApp. Siapa yang balas dahulu akan uruskan anda.',
    'chat' => 'Chat di WhatsApp',
    'chat_with' => 'Chat dengan :name di WhatsApp',
],
```

"Runner" is already a loanword in this file (`steps.items.3.body`: *"Runner kami mendaftar bagi pihak anda"*),
and `'Chat di WhatsApp'` is lifted verbatim from the existing `cta.secondary` so the buttons read alike.

**Nav + footer link:** add `'#runners' => __('landing.nav.runners')` to the `$links` arrays in
`nav.blade.php:2-7` and `footer.blade.php:2-7`, plus `nav.runners` → `'Runners'` / `'Runner'` in both
lang files. Desktop nav goes 4 links → 5 plus the CTA; check it at `md:` before shipping.

Run the README's en/ms drift-check snippet — it must print `Keys match.`

---

## Phase 3 — Admin

**Infrastructure first** (the landmines from above):

- `bootstrap/app.php` — add `->withCommands()` so `app/Console/Commands/` is scanned.
- `bootstrap/app.php`'s `withMiddleware()` callback (**not** `AppServiceProvider::boot()` — see the
  constraints section above for why) — add both redirects with a comment saying why they live here:
  ```php
  $middleware->redirectGuestsTo(fn () => route('admin.login'));
  $middleware->redirectUsersTo(fn () => route('admin.runners.index'));
  ```
  `AppServiceProvider` stays empty/stock.

**Routes** — append an `admin` prefix group to `routes/web.php`: `guest` middleware around
`GET/POST login` (POST also `throttle:5,1`), `auth` middleware around `POST logout`, the runner resource
routes, and `PATCH runners/{runner}/move/{direction}` with `->whereIn('direction', ['up','down'])`.

**Controllers** in `app/Http/Controllers/Admin/`:

- `LoginController` — `create`/`store`/`destroy`. `store` must call `$request->session()->regenerate()`;
  `destroy` must call `invalidate()` + `regenerateToken()`. These are the session-fixation defences Breeze
  would give us; hand-rolling means writing them ourselves. Failure throws one generic
  `ValidationException` with `__('auth.failed')` — never reveal whether the email exists.
- `RunnerController` — `index` deliberately does **not** apply `active()` (the admin must see
  deactivated runners to reactivate them). `store` sets `'position' => (int) Runner::max('position') + 1`.
  Keep real `destroy` alongside the `is_active` toggle, behind an `onsubmit="return confirm(...)"`.
  Deleting leaves a gap in positions (1,2,4,5) — harmless, since ordering is by value, not index.
- `RunnerOrderController` (single-action) — swap `position` with the nearest neighbour inside a
  `DB::transaction`. This only works because `position` has no unique index.

**`app/Http/Requests/RunnerRequest.php`** — one request for store and update.

```php
protected function prepareForValidation(): void
{
    // Normalise BEFORE validating. Rule::unique compares RAW input against stored
    // (normalised) values, so "+60 14-533 2637" would otherwise pass uniqueness and
    // then hit the DB constraint as a raw QueryException — a 500, not a form error.
    $this->merge(['phone' => Runner::normalisePhone((string) $this->input('phone'))]);
}
```

Rules: `name` required/max:60; `phone` required + `regex:/^601\d{8,9}$/` (covers both the 8-digit
subscriber numbers and Ezdie's/Jue's 9-digit ones) + `Rule::unique('runners','phone')->ignore($this->route('runner'))`;
`is_active` required|boolean. The regex is mobile-only on purpose — leave a comment that a landline or
WhatsApp Business number would need `/^60\d{8,11}$/`.

`is_active` as `required|boolean` needs a `<input type="hidden" name="is_active" value="0">` immediately
before the checkbox, or an unchecked box submits nothing and validation fails.

**Views** under `resources/views/admin/`:
- `layout.blade.php` — a **separate** minimal layout, not `layouts/app.blade.php` (which pulls in the
  marketing nav, footer, OG tags and gradient blobs). Still `@vite(...)` so the `@theme` tokens and
  `.glass-card`/`.btn-primary` apply. Include `<meta name="robots" content="noindex, nofollow">`.
- `login.blade.php`, `runners/index.blade.php`, `runners/create.blade.php`, `runners/edit.blade.php`
  + a shared `runners/_form.blade.php` with `old()` repopulation and `@error` messages.
- The index table badges the row where `$runner->phone === config('jubahrunner.whatsapp_number')` as
  **"Main line"** — see Phase 4.

**Admin copy is hardcoded English**, not added to `lang/*/landing.php`. That file is documented as the
landing page's copy and is tied to a key-parity invariant; doubling its surface with internal strings
nobody will translate is a real cost. One comment line in the Blade saying so.

**Seeding the admin account** — `app/Console/Commands/MakeAdminUser.php`, signature
`jubahpanda:admin {email} {--name=} {--password=}`. It prompts with `$this->secret()` when `--password`
is absent, enforces ≥12 characters, and uses `User::updateOrCreate` so it doubles as the password-reset
path. **Not an env-driven seeder** — per constraint 4, `env()` returns `null` in prod once config is
cached, silently. Putting the password in `config/` instead would bake plaintext into
`bootstrap/cache/config.php` (mode 644, group-traversable), which is strictly weaker than `.env` at 600.
Over SSH it needs `ssh -t` to get a TTY.

**Also** add `Disallow: /admin/` to `public/robots.txt`. Not a security control — the `auth` middleware
is — but it keeps the login page out of search results.

---

## Phase 4 — Config, ignores, docs

**Leave `config/jubahrunner.php` wired to `.env`. Do not point it at the database.** Two reasons beyond
scope: config is resolved at boot and baked by `config:cache`, which runs *before* `migrate` on a fresh
box; and wiring every CTA to the DB means a bad DB file kills every call-to-action on the page. The
business line and the team directory are genuinely different things — that they coincide today at
`60145332637` (Dhiya) is a staffing fact, not a modelling error. Add a comment next to `whatsapp_number`
saying exactly that, and badge the matching row in the admin so nobody deletes it thinking it is a dupe.
**Do not auto-sync them in either direction.**

**No prod session change.** `SESSION_DRIVER=file` is fully sufficient for admin login — the guard stores
only a user id, and the storage backend is irrelevant to authentication. `storage/framework/sessions` is
already chowned and already exercised by the BM/EN toggle. Explicitly **do not** switch prod to
`SESSION_DRIVER=database` just because a database now exists; that would put per-request write load on
the SQLite file for zero gain.

**Root `.gitignore`** — add `*.sqlite`, `*.sqlite-journal`, `*.sqlite-shm`, `*.sqlite-wal`. Pure defence
in depth (`database/.gitignore` already covers the real location), but it costs four lines and guards
against a scratch copy landing in the repo root.

**`.env.example`** — document the SQLite path, and document the *absence* of an admin password variable:

```
# The admin login is created with:  php artisan jubahpanda:admin you@example.com
# There is no ADMIN_PASSWORD var on purpose — the command prompts, so the password
# never lands in .env, in git, or in shell history.
```

**`docs/deploy.md`** — rewrite before deploying, then follow it rather than ad-hoc commands:

| Section | Change |
|---|---|
| `### No database` (~L97-101) | Rewrite as `### A small SQLite database`: what it holds, why SQLite over the MySQL already on the box, where the file lives, that it is gitignored and outside the docroot, and that it is **the only non-reproducible state on this box**. Keep the "do not provision MySQL" line. |
| apt block (~L108-110) | Add `php8.4-sqlite3`. Amend the trailing note: `php8.4-mysql is still NOT needed.` |
| `.env` heredoc (~L175-180) | Add `DB_CONNECTION`/`DB_DATABASE`. Keep `file`/`file`/`sync` and comment why they stay. |
| "Writable runtime dirs" | Now three — add `database/`, and note `SQLiteConnector` **throws** if the file is absent rather than creating it. |
| `## The standard deploy` | A deploy containing a new migration needs `migrate --force` between `config:cache` and `route:cache`. |
| `## Sanity-check after deploying` | Add the curl checks below plus a browser check. |
| `## Do not` | Add: do not commit the `.sqlite`; do not run `migrate:fresh`/`refresh` in prod; do not run `db:seed` without `--class=`. |
| **New** `## Backing up the database` | `apt install sqlite3`, then `sqlite3 <db> ".backup /tmp/..."` + `scp`. Note a plain `cp` can tear mid-write. A lost DB costs a re-seed + `jubahpanda:admin`, and that blast radius grows once the registration form lands. |

---

## Deployment — two deploys

**Deploy 1: the read path.** Proves SQLite works end-to-end on that box against a page that only reads.

```bash
# 1. SQLite PDO driver
ssh 160.30.5.87 "apt install -y php8.4-sqlite3 && systemctl restart php8.4-fpm"
ssh 160.30.5.87 "php8.4 -m | grep -iE 'pdo_sqlite|sqlite3'"          # both must print

# 2. Create the file — SQLiteConnector throws if absent, it will NOT create it.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo touch database/database.sqlite \
  && chmod 750 database && chmod 600 database/database.sqlite"
# 750 on the dir so php-fpm can create -wal/-shm siblings; www-data gets traverse only.

# 3. Append DB keys to .env by hand (git pull never touches it)
#    DB_CONNECTION=sqlite
#    DB_DATABASE=/opt/runnerconvo/runnerconvo/database/database.sqlite

# 4. Pull, re-cache config FIRST so migrate sees the new DB config, then migrate + seed.
#    --force is required: APP_ENV=production makes both prompt otherwise.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo git pull \
  && sudo -u runnerconvo php artisan config:cache \
  && sudo -u runnerconvo php artisan migrate --force \
  && sudo -u runnerconvo php artisan db:seed --class=RunnerSeeder --force"

# 5. Assets — LOCAL machine. lg:grid-cols-4, h-14 w-14 etc. are not in the current bundle;
#    skipping this renders the new section unstyled. VPS Node is v18 and cannot build Vite 8.
npm run build && scp -r public/build 160.30.5.87:/tmp/rc-build
ssh 160.30.5.87 "rm -rf /opt/runnerconvo/runnerconvo/public/build \
  && mv /tmp/rc-build /opt/runnerconvo/runnerconvo/public/build \
  && chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/public/build"

# 6. Remaining caches
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo php artisan route:cache && sudo -u runnerconvo php artisan view:cache"
```

**Deploy 2: the write path.** Ship the admin once deploy 1 is confirmed green — `git pull`,
`config:cache`, `route:cache`, `view:cache`, a fresh `npm run build` + `scp` (the admin views are
form-heavy and pull in a lot of new utilities), then:

```bash
ssh -t 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo php artisan jubahpanda:admin you@example.com --name='Dhiya'"
```

`-t` allocates the TTY that `$this->secret()` needs. The FPM pool's `disable_functions` is irrelevant
throughout: PDO SQLite is a native extension, and `php artisan` runs under the CLI SAPI, which that pool
config does not touch.

---

## Verification

**Locally, before either deploy:**
```bash
php artisan migrate && php artisan db:seed --class=RunnerSeeder
php artisan tinker --execute="dump(App\Models\Runner::active()->ordered()->pluck('name','phone'));"
php artisan test
npm run dev          # eyeball the section, and the nav at md: width
php -r "<README drift-check snippet>"   # must print: Keys match.
```

**Tests** — `phpunit.xml` needs no changes; every DB-touching test just adds `use RefreshDatabase;`.

**Fix `tests/Feature/ExampleTest.php` first**, before anything touches `/`: it hits `/` with no schema
(the `RefreshDatabase` import is commented out at line 5), so it will fail with `no such table: runners`
the moment the landing page queries. Fold it into `LandingPageTest`.

New tests:
- `tests/Feature/LandingPageTest.php` — renders; active runners listed with `wa.me` links; inactive
  hidden; **rendered in position order** (catches a missing `ordered()` scope); section hidden when empty.
- `tests/Feature/Admin/AdminAuthTest.php` — **guests redirected to `admin.login`** (the regression test
  for finding 1 — without the callback this asserts-fails on a 401); login succeeds; wrong password
  rejected with `assertGuest()`; logout; **logged-in admin at `/admin/login` lands on the admin index,
  not `/`** (regression test for finding 2).
- `tests/Feature/Admin/RunnerManagementTest.php` — create; **phone normalised on create** (`'+60 14-533 2637'`
  → `60145332637`); **duplicate phone in a different format rejected as a validation error, not a 500**
  (this is what proves `prepareForValidation` is in the right place); invalid phones rejected; rename;
  deactivate disappears from the landing page; move up swaps positions; move first up is a no-op;
  guests cannot create.
- `tests/Unit/RunnerTest.php` — `normalisePhone` data provider; `whatsapp_url` accessor.
- `tests/Feature/TranslationParityTest.php` — optional, ~10 lines, turns the README drift-check into a
  real assertion enforced by `php artisan test`. Recommended.

**In production, after each deploy:**
```bash
curl -s  https://jubahpanda.my/ | grep -c 'wa.me/60'              # >= 8
curl -s  https://jubahpanda.my/ | grep -o 'id="runners"'          # present
curl -sI https://jubahpanda.my/database/database.sqlite | head -1 # 404 — docroot is public/
curl -sI https://jubahpanda.my/admin | head -1                    # deploy 2: 302 to /admin/login, NOT 401
```
A bare `401` on that last check is the exact symptom of a missing `Authenticate::redirectUsing()`.

Then in a browser: log in at `/admin`, rename a runner, confirm the landing page shows it immediately
(there is no caching layer, by design).

---

## Known trade-offs

1. **The landing page gains a runtime dependency on SQLite.** Today it renders with the DB file deleted.
   The `rescue()` in `LandingController` is what preserves that property — without it, a corrupt DB file
   500s the whole marketing page rather than just dropping the runners section.
2. **An auth endpoint now lives on the public marketing domain.** `throttle:5,1` + a 12-char minimum +
   `noindex` is proportionate for a team of eight. An Apache `Require ip` on `/admin` is cheap but would
   break admin access from mobile data — which is exactly when the team needs it. Recommend relying on app auth.
3. **Blade changes now near-always need a local `npm run build` + `scp`.** Already true, but it bites
   harder here; the failure mode is subtle (page works, looks broken) and the fix needs Node ≥20.19.
4. **You now own backups.** `git clone` no longer fully reconstitutes the box.
5. **No caching, deliberately.** Eight rows behind a composite index in a local file; the `CACHE_STORE=file`
   alternative is also a disk read plus an `unserialize()`. Caching would buy nothing and create the
   "I edited a runner and the site still shows the old one" support ticket. Document the shape in the
   README as a future option; don't ship it.

## Order of work

1. Migration + model + factory + seeder; `migrate` + seed green locally; verify in tinker.
2. Fix `ExampleTest` — before anything touches `/`.
3. `LandingController` + route + partial + component + both lang files + nav/footer links; drift-check.
4. `LandingPageTest` — lock public behaviour before admin work starts.
5. `bootstrap/app.php` `->withCommands()` + the two `AppServiceProvider` redirect callbacks.
6. Login controller + admin layout + login view + `jubahpanda:admin` + `AdminAuthTest`.
7. `RunnerRequest` + `RunnerController` + `RunnerOrderController` + admin views + `RunnerManagementTest`.
8. `robots.txt`, root `.gitignore`, `.env.example`, README, `config/jubahrunner.php` comment,
   `config/database.php` WAL/busy_timeout.
9. `docs/deploy.md` rewrite — **before** deploying.
10. Deploy 1 (work items 1-4), verify, then Deploy 2.
