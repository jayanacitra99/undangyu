# 06 — Claude Code Playbook

How to actually build this with Claude Code without generating an unmaintainable pile.

---

## The one rule that matters

**Build vertical slices, not horizontal layers.**

Do NOT say "generate all 40 migrations" then "generate all 40 models" then "generate all
controllers." You'll get a large volume of plausible code that has never run, and debugging
it is worse than writing it.

Instead: one feature at a time, end to end — migration → model → factory → policy → request →
controller → view → test → **run it** → commit. A working slice you can click through beats
ten unverified ones.

---

## Session 0 — Environment setup

### Prerequisites (Windows)

```powershell
# PHP 8.3+, Composer, Node 20+, MySQL
# Easiest path on Windows: Laravel Herd (https://herd.laravel.com)

php -v          # >= 8.3
composer -V
node -v         # >= 20
mysql --version

# Claude Code
npm install -g @anthropic-ai/claude-code
claude --version
```

### Scaffold

```powershell
laravel new undangyu
# Starter kit: choose "None" — AdminLTE replaces it
# Database: MySQL
# Testing: Pest (or PHPUnit, your call)

cd undangyu
git init
git add .
git commit -m "chore: initial Laravel scaffold"
```

### Drop in the docs

```powershell
# Copy this documentation package into the repo
mkdir docs
# ...copy 01-07 markdown files into docs/
# ...copy CLAUDE.md to the repo root
git add . && git commit -m "docs: project specification"
```

### Configure

```powershell
copy .env.example .env
php artisan key:generate
# Edit .env: DB_DATABASE=undangyu, CACHE_STORE=redis, QUEUE_CONNECTION=redis
php artisan migrate
```

### Start Claude Code

```powershell
claude
```

First message:

> Read `CLAUDE.md` and `docs/01-scope-and-vision.md` through `docs/05-technical-architecture.md`.
> Summarise back to me: the tech stack decisions, the three surfaces, and the template engine
> approach. Don't write any code yet.

**Verify the summary is right before continuing.** If Claude misread the architecture, fix it
now — everything downstream inherits the misunderstanding.

---

## Build order

Each session below is one focused Claude Code conversation. Commit at the end of each. Run
`/clear` between sessions so context stays clean.

### Phase 1 — Foundation

| # | Session | Output |
|---|---|---|
| 1 | Base install & config | Packages installed, config files, route file split, Pint config |
| 2 | Auth & roles | Auth scaffolding, Spatie permission, roles seeder, middleware |
| 3 | AdminLTE layout | `layouts/adminlte.blade.php`, sidebar, Vite config, Vue island bootstrap |
| 4 | Settings module | `settings` table, cached `Setting` facade, admin settings page |
| 5 | Event types & template categories | Migrations, models, seeders, admin CRUD |
| 6 | Templates | Migration, model, admin CRUD, screenshot upload, public gallery |
| 7 | Packages & features | Migrations, entitlement resolver service, admin CRUD, public pricing page |

### Phase 2 — Commerce

| # | Session | Output |
|---|---|---|
| 8 | Orders | Migration, model, `CreateOrder` action, checkout flow |
| 9 | Payment gateway | `PaymentGateway` interface, driver, service provider binding |
| 10 | Webhook handler | Idempotent handler + tests. **Write the tests first here** |
| 11 | Provisioning | `ProvisionInvitationJob`, entitlement snapshot, notifications |
| 12 | Manual payment | Proof upload, admin verification queue |
| 13 | Invoices | PDF generation, download |

### Phase 3 — Core invitation

| # | Session | Output |
|---|---|---|
| 14 | Invitation schema | All invitation + child migrations, models, relationships, factories |
| 15 | Policies & scoping | `InvitationPolicy`, cross-tenant tests |
| 16 | Builder shell | Client dashboard, invitation list, builder page skeleton |
| 17 | Builder: persons & events | Vue island, repeatable forms, autosave API |
| 18 | Builder: media | Upload, client-side compression, conversions, quota enforcement |
| 19 | Builder: gifts, story, sections | Remaining sections, drag-reorder |
| 20 | Payload & caching | `InvitationPayload` resource, Redis cache, invalidation observers |
| 21 | Public renderer shell | Blade shell, SSR head, OG tags, Vue mount |
| 22 | First template | One complete Vue template under `templates/{view_key}/` |
| 23 | Publish flow | Validation gate, state machine, OG image generation, expiry scheduling |

### Phase 4 — Guests & responses

| # | Session | Output |
|---|---|---|
| 24 | Guest CRUD & groups | Migration, model, token generation, Vue guest table |
| 25 | Guest import | Excel import, queued job, error report |
| 26 | Guest links & WA share | Per-guest links, message templates, bulk copy |
| 27 | Personalization | Token resolution on public page, open tracking |
| 28 | RSVP | Public form, validation, dashboard summary |
| 29 | Wishes | Public form, moderation queue, live feed, rate limiting |

### Phase 5 — Operations

| # | Session | Output |
|---|---|---|
| 30 | Analytics | View tracking job, rollup command, client dashboard charts |
| 31 | Admin dashboard | Revenue widgets, order queue, user management, impersonation |
| 32 | Notifications | Transactional emails, in-app notifications, expiry warnings |
| 33 | Scheduled tasks | Expiry, cleanup, rollups, backups |
| 34 | Hardening | Rate limits, security headers, load test, fix what breaks |

### Phase 6 — Growth (post-MVP)

35. QR check-in · 36. Scanner page · 37. WhatsApp blast · 38. Affiliate program ·
39. Coupons · 40. Custom domains · 41. Support tickets

---

## Session prompt templates

Copy-paste these, filling the brackets.

### Starting a session

