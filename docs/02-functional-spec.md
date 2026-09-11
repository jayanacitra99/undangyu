# 02 — Functional Specification

Modules are numbered. Every feature has an ID (`M{module}.{n}`) so you can reference it in
Claude Code prompts, commits, and issues: `feat(M5.3): guest CSV import`.

Priority: **P0** = MVP blocker · **P1** = Phase 2 · **P2** = Phase 3+

---

## M1 — Identity & Access

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M1.1 | Register (email + phone), login, logout | P0 | Phone is required — WhatsApp is the primary channel in this market |
| M1.2 | Email verification | P0 | Laravel built-in |
| M1.3 | Password reset | P0 | |
| M1.4 | Roles & permissions | P0 | `spatie/laravel-permission`. Roles: `super-admin`, `admin`, `support`, `client`, `reseller`, `usher` |
| M1.5 | Profile management (name, phone, avatar, password change) | P0 | |
| M1.6 | Social login (Google) | P1 | Reduces signup friction significantly |
| M1.7 | Admin impersonation of a client | P1 | Essential for support. Must be audit-logged and visibly banner'd |
| M1.8 | 2FA for admin accounts | P1 | Admin panel touches money |
| M1.9 | Session/device management | P2 | |

**Acceptance (M1.4):** A `client` can only ever read/write invitations where
`invitations.user_id = auth()->id()`. Enforced by policy, not by controller `if` statements.

---

## M2 — Packages, Orders & Payments

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M2.1 | Package CRUD (admin) | P0 | Name, price, discount price, active days, sort order, is_active |
| M2.2 | Package feature flags | P0 | Key-value per package: `max_guests`, `max_photos`, `max_video_mb`, `whatsapp_quota`, `custom_domain`, `remove_watermark`, `rsvp`, `guestbook`, `checkin`, `music`, `live_stream`, `export`, `analytics_advanced` |
| M2.3 | Public pricing page | P0 | Comparison table driven by M2.2 |
| M2.4 | Checkout: select package + template → order | P0 | |
| M2.5 | Payment gateway integration | P0 | Midtrans Snap or Xendit Invoice. VA, QRIS, e-wallet, card |
| M2.6 | Payment webhook/callback handler | P0 | **Idempotent.** Verify signature. Log raw payload. Never trust client-side status |
| M2.7 | Manual bank transfer + admin verification | P0 | Still ~30% of Indonesian transactions. Upload proof, admin approves |
| M2.8 | Invoice generation (PDF) | P0 | |
| M2.9 | Order status lifecycle | P0 | `pending → paid → provisioned` / `expired` / `cancelled` / `refunded` |
| M2.10 | Auto-expire unpaid orders | P0 | Scheduled job, 24h default |
| M2.11 | Add-on purchase against existing invitation | P1 | Extra guests, WA credits, extend period, upgrade tier |
| M2.12 | Coupon / voucher engine | P1 | Percent or fixed, min order, max uses, per-user limit, date window |
| M2.13 | Refund workflow | P1 | Admin-initiated, gateway refund call + local record |
| M2.14 | Revenue dashboard | P1 | MRR-equivalent, by package, by template, by channel |

**Acceptance (M2.6):** Replaying the same webhook payload 5× must produce exactly one paid
order and one provisioned invitation. Use a unique index on `payments.gateway_ref`.

**Acceptance (M2.9):** `provisioned` means the invitation row exists, quotas are applied from
the package's feature flags, and the client has been notified. Provisioning runs in a queued
job — never inline in the webhook handler.

---

## M3 — Event Types & Templates

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M3.1 | Event type CRUD | P0 | Wedding, engagement, birthday, aqiqah, khitanan, graduation, corporate, seminar, general. Each defines `default_sections` and `person_roles` |
| M3.2 | Template category CRUD | P0 | Floral, minimalist, luxury, islamic, rustic, javanese, modern, dark |
| M3.3 | Template CRUD (admin) | P0 | Name, slug, thumbnail, screenshots, view identifier, min package tier, supported event types, config schema, status, version |
| M3.4 | Template ↔ event type mapping | P0 | Many-to-many. A template can serve wedding + engagement |
| M3.5 | Template gallery (public, filterable) | P0 | Filter by event type, category, tier, colour |
| M3.6 | Live template preview with demo data | P0 | Route: `/preview/{template:slug}` with a seeded demo invitation |
| M3.7 | Template config schema | P0 | JSON defining which colour tokens, fonts, and toggles the template exposes |
| M3.8 | Per-invitation theme overrides | P1 | Client tweaks primary colour, font pair, cover style within schema bounds |
| M3.9 | Template versioning | P1 | Publishing v2 must not break live invitations pinned to v1 |
| M3.10 | Template marketplace + designer accounts | P2 | Revenue share |

