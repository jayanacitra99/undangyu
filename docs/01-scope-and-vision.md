# 01 — Scope & Vision

## 1. Product statement

Undangyu is a self-service digital invitation platform. A user buys a package, configures an
event through a dashboard, and publishes a shareable web invitation with built-in guest
management, RSVP collection, digital gifting, and venue check-in.

Weddings drive most revenue. The system is built event-type agnostic so birthdays, aqiqah,
khitanan, engagements, graduations, and corporate events are seed data — not code changes.

---

## 2. Personas

### P1 — The Client (buyer)
Getting married in 3 months. Non-technical. On a phone 80% of the time. Wants to pick a
pretty template, type in names and dates, upload photos, and blast a WhatsApp link to 400
people. Panics if the builder has more than ~8 steps.

**Needs:** dead-simple builder, live preview, mobile-editable, WhatsApp-ready share text,
guest list import from a spreadsheet, real-time RSVP count.

### P2 — The Guest
Receives a WhatsApp link. Opens on a mid-range Android on 4G. Has ~15 seconds of patience.

**Needs:** loads under 3s, sees their own name, one-tap RSVP, one-tap "copy bank account",
one-tap "open in Maps", one-tap add-to-calendar.

### P3 — The Admin (you / your team)
Manages the template catalog, packages, pricing, coupons, and payment verification. Needs to
see revenue, churn, and which templates convert.

**Needs:** AdminLTE dashboard, order queue, refund tool, impersonate-client for support,
audit trail.

### P4 — The Reseller / Affiliate
Wedding organizers and print shops who sell invitations to their own customers. This persona
is how the Indonesian digital invitation market actually scales — **do not skip it.**

**Needs:** referral code, commission dashboard, ability to create invitations on behalf of
customers, white-label-ish branding, withdrawal requests.

### P5 — The Usher (venue staff)
On the day. Scans QR codes at the door on a phone. Marks souvenirs handed out.

**Needs:** offline-tolerant scanner page, fast search by name, no login friction (scoped
token link).

---

## 3. Business model

| Stream | Detail |
|---|---|
| **Package purchase** | One-time per event. Tiers: Free (watermarked, 1 template, 50 guests), Basic, Premium, Exclusive. Active for N days after publish. |
| **Add-ons** | Extra guest quota, WhatsApp blast credits, custom domain, extend active period, premium template upgrade, extra storage. |
| **Reseller tier** | Bulk credit purchase at a discount; resellers issue invitations to their own customers. |
| **Affiliate commission** | % of order value for referred sales, with withdrawal flow. |
| **Template marketplace (Phase 4)** | Third-party designers submit templates, revenue share. |

**Pricing note:** the Indonesian market anchors low (roughly Rp 50k–500k per invitation).
Model quota-based upsells rather than recurring subscriptions — clients buy once per event.

---

## 4. Scope

### 4.1 In scope — MVP (Phase 1)

- Auth: register, login, email verify, password reset, roles (admin / client)
- Package catalog + checkout + payment gateway + invoice
- Template catalog (admin CRUD) + template picker for client
- Invitation builder: event type, persons, event sessions, story, gallery, gifts, settings
- Public invitation renderer with per-guest personalization
- Guest list: manual add, CSV/Excel import, groups, per-guest link generation
- RSVP collection + wishes/guestbook with moderation
- Digital gift display (bank accounts, e-wallets, gift address)
- Admin panel: orders, users, templates, packages, settings
- WhatsApp share link generation (`wa.me` deep links with prefilled text)
- Basic analytics: views, RSVP counts, wish counts

### 4.2 In scope — Phase 2

- QR check-in system + usher scanner page
- WhatsApp blast via provider API (Wablas / Fonnte / official Cloud API)
- Affiliate/reseller program with commission + withdrawals
- Coupons & vouchers
- Custom subdomain / custom domain per invitation
- Advanced analytics dashboard (funnel, device, geo, time-of-day)
- Guest export, RSVP export, wishes export
- Support ticket system

### 4.3 In scope — Phase 3+

- Template marketplace with designer accounts and revenue share
- Multi-language invitations (ID / EN / AR)
- Live streaming embed + virtual attendance
- Seating chart / table assignment builder
- Photo booth / guest photo upload wall
- Budget & vendor tracker for the client
- Mobile app wrapper (Capacitor) for the usher scanner
- White-label mode for enterprise resellers

### 4.4 Explicitly OUT of scope

- Physical invitation printing/fulfilment
- Full wedding-planning suite (vendor marketplace, venue booking)
- Payment *collection on behalf of the couple* — Undangyu **displays** gift accounts,
  it does not custody guest money. This is a deliberate legal/regulatory decision;
  handling third-party funds pulls you into payment-institution licensing.
- Native iOS/Android apps in Phase 1–3
- Social network features between clients

---

## 5. Non-functional requirements

| Area | Target |
|---|---|
| **Public invitation LCP** | < 2.5s on 4G, mid-range Android |
| **Invitation page weight** | < 1.5 MB initial (lazy-load gallery) |
| **Uptime** | 99.5% — a wedding day outage is unrecoverable reputationally |
| **Concurrency** | 500 concurrent guests per invitation at peak (blast moment) |
| **Data retention** | Guest data retained for active period + 90 days, then purged |
| **Backups** | Nightly DB dump, 30-day retention, tested restore quarterly |
| **Accessibility** | Public invitation: WCAG 2.1 AA where feasible (contrast, alt text, keyboard RSVP) |
| **Localisation** | Dashboard ID + EN. Invitation content language is per-invitation. |

---

## 6. Critical constraints that shape the design

1. **The blast moment.** A client sends 400 WhatsApp messages in 5 minutes. Your public
   invitation route must survive a thundering herd. Cache aggressively, keep the render
   path free of N+1 queries, and never write to the DB on a plain page view (queue view
   tracking).

2. **WhatsApp link preview is a conversion feature, not a nicety.** Every invitation needs
   correct per-invitation OG tags, a 1200×630 preview image, and a crawlable server-rendered
   `<head>`. If the renderer is a client-side-only SPA, previews break. See
   [05 § Template Engine](05-technical-architecture.md#template-engine).

3. **Mobile-first for the builder, not just the invitation.** Clients edit on phones.
   AdminLTE's default sidebar layout is desktop-biased — the client-facing builder needs
   a mobile-optimised override.

4. **The event day is immovable.** There is no "we'll ship the fix next sprint." Build a
   read-only fallback render path so an invitation still displays if the API/queue is down.

---

## 7. Success metrics

| Metric | Why |
|---|---|
| Checkout conversion (template view → paid) | Core funnel health |
| Time-to-publish (purchase → published) | Builder UX quality |
| Guest RSVP rate per invitation | Product value proof for upsell |
| Invitation page LCP p75 | Guest experience |
| Reseller-originated revenue share | Channel viability |
| Support tickets per 100 invitations | Builder clarity |
