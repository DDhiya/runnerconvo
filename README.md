# JubahPanda

Landing page for **JubahPanda**, a convocation robe runner service for graduates of
Universiti Malaysia Pahang Al-Sultan Abdullah (UMPSA).

Graduates register with us, we collect their robe from the university on their behalf,
store it safely, and hand it over 1–2 days before convocation.

> JubahPanda is an independent service. It is not affiliated with,
> endorsed by, or representing UMPSA.

## Status

A bilingual landing page plus a **registration form** (`/register`) that stores bookings in
SQLite, and a password-protected **admin** (`/admin`) where the team manages bookings, runners
and the form's option lists (faculties, robe sizes, convocation sessions). There is no payment
gateway yet — the booking schema already has the columns one will fill.

`JP_REGISTER_URL` is now only an **override / kill switch**: blank sends every "Register" button
to `/register`; set it to a `wa.me` link to point them elsewhere instantly (`.env` + `config:cache`,
no deploy). The full design is in [`docs/registration-form-plan.md`](docs/registration-form-plan.md).

## Stack

| | |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Styling | Tailwind CSS v4 via `@tailwindcss/vite` (CSS-first, no `tailwind.config.js`) |
| Build | Vite 8 |
| Fonts | Instrument Sans, self-hosted at build time |
| Languages | Bahasa Melayu (default) and English |

## Local setup

```bash
composer install
npm install
cp .env.example .env      # then fill in the JP_* values
php artisan key:generate
```

Run it with two terminals:

```bash
npm run dev          # Vite
php artisan serve    # http://127.0.0.1:8000
```

### PHP extensions

`intl`, `pdo_sqlite` and `sqlite3` must be enabled in `php.ini`. Laravel defaults to
SQLite, so `pdo_sqlite` is required even though this page reads no data. Verify with
`php -m`.

## How the page is put together

```
resources/views/
  layouts/app.blade.php      meta tags, gradient background, skip link
  landing.blade.php          section order
  partials/                  nav, hero, problem, steps, pricing, pickup, faq, cta, footer
resources/css/app.css        design tokens (@theme) + .glass-card / .btn-* / .eyebrow
resources/js/app.js          mobile menu, sticky header, scroll reveal
lang/{en,ms}/landing.php     all copy — no user-facing strings live in Blade
config/jubahrunner.php       WhatsApp, Instagram, email, address, register URL
                              (filename kept through the JubahPanda rebrand, same
                              as the repo, VPS path and system user — all still
                              `runnerconvo`. The env vars it reads are `JP_*`.)
public/images/               logo mark, wordmark lockup and generated og-image.png
app/Http/Middleware/SetLocale.php
```

**Adding or changing copy** means editing `lang/en/landing.php` and `lang/ms/landing.php`.
Both files must keep identical key structures. To check they have not drifted:

```bash
php -r '
function flat($a,$p=""){ $o=[]; foreach($a as $k=>$v){ $key=$p===""?$k:"$p.$k";
  if(is_array($v)) $o=array_merge($o,flat($v,$key)); else $o[]=$key; } return $o; }
$en=flat(require "lang/en/landing.php"); $ms=flat(require "lang/ms/landing.php");
sort($en); sort($ms);
$d=array_merge(array_diff($en,$ms),array_diff($ms,$en));
echo $d ? "MISMATCH: ".implode(", ",$d)."\n" : "Keys match.\n";'
```

**Language switching** is server-side: `/lang/ms` and `/lang/en` store the choice in the
session, and `SetLocale` middleware applies it. Only locales in `SetLocale::SUPPORTED`
are accepted.

## Before launch — placeholders to fill

Set these in `.env`:

| Variable | What it is |
|---|---|
| `JP_WHATSAPP_NUMBER` | Digits only, international format, no `+` (e.g. `60123456789`) |
| `JP_INSTAGRAM` | Handle without the `@` |
| `JP_EMAIL` | Contact address, shown in the footer and the privacy notice (`support@jubahpanda.my`) |
| `JP_REGISTER_URL` | Blank = the in-app form at `/register`. Override / kill switch only |
| `JP_REGISTRATION_CLOSES_AT` | e.g. `2026-10-21 23:59`, Kuala Lumpur time. Blank = open indefinitely |
| `JP_ADDRESS` | Pickup point address, shown on the page and used to build the map embed |
| `JP_MAP_URL` | "Get directions" link (a Google Maps share link) |

`pricing.price` in `lang/{en,ms}/landing.php` is the flat fee, currently **RM 45**.
`pickup.hours` and `pickup.window` there are the real pickup dates/hours for the
current convocation — update them each intake.

Also outstanding:

- **Payment** — step 2 is a placeholder. See the `TODO(payment)` block in
  `resources/views/partials/steps.blade.php` for the options considered
  (ToyyibPay / Billplz for FPX, DuitNow QR, or Stripe).

## Next steps

1. **Payment integration** - fills `paid_at` / `payment_method` / `payment_reference` on `bookings`
   and moves `submitted` to `confirmed`; nothing else in the schema changes.
2. **Captcha, only if junk bookings appear.** The form is protected by a honeypot, a 3-second
   timer and a per-IP throttle. If that is not enough, use **Cloudflare Turnstile** (free, native to
   the Cloudflare zone, no image puzzles, sends nothing to Google): a widget script, a
   `TURNSTILE_SECRET` in `.env` read via `config/services.php`, and one `Http::asForm()->post()` to
   `siteverify` in `RegistrationRequest::after()`. A zero-code first step is one Cloudflare
   edge rate-limiting rule on `POST /register`.
3. ~~Confirmation email~~ - done: graduates who give the optional email get a bilingual
   confirmation, and every booking alerts `JP_EMAIL` + `JP_NOTIFY_EMAILS` (see "Mail" in
   `docs/deploy.md`). Next step there, if wanted: a status-change email ("your robe is ready").
4. **Master/PhD matric format** - `Booking::matricRuleFor()` accepts any 5-15 letter/digit value
   for them until the real format is confirmed (`TODO(matric)`).
