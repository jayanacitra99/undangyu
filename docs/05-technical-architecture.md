# 05 — Technical Architecture

---

## 1. Stack

| Layer | Choice | Why |
|---|---|---|
| Framework | Laravel 13 (PHP 8.3+) | Your stack |
| Admin & client dashboard | **Blade + AdminLTE 4 + Vue 3 islands** | See §2 — this is the important decision |
| Public invitation renderer | **Vue 3 + Vite, mounted on a Blade-rendered shell** | SSR head for OG tags, SPA-quality animation below |
| CSS (dashboard) | AdminLTE's Bootstrap 5 | Comes with the template |
| CSS (invitations) | Tailwind, scoped to the public bundle | Templates need free-form design; Bootstrap fights you here |
| Database | MySQL 8 | JSON columns, CTEs, window functions |
| Cache / queue / session | Redis | Non-negotiable for the blast moment |
| Queue dashboard | Laravel Horizon | |
| Storage | S3 or Cloudflare R2 | R2 has no egress fees — meaningful for image-heavy invitations |
| CDN | Cloudflare | Free tier is enough to start |
| Payments | Midtrans **or** Xendit | See §6 |
| Errors | Sentry | Already connected |
| Auth | Laravel session (dashboard) + Sanctum (API/scanner) | |

---

## 2. The AdminLTE + Vue decision — read this before you scaffold

You asked for AdminLTE. It's a good call for the admin panel, but it creates real friction if
applied naively to the whole app. Here's the honest tradeoff.

**AdminLTE is a Bootstrap admin theme.** AdminLTE 3 depends on jQuery; AdminLTE 4 is
Bootstrap 5 and largely jQuery-free (prefer 4). It's built for server-rendered pages. If you
run a full Inertia/Vue SPA and try to bolt AdminLTE onto it, you spend your time fighting
initialization lifecycles — AdminLTE's JS expects DOM to exist at page load, Vue replaces DOM
after mount, and components silently stop working.

**Recommended: three surfaces, three approaches.**

| Surface | Approach | Reasoning |
|---|---|---|
| **Admin panel** | Pure Blade + AdminLTE | Tables, forms, CRUD. Server-rendered is faster to build and perfectly adequate. Zero Vue needed |
| **Client dashboard** | Blade + AdminLTE shell, **Vue islands** for complex widgets | Layout/nav from AdminLTE; mount Vue components only where you need reactivity — the builder, guest table, live preview, blast progress |
| **Public invitation** | Blade shell (head + OG tags) + Vue 3 app | Needs SSR'd `<head>` for WhatsApp previews and a rich animated body. Tailwind, not Bootstrap |

**Vue islands pattern:**

```blade
{{-- resources/views/client/invitations/builder.blade.php --}}
@extends('layouts.adminlte')

@section('content')
    <div id="invitation-builder"
         data-invitation="{{ $invitation->toJson() }}"
         data-schema="{{ $template->config_schema }}"></div>
@endsection

@push('scripts')
    @vite('resources/js/islands/invitation-builder.js')
@endpush
```

```js
// resources/js/islands/invitation-builder.js
import { createApp } from 'vue';
import Builder from '@/components/builder/InvitationBuilder.vue';

const el = document.getElementById('invitation-builder');
if (el) {
    createApp(Builder, {
        invitation: JSON.parse(el.dataset.invitation),
        schema: JSON.parse(el.dataset.schema),
    }).mount(el);
}
```

This gives you AdminLTE's chrome for free and Vue's reactivity exactly where it earns its
keep. It's less architecturally pure than a full SPA, and that's fine — it ships faster and
breaks less.

**If you'd rather go full Inertia + Vue:** then drop AdminLTE and use a Vue-native admin
layout instead. Pick one; mixing them halfway is the worst of both.

---

## 3. Repository structure

