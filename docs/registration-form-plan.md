# Registration form: in-app bookings on SQLite, managed from /admin

> Implementation plan. Written 2026-09-25 against commit `e568602`.
>
> **Status (2026-09-25): deployed to production, form still UNLINKED.** The code, migrations and cron are live; the Register buttons still point at WhatsApp (`JP_REGISTER_URL` pinned) until the option lists are entered and the flip. Phases 1-5 and the docs have 106 passing tests. Phase 6 deployment steps live in `docs/deploy.md` under
> "Adding the registration form". What differs from the plan below:
>
> - Booking routes bind on `{booking}` with `Booking::getRouteKeyName() = 'reference'`, rather than
>   `{booking:reference}` in each route.
> - `RegistrationRequest::messages()` overrides the rule-wide `required` message
>   (`register.errors.required`, "Full name is required.") and `delivery_method.required`. The
>   framework wording read badly with question-style labels ("The How will you get your robe?
>   field is required.").
> - `BookingFilters` lives in `app/Support/` and is shared by the index and the export, as planned.
> - The form's controls sit in a small `x-field` Blade component (label, control, hint, error).
> - `addcslashes` LIKE-escaping in `Booking::search()` was dropped: SQLite has no default `ESCAPE`
>   character, so it did nothing, and the term is a bound parameter anyway.
> - `Booking::label()` from Phase 1 does not exist: the three lists are `BookingOption` rows now.
> - Not done here because they're operational: entering the option lists, the `support@` mailbox
>   (done 2026-09-25), the deploy and the flip.

## Context

Every "Register" button on jubahpanda.my goes somewhere else. `config('jubahrunner.register_url')`
reads `JP_REGISTER_URL`, falls back to `wa.me/<JP_WHATSAPP_NUMBER>`, and five call sites use it
(`nav.blade.php` ×2, `hero`, `pricing`, `cta`). Every booking arrives as a WhatsApp chat or a Google
Form row, and the team keeps track of them by hand.

This change moves registration into the app. It adds a bilingual form at `/register` that writes a
booking to the existing SQLite database, a confirmation page with a reference number and a WhatsApp
handoff, and a Bookings section in `/admin` where the team can filter, edit, assign, change status and
export. Payment stays out of scope, but the schema already has the columns a gateway will fill.

**Time pressure is real.** The pickup window is 23 Oct – 3 Nov 2026, four weeks from now. Aim to
have the form live by about 2 Oct so there is a registration period before the runners go to UMPSA.
Anything not needed on launch day is marked *later*.

**Decisions made (asked 2026-09-25):**

| Question | Decision |
|---|---|
| Same matric number twice | **Blocked while a live booking exists.** A cancelled booking frees the number again. |
| When payment happens | **Self-pickup pays before we collect; Kuantan COD pays at handover.** So payment is tracked separately from the fulfilment stages. |
| Runners | **Optional `runner_id` FK, set by an admin.** The confirmation page hands off to the main business line. |
| Authorisation letter / IC | **Over WhatsApp, never uploaded.** The form only asks the graduate to acknowledge it. |
| Extra fields | **Convocation session, programme level, free-text note.** No email. |
| Closing | **`JP_REGISTRATION_CLOSES_AT` in `.env`** (Kuala Lumpur time). No capacity cap. |
| Retention | **Keep for 1 year, then delete.** |
| Data controller named in the notice | **JubahPanda**, contact via `JP_EMAIL` (`support@jubahpanda.my`) and the main WhatsApp line. |
| Faculties, robe sizes, convocation sessions | **Admin-managed** (add, edit, reorder, deactivate; delete only if unused), bilingual labels. Phase 1b. |
| Master/PhD matric format | **Unconfirmed.** Strict `XXyyaaa` for Diploma/Bachelor; loose fallback for Master/PhD. |

**Decisions taken in this plan (reasoning below):** the form gets its own `/register` page, not a
landing section. `JP_REGISTER_URL` survives as an override and kill switch. No captcha at launch.
Bookings go in SQLite, not the MySQL that `docs/deploy.md` currently earmarks "for when the
registration form lands".

### Inputs

**Supplied 2026-09-25:**

| Input | Answer | Where it lands |
|---|---|---|
| Matric number format | `XXyyaaa`: 2 uppercase course letters, 2-digit intake year, 3-digit running number from `001` (e.g. `CB22001`) | Phase 2 regex `/^[A-Z]{2}\d{2}(?!000)\d{3}$/` |
| Closing date | 21 October 2026 | `JP_REGISTRATION_CLOSES_AT="2026-10-21 23:59"` (KL time) |
| VPS location | Malaysia (Johor) | Privacy notice: data stored in Malaysia; no cross-border clause for hosting |
| Contact email | `support@jubahpanda.my`, **live since 2026-09-25** (Cloudflare Email Routing; see "Mail" in `deploy.md`) | `JP_EMAIL` default in `config/jubahrunner.php` and `.env.example`; prod `.env` |

**The mailbox must exist before the flip.** The privacy notice names it as the channel for access and
correction requests, and the footer already shows `JP_EMAIL` publicly. If it's not ready at launch,
the notice lists WhatsApp first and the email second, and the email line gets added once mail is
actually delivered.

**No longer blocking the build:** faculties, robe sizes and convocation sessions are managed in
`/admin/options` (decided 2026-09-25; see Phase 1b). The team types them in during the unlinked
live test, before the flip. The form shows "registration opens soon" until every list has at least
one active entry. Still worth confirming before then: whether robe sizes differ by programme level.

**Still open:** the Master/PhD matric format. Until it's confirmed, the strict `XXyyaaa` regex
applies only to Diploma and Bachelor (Phase 2).

---

## Verified constraints that shape the work

Six things checked against the code and the vendor tree, on top of the gotchas already recorded in
the runner-directory plan:

1. **Nav and footer links are bare fragments.** `nav.blade.php:2-8` and `footer.blade.php:2-8` use
   `'#how' => …`. On `/register` those resolve to `/register#how`, which goes nowhere. They must
   become `route('home').'#how'`. On the landing page itself, a same-document URL that differs only
   by fragment still scrolls without a reload, so nothing regresses there.
2. **`$request->ip()` is currently a Cloudflare edge IP.** `bootstrap/app.php` never calls
   `trustProxies()`, and `deploy/runnerconvo.conf` has no `mod_remoteip`. Every request reaches
   php-fpm with a Cloudflare address as `REMOTE_ADDR`, so a per-IP throttle puts unrelated
   graduates who happen to share an edge node in the same bucket. The existing `throttle:5,1` on
   admin login has the same flaw today. The fix is in Phase 3. Pre-flight check on the box:
   `apache2ctl -M | grep -i remoteip`. The fix is safe either way: if a global `mod_remoteip` already
   rewrites `REMOTE_ADDR`, the trusted-proxy match simply never fires.
3. **An expired CSRF token loses everything the registrant typed.** A form left open longer than
   `SESSION_LIFETIME` (120 min) gets a bare English "419 Page Expired". The fix is a render callback
   in `withExceptions()`. Checked in `vendor/.../Foundation/Exceptions/Handler.php:721-723`: the
   handler runs `prepareException()` before `renderViaCallbacks()`, and line 779 turns
   `TokenMismatchException` into `HttpException(419)`. So the callback has to match `HttpException`
   with status 419 and a `TokenMismatchException` as its previous exception. A callback typed on
   `TokenMismatchException` never fires.
4. **SQLite partial indexes need raw SQL.** Laravel 13's `SQLiteGrammar` has no partial-index
   support (checked), so "one live booking per matric number" needs a
   `DB::statement('CREATE UNIQUE INDEX … WHERE …')`. The race between `Rule::unique` and the insert
   is then caught as `Illuminate\Database\UniqueConstraintViolationException`, which exists in this
   vendor tree.
5. **`config/app.php` timezone is `UTC`.** Keep storage in UTC and don't change it. Add
   `jubahrunner.timezone = 'Asia/Kuala_Lumpur'` and use it to parse the closing date and to display
   times in the admin.
6. **The existing backup command writes PII into OneDrive.** `docs/deploy.md` has
   `scp … ./backups/`. This working copy lives under `OneDrive - UMPSA\…`, so once the database holds
   bookings, that command syncs graduates' matric and phone numbers into the university's OneDrive
   tenant. `*.sqlite` being gitignored doesn't prevent that. The backup destination has to move
   (Phase 6).

Already true and needs no change: `phpunit.xml` uses SQLite `:memory:`. `foreign_key_constraints`
defaults to true, so `nullOnDelete()` is enforced in tests too. WAL and `busy_timeout` are set.
`@source` in `app.css` already scans the framework's pagination views, so `->links()` is styled.

---

## Phase 1: Data layer

**New** `database/migrations/<timestamp>_create_bookings_table.php`

```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    // Public handle, "JP-" + 6 chars from an unambiguous alphabet. Random, not
    // sequential: it goes into WhatsApp messages, so it must not reveal volume
    // or be guessable.
    $table->string('reference', 9)->unique();

    $table->string('full_name', 120);
    $table->string('matric_no', 20);   // uppercased, spaces/hyphens stripped
    // Digits only, 60…, same convention as runners.phone. NOT unique: one parent
    // may register two siblings from one number.
    $table->string('phone', 20);
    // Admin-managed lists (Phase 1b). restrictOnDelete: an option that any booking
    // uses can only be deactivated, never deleted, so no booking loses its label.
    $table->foreignId('faculty_id')->constrained('booking_options')->restrictOnDelete();
    $table->foreignId('robe_size_id')->constrained('booking_options')->restrictOnDelete();
    $table->foreignId('convocation_session_id')->constrained('booking_options')->restrictOnDelete();
    // Fixed set (diploma|bachelor|master|phd) in lang/*/register.php. It stays
    // out of the admin lists because the matric rule branches on it (Phase 2).
    $table->string('programme_level', 20);
    $table->string('delivery_method', 10);          // pickup | cod
    $table->text('delivery_address')->nullable();   // required when cod
    $table->text('notes')->nullable();              // the registrant's own note
    $table->string('locale', 2);                    // language they registered in

    $table->string('status', 20)->default('submitted');
    $table->foreignId('runner_id')->nullable()->constrained()->nullOnDelete();
    $table->text('admin_notes')->nullable();

    // Payment is tracked apart from fulfilment: COD pays at handover, so "paid"
    // can't be a step in a straight line. A gateway webhook later fills these
    // same columns, and nothing else changes.
    $table->unsignedInteger('amount_sen');
    $table->timestamp('paid_at')->nullable();
    $table->string('payment_method', 20)->nullable();   // transfer | duitnow | cash | (gateway later)
    $table->string('payment_reference', 100)->nullable();

    // Set on first entry to each stage, kept if an admin later moves the booking back.
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('collected_at')->nullable();
    $table->timestamp('handed_over_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();

    // PDPA: when they agreed, and to which version of the notice.
    $table->timestamp('consented_at');
    $table->string('privacy_version', 20);
    $table->timestamps();

    $table->index(['status', 'created_at']);
    // SQLite does not index FK columns on its own.
    $table->index('runner_id');
    $table->index('faculty_id');
    $table->index('robe_size_id');
    $table->index('convocation_session_id');
});

// One LIVE booking per matric number. It's partial so that a cancelled booking
// doesn't block re-registering. The schema builder has no partial-index API for
// SQLite, hence raw SQL.
DB::statement("CREATE UNIQUE INDEX bookings_matric_no_live_unique ON bookings (matric_no) WHERE status <> 'cancelled'");
```

No `SoftDeletes`. A PDPA erasure request has to actually erase the row, and the partial index
already provides the "cancelled but kept" state.

**New** `app/Enums/BookingStatus.php`, a string-backed enum with `label()`:

```
submitted ──► confirmed ──► collected ──► handed_over
    │             │             │
    └─────────────┴─────────────┴──────► cancelled
```

| Status | Means | Guard (admin form, `after()` hook) |
|---|---|---|
| `submitted` | Form received; nobody has spoken to them yet | none |
| `confirmed` | Details and documents confirmed on WhatsApp | none |
| `collected` | A runner holds the robe | **Self-pickup: `paid_at` must be set.** |
| `handed_over` | The graduate has the robe (picked up or delivered) | **`paid_at` must be set.** For COD, the admin ticks "Paid" in the same save. |
| `cancelled` | Dead; frees the matric number | Un-cancelling fails validation if another live booking now holds the number. |

Admins may move a booking backwards to undo a mistake. There's no strict state machine: the team is
eight people, and a locked workflow would just push the fix into tinker. The user's suggested
`submitted → paid → collected → handed over` maps onto this, with "paid" as a flag beside the line
rather than a stage on it. That's forced by the COD decision.

**Payment slot for later:** a ToyyibPay/Billplz/DuitNow webhook sets `paid_at`, `payment_method` and
`payment_reference`, and moves `submitted → confirmed`. `amount_sen` is snapshotted at booking time
from `config('jubahrunner.price_sen')` so a mid-season price change doesn't rewrite old bookings.
Admins can edit it, because the pricing footnote says "Final price confirmed before payment".

**New** `app/Models/Booking.php`, following `Runner`'s idioms (`#[Fillable]`, `#[Scope]`, `Attribute`):

- `#[Fillable([...])]` with every column except `id` and `reference`. Mass assignment is safe because
  the public request's `validated()` only ever contains public fields.
- `casts()`: `status` → `BookingStatus`, all `*_at` → `datetime`, `amount_sen` → `integer`.
- `booted()` → `creating`: generate `reference` as `JP-` plus 6 characters from
  `ABCDEFGHJKMNPQRSTUVWXYZ23456789`. That alphabet has no 0/O, 1/I/L, so a reference survives being
  read out over a phone call. Retry while `exists()`; the unique index is the backstop.
- Mutators as backstops, exactly like `Runner::phone()`: `phone` → `Runner::normalisePhone()`,
  `matric_no` → `static::normaliseMatric()` (uppercase, strip `\s` and `-`).
- `#[Scope] live()` → `where('status', '!=', 'cancelled')`. `#[Scope] search(string $term)` → `LIKE` on
  reference, name, matric and phone. When the term is phone-shaped, normalise it first, so that
  `012-345` finds `6012345…`.
- `whatsappUrl` accessor → `wa.me/{phone}` (to the registrant), the same pattern as `Runner`.
- `belongsTo` for `faculty`, `robeSize` and `convocationSession` (all `BookingOption`). The admin
  index and the export eager-load all three.
- `moveTo(BookingStatus $status)` sets `status` plus the stage timestamp if it's still null.
- `belongsTo(Runner::class)`, and `hasMany(Booking::class)` on `Runner`.

**New** `database/factories/BookingFactory.php` with states `cod()`, `paid()`, `cancelled()`, and
`status(BookingStatus)`. The three option FKs default to `BookingOption::factory()->faculty()` and so on.

## Phase 1b: Admin-managed option lists

Faculties, robe sizes and convocation sessions live in one table, so one model, one controller and
one set of views handle all three. This is modelled closely on `runners`: `position` for order,
`is_active` for "hidden but kept".

**New** migration, dated **before** the bookings one because bookings reference it:

```php
Schema::create('booking_options', function (Blueprint $table) {
    $table->id();
    $table->string('type', 30);                 // BookingOptionType: faculty | robe_size | convocation_session
    // Both languages on the row: the public form is bilingual, and these are no
    // longer in lang files, so the parity test can't police them. The admin form
    // requires both instead.
    $table->string('label_en', 120);
    $table->string('label_ms', 120);
    $table->unsignedSmallInteger('position')->default(0);   // NOT unique, same reasoning as runners
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['type', 'label_en']);
    $table->index(['type', 'is_active', 'position']);
});
```

There's no `code` column. Bookings point at the row id, so renaming a label (fixing a typo, adding a
date to a session) updates every booking at once, which is what the team will want.