```
Read CLAUDE.md and docs/02-functional-spec.md § [MODULE].

We're building [FEATURE] (ref: [M-ID]).

Before writing code:
1. List the files you'll create or modify
2. Flag anything in the spec that's ambiguous or that you'd do differently
3. Wait for my go-ahead

Follow the conventions in CLAUDE.md. Don't create files outside the listed set.
```

### Schema work

```
Create the migration and model for [TABLE], per docs/03-database-erd.md § [SECTION].

Requirements:
- Exact columns, types, nullability, and defaults from the spec
- All indexes listed in § 5 that apply to this table
- Foreign keys with the cascade behaviour from § 4
- Model: $fillable, casts (including encrypted casts where specified), relationships, enums
- A factory with realistic Indonesian sample data

Then run `php artisan migrate` and show me the resulting schema.
```

### Feature slice

```
Implement [FEATURE] end to end:
1. FormRequest with validation
2. Action class in app/Actions/[Domain]/
3. Controller method (thin — delegate to the action)
4. Route in routes/[file].php
5. Blade view / Vue island
6. Feature test covering the happy path and the two most likely failure modes

Run the test. Show me the output. Don't move on until it passes.
```

### Vue island

```
Create a Vue 3 island for [WIDGET]:
- Entry point: resources/js/islands/[name].js
- Component: resources/js/components/[path]/[Name].vue
- Composition API with <script setup>
- Props passed via data attributes from Blade
- Autosave to [endpoint] debounced at 800ms
- Bootstrap 5 classes (AdminLTE context) — NOT Tailwind
- Loading and error states

Then add the Blade mount point and register the entry in vite.config.js.
```

### Public invitation template

```
Create the invitation template "[NAME]" at
resources/js/invitation/templates/[view_key]/.

- Vue 3 <script setup>, Tailwind (public bundle only)
- Consumes the InvitationPayload shape — read app/Http/Resources/InvitationPayload.php first
- Reads theme tokens from theme_config, with fallbacks from default_config
- Sections: cover, persons, events, countdown, story, gallery, gifts, rsvp, wishes, closing
- Respects section visibility and ordering from invitation_sections
- Mobile-first, 375px baseline
- Scroll animations, lazy-loaded images with WebP srcset
- No layout shift on load

Also give me the DB seeder row with config_schema and default_config.
```

### Debugging

```
[paste the exact error, full stack trace]

Context: [what I was doing]
Relevant files: [paths]

Diagnose the root cause before proposing a fix. If the fix belongs somewhere other than where
the error surfaced, say so.
```

### Review checkpoint (run every ~5 sessions)

```
Review the code written since [commit hash] against CLAUDE.md conventions.

Report:
1. Convention violations
2. Missing authorization checks on any route touching user data
3. N+1 query risks
4. Missing validation
5. Anything that duplicates existing code

Prioritised list. Don't fix anything yet.
```

---

## Working practices

**Commit after every session.** `git commit -m "feat(M5.3): guest CSV import"`. If a session
goes badly, `git reset --hard` is cheap and you lose one session's work, not a week's.

**Run the code.** After every slice. Claude Code writes plausible Laravel — plausible is not
the same as working. The gap shows up at runtime, not in review.

**Clear context between sessions.** `/clear`. Long contexts drift; Claude starts referencing
decisions from three features ago that no longer apply.

**Keep CLAUDE.md current.** When you make a decision mid-build ("we're using Xendit", "guest
tokens are 4-char suffixes"), add it to CLAUDE.md immediately. That's what makes the next
session start correctly instead of re-litigating.

**Push back on scope in-session.** If Claude offers to also build three adjacent features,
decline. Scope creep inside a session is how slices stop being verifiable.

**Don't let it invent schema.** If a column isn't in `docs/03-database-erd.md`, either add it
to the doc first or tell Claude to stick to the spec. Schema drift between the doc and the DB
makes the doc worthless within two weeks.

**Use the MCP servers you have connected.** You've got Sentry, Atlassian, Figma, Slack, and
Laravel Nightwatch available — Sentry for triaging production errors during Phase 5 hardening,
Figma if your templates are designed there.

---

## Definition of done, per slice

- [ ] Migration runs clean on a fresh DB (`migrate:fresh --seed`)
- [ ] Model has `$fillable`, casts, relationships, factory
- [ ] Validation in a FormRequest, not the controller
- [ ] Authorization via a Policy, checked on every route
- [ ] No N+1 (verified with Debugbar or `preventLazyLoading`)
- [ ] Feature test passes
- [ ] `./vendor/bin/pint` clean
- [ ] Manually clicked through in the browser
- [ ] Committed with a conventional-commit message

---

## Common failure modes

| Symptom | Cause | Fix |
|---|---|---|
| Generated code references non-existent models | Claude working ahead of the schema | Build schema-first, one module at a time |
| AdminLTE JS components dead after Vue mounts | Vue replacing DOM AdminLTE initialised | Mount Vue into dedicated container divs only; never let Vue own the AdminLTE chrome |
| Bootstrap and Tailwind classes fighting | Both loaded on the same page | Separate Vite bundles: dashboard = Bootstrap, public invitation = Tailwind |
| Invitation shows stale data | Missing cache invalidation | Observer on every child model, added at Session 20 |
| Slow invitation page | N+1 in payload query | `preventLazyLoading()` in `AppServiceProvider` for local/staging |
| Webhook creates duplicate invitations | Non-idempotent handler | Unique index on `gateway_ref` + `lockForUpdate` |
| Guest import times out | Synchronous processing | Queue it. Always |
| WhatsApp preview shows nothing | Client-side-only `<head>` | SSR the meta tags in the Blade shell |