```
undangyu/
├── app/
│   ├── Actions/                 # single-purpose invokable classes
│   │   ├── Invitations/         # PublishInvitation, DuplicateInvitation
│   │   ├── Orders/              # CreateOrder, ApplyCoupon, ProvisionInvitation
│   │   └── Guests/              # GenerateGuestToken, ImportGuests
│   ├── Console/Commands/
│   ├── Enums/                   # PHP 8.1 backed enums — OrderStatus, InvitationStatus…
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Client/
│   │   │   ├── Public/          # invitation renderer, RSVP, wishes
│   │   │   ├── Api/             # scanner, builder autosave
│   │   │   └── Webhooks/
│   │   ├── Middleware/
│   │   ├── Requests/            # ALL validation lives here
│   │   └── Resources/           # API resources
│   ├── Jobs/
│   ├── Listeners/
│   ├── Models/
│   ├── Notifications/
│   ├── Observers/               # cache invalidation
│   ├── Policies/
│   ├── Services/
│   │   ├── Payment/             # PaymentGateway interface + Midtrans/Xendit drivers
│   │   ├── Whatsapp/            # WhatsappProvider interface + drivers
│   │   ├── Media/
│   │   └── Entitlements/
│   └── Support/                 # helpers, value objects
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/             # adminlte.blade.php, public.blade.php
│   │   ├── admin/
│   │   ├── client/
│   │   ├── public/              # invitation shell
│   │   └── templates/           # per-template Blade shells if needed
│   ├── js/
│   │   ├── islands/             # entry points mounted into Blade
│   │   ├── components/
│   │   │   ├── builder/
│   │   │   ├── guests/
│   │   │   └── shared/
│   │   ├── invitation/          # public renderer app
│   │   │   ├── templates/       # one folder per template view_key
│   │   │   └── sections/        # shared section components
│   │   └── composables/
│   └── css/
├── routes/
│   ├── web.php
│   ├── admin.php
│   ├── client.php
│   ├── public.php
│   ├── api.php
│   └── webhooks.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── docs/                        # this documentation
└── CLAUDE.md
```

**Route file split matters.** A single `web.php` with 200 routes becomes unnavigable, and
Claude Code works much better against small focused files. Register the extras in
`bootstrap/app.php`.

---

## 4. Packages

```bash
# Core
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog
composer require spatie/laravel-backup
composer require spatie/laravel-sluggable
composer require laravel/horizon
php artisan install:api                      # installs sanctum + creates routes/api.php

# Domain
composer require maatwebsite/excel          # guest import/export
composer require simplesoftwareio/simple-qrcode
composer require barryvdh/laravel-dompdf    # invoices
composer require intervention/image intervention/image-laravel  # OG image generation
composer require propaganistas/laravel-phone # phone normalisation — essential for WA
composer require sentry/sentry-laravel

# Payment (pick one)
composer require midtrans/midtrans-php
# or
composer require xendit/xendit-php

# Dev
composer require --dev laravel/pint
composer require --dev larastan/larastan
composer require --dev barryvdh/laravel-debugbar
```

```bash
# Frontend
npm install vue @vitejs/plugin-vue
npm install admin-lte@^4
npm install bootstrap @popperjs/core
npm install axios
npm install @vueuse/core
npm install vuedraggable@next      # section reordering
npm install html5-qrcode           # scanner
npm install swiper                 # gallery
npm install aos                    # scroll animations
npm install dayjs
npm install tailwindcss @tailwindcss/vite   # public invitations only
```

---

## 5. Template engine

The key architectural question: how does a template become a rendered page?

**Recommended: hybrid.**

- Each template is a **Vue component folder** under `resources/js/invitation/templates/{view_key}/`.
  This gives you real animation, real layout freedom, real performance.
- Each template declares a **`config_schema`** in the DB describing what the client can tweak
  (colour tokens, font pairs, cover style, section toggles).
- The invitation's `theme_config` JSON holds the client's chosen values, validated against
  the schema.