**New** `app/Enums/BookingOptionType.php`: string-backed, with `label()` ("Faculties", "Robe sizes",
"Convocation sessions") and `singular()`.

**New** `app/Models/BookingOption.php`:
- `#[Fillable(['type', 'label_en', 'label_ms', 'position', 'is_active'])]`, casting `type` to the enum.
- `#[Scope] ofType()`, `#[Scope] active()`, and `#[Scope] ordered()` (`position`, then `id`, copied
  from `Runner`).
- A `label` accessor that returns `label_ms` when `app()->getLocale() === 'ms'`, otherwise `label_en`.
  The admin always calls `label_en` directly.
- `hasMany` bookings, one relation per FK column, with an `isInUse()` helper.

**New** `database/factories/BookingOptionFactory.php` with `faculty()`, `robeSize()`, `session()` and
`inactive()` states.

**Admin**, under the existing `auth` group:

```php
Route::prefix('options/{type}')->whereIn('type', ['faculty', 'robe_size', 'convocation_session'])
    ->name('options.')->group(function () {
        Route::get('/', [BookingOptionController::class, 'index'])->name('index');
        Route::get('create', [BookingOptionController::class, 'create'])->name('create');
        Route::post('/', [BookingOptionController::class, 'store'])->name('store');
        Route::get('{option}/edit', [BookingOptionController::class, 'edit'])->name('edit');
        Route::patch('{option}', [BookingOptionController::class, 'update'])->name('update');
        Route::delete('{option}', [BookingOptionController::class, 'destroy'])->name('destroy');
        Route::patch('{option}/move/{direction}', BookingOptionOrderController::class)
            ->whereIn('direction', ['up', 'down'])->name('move');
    });
```

