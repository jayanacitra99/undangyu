# CLAUDE.md — Undangyu

Project memory for Claude Code. Keep this current — when a decision is made mid-build, add it
here immediately.

---

## What this is

Multi-event digital invitation SaaS. Clients buy a package, build an invitation through a
dashboard, publish it at `undangyu.id/{slug}`, and share per-guest personalized links.
Weddings are the primary vertical; the system is event-type agnostic by design.

Full specs in `docs/`. Read `docs/01` through `docs/05` before substantial work.

---

## Stack

- Laravel 13, PHP 8.3+, MySQL 8, Redis (cache + queue + session)
- **Admin panel:** Blade + AdminLTE 4 (Bootstrap 5). No Vue.
- **Client dashboard:** Blade + AdminLTE shell with **Vue 3 islands** for complex widgets
- **Public invitation:** Blade shell (SSR `<head>` for OG tags) + Vue 3 app, **Tailwind**
- Vite, Horizon, Sanctum (API/scanner only)
- Storage: S3/R2 · CDN: Cloudflare · Errors: Sentry

### Decisions already made — don't re-litigate

- **Not** a full Inertia SPA. AdminLTE + Vue islands. Mixing Inertia with AdminLTE causes
  lifecycle conflicts.
- Dashboard uses **Bootstrap 5** classes. Public invitations use **Tailwind**. Separate Vite
  bundles. Never mix them on one page.
- Templates are **Vue component folders**, not DB-driven JSON layouts. `config_schema`
  bounds what clients can customise.
- `invitations.entitlements` is a **snapshot** taken at provisioning. Never read limits from
  the live package.
- `templates` are **versioned**; `invitations.template_version` pins the render.
- Affiliate balance is computed from an **append-only ledger**. Never `UPDATE` a balance column.

### Decisions still open — ask before assuming

- **Database engine: MySQL 8 vs PostgreSQL 16** → *(record here before Session 1)*. The docs
  are written for MySQL 8, but Claude Code cloud sessions ship PostgreSQL 16 and no MySQL.
  Nothing in the schema requires MySQL. See `docs/06` § Cloud vs local.
- Payment gateway: Midtrans vs Xendit → *(record the choice here when made)*
- WhatsApp provider: Wablas / Fonnte / Cloud API → *(record here)*
- Guest link format: `?to={token}` is the default assumption

### If this is a cloud session

Cloud VMs ship PHP 8.3, Composer, Node, Redis and PostgreSQL — **not MySQL**, and no browser.
Redis and any DB installed via setup script are present but **stopped**. Start them before
anything else:

```bash
service redis-server start
service mysql start        # only if MySQL was installed via the environment setup script
```

Then confirm `php artisan migrate:status` runs before beginning the session's work.

Sessions whose verification needs a browser — anything touching AdminLTE rendering, the
invitation templates, WhatsApp link previews, or Lighthouse — can't be completed in the cloud.
`docs/06` marks each session 💻 local, ☁️ cloud, or 🔀 either.

---

## Conventions

### PHP
- PSR-12 via Pint. Run `./vendor/bin/pint` before committing.
- Strict types where practical. PHP 8.1 **backed enums** for all statuses — no string literals.
- **Validation only in FormRequests.** Never inline in controllers.
- **Authorization only in Policies.** Never `if ($invitation->user_id !== auth()->id())`.
- Controllers are thin. Business logic goes in `app/Actions/{Domain}/{VerbNoun}.php` as
  single-purpose invokable classes.
- Models: explicit `$fillable` (never `$guarded = []`), explicit casts, typed relationships.
- Money is `decimal(12,2)`. Never float.
- Datetimes stored UTC, rendered in the invitation's timezone.

### Database
- Migrations follow `docs/03-database-erd.md` exactly. If a column isn't in the doc, update
  the doc first.
- Foreign keys always declare `onDelete` behaviour per § 4 of that doc.
- Every table listed in § 5 gets its indexes at creation time, not later.
- Factories for every model, with realistic Indonesian sample data (names, phone numbers as
  `+62…`, venues, IDR amounts).