- Content comes from the normalised tables (`invitation_persons`, `invitation_events`, …),
  not from the template.

```
Content (DB tables)  ─┐
Theme (theme_config) ─┼─→ InvitationPayload (cached JSON) ─→ Vue template component ─→ HTML
Schema (template)    ─┘
```

**Why not pure DB-driven JSON layouts?** You'd be building a page builder. That's a
year of work and the output looks generic. Wedding invitations sell on visual distinctiveness.

**Why not pure file-based with no schema?** Then clients can't change a single colour without
you deploying. The schema is the compromise: designers get real code, clients get bounded
customisation.

**Adding a template = ** create the Vue folder + insert a DB row + build. Accept that this
needs a deploy. That's a normal release cadence, not a blocker.

**`config_schema` shape:**

```json
{
  "colors": {
    "primary":   { "type": "color", "default": "#8B7355", "label": "Warna utama" },
    "secondary": { "type": "color", "default": "#D4C5B0" },
    "text":      { "type": "color", "default": "#3A3A3A" }
  },
  "fonts": {
    "heading": { "type": "select", "options": ["Playfair Display", "Cormorant", "Marcellus"], "default": "Playfair Display" },
    "body":    { "type": "select", "options": ["Lato", "Montserrat", "Inter"], "default": "Lato" }
  },
  "options": {
    "cover_style":     { "type": "select", "options": ["full", "framed", "split"], "default": "full" },
    "animation":       { "type": "select", "options": ["fade", "slide", "none"], "default": "fade" },
    "show_countdown":  { "type": "boolean", "default": true },
    "show_music_button": { "type": "boolean", "default": true }
  }
}
```

Validate `theme_config` against `config_schema` server-side on save. Never trust the client
to stay inside the schema.

---

## 6. Payments

**Midtrans vs Xendit:**

| | Midtrans | Xendit |
|---|---|---|
| Local method coverage | Excellent | Excellent |
| Snap popup UI | Very good, saves you building checkout | Invoice page, also fine |
| Developer docs | Adequate | Better |
| Payout/disbursement API | Available | Better — matters for affiliate withdrawals (M10.5) |
| Market familiarity (ID) | Higher | High |

**Recommendation:** Xendit if you're confident you'll build the affiliate payout automation;
Midtrans if you want the fastest path to a working checkout and will pay affiliates manually
at first. Either way, hide it behind an interface:

```php
interface PaymentGateway
{
    public function createTransaction(Order $order): PaymentSession;
    public function verifyWebhook(Request $request): bool;
    public function parseWebhook(Request $request): PaymentUpdate;
    public function refund(Payment $payment, ?float $amount = null): RefundResult;
}
```

Bind the concrete driver in a service provider from `config('payment.driver')`. Swapping
gateways later then costs one class, not a rewrite.

**Non-negotiable webhook rules:**
1. Verify signature before anything else.
2. Look up by `gateway_ref` with `lockForUpdate()` inside a transaction.
3. If already settled → return 200, do nothing.
4. Log raw payload before processing.
5. Dispatch provisioning to a queue; return 200 fast.
6. Run a daily reconciliation command against the gateway's settlement report.

---

## 7. Caching strategy

| What | Where | TTL | Invalidated by |
|---|---|---|---|
| Published invitation payload | Redis, tagged `invitation:{id}` | 1h | Observer on invitation + all child models |
| Template config schema | Redis | 24h | Template save |
| Package + features | Redis | 24h | Package save |
| Settings | Redis, forever | ∞ | Setting save |
| Public invitation HTML (no `?to=`) | Cloudflare edge | 10m | Purge on publish/update |
| Wishes feed | Redis, tagged | 60s | New approved wish |
| RSVP summary counts | Redis, tagged | 60s | New RSVP |

**Personalized pages can't be edge-cached** (the `?to=` token varies per guest). Keep the
Laravel-side payload cache hot so the per-guest render is just a template fill.