- `BookingOptionController` mirrors `RunnerController`:
  - `store` appends at `max(position) + 1` within the type.
  - Every action checks that `$option->type` matches the `{type}` URL segment, and 404s otherwise, so
    a faculty can't be edited through the sessions URL.
  - **`destroy` is only allowed when `! $option->isInUse()`.** Otherwise it redirects with "In use
    by N bookings; deactivate it instead." `restrictOnDelete` is the backstop if two admins race.
- `BookingOptionOrderController` is a copy of `RunnerOrderController`'s swap, scoped to the type.
- `BookingOptionRequest`: `label_en` and `label_ms` required|string|max:120, `label_en` unique within
  the type (ignoring self), `is_active` required|boolean (with the same hidden-input-before-checkbox
  trick as `runners/_form`).
- Views go in `admin/options/{index,create,edit,_form}.blade.php`, copying the runners views. The index
  shows a **"Used by"** count column (`withCount`), so the team knows what can be deleted.
- The admin nav becomes **Bookings · Runners · Faculties · Robe sizes · Sessions**. The three list
  pages share one set of views, so the extra nav links cost nothing.

**Public side:**
- `RegistrationWindow::isOpen()` also requires **at least one active option of each type**.
  Otherwise `/register` shows an "opens soon" card, not a form with an empty dropdown. That's what
  makes it safe to deploy before the lists are typed in.
- The form's selects use `BookingOption::ofType($t)->active()->ordered()->get()`: three small
  queries, no caching. Same reasoning as runners.
- A booking's own options stay visible in the admin even after they're deactivated. The admin
  booking form's selects list active options **plus** the booking's current one, the same pattern as
  the runner select.

**Edit** `config/jubahrunner.php`:

```php
// Blank (the default) = the in-app form at /register. Set it only as an override,
// e.g. back to https://wa.me/... as a KILL SWITCH if the form misbehaves. Flipping
// it is an .env edit + config:cache, no deploy. route() can't be called here:
// config is evaluated, and cached, before routes exist.
'register_url' => env('JP_REGISTER_URL') ?: null,

// "2026-10-18 23:59", read as Kuala Lumpur time. Blank = open indefinitely.
'registration_closes_at' => env('JP_REGISTRATION_CLOSES_AT'),
'timezone' => 'Asia/Kuala_Lumpur',

// (Also change the JP_EMAIL fallback above from hello@ to support@jubahpanda.my.)

// Must match pricing.price in lang/*/landing.php; a test enforces it.
'price_sen' => 4500,

// Bump whenever lang/*/privacy.php changes materially; stored on each booking.
'privacy_version' => '2026-09-25',
'retention_days' => 365,
```

**New** `app/Support/RegistrationWindow.php`, with `isOpen()` and `closesAt()`. It parses the config
string in `jubahrunner.timezone`. The controller, the view and the tests all go through this one place.

---

## Phase 2: Public form

### Where it lives: its own page, `/register`

The form gets its own page rather than a landing section, for three reasons:

- The landing page is a **read path wrapped in `rescue()`** that degrades quietly. The form is a
  **write path that must fail loudly.** Separate routes keep those two failure policies from
  touching.
- A form section on `/` would mean validation redirects back to `/` with `#register`, a long form
  inside a marketing scroll, and the throttle middleware sitting next to the page everyone loads.
- `/register` is a clean link to paste into WhatsApp groups and Instagram bios.

**Routes** (`routes/web.php`):

```php
Route::get('register', [RegistrationController::class, 'create'])->name('register');
Route::post('register', [RegistrationController::class, 'store'])
    ->middleware('throttle:registrations')->name('register.store');
Route::get('register/done', [RegistrationController::class, 'show'])->name('register.done');
Route::get('privacy', PrivacyController::class)->name('privacy');
```

**New** `app/Http/Controllers/RegistrationController.php`:

```php
public function store(RegistrationRequest $request): RedirectResponse
{
    // NO rescue() here, unlike LandingController. A write that fails quietly
    // leaves a graduate believing they have booked. Let it 500 (errors/500 says
    // "not saved, WhatsApp us").
    try {
        $booking = Booking::create([
            ...$request->safe()->except(['documents_ack', 'consent']),
            'amount_sen' => config('jubahrunner.price_sen'),
            'consented_at' => now(),
            'privacy_version' => config('jubahrunner.privacy_version'),
            'locale' => app()->getLocale(),
        ]);
    } catch (UniqueConstraintViolationException $e) {
        // Two submits raced past Rule::unique; the partial index caught the second.
        // Only translate the matric index. Anything else is a real bug and should 500.
        throw_unless(str_contains($e->getMessage(), 'matric_no'), $e);
        throw ValidationException::withMessages(['matric_no' => __('register.errors.duplicate')]);
    }

    // put(), not flash(): a refresh of the done page must still work.
    $request->session()->put('registration.reference', $booking->reference);

    return to_route('register.done');
}
```

