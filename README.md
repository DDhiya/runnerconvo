# JubahRunner

Landing page for **JubahRunner**, a convocation robe runner service for graduates of
Universiti Malaysia Pahang Al-Sultan Abdullah (UMPSA).

Graduates register with us, we collect their robe from the university on their behalf,
store it safely, and hand it over 1–2 days before convocation.

> JubahRunner is an independent, student-run service. It is not affiliated with,
> endorsed by, or representing UMPSA.

## Status

This is the **landing page skeleton**. It is a static marketing page — there is no
database-backed registration form, no payment gateway, and no admin panel yet.
Every "Register" button points at `JR_REGISTER_URL` (a Google Form or WhatsApp for now).

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
cp .env.example .env      # then fill in the JR_* values
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
config/jubahrunner.php       WhatsApp, Instagram, email, register URL
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
| `JR_WHATSAPP_NUMBER` | Digits only, international format, no `+` (e.g. `60123456789`) |
| `JR_INSTAGRAM` | Handle without the `@` |
| `JR_EMAIL` | Contact address |
| `JR_REGISTER_URL` | Registration form link; falls back to WhatsApp if blank |

And in `lang/{en,ms}/landing.php`:

- `pricing.price` — currently a placeholder **RM 35**
- `pickup.area`, `pickup.hours`, `pickup.window` — currently Pekan, Pahang / by appointment

Also outstanding:

- **Payment** — step 2 is a placeholder. See the `TODO(payment)` block in
  `resources/views/partials/steps.blade.php` for the options considered
  (ToyyibPay / Billplz for FPX, DuitNow QR, or Stripe).
- **Share image** — add `public/og-image.png` (1200×630) and uncomment the `og:image`
  tag in `layouts/app.blade.php`. These links get pasted into WhatsApp groups.
- **Logo** — the wordmark is currently text only.

## Next steps

1. Real registration form persisting to a database
2. Payment integration
3. Admin view for the team to see bookings and payment status