**Observer pattern for invalidation:**

```php
// app/Observers/InvalidatesInvitationCache.php
public function saved(Model $model): void
{
    Cache::tags("invitation:{$model->invitation_id}")->flush();
}
```

Register it on every child model. Do this once, at the start — retrofitting cache
invalidation after the fact is how you end up serving a guest last week's venue address.

---

## 8. Security requirements

| Concern | Mitigation |
|---|---|
| Guest token enumeration | Random suffix, not sequential. Rate-limit token lookups |
| Cross-tenant data access | Policies on every model + `AuthorizesRequests`. Test explicitly |
| Mass assignment | `$fillable` on every model. Never `$guarded = []` |
| XSS in wishes/names | Escape on output. Never `v-html` on user content |
| Bank account exposure | Encrypted cast at rest; displayed only on published invitations |
| Webhook forgery | Signature verification, IP allowlist where the gateway supports it |
| File upload abuse | MIME validation, extension allowlist, size caps, store outside webroot, never execute |
| RSVP/wish spam | Rate limit per IP per invitation, honeypot field, optional captcha at high volume |
| Admin panel | 2FA, IP logging, activity log on every write |
| Impersonation | Audit-logged, visible banner, time-limited, super-admin/admin/support only |
| PII retention | Purge guest data after retention window (M13.10) |
| Password-protected invitations | Hash the password, session-scope the unlock |

**Guest data is other people's PII.** Names, phone numbers, and addresses of hundreds of
people per invitation, held on behalf of your client. Treat a leak as a serious incident,
publish a clear privacy policy, and implement the purge job before you have real volume —
not after.

---

## 9. Performance targets & techniques

| Technique | Where |
|---|---|
| Eager loading everywhere | The invitation payload query is one query with nested `with()` |
| Payload cached as JSON | Avoid rebuilding the object graph per request |
| Queued view tracking | Never write to the DB on a page view |
| Image conversions on upload | Never resize on request |
| WebP + `srcset` | All invitation images |
| Lazy-load below the fold | Gallery, wishes |
| Route-level code splitting | Only ship the one template's bundle |
| Aggregate analytics tables | Charts never touch raw view rows |
| Chunked imports | Guest import batches of 100 |
| DB connection pooling | Under blast load |

**Load-test before the first real wedding.** Simulate 500 concurrent requests against one
published invitation. Fix what breaks. You do not get a second chance on someone's wedding day.

---

## 10. Environments & deployment

| Env | Purpose |
|---|---|
| local | Laravel Herd / Laragon on Windows, SQLite or MySQL |
| staging | Mirrors production, seeded demo data, gateway in sandbox mode |
| production | VPS or managed (Forge/Ploi + DigitalOcean/Hetzner) |

**Required in production:** Redis, queue workers via Supervisor (or Horizon), scheduler cron,
daily DB backup to off-server storage, Sentry, uptime monitoring with alerting to your phone.

**Deployment checklist:**
```bash
php artisan down --render=maintenance
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache route:cache view:cache event:cache
php artisan queue:restart
php artisan up
```

---

## 11. Testing strategy

Test what breaks money or weddings. Skip the rest.

**Must have Feature tests:**
- Payment webhook: valid, invalid signature, duplicate delivery, unknown ref
- Order → provisioning: entitlements snapshotted correctly
- Publish validation: each gate rejects appropriately
- Cross-tenant access: client A cannot read/write client B's invitation, guests, or RSVPs
- Guest quota enforcement at the boundary
- Guest import: partial success with error report
- Guest token resolution: valid, invalid, expired invitation
- RSVP submission: with token, without token, over `max_pax`
- Invitation expiry behaviour

**Unit tests:**
- Coupon discount calculation (percent, fixed, max discount, min order)
- Commission calculation
- Entitlement resolution
- Phone normalisation
- Token generation uniqueness under collision

Run `pint` and `larastan` (level 5+) in CI.