`create()` renders the closed card instead of the form when `! RegistrationWindow::isOpen()`.
`store()` redirects back to `register` in that case. `show()` looks up the session's reference and
redirects to `register` if there isn't one or the booking has since been deleted.

**New** `app/Http/Requests/RegistrationRequest.php`, copying `RunnerRequest`'s shape:

- `prepareForValidation()` normalises `phone` (via `Runner::normalisePhone()`) **and** `matric_no`
  (via `Booking::normaliseMatric()`) **before** the unique rule, for the same reason given in the
  `RunnerRequest` comment. Otherwise `cb 19012` passes as not-a-duplicate of `CB19012` and then hits
  the DB constraint.
- Rules:
  - `full_name` required|string|max:120
  - `matric_no` required, a regex, and `Rule::unique('bookings','matric_no')->where(fn ($q) => $q->where('status','!=','cancelled'))`
  - `phone` required and `regex:/^601\d{8,9}$/` (the same regex as `RunnerRequest`)
  - `faculty_id`, `robe_size_id` and `convocation_session_id` each use
    `Rule::exists('booking_options', 'id')->where('type', …)->where('is_active', true)`. A
    deactivated option, or an id belonging to a different list, fails.
  - `programme_level` `Rule::in(array_keys(trans('register.options.programme_level', [], 'en')))`.
    The **en** list is the source of truth, so the result doesn't depend on locale.
  - `delivery_method` `in:pickup,cod`
  - `delivery_address` `required_if:delivery_method,cod`|nullable|string|max:300
  - `notes` nullable|string|max:500
  - `documents_ack` accepted
  - `consent` accepted
- **Matric regex depends on programme level.** Normalisation (uppercase, strip spaces and hyphens)
  runs first, so `cb 22-001` becomes `CB22001` before either regex sees it.
  - **Diploma and Bachelor:** `/^[A-Z]{2}\d{2}(?!000)\d{3}$/`, the confirmed `XXyyaaa` format (course
    letters, intake year, running number from `001`). The field hint shows the example `CB22001`.
  - **Master and PhD:** `/^[A-Z0-9]{5,15}$/` until their format is confirmed, with a
    `// TODO(matric)` comment. Applying the strict rule to them on launch day could block real
    postgraduates.
  - The branch is written once, in `Booking::matricRuleFor(string $level)`, and both
    `RegistrationRequest` and `BookingUpdateRequest` call it.
  - The `matric_no` column stays `string(20)`, so a format change never needs a migration.
- `documents_ack` isn't stored. Submitting at all is only possible with it ticked, and
  `consented_at` records that moment.
- `attributes()` returns `__('register.fields.<field>.label')` for each field, so `:attribute` is
  localised in both languages.
- `messages()`: `matric_no.unique` → `__('register.errors.duplicate')`. The wording is "a booking
  with this matric number already exists; if that wasn't you or you need to change it, WhatsApp
  us". It confirms that a booking exists and nothing more.
- `after()` holds the **bot checks** (Phase 3).

**Phone numbers are Malaysian mobiles only**, as for runners. International graduates are told in the
field hint to WhatsApp the team. See *Known trade-offs*.

### Form UX: `resources/views/register/create.blade.php`

It uses `layouts/app`, so the marketing nav, footer and gradient ground carry over. Structure,
reusing the existing idiom:

- `<section class="section">` with the header block from `pickup.blade.php:9-13` (eyebrow,
  `.section-title`, `mt-4 text-ink-soft`), then a `max-w-2xl` `.glass-card p-6 sm:p-8`.
- An **error summary** at the top when `$errors->any()`: `role="alert"`, a list of links to each
  field `#id`, and `tabindex="-1"` focused by a line in `app.js` so screen-reader and keyboard users
  land on it.
- Three `<fieldset>`s, with `<legend>` styled like the pickup labels
  (`text-xs font-bold tracking-wide text-ink-muted uppercase`):
  1. **About you**: full name (`autocomplete="name"`), matric (`autocapitalize="characters"`,
     `spellcheck="false"`), WhatsApp number (`type="tel" inputmode="tel" autocomplete="tel"`).
  2. **Your robe**: programme level first, because it decides which matric rule applies and the
     label reads naturally in that order. Then faculty, robe size and convocation session. All four
     are `<select>`s: the first from the lang list, the other three from `booking_options`, each
     showing `$option->label` in the page's language.
  3. **Getting it to you**: two radio **cards** reusing the pickup section's visual language (icon
     chip, `self_badge`/`cod_badge` pill, `peer-checked:ring-2 ring-brand-500`), then the address
     textarea. Under the COD card, reuse `__('landing.pickup.note')` verbatim (the Kuantan-only rule).
     No new copy, and it can't drift.
- Notes textarea, then two checkboxes (documents acknowledgement; consent with a link to
  `route('privacy')` in a new tab), then a full-width `.btn-primary` submit.
- Inputs copy the admin `_form` classes **but at `text-base`, not `text-sm`**. iOS Safari zooms into
  any input under 16px, and most registrants will be on phones inside the WhatsApp or Instagram
  in-app browser.
- **Progressive enhancement** in `resources/js/app.js`, following the existing
  `querySelector → if (el)` pattern:
  - `[data-delivery]` hides the address field unless COD is picked. Without JS it's always visible,
    labelled "(COD only)".
  - `[data-submit-once]` disables the button on submit. The partial index is still the real guard
    against double submits.
- **Closed state:** a `.glass-card` saying registration closed on `<date>`, with a WhatsApp button
  (`<x-whatsapp-icon>`, main line). An **"opens soon"** variant uses the same card, shown while any of
  the three option lists has no active entry.
- `@section('title', __('register.meta.title'))`.

**Edit** `layouts/app.blade.php` to add `@stack('head')` before `@vite`, so the done page can push
`<meta name="robots" content="noindex">`.

### After submitting: `resources/views/register/done.blade.php`

- A large, copyable **reference** (`JP-7K3Q9X`, `select-all` on the element) and a summary: name,
  session label, delivery method.
- "What happens next" as a short ordered list, echoing steps 2-3 of the landing page. The team will
  WhatsApp them to confirm details, documents and payment. Self-pickup pays before collection; COD
  pays at handover.
- A primary **"Message us on WhatsApp"** button: `wa.me/<main line>?text=` + `rawurlencode()` of
  `__('register.done.whatsapp_message', ['reference' => …, 'name' => …])`. The matric number is left
  out of the prefilled text on purpose: that text sits in the graduate's own WhatsApp history, and
  the reference is enough for the team.
- `noindex`, via `@push('head')`.

### Replacing the external link

**Edit** `app/Providers/AppServiceProvider.php` `boot()`:

```php
// One place resolves the Register target. The .env override wins (kill switch);
// otherwise it's the in-app form. It's a composer rather than config because
// route() doesn't exist yet when config is built.
View::composer('partials.*', fn ($view) => $view->with(
    'registerUrl', config('jubahrunner.register_url') ?: route('register'),
));
```

Replace `config('jubahrunner.register_url')` with `$registerUrl` at all five call sites, and fix the
fragment links in `nav.blade.php` and `footer.blade.php` (constraint 1).

### Localisation