### Vue
- Composition API, `<script setup>`, always.
- Dashboard islands: Bootstrap 5 classes.
- Invitation templates: Tailwind.
- Props into islands via `data-*` attributes on the mount div, JSON-encoded.
- Autosave debounced at 800ms.
- Never `v-html` on user-supplied content.

### Routes
Split by surface — `web.php`, `admin.php`, `client.php`, `public.php`, `api.php`,
`webhooks.php`. Registered in `bootstrap/app.php`.

### Naming
- Tables: plural snake_case · Models: singular StudlyCase
- Controllers: `{Resource}Controller`, namespaced by surface
- Actions: verb-first — `PublishInvitation`, `ProvisionInvitation`, `ImportGuests`
- Jobs: `{Verb}{Noun}Job` · Enums: `App\Enums\{Noun}Status`
- Commits: conventional, referencing the spec ID — `feat(M5.3): guest CSV import`
- Branches: `session/NN-slug` — e.g. `session/10-payment-webhook`. One branch per session
  from `docs/06`, one PR each, merged before the next session starts

### Git workflow
- **Never commit directly to `main`.** Every session works on its own `session/NN-slug`
  branch and lands via a squash-merged PR.
- Open the PR with `gh pr create --fill --base main` at the end of a session, once the
  session's Verify block passes.
- CI runs pint, phpstan and `php artisan test` on every PR. A red PR is not done.
- In a cloud session, push protection means you can only push to the branch the session
  started on. If the branch doesn't exist yet, create and push it as the first action.

---

## Hard rules

1. **Never write to the DB on a public invitation page view.** Queue it.
2. **Every public endpoint is rate-limited.** RSVP, wishes, view tracking, token lookup.
3. **Payment webhooks are idempotent.** Unique index on `payments.gateway_ref`,
   `lockForUpdate` in a transaction, return 200 on duplicates, provision via queue.
4. **Cross-tenant isolation is tested, not assumed.** Every model touching client data gets
   a policy and a test proving client A can't reach client B's records.
5. **Cache invalidation via observers.** Every invitation child model flushes
   `invitation:{id}` tags on save/delete.
6. **Eager load.** `Model::preventLazyLoading()` is on in local and staging.
7. **Guest tokens are never sequential.** Slug + random suffix.
8. **Bank account numbers use the `encrypted` cast.**
9. **Long-running work is queued** — imports, blasts, media conversions, provisioning, OG
   image generation.
10. **Quotas are enforced server-side** against `invitations.entitlements`, in a FormRequest.

---

## What "done" means

A slice is done when: migration runs on a fresh DB, model has fillable/casts/relations/factory,
validation is in a FormRequest, authorization is in a Policy, no N+1, feature test passes,
Pint is clean, and you've clicked through it in a browser.

A **session** is done when its slice is done, the PR is open and green, and it has been
squash-merged into `main`. An open PR is work in progress, not a finished session.

---

## Working style

- Build **vertical slices** — one feature end to end, then run it. Never generate all
  migrations, then all models, then all controllers.
- Before writing code in a new session: list the files you'll touch, flag ambiguities, wait
  for go-ahead.
- Don't create files outside the agreed set.
- Don't add features that weren't asked for.
- If something in the spec looks wrong, say so rather than silently working around it.
- If a fix belongs somewhere other than where the error surfaced, say so.

---

## Domain glossary

| Term | Meaning |
|---|---|
| **Invitation** | One event's invitation site. The core entity |
| **Section** | A configurable block within an invitation (cover, persons, gallery…) |
| **Person** | Someone the event is about — bride, groom, celebrant, child |
| **Event session** | A dated occasion within an invitation. Akad + Resepsi = 2 sessions |
| **Guest** | A named invitee with a unique token and personalized link |
| **Token** | The URL-safe per-guest identifier in `?to=` |
| **RSVP** | Attendance confirmation. May be tokenless (anonymous) |
| **Wish** | Guestbook message, optionally moderated |
| **Entitlements** | Package limits snapshotted onto the invitation at provisioning |
| **Blast** | Bulk WhatsApp send to a guest list |
| **Undangan** | (id) invitation · **Tamu** guest · **Acara** event · **Resepsi** reception · **Akad** ceremony |
