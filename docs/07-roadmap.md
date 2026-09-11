# 07 — Roadmap

Estimates assume one developer working with Claude Code, part-time-ish. Treat them as relative
weight, not commitments.

---

## Phase 1 — Foundation & Commerce
**Sessions 1–13 · ~3–4 weeks**

Auth, roles, AdminLTE shell, settings, event types, template catalog, packages with feature
flags, orders, payment gateway, webhooks, provisioning, manual payment verification, invoices.

**Exit criteria:** a real person can pay real money and end up with a draft invitation record
that has correct entitlements. Nothing renders publicly yet.

---

## Phase 2 — The Invitation
**Sessions 14–23 · ~4–5 weeks**

Full invitation schema, policies, builder UI, media pipeline, payload caching, public
renderer, first complete template, publish flow with OG image generation.

**Exit criteria:** you can build an invitation in the dashboard, publish it, paste the URL
into WhatsApp, and get a correct preview card. **This is the hardest phase and the one that
determines whether the product is good.**

---

## Phase 3 — Guests & Responses
**Sessions 24–29 · ~2–3 weeks**

Guest CRUD, groups, Excel import, per-guest tokens and links, WhatsApp share text,
personalization, RSVP, wishes with moderation.

**Exit criteria:** end-to-end guest journey works. Client imports 200 guests, sends links,
watches RSVPs arrive.

---

## 🚩 MVP CUT LINE — launch here

At this point you have a sellable product. Everything after is growth, not viability.

**Before launch:**
- [ ] 5–8 templates built and polished (one template is not a catalog)
- [ ] Load test: 500 concurrent on one invitation
- [ ] WhatsApp preview verified on real iOS and Android devices
- [ ] Payment tested end-to-end with real money, small amount
- [ ] Backup and restore tested
- [ ] Privacy policy and terms published (you're holding third-party PII)
- [ ] Sentry alerting to your phone
- [ ] A rollback plan

**Run 3–5 real invitations for friends or at cost before charging strangers.** Wedding-day
failures are unrecoverable reputationally, and real usage surfaces things no test does.

---

## Phase 4 — Operations & Polish
**Sessions 30–34 · ~2 weeks**

Analytics, admin dashboard, notifications, scheduled tasks, security hardening, performance.

---

## Phase 5 — Growth
**Sessions 35–41 · ~4 weeks**

QR check-in, usher scanner, WhatsApp blast, affiliate/reseller program, coupons, custom
domains, support tickets.

**Sequencing note:** build the **affiliate program before the WhatsApp blast** if you want
distribution. Resellers bring volume; blast is a feature existing clients want. Growth beats
polish at this stage.

---

## Phase 6 — Scale
**Ongoing**

Template marketplace, multi-language, seating charts, guest photo wall, budget tracker,
white-label, mobile scanner app.

---

## Template production — the parallel track

**This is the constraint nobody plans for.** Your engineering can be perfect and the product
still won't sell without a strong template catalog. Templates are design work, not development
work, and they don't compress.

| Milestone | Templates needed |
|---|---|
| Internal testing | 1 |
| Soft launch | 5–8 across 3+ categories |
| Competitive catalog | 20–30 |
| Market-leading | 50+ |

Budget roughly 1–2 days per template for design + build + QA once the engine exists. Start
this track during Phase 2, not after. If design isn't your strength, commission a designer
early — it's the highest-leverage money you'll spend on this project.

---

## Risk register

| Risk | Impact | Mitigation |
|---|---|---|
| Wedding-day outage | Severe, unrecoverable | Load test, edge cache, read-only fallback render, monitoring with phone alerts |
| WhatsApp number banned (M8.3) | Blast feature dead | Rate limit, use client's own number, read Meta's policy, have a fallback provider |
| Payment webhook bugs | Money lost or double-provisioned | Idempotency, daily reconciliation, raw payload logging |
| Guest PII breach | Legal + reputational | Encryption, retention purge, access audit, minimal collection |
| Thin template catalog | No sales | Start template production in Phase 2, commission a designer |
| Scope creep | Never ships | The MVP cut line above is the line. Defend it |
| Feature parity race with incumbents | Endless | Compete on template quality and reseller economics, not feature count |

---

## What I'd do differently from the plan

Two honest notes on the spec you asked for:

**1. This is a lot.** The full feature set here is a 6–9 month build for one person. That's
fine if you know it going in — but the failure mode is building 60% of everything and shipping
nothing. The MVP cut line exists for a reason. Everything in Phases 5–6 is real value, and
none of it matters if Phase 3 never finishes.

**2. "Everything can be adjusted" has a cost.** Maximum configurability means more surface
area to build, more ways for clients to make ugly invitations, and more support load. The
`config_schema` approach deliberately bounds customisation — clients pick from curated colour
and font options rather than getting a free-form editor. That constraint is a feature. Resist
the pull toward a full page builder; it's where this kind of project goes to die.