- **New** `lang/en/register.php` and `lang/ms/register.php` hold all public form, done page and closed
  state copy: `fields.*.label` and `fields.*.hint`, `options.programme_level` as a `code => label`
  map (identical keys in both locales; the other three lists live in the database, see Phase 1b),
  `errors.{duplicate,generic,expired}`,
  and `done.whatsapp_message`. Keeping this separate from `landing.php` matches that file's
  documented role as the landing page's copy.
- **New** `lang/en/privacy.php` and `lang/ms/privacy.php` hold the notice (Phase 5).
- **New `lang/ms/validation.php`**, deliberately **partial**. It contains only the rules the public
  form uses (`accepted`, `required`, `required_if`, `string`, `max.string`, `in`, `regex`) plus empty
  `custom`/`attributes` arrays. A comment at the top says any rule not listed falls back to English
  through `APP_FALLBACK_LOCALE=en`. The translator merges this file with the framework's, and a
  missing key falls back rather than printing the raw key. There's no `lang/en/validation.php`; the
  framework's copy is used.
- **New** `app/Http/Middleware/AdminLocale.php` forces `en` on the admin group. Before this change,
  an admin whose session held `ms` from the landing-page toggle still got English validation errors
  (there was no Malay file). With `lang/ms/validation.php` in place they'd get Malay errors inside
  the English admin.
- **Edit** `tests/Feature/TranslationParityTest.php` to iterate over every `lang/en/*.php` and
  assert that a `lang/ms/` twin exists with identical keys. `validation.php` is ms-only and partial,
  so it's naturally excluded because the iteration starts from `en`.
- Switching language mid-form is a GET that reloads the page and drops what's been typed. That's
  acceptable: people pick a language before they start typing.

**Friendly error pages.** **New** `resources/views/errors/500.blade.php` and `errors/429.blade.php`:
standalone HTML with inline styles and **no `@vite`** (a missing manifest must not break the error
page itself). The copy comes from `register.php`: "Something went wrong on our side. If you were
registering, your booking was **not** saved. Please try again or WhatsApp us", with a link to the
main line. This is the "fail visibly" requirement done properly: a plain English "Server Error"
page is visible, but it doesn't tell the graduate what to do next.

---

## Phase 3: Abuse protection

**Real client IP first (constraint 2).** In `bootstrap/app.php`'s `withMiddleware()`:

```php
// Every request arrives from a Cloudflare edge IP. Trust only Cloudflare's published
// ranges (https://www.cloudflare.com/ips/, copied 2026-09-XX) so $request->ip() is
// the visitor. Otherwise per-IP throttles lump strangers together. A spoofed
// X-Forwarded-For is harmless: Cloudflare appends the real IP, and Symfony reads
// from the right, skipping trusted hops.
$middleware->trustProxies(
    at: [/* 15 IPv4 + 7 IPv6 CIDRs */],
    headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
);
```

This also fixes the admin login throttle.

**Throttle.** A named limiter in `AppServiceProvider::boot()`. `RateLimiter::for` is resolved per
request, so the provider-order gotcha doesn't apply to it.

```php
RateLimiter::for('registrations', fn (Request $request) => [
    // Distinct keys: two limits with the same by() share one counter.
    Limit::perMinute(10)->by('reg-min:'.$request->ip()),
    Limit::perHour(60)->by('reg-hour:'.$request->ip()),
]);
```

These limits are **generous on purpose.** Every POST counts, including ones that fail validation,
and a hostel, campus Wi-Fi or mobile carrier NAT puts many real graduates behind one IP. Bots are
stopped by the checks below; the throttle only has to stop floods.

**Honeypot and time trap**, both in `RegistrationRequest::after()`:

- A text input named `contact_me_by_fax_only`, positioned off-screen with `aria-hidden="true"`,
  `tabindex="-1"` and `autocomplete="off"`. The name is chosen so browser autofill never matches it.
  It must be empty.
- `_started`: `Crypt::encryptString(now()->timestamp)` rendered into the form. Reject if it's
  missing, fails to decrypt, or is under 3 seconds old.
- On either failure: a **visible** generic error (`register.errors.generic`, "please try again"), no
  booking, and `Log::info('registration.rejected', ['reason' => …])`. It's not a fake success page. A
  real person whose password manager filled the honeypot has to find out it didn't work.

**Expired CSRF token (constraint 3).** In `withExceptions()`:

```php
$exceptions->render(function (HttpException $e, Request $request) {
    if ($e->getStatusCode() === 419 && $request->routeIs('register.store')) {
        return back()->withInput($request->except('_token', '_started'))
            ->withErrors(['form' => __('register.errors.expired')]);
    }
});
```

**Captcha: not at launch.** A few hundred graduates on an unadvertised URL, plus the honeypot, time
trap and throttle, is proportionate. If junk bookings show up anyway, **Cloudflare Turnstile** is the
answer, not reCAPTCHA. It's free, it's native to the Cloudflare zone this already sits behind, it
needs no image puzzles, and it sends no data to Google. It adds a widget script, a
`TURNSTILE_SECRET` in `.env` read via `config/services.php`, and one `Http::asForm()->post()` to
`siteverify` in `after()`. The `Http` facade uses curl, not a shell, so the FPM `disable_functions`
list doesn't affect it. Keep that as a documented follow-up in the README. A zero-code option to use
first: Cloudflare's free plan includes one edge **rate-limiting rule**, which could cover
`POST /register`.

---

## Phase 4: Admin

**Navigation and entry point.**

- **Routes:** `GET /admin` becomes a redirect to `admin.bookings.index`. Bookings are the daily work;
  runners are edited once a season. The runners index moves from `/` to `GET runners` with its route
  name **unchanged** (`admin.runners.index`), so `RunnerController`'s redirects keep working. Add
  `AdminLocale` and a **`Cache-Control: no-store`** response header to the whole `auth` group (a small
  `app/Http/Middleware/NoStore.php`). Admin pages now carry personal data, and the team's phones are
  shared devices in practice.
- **`bootstrap/app.php`:** `redirectUsersTo(fn () => route('admin.bookings.index'))`, in the same
  `withMiddleware()` callback where it lives today. Do **not** move it to a provider (see the
  runner-directory plan, constraint 1).
- **`admin/layout.blade.php`:**
  - The brand link points at bookings.
  - Add a nav: **Bookings · Runners · Faculties · Robe sizes · Sessions** (the last three come from
    Phase 1b), with the active state from `request()->routeIs(…)`. On phones it wraps or scrolls
    horizontally.
  - Widen `max-w-4xl` to `max-w-6xl` so the bookings table fits.

**Routes** (inside the existing `auth` group; `export` goes **before** `{booking}`):

```php
Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::get('bookings/export', BookingExportController::class)->name('bookings.export');
Route::get('bookings/{booking:reference}', [BookingController::class, 'show'])->name('bookings.show');
Route::patch('bookings/{booking:reference}', [BookingController::class, 'update'])->name('bookings.update');
Route::delete('bookings/{booking:reference}', [BookingController::class, 'destroy'])->name('bookings.destroy');
```

Binding by `reference` keeps sequential IDs out of URLs and screenshots.

**`BookingController`:**

- **`index`**:
  - Filters: `status`, `convocation_session_id`, `faculty_id`, `delivery_method`, `runner_id` (with
    an "unassigned" option), `paid` (yes/no). The option filters list inactive entries too, so an old
    season stays filterable.
  - `q` search via `search()`.
  - Status count chips across the top, each a filter link.
  - Newest first, `paginate(50)->withQueryString()`.
  - Columns: reference, name + matric, session, size, delivery, status badge, paid badge, runner,
    created. Times use `->timezone(config('jubahrunner.timezone'))`.
  - The table sits in `overflow-x-auto` for phones.