**Acceptance (M3.9):** `invitations.template_version` pins the version. Editing a template
in admin creates a new version; existing invitations keep rendering the pinned one until
the client explicitly upgrades.

---

## M4 — Invitation Builder (the core product)

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M4.1 | Create invitation from a paid order | P0 | |
| M4.2 | Slug management | P0 | Auto-suggest from names, editable, globally unique, reserved-word blocklist |
| M4.3 | **Persons** section | P0 | Repeatable. Role (bride/groom/celebrant/host/child), full name, nickname, photo, parents, bio, Instagram, order. Roles allowed come from the event type |
| M4.4 | **Event sessions** section | P0 | Repeatable. Name (Akad/Resepsi/Main), start, end, timezone, venue name, address, Maps URL, lat/lng, dress code, notes, live stream URL |
| M4.5 | **Story / timeline** section | P1 | Repeatable: date, title, description, image |
| M4.6 | **Gallery** section | P0 | Images + video (YouTube/Vimeo embed or upload), captions, ordering, cover selection |
| M4.7 | **Gift / digital envelope** section | P0 | Repeatable: type (bank/e-wallet/physical address), provider, account name, account number, QRIS image, notes. Copy-to-clipboard on the public side |
| M4.8 | **Music** | P0 | Pick from a curated licensed library or upload. Autoplay-on-interaction, mute toggle persisted |
| M4.9 | **Quotes / verse** section | P1 | Religious verse or quote with attribution |
| M4.10 | **Custom sections** | P1 | Free-form title + rich text + optional image, arbitrarily many |
| M4.11 | Section ordering & visibility toggles | P0 | Drag-to-reorder, per-section show/hide |
| M4.12 | Cover / opening screen config | P0 | Cover image, greeting text, "Open Invitation" button label |
| M4.13 | Live preview (side-by-side or device frame) | P0 | Iframe against a preview token route |
| M4.14 | Draft → publish state machine | P0 | See [04 § Invitation lifecycle](04-flowcharts.md#4-invitation-lifecycle-state-machine) |
| M4.15 | Share-preview link for unpublished drafts | P0 | Signed URL, time-limited. Clients always want to show their mother first |
| M4.16 | SEO / OG meta per invitation | P0 | Title, description, 1200×630 image. **Auto-generate the OG image** from cover + names |
| M4.17 | Invitation password protection | P1 | Optional passphrase gate |
| M4.18 | Active period + expiry + renewal | P0 | Expired invitations show a polite "this invitation has ended" page, not a 404 |
| M4.19 | Duplicate/clone invitation | P1 | For resellers building many |
| M4.20 | Multi-language invitation content | P2 | Per-field translations |

**Acceptance (M4.16):** Pasting an invitation URL into WhatsApp must show the couple's photo
and names in the preview card within 5 seconds of first paste. Test this on a real device
before calling M4 done — it is the single highest-leverage detail in the product.

---

## M5 — Guest Management

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M5.1 | Manual guest add / edit / delete | P0 | Title (Bapak/Ibu/Saudara/Saudari), name, phone, email, address, group, max pax, VIP flag, notes |
| M5.2 | Guest groups | P0 | Named + colour-coded. Family, friends, office, neighbours |
| M5.3 | Bulk import from CSV / XLSX | P0 | Template download, column mapping, dry-run validation, per-row error report, partial success |
| M5.4 | Per-guest unique link | P0 | `{invitation_url}?to={guest_token}`. Token is a slug, not a sequential ID |
| M5.5 | Personalized greeting on public page | P0 | "Kepada Yth. Bapak Budi Santoso" resolved from the token |
| M5.6 | Copy-per-guest WhatsApp message | P0 | Per-guest `wa.me/{phone}?text={urlencoded}` deep link with a customisable message template |
| M5.7 | Bulk copy / bulk open WhatsApp | P0 | The realistic manual-blast workflow before M8 exists |
| M5.8 | Guest quota enforcement | P0 | Hard-blocked at the package limit, with an upsell CTA |
| M5.9 | Guest export (CSV/XLSX) | P1 | |
| M5.10 | Guest search, filter, pagination | P0 | Must handle 1000+ rows without dying |
| M5.11 | Link-open tracking per guest | P1 | `opened_at`, `open_count` — lets the client chase non-openers |
| M5.12 | Table / seating assignment | P2 | |

**Acceptance (M5.3):** Importing a 500-row spreadsheet with 12 malformed rows must import 488
successfully and return a downloadable error report naming row numbers and reasons. Never
all-or-nothing.

---

## M6 — RSVP & Guestbook

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M6.1 | RSVP form on public invitation | P0 | Name (prefilled from token), attendance (yes/no/maybe), pax count, which session |
| M6.2 | RSVP validation against `max_pax` | P0 | |
| M6.3 | Edit own RSVP | P1 | Via the same guest token |
| M6.4 | Wishes / guestbook submission | P0 | Name + message. Rate-limited, honeypot, optional profanity filter |
| M6.5 | Wishes moderation queue | P0 | Client-configurable: auto-approve or hold. Pin, hide, delete |
| M6.6 | Live wishes feed on the invitation | P0 | Paginated, newest first |
| M6.7 | RSVP dashboard & summary | P0 | Total yes/no/maybe, total pax, by session, by group |
| M6.8 | RSVP + wishes export | P1 | |
| M6.9 | Anonymous RSVP (no guest token) | P0 | Public link sharers exist. Must handle guests with no token |

**Acceptance (M6.4):** An unauthenticated bot hitting the wishes endpoint 100×/min must be
throttled to a sane rate per IP per invitation, and no submission may render unescaped HTML.

---

## M7 — Check-in & Day-of Operations

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M7.1 | QR code generation per guest | P1 | Embedded in the guest's invitation view + downloadable sheet |
| M7.2 | Usher scanner page | P1 | Camera-based scan, works on a phone browser, scoped access token (no full login) |
| M7.3 | Manual check-in by name search | P1 | The camera will fail. Always have the fallback |
| M7.4 | Actual pax recording at check-in | P1 | RSVP said 2, three showed up. Record reality |
| M7.5 | Souvenir / door-gift tracking | P1 | Checkbox per guest, prevents double-collection |
| M7.6 | Live attendance counter | P1 | For the client and the MC |
| M7.7 | Offline-tolerant scanning | P2 | Queue scans locally, sync when connectivity returns. Venues have bad wifi |
| M7.8 | Printable guest check-in sheet (PDF) | P1 | The universal fallback |

---

## M8 — Messaging & Notifications

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M8.1 | Transactional email (order, payment, publish, expiry warning) | P0 | |
| M8.2 | Message templates with variables | P1 | `{guest_name}`, `{invitation_url}`, `{event_date}`, `{couple_names}` |
| M8.3 | WhatsApp blast via provider API | P1 | Wablas / Fonnte / WhatsApp Cloud API. Queued, rate-limited, quota-metered |
| M8.4 | Blast progress & per-recipient status | P1 | Sent / delivered / read / failed |
| M8.5 | Scheduled reminder blast | P1 | "H-3" reminder to non-responders |
| M8.6 | In-app notifications | P1 | New RSVP, new wish, order paid, expiry approaching |
| M8.7 | Expiry warning sequence | P0 | H-7 and H-1 emails before invitation expires |

**Warning on M8.3:** Unofficial WhatsApp gateway providers get numbers banned. Meter usage,
enforce opt-out, and put the client's own number on the line where possible. Read the
provider's ToS and Meta's business messaging policy before shipping this — the compliance
risk sits with you, not the provider.

---

## M9 — Analytics

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M9.1 | Invitation view counter | P0 | Queued write, not synchronous |
| M9.2 | Unique visitor estimate | P1 | Hashed IP + UA + day |
| M9.3 | Daily aggregate rollup table | P1 | Never query raw view rows for charts |
| M9.4 | RSVP funnel (views → RSVP started → RSVP submitted) | P1 | |
| M9.5 | Device / browser / referrer breakdown | P1 | |
| M9.6 | Per-guest open tracking | P1 | |
| M9.7 | Admin platform analytics | P1 | Template popularity, conversion by package, revenue trend |

---

## M10 — Reseller & Affiliate

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M10.1 | Affiliate registration + unique referral code | P1 | |
| M10.2 | Referral attribution (cookie + code at checkout) | P1 | Define the attribution window explicitly (e.g. 30 days, last-click) |
| M10.3 | Commission calculation on paid orders | P1 | Percent or fixed, configurable per affiliate tier |
| M10.4 | Affiliate dashboard (clicks, conversions, earnings) | P1 | |
| M10.5 | Withdrawal request + admin approval | P1 | Bank details, minimum threshold, status tracking |
| M10.6 | Reseller credit balance | P1 | Buy 10 invitations upfront at a discount, issue them one at a time |
| M10.7 | Reseller creates invitations for customers | P1 | Reseller owns the record; customer gets limited edit access |
| M10.8 | White-label branding for resellers | P2 | |

**Note:** M10.5 moves real money. Treat commission ledger entries as append-only — never
`UPDATE` a balance column directly; compute the balance from ledger rows. This is the single
most common source of "where did the money go" bugs.

---

## M11 — Admin Panel

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M11.1 | Dashboard: revenue, orders, new users, active invitations | P0 | |
| M11.2 | Order management + manual payment verification | P0 | |
| M11.3 | User management (search, suspend, roles, impersonate) | P0 | |
| M11.4 | Invitation management (search, view, suspend, extend) | P0 | |
| M11.5 | Template & category management | P0 | |
| M11.6 | Package & feature-flag management | P0 | |
| M11.7 | Coupon management | P1 | |
| M11.8 | Global settings (site, payment, storage, WA provider, SEO) | P0 | Cached, typed |
| M11.9 | Audit log viewer | P1 | `spatie/laravel-activitylog` |
| M11.10 | Support ticket queue | P1 | |
| M11.11 | Announcement banner to clients | P1 | |
| M11.12 | Queue & failed-job monitor | P0 | Laravel Horizon if Redis |

---

## M12 — Media & Storage

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M12.1 | Image upload with client-side compression | P0 | Clients upload 8 MB phone photos. Compress before upload |
| M12.2 | Server-side resize + WebP conversion | P0 | Multiple conversions: thumb, medium, full, OG |
| M12.3 | Storage quota enforcement per package | P0 | |
| M12.4 | Audio upload / curated library | P0 | Licensing matters — a curated library you have rights to is safer than free upload |
| M12.5 | Video: embed-first, upload as premium add-on | P1 | Video hosting is expensive; prefer YouTube/Vimeo embeds |
| M12.6 | CDN delivery | P1 | Cloudflare R2 / S3 + CloudFront. Critical for the blast moment |
| M12.7 | Auto-generated OG preview image | P0 | Composite cover + names into 1200×630 |
| M12.8 | Orphaned media cleanup job | P1 | |

---

## M13 — System & Platform

| ID | Feature | Pri | Notes |
|---|---|---|---|
| M13.1 | Queue workers + Horizon | P0 | Blasts, imports, media, provisioning |
| M13.2 | Scheduled tasks | P0 | Expire orders, expire invitations, send warnings, roll up analytics, cleanup |
| M13.3 | Error tracking | P0 | Sentry — you already have this connected |
| M13.4 | Structured logging | P0 | |
| M13.5 | Health check endpoint | P0 | |
| M13.6 | Database backup automation | P0 | `spatie/laravel-backup` |
| M13.7 | Rate limiting on public endpoints | P0 | RSVP, wishes, view tracking |
| M13.8 | Cache strategy for public invitations | P0 | See [05 § Caching](05-technical-architecture.md#caching-strategy) |
| M13.9 | Custom domain / subdomain routing | P1 | |
| M13.10 | Data purge job (post-retention) | P1 | Guest PII deletion after retention window |
