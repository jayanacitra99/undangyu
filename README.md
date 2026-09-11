# Undangyu

Multi-event digital invitation platform. Laravel + Vue + AdminLTE.

> **Positioning:** Weddings are the primary revenue vertical, but the data model and
> template engine are event-type agnostic from day one. Adding "birthday" or "aqiqah"
> later must be a seed row + a template, never a schema migration.

---

## Documentation index

| Doc | What's in it | Read when |
|---|---|---|
| [01 — Scope & Vision](docs/01-scope-and-vision.md) | Personas, business model, in/out of scope, success metrics | First. Before writing any code. |
| [02 — Functional Spec](docs/02-functional-spec.md) | Every module, every feature, acceptance criteria | Building a feature |
| [03 — Database & ERD](docs/03-database-erd.md) | Full ERD, all tables, columns, indexes, relationships | Writing migrations/models |
| [04 — Flowcharts](docs/04-flowcharts.md) | User journeys, state machines, sequence diagrams | Designing a flow |
| [05 — Technical Architecture](docs/05-technical-architecture.md) | Stack, packages, folder structure, conventions, security | Setting up the repo |
| [06 — Claude Code Playbook](docs/06-claude-code-playbook.md) | Exact session-by-session prompts to build this | Every coding session |
| [07 — Roadmap](docs/07-roadmap.md) | Phased delivery plan, MVP cut line, estimates | Planning sprints |
| [`CLAUDE.md`](CLAUDE.md) | Project memory file — copy to repo root | Copy once at project init |

---

## The 60-second version

**What it is:** A SaaS where a client buys a package, picks a template, fills in their event
details through a dashboard, and gets a shareable link (`undangyu.id/nama-acara`) plus
per-guest personalized links (`undangyu.id/nama-acara?to=budi-santoso`). Guests open it,
RSVP, leave wishes, and get scanned in at the venue via QR.

**Three surfaces:**

1. **Public invitation renderer** — the thing guests see. Must be fast, animated, mobile-first,
   and have perfect WhatsApp link previews. Vue 3.
2. **Client dashboard** — where the buyer builds and manages their invitation. AdminLTE + Blade
   with Vue islands for the complex bits.
3. **Admin panel** — where you manage templates, packages, orders, payouts. AdminLTE + Blade.

**Money:** one-time package purchase per event (Indonesian market norm), plus add-ons
(extra guest quota, WhatsApp blast credits, custom domain, premium template), plus a
reseller/affiliate commission tier.

---

## Quick start

```bash
# 1. Scaffold
laravel new undangyu
cd undangyu

# 2. Drop CLAUDE.md and docs/ into the repo root
#    (copy from this package)

# 3. Start Claude Code and follow docs/06-claude-code-playbook.md
claude
```

Full setup in [docs/06-claude-code-playbook.md § Session 0](docs/06-claude-code-playbook.md).

---

## Three decisions to make before you start

These block everything else. Answers go in `CLAUDE.md`.

1. **Payment gateway** — Midtrans (best local coverage, QRIS + VA + e-wallet) vs Xendit
   (better DX, better docs, better payout API for the affiliate feature). See
   [05 § Payments](docs/05-technical-architecture.md#payments).
2. **Guest link strategy** — query param (`?to=slug`) vs path segment (`/undangan/slug/budi`).
   Query param is simpler and what the Indonesian market expects. See
   [02 § Guest Management](docs/02-functional-spec.md#5-guest-management).
3. **Template rendering** — DB-driven JSON config (flexible, no deploy to add a template) vs
   file-based Vue components per template (faster, richer animation, needs a deploy).
   Recommendation: **hybrid** — see [05 § Template Engine](docs/05-technical-architecture.md#template-engine).