- **`show`**: one page that both shows and edits the booking. All registrant fields are editable (to
  fix typos), plus status, runner, admin notes, amount, and payment (a "Paid" checkbox, method
  select and reference).
  - The runner `<select>` lists **active** runners **plus** the currently assigned one even if it's
    inactive, so saving the form doesn't silently unassign them.
  - A **WhatsApp button to the registrant** (`$booking->whatsapp_url`, `<x-whatsapp-icon>`), with
    text prefilled from `__('register.admin_greeting', […], $booking->locale)` so the first message
    is in the language they registered in.
- **`update`**: `BookingUpdateRequest`, which follows the same normalisation as `RegistrationRequest`.
  - `status` validated as `Rule::enum(BookingStatus::class)`.
  - `runner_id` nullable|exists:runners,id.
  - The three option FKs use `exists` plus the right `type`, and accept **either an active option or
    the booking's current one**. An admin fixing a typo in the name mustn't be forced to change a
    session that has since been deactivated.
  - The matric regex from `Booking::matricRuleFor()`.
  - The matric unique rule `->ignore($booking)`, applied only when the new status isn't cancelled
    (this covers the un-cancel case).
  - The payment guards from Phase 1 go in `after()`.
  - Ticking "Paid" sets `paid_at = now()` if it's null; unticking clears the payment fields.
  - Status changes go through `$booking->moveTo()`.
- **`destroy`**: a real delete behind `confirm()`. It exists to handle PDPA erasure and consent
  withdrawal, and the button label says so ("Delete permanently (erasure request)").

**`BookingExportController`** (single action), a CSV via `response()->streamDownload()`:

- Applies **the same filters as the index.** Share one `BookingFilters` query-builder class between
  the two so the export can't drift from what's on screen.
- The file starts with a UTF-8 BOM so Excel shows Malay names correctly. Filename
  `jubahpanda-bookings-YYYY-MM-DD.csv`.
- **Formula-injection escaping**: prefix any cell starting with `= + - @ \t \r` with `'`. Names and
  notes are typed by the public.
- **Every export is logged**: `Log::notice('bookings.exported', ['user' => email, 'filters' => …, 'rows' => n])`.
  Since every admin sees everything, this record of who took a copy of the data is the cheap part of
  accountability.

**Admin copy stays hardcoded English**, as before, with the same one-line comment in the new views.
The one exception is `register.admin_greeting`: it's sent to the registrant, so it's public copy.

**Runner delete:** extend the existing `confirm()` text to say "N bookings will become unassigned".
`nullOnDelete` does the rest.

---

## Phase 5: Personal data (PDPA 2010)

This is not legal advice. It's the practical minimum for a small team handling a few hundred records.

**What's collected:** name, matric number, WhatsApp number, faculty, programme level, robe size,
convocation session, a Kuantan address only for COD, and a free-text note. **No email, no IC number,
no uploads** (decided). Every field is used by someone on the team; that's the data-minimisation
test.

**Notice (s.7) and consent (s.6).** **New** `/privacy` page rendering **both languages on one page**,
current locale first. s.7(3) requires the notice in both the national language and English, and this
satisfies that literally rather than relying on the language toggle. It's linked from the consent
checkbox and the footer. Contents:

- Who: JubahPanda, contact `JP_EMAIL` and the main WhatsApp line.
- What is collected, and which fields are obligatory (all except the note). Without them the robe
  can't be collected.
- Purpose: collecting the robe from UMPSA on the graduate's behalf, contacting them, arranging
  handover, and keeping payment records.
- Source: the graduate.
- **Disclosure:** UMPSA robe-counter staff (name, matric number and authorisation letter, when the
  runner collects); WhatsApp/Meta as the messaging channel; Cloudflare, which carries traffic to the
  site; the hosting provider. **Data is stored on a server in Malaysia (Johor).**
- Retention: **1 year from registration, plus up to 14 days in rolling backups.**
- Rights: access, correction, and withdrawing consent (which means deletion), via WhatsApp or email.
- Security: access is limited to the team's admin accounts.

Consent checkbox copy:

> **en:** I have read the privacy notice and agree to JubahPanda using these details to collect my
> robe from UMPSA on my behalf and to contact me about it on WhatsApp.
>
> **ms:** Saya telah membaca notis privasi dan bersetuju JubahPanda menggunakan butiran ini untuk
> mengambil jubah saya daripada UMPSA bagi pihak saya dan menghubungi saya melalui WhatsApp mengenainya.

`consented_at` and `privacy_version` go on each row. Bump the version when the notice changes
materially.

**Retention: 1 year.** **New** `app/Console/Commands/PurgeBookings.php`:

- Signature `jubahpanda:purge-bookings {--dry-run} {--force}`.
- It hard-deletes bookings whose `created_at` is older than `jubahrunner.retention_days`, whatever
  their status.
- `ConfirmableTrait`, so production requires `--force`.
- It prints the count. `--dry-run` prints what would go without deleting.
- It's picked up by the existing `->withCommands()`.
- It runs daily from cron (Phase 6), not from the Laravel scheduler, because there's no
  `schedule:run` cron on the box and one job doesn't justify adding one.
- The first real deletion happens in October 2027. The cron job exists from now on so nobody has to
  remember.

**Every admin sees everything.** That's accepted, not solved. There are no roles, and adding them
isn't worth it for eight people who all do every job. Mitigations in this plan:

- as few admin accounts as possible (only the people who handle bookings);
- `no-store` on admin responses;
- export logging;
- a real delete for erasure requests;
- `noindex` and `robots.txt` (already present).

Removing someone's access is currently a tinker one-liner (`User::where('email', …)->delete()`).
Document it in `docs/deploy.md`. Revisit roles if anyone outside the runner team ever gets a login.

**Breach.** The 2024 PDPA amendments (in force 2025) require notifying the Personal Data Protection
Commissioner of a data breach within 72 hours, and affected individuals without undue delay where
significant harm is likely. Check the current Commissioner guidelines when writing this up. Add a
short "If the database leaks" note to `docs/deploy.md` covering who to tell and where the export
logs are. A DPO appointment isn't triggered at this volume.

---

## Phase 6: Config, docs, backups

**`.env.example`**: document the new meaning of `JP_REGISTER_URL` (blank means the in-app form; set
it only as an override or kill switch) and add `JP_REGISTRATION_CLOSES_AT=` with a format example.

**README.md**: rewrite "Status" (there's now a registration form and admin), tick off "Next steps" 1
and 3, add the new `JP_*` rows to the env table, and document the Turnstile follow-up.

**New** `deploy/jubahpanda.cron`, installed as `/etc/cron.d/jubahpanda` (the file name has no dot,
so cron.d accepts it):

```cron
# Both run as runnerconvo, NOT root. A root sqlite3 opening a WAL database can
# create root-owned -wal/-shm files, and php-fpm (runnerconvo) then fails every
# write with "attempt to write a readonly database".
15 3 * * * runnerconvo sqlite3 /opt/runnerconvo/runnerconvo/database/database.sqlite ".backup /var/backups/jubahpanda/jubahpanda-$(date +\%F).sqlite" && find /var/backups/jubahpanda -name '*.sqlite' -mtime +14 -delete
30 3 * * * runnerconvo cd /opt/runnerconvo/runnerconvo && php artisan jubahpanda:purge-bookings --force >/dev/null
```

**`docs/deploy.md`**: rewrite before deploying, then follow it:

| Section | Change |
|---|---|
| `### A small SQLite database` | Now holds `bookings`, **graduates' personal data**. Replace the "MySQL … for when the registration form lands" paragraph: bookings are SQLite too (a few hundred rows, one writer at a time, WAL already on). Keep "do not provision MySQL". |
| apt block comment | Drop the "registration form … separate future deploy" aside. `php8.4-mysql` is still not needed. |
| `.env` heredoc | Add `JP_REGISTRATION_CLOSES_AT=` and comment `JP_REGISTER_URL` as the kill switch. |
| `## When that is not enough` | New bullet **"Registration: close, reopen, kill switch"**: an `.env` edit plus `config:cache`, no deploy. |
| `## Sanity-check after deploying` | Add the curl checks from *Verification*. |
| `## Backing up the database` | **No longer optional.** Install `deploy/jubahpanda.cron`; `install -d -o runnerconvo -g runnerconvo -m 700 /var/backups/jubahpanda`. **Change the off-box copy's destination from `./backups/` to a folder outside OneDrive** (constraint 6), and say why. Add a restore drill: `sqlite3 copy.sqlite "PRAGMA integrity_check; SELECT count(*) FROM bookings;"`. |
| **New** `## Personal data` | Who has admin access and how to revoke it; export logs live in `storage/logs`; the purge cron; the "if the database leaks" steps. |
| `## Do not` | Add: do not copy the database or a CSV export into OneDrive, WhatsApp or email; do not run `sqlite3` against the live file as root; do not delete `/etc/cron.d/jubahpanda` to "clean up". |

---

## Deployment: one code deploy, two exposure steps

The `JP_REGISTER_URL` override means a single code deploy can ship with the form **unlinked**. It gets
tested live, and only then do the CTAs switch over. Switching back is an `.env` edit.

```bash
# 0. Pre-flight: is mod_remoteip already rewriting REMOTE_ADDR? (Either answer is fine; just know.)
ssh 160.30.5.87 "apache2ctl -M 2>/dev/null | grep -i remoteip || echo 'not loaded'"

# 1. Backup NOW, before the first migration that touches data worth keeping.
ssh 160.30.5.87 "apt install -y sqlite3 && install -d -o runnerconvo -g runnerconvo -m 700 /var/backups/jubahpanda \
  && sudo -u runnerconvo sqlite3 /opt/runnerconvo/runnerconvo/database/database.sqlite \
     \".backup /var/backups/jubahpanda/pre-bookings.sqlite\""

# 2. Pin the CTAs to the CURRENT target so this deploy changes nothing publicly.
#    By hand in /opt/runnerconvo/runnerconvo/.env:
#      JP_REGISTER_URL=https://wa.me/<JP_WHATSAPP_NUMBER>
#      JP_REGISTRATION_CLOSES_AT="2026-10-21 23:59"
#      JP_EMAIL=support@jubahpanda.my

# 3. Pull, config FIRST, migrate, then the other caches.
ssh 160.30.5.87 "cd /opt/runnerconvo/runnerconvo \
  && sudo -u runnerconvo git pull \
  && sudo -u runnerconvo php artisan config:cache \
  && sudo -u runnerconvo php artisan migrate --force \
  && sudo -u runnerconvo php artisan route:cache \
  && sudo -u runnerconvo php artisan view:cache"

# 4. Assets: LOCAL machine. The form, radio cards, admin table and nav are all new
#    utilities. Skipping this renders /register unstyled.
npm run build && scp -r public/build 160.30.5.87:/tmp/rc-build
ssh 160.30.5.87 "rm -rf /opt/runnerconvo/runnerconvo/public/build \
  && mv /tmp/rc-build /opt/runnerconvo/runnerconvo/public/build \
  && chown -R runnerconvo:runnerconvo /opt/runnerconvo/runnerconvo/public/build \
  && chmod -R go+rX /opt/runnerconvo/runnerconvo/public/build"

# 5. Cron (backup + purge).
scp deploy/jubahpanda.cron 160.30.5.87:/tmp/jubahpanda.cron
ssh 160.30.5.87 "install -m 644 -o root -g root /tmp/jubahpanda.cron /etc/cron.d/jubahpanda && rm /tmp/jubahpanda.cron"
```

**Enter the option lists.** Log in and fill in `/admin/options/faculty`, `robe_size` and
`convocation_session` in both languages. Until all three have an active entry, `/register` shows
"opens soon". That's intended, and it means this step can happen before or after the deploy steps
above.

**Test live while the form is unlinked.** Open `https://jubahpanda.my/register` directly on a phone,
in both languages. Submit a booking with an obviously fake matric that still fits the format
(`ZZ99001`), find it in `/admin/bookings`, walk it through every status, export a CSV, then
**delete it**. Also try deleting an option that booking used before you delete the booking: it
should refuse.

**Flip.** Blank `JP_REGISTER_URL=` in `.env`, then run `sudo -u runnerconvo php artisan config:cache`.
The five CTAs now point at `/register`.

**Kill switch.** Put `JP_REGISTER_URL=https://wa.me/…` back and `config:cache` again. That's instant
and needs no deploy. `/register` itself stays reachable for anyone who already has the link. If that
matters, set `JP_REGISTRATION_CLOSES_AT` to a past time as well.

---

## Verification

**Locally:**
```bash
php artisan migrate && php artisan test
php artisan tinker --execute="App\Models\Booking::factory()->count(30)->create();"
npm run dev    # /register at 375px in both languages; submit with errors in ms; COD toggle; /admin/bookings filters + export
php artisan jubahpanda:purge-bookings --dry-run
```

**Tests.** Every DB-touching test uses `RefreshDatabase`. A `validPayload()` helper includes a
`_started` token from 1 minute ago.

- **New** `tests/Feature/Registration/RegistrationFormTest.php`:
  - The page renders in `en` and `ms`.
  - A valid submission creates a `submitted` booking with **phone and matric normalised**, a
    reference matching `/^JP-[A-HJKMNP-Z2-9]{6}$/`, `consented_at`, `privacy_version`, `locale`, and
    `amount_sen = 4500`. It redirects to `register.done`, which shows the reference and a `wa.me` link
    whose `text=` contains it.
  - `register.done` without a session redirects to `register`.
  - Each field is required.
  - COD without an address fails; pickup without one passes.
  - Unchecked consent or documents fails.
  - An **inactive** option id fails, and so does an id from the wrong list (a session id sent as
    `faculty_id`). Inactive options aren't rendered in the selects. The `ms` page shows `label_ms`.
  - **Matric by level:** Bachelor `CB22001` passes; `CB22000`, `CB2201` and `CBA22001` fail. A PhD
    with `PHD21001` passes on the loose rule.
  - With one list empty, `/register` shows "opens soon" and a POST creates nothing.
  - **A duplicate matric formatted differently** (`cb-19012` vs `CB19012`) fails as a validation
    error, not a 500.
  - **A cancelled booking doesn't block** re-registering.
  - **A closed window** shows the closed card, and a POST creates nothing.
  - **The write path is not rescued**: `Schema::drop('bookings')` followed by a valid POST gives
    `assertServerError()`.
- **New** `tests/Feature/Registration/RegistrationProtectionTest.php`:
  - A filled honeypot is rejected with a visible error and nothing created.
  - A submission after 1 second is rejected (`travel`).
  - A tampered `_started` is rejected.
  - The 11th POST in a minute returns 429.
  - **Two different `X-Forwarded-For` IPs behind one Cloudflare `REMOTE_ADDR` get separate buckets**
    (the regression test for constraint 2).
  - An expired token redirects back with input and the `form` error (the regression test for
    constraint 3). Laravel skips CSRF checks under PHPUnit, so bind a `ValidateCsrfToken` subclass
    whose `runningUnitTests()` returns false, then POST without a token.
- **New** `tests/Feature/Registration/RegistrationLocaleTest.php`:
  - With `session(['locale' => 'ms'])`, an empty POST shows Malay messages with Malay attribute
    names (for example, "wajib").
  - An admin with an `ms` session still gets English errors on the runner form (the `AdminLocale`
    regression test).
- **New** `tests/Feature/Admin/BookingManagementTest.php`:
  - Guests are redirected to `admin.login`.
  - `/admin` redirects to bookings.
  - Filtering by status, session and runner.
  - Search by reference, by matric, and **by phone typed as `012-…`**.
  - Updating details, assigning a runner, and an inactive assigned runner staying selected.
  - `moveTo` sets the stage timestamps.
  - **A self-pickup booking can't be `collected` unpaid.**
  - **`handed_over` requires paid**, and COD succeeds when "Paid" is ticked in the same request.
  - Un-cancelling onto a taken matric fails.
  - Deleting a runner leaves its bookings unassigned.
  - Destroying a booking removes it.
  - Admin responses carry `no-store`.
- **New** `tests/Feature/Admin/BookingExportTest.php`: the header row and BOM; filters respected;
  `=HYPERLINK(…)` in a name comes out prefixed with `'`; the export is logged (`Log::spy()`).
- **New** `tests/Feature/PurgeBookingsTest.php`: bookings older than 365 days are deleted and newer
  ones kept, `--dry-run` deletes nothing, and production without `--force` refuses.
- **New** `tests/Unit/BookingTest.php`: a `normaliseMatric` data provider; reference alphabet and
  length; `matricRuleFor()` per level.
- **New** `tests/Feature/Admin/BookingOptionManagementTest.php`:
  - Create appends at the end of its own type.
  - Both labels are required.
  - A duplicate `label_en` within a type is rejected, but the same label in another type is allowed.
  - Editing a faculty through the sessions URL 404s.
  - Deactivating hides it from `/register`.
  - Delete works when the option is unused and is **refused with a message when it's used** (no 500).
  - Move up/down swaps within the type only.
  - Renaming a label shows up on existing bookings.
  - Guests are redirected.
- **Edit** `AdminAuthTest`: the login and "already logged in" redirects now expect
  `admin.bookings.index`.
- **Edit** `LandingPageTest`: the CTAs point at `route('register')` when `register_url` is null and
  at the override when it's set; the nav links are `…/#how`; and **`config('jubahrunner.price_sen')`
  matches `landing.pricing.price` in both locales** (the drift guard for the duplicated price).
- **Edit** `TranslationParityTest`: every `lang/en/*.php` file has a key-identical `lang/ms/` twin.

**In production, after the deploy and again after the flip:**
```bash
curl -sI https://jubahpanda.my/register | head -1                        # 200
curl -s  https://jubahpanda.my/register | grep -c 'contact_me_by_fax_only' # 1
curl -s  -o /dev/null -w '%{http_code}\n' -X POST https://jubahpanda.my/register  # 302 back to /register (no CSRF token: the expired-token handler)
curl -sI https://jubahpanda.my/register/done | grep -i location          # → /register
curl -sI https://jubahpanda.my/admin/bookings | head -1                  # 302 to /admin/login
curl -s  https://jubahpanda.my/ | grep -o 'href="[^"]*register"' | sort -u  # after flip: https://jubahpanda.my/register
ssh 160.30.5.87 "ls -l /var/backups/jubahpanda/"                         # next morning: a dated file, owner runnerconvo
```

---

## Known trade-offs

1. **Malaysian mobiles only.** International graduates can't complete the form and are told to
   WhatsApp the team instead. Relaxing the regex to allow any country code is a one-line change, but
   it makes "is this a real WhatsApp number?" harder to check, so wait until someone actually asks.
2. **The matric regex is strict.** If any graduate's matric doesn't fit `XXyyaaa`, the form blocks
   them. They'll see the error with the example format, and the team can add them from WhatsApp by
   loosening the regex (a one-line change).
3. **SQLite for public writes.** At a few hundred bookings over a few weeks, one writer at a time with
   WAL and `busy_timeout=5000` is plenty. The ceiling is concurrent writes, not rows. If a launch-day
   rush ever produced `database is locked`, the 500 page tells people to retry, and moving to the
   box's MySQL would be the next step. That isn't worth doing preemptively.
4. **No captcha.** The bet is that honeypot, time trap and throttle are enough for an unadvertised
   URL. Turnstile is about 30 lines if the bet turns out wrong.
5. **No email confirmation.** `MAIL_MAILER=log`, and WhatsApp is the real channel anyway. The
   reference on the done page, plus the prefilled WhatsApp message, is the receipt. (Since
   2026-09-25 prod mail works, via Resend. See "Mail" in `docs/deploy.md`. This is now a choice
   rather than a constraint.)
6. **Every admin sees every booking**, with no roles and no per-runner scoping. That's accepted for
   eight people. Export logging and a short admin list are the mitigations.
7. **Option lists live in the database, so nothing in CI checks their translations.** The parity test
   can't see them. The admin form requiring both `label_en` and `label_ms` is the only guard, so a
   careless Malay label ships as typed. In exchange, the team edits the lists without a deploy, and
   deactivate-not-delete means an old booking never loses its label. The `/register` page also now
   reads three small queries from SQLite. That's fine: it's the write path, which is allowed to fail
   visibly.
8. **The price exists twice**, as `price_sen` in config and "RM 45" in both lang files. A test pins
   them together, so a change that updates only one of them fails CI.
9. **The kill switch doesn't hide `/register`.** It only moves the CTAs. Anyone holding the direct
   link can still register until `JP_REGISTRATION_CLOSES_AT` passes.

## Order of work

1. Confirm the Master/PhD matric format if possible (not blocking; the loose fallback covers it).
2. `booking_options` migration, enum, model and factory, then the bookings migration, enum, model and
   factory, `RegistrationWindow`, config keys; `BookingTest`.
3. `trustProxies`, the rate limiter, and the 419 render callback in `bootstrap/app.php` and
   `AppServiceProvider`, with their regression tests **first**.
4. Lang files (`register`, `privacy`, `ms/validation`) and the generalised `TranslationParityTest`.
5. `RegistrationRequest`, `RegistrationController`, form, done and closed views, `app.js`
   enhancements, error pages; the Registration tests.
6. The `$registerUrl` composer, the five CTA call sites, the nav/footer fragment fix; the
   `LandingPageTest` edits.
7. Admin nav, the `/admin` redirect, `AdminLocale` and `NoStore`, **option lists CRUD** (Phase 1b),
   then bookings index, show, update, export and destroy; the Admin tests and `AdminAuthTest` edits.
8. `/privacy` page, `PurgeBookings` command, `deploy/jubahpanda.cron`.
9. `.env.example`, README, and the **`docs/deploy.md` rewrite before deploying**.
10. Deploy unlinked, test live, delete the test booking, flip, and confirm the backup ran the next
    morning.
