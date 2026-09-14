# 06 — Claude Code Playbook

**This is a working document. Tick the boxes as you go. Keep it open while you build.**

Every session has the same shape:

> **Goal** → **Depends on** → **Prep prompt** → **Numbered steps with exact prompts** →
> **Verify** (commands whose output you check) → **Commit** → **Done when**

Lost your place? Jump to [§ Where am I?](#where-am-i) at the bottom.

---

## Progress tracker

Update Status as you go. `⬜ not started · 🟡 in progress · ✅ done · ⏭ skipped`
**PR** is the merged pull request number for that session — see [§ Branch and PR workflow](#branch-and-pr-workflow).

**Where** tells you whether a session suits a cloud session or needs your local machine —
see [§ Cloud vs local](#cloud-vs-local). `☁️ cloud · 💻 local · 🔀 either`

### Phase 1 — Foundation
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 0 | Environment setup | 💻 | ⬜ | |
| 1 | Install & configure packages | 💻 | ⬜ | |
| 2 | Auth, roles & permissions | ☁️ | 🟡 | |
| 3 | AdminLTE layout & Vite | 💻 | 🟡 | |
| 4 | Settings module | ☁️ | 🟡 | |
| 5 | Event types & template categories | ☁️ | 🟡 | |
| 6 | Templates | 🔀 | ⬜ | |
| 7 | Packages & entitlements | ☁️ | ⬜ | |

### Phase 2 — Commerce
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 8 | Orders & checkout | 🔀 | ⬜ | |
| 9 | Payment gateway abstraction | 🔀 | ⬜ | |
| 10 | Webhook handler (test-first) | ☁️ | ⬜ | |
| 11 | Provisioning job | ☁️ | ⬜ | |
| 12 | Manual payment verification | 🔀 | ⬜ | |
| 13 | Invoices | 🔀 | ⬜ | |

### Phase 3 — Core invitation
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 14 | Invitation schema | ☁️ | ⬜ | |
| 15 | Policies & tenant isolation | ☁️ | ⬜ | |
| 16 | Builder shell | 💻 | ⬜ | |
| 17 | Builder: persons & event sessions | 💻 | ⬜ | |
| 18 | Builder: media pipeline | 💻 | ⬜ | |
| 19 | Builder: gifts, story, sections | 💻 | ⬜ | |
| 20 | Payload resource & caching | ☁️ | ⬜ | |
| 21 | Public renderer shell | 💻 | ⬜ | |
| 22 | First invitation template | 💻 | ⬜ | |
| 23 | Publish flow | 💻 | ⬜ | |

### Phase 4 — Guests & responses
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 24 | Guest CRUD & groups | 🔀 | ⬜ | |
| 25 | Guest import | ☁️ | ⬜ | |
| 26 | Guest links & WhatsApp share | 💻 | ⬜ | |
| 27 | Personalization on public page | 💻 | ⬜ | |
| 28 | RSVP | 🔀 | ⬜ | |
| 29 | Wishes & moderation | ☁️ | ⬜ | |

### 🚩 MVP CUT LINE

### Phase 5 — Operations
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 30 | Analytics | ☁️ | ⬜ | |
| 31 | Admin dashboard | 💻 | ⬜ | |
| 32 | Notifications | 🔀 | ⬜ | |
| 33 | Scheduled tasks | ☁️ | ⬜ | |
| 34 | Hardening & load test | 💻 | ⬜ | |

### Phase 6 — Growth
| # | Session | Where | Status | PR |
|---|---|:--:|:--:|---|
| 35 | QR code generation | ☁️ | ⬜ | |
| 36 | Usher scanner page | 💻 | ⬜ | |
| 37 | WhatsApp blast | ☁️ | ⬜ | |
| 38 | Affiliate program | ☁️ | ⬜ | |
| 39 | Coupons | ☁️ | ⬜ | |
| 40 | Custom domains | 💻 | ⬜ | |
| 41 | Support tickets | 🔀 | ⬜ | |

---

## The one rule

**Build vertical slices, not horizontal layers.** One feature end to end — migration → model
→ policy → request → action → controller → view → test → **run it** → commit. Never "generate
all 40 migrations" then "generate all 40 models." Unverified code compounds into a pile you
can't debug.

---

## Session protocol

Do this every session. It's the thing that keeps you from losing track.

**Before:**
1. Open this file. Find your session. Read its Goal, Where, and Done-when.
2. Sync and branch:
   ```powershell
   git checkout main && git pull
   git checkout -b session/NN-slug     # the branch name is in the session's Commit & PR block
   ```
   The working tree must be clean before you branch. **For a cloud session, push the branch
   first** — the cloud VM clones your GitHub remote at your current branch, not your local
   checkout, and push protection means it can only push back to that same branch.
3. Start a fresh context:
    - **Local:** `/clear`
    - **Cloud:** `/clear` doesn't exist in cloud sessions. Start a new session from the
      sidebar at claude.ai/code instead.
4. Paste the session's **Prep prompt**. Read the plan it returns. Approve or correct it.

**During:**
5. Work the numbered steps in order. Don't skip ahead.
6. After each step that writes code, run its verify command yourself.

**After:**
7. Run the session's full **Verify** block.
8. Commit, push, and open the PR — see the session's **Commit & PR** block.
9. [Close out](#close-out-a-session): review the diff, merge, sync `main`.
10. Come back here: set Status ✅ and record the PR number.

Steps 1, 3, 9 and 10 are the ones people skip, and they're exactly the ones that prevent
losing track.

---

## Branch and PR workflow

One branch and one PR per session. The PR is your review gate: a self-contained diff you read
before it reaches `main`, plus a permanent record of what each session actually changed.

### Prerequisites

```powershell
gh --version        # GitHub CLI — install from https://cli.github.com if missing
gh auth login
```

`gh` is pre-installed in cloud sessions and authenticates through the GitHub proxy, so you
don't need `gh auth login` there.

### Branch naming

`session/NN-slug` — e.g. `session/10-payment-webhook`. Each session's **Commit & PR** block
names its branch. The number keeps branches sorted and makes it obvious which session a stale
branch belongs to.

### Close out a session

```powershell
gh pr view --web          # read the diff yourself — this is the point of the PR
gh pr merge --squash --delete-branch
git checkout main && git pull
```

Squash-merge keeps `main` at one commit per session, which makes the "Where am I?" recovery
below work cleanly: `git log --oneline` on `main` reads as a list of completed sessions.

### Merge promptly — don't stack PRs

This matters more here than on a team project. The sessions are sequentially dependent:
Session 15 needs Session 14's schema, Session 20 needs Session 19's editors. If Session 14's
PR is still open when you start 15, you either branch off an unmerged branch (and get a PR
diff containing both sessions) or branch off `main` and lose the schema entirely.

**Merge each PR before starting the next session.** You're reviewing your own work, so the PR
is a checkpoint, not an approval queue. If you're not comfortable merging it, the session
isn't done — go back to its Done-when.

If you genuinely need to work ahead while a PR is open, branch from the open branch rather
than `main`, and set the PR base accordingly:

```powershell
git checkout -b session/15-policies-isolation session/14-invitation-schema
gh pr create --fill --base session/14-invitation-schema
```

Then merge in order, bottom-up. GitHub retargets the child PR to `main` automatically when
the parent merges.

### Cloud sessions and PRs

Cloud sessions create PRs from the web UI when a task finishes, and `gh pr create` works
inside them too. Two things to know:

- **Push protection**: a cloud session can only push to the branch it started on. Create and
  push the branch before starting the session, or ask Claude to create it as its first action.
- **Auto-fix**: once a PR is open, you can have Claude watch it and respond to CI failures and
  review comments automatically. Requires the Claude GitHub App installed on the repo. Useful
  from Session 1 onward if you add CI — see below.

### Add CI so the PRs mean something

A PR with no checks is just a diff viewer. Add this early — ideally as part of Session 1 — and
every subsequent PR tells you whether the session actually passed before you merge it.

`.github/workflows/ci.yml`:

```yaml
name: CI
on:
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_DATABASE: undangyu_test
          MYSQL_ALLOW_EMPTY_PASSWORD: "yes"
        ports: ["3306:3306"]
        options: >-
          --health-cmd="mysqladmin ping" --health-interval=10s
          --health-timeout=5s --health-retries=5
      redis:
        image: redis:7
        ports: ["6379:6379"]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: none
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan migrate --force
        env:
          DB_DATABASE: undangyu_test
          DB_USERNAME: root
          DB_PASSWORD: ""
      - run: ./vendor/bin/pint --test
      - run: ./vendor/bin/phpstan analyse
      - run: php artisan test
```

Swap the MySQL service for `postgres:16` if you took the PostgreSQL route.

### Where this replaces the old advice

Earlier drafts said "commit after every session." That still holds — the commit is just no
longer the end of the session. The end is a merged PR and a clean `main`.

---

## Cloud vs local

Claude Code runs either on your machine or on Anthropic's cloud infrastructure at
claude.ai/code. Cloud sessions are worth using — they run in parallel, persist when you close
the browser, and you can steer them from your phone. But they can't do everything this
project needs.

### What the cloud sandbox gives you

Each session gets a fresh Ubuntu 24.04 VM (4 vCPU, 16 GB RAM, 30 GB disk) with your repo
cloned and toolchains pre-installed:

| Relevant to us | Status |
|---|---|
| PHP 8.3 + Composer | ✅ pre-installed |
| Node 20 / 21 / 22 + npm | ✅ pre-installed (22 on PATH) |
| Redis 7.0 | ✅ pre-installed, **not running** — ask Claude to `service redis-server start` |
| packagist.org, registry.npmjs.org | ✅ on the default Trusted allowlist |
| Docker + compose | ✅ available |
| **MySQL** | ❌ **not pre-installed.** Only PostgreSQL 16 ships |
| Browser | ❌ none |
| Shell access for you | ❌ none — Claude runs every command |

### The database decision

Our ERD specifies MySQL 8, which isn't in the cloud image. Three options:

1. **Install MySQL via setup script** (below). Runs once, then Anthropic snapshots the
   filesystem and reuses it, so later sessions start with it already on disk.
2. **Run MySQL in Docker** via a `compose.yaml` in the repo. Docker Hub is on the Trusted
   allowlist. Note the cache keeps pulled images but not running containers — Claude starts
   them each session.
3. **Switch the project to PostgreSQL 16.** Worth considering seriously. Nothing in our
   schema needs MySQL specifically, Postgres has stronger JSON support for the `theme_config`
   and `entitlements` columns we lean on, and it's zero-config in the cloud.

**Decided at Session 2: MySQL 8** (option 1 — installed via the setup script). Recorded in
`CLAUDE.md` § Decisions already made. `docs/03-database-erd.md` stays authoritative.

### Setup script (option 1)

Paste into the **Setup script** field of your cloud environment at claude.ai/code
(cloud icon above the message box → environment settings):

```bash
#!/bin/bash
apt-get update -qq || true
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server || true
service mysql start || true
mysql -e "CREATE DATABASE IF NOT EXISTS undangyu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';" || true
exit 0
```

Two constraints to respect: the script **must exit zero** or the session fails to start
(hence the `|| true` on every line), and it must **finish within about five minutes** or the
environment cache won't build.

The cache keeps files, not processes — so MySQL is installed but stopped at the start of each
new session. Add this to your **environment variables** and ask Claude to start the services
at the top of each cloud session:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=undangyu
DB_USERNAME=root
DB_PASSWORD=
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

Opening line for any cloud session:

> Start MySQL and Redis (`service mysql start`, `service redis-server start`), then confirm
> `php artisan migrate:status` runs before we begin.

### Which sessions go where

The rule is simple: **if the session's Verify block has a manual browser check, it needs your
machine.**

**☁️ Cloud** — backend work fully verified by `php artisan test`. Sessions 2, 4, 5, 7, 10, 11,
14, 15, 20, 25, 29, 30, 33, 35, 37, 38, 39. Session 10 (the test-first webhook) and Session 15
(tenant isolation) are near-perfect fits: pure test-driven, no UI.

**💻 Local** — anything with a visual or device check. Sessions 0, 1, 3, 16–19, 21, 22, 23, 26,
27, 31, 34, 36, 40. Session 21 needs a real phone to confirm the WhatsApp preview card renders;
Session 22 needs Lighthouse and a real Android; Session 36 needs a camera.

**🔀 Either** — build in cloud, verify locally. Sessions 6, 8, 9, 12, 13, 24, 28, 32, 41.

### Moving between them

```powershell
claude --cloud "Execute Session 10 from docs/06-claude-code-playbook.md"   # start in cloud
claude --teleport                                                           # pull it back local
```

`--teleport` fetches the cloud session's branch and loads the full conversation into your
terminal. It needs a clean working tree, the same repository (not a fork), and the same
claude.ai account. Handy pattern: run a cloud session, then teleport in to do the manual
browser verification before committing.

### Cloud caveats worth knowing

- Cloud sessions are a **research preview** for Pro, Max, and Team plans, and for Enterprise
  users with premium or Chat + Claude Code seats.
- They share your account's rate limits; parallel sessions consume proportionately more.
- Sessions stop after inactivity and the VM is reclaimed. Reopening provisions a fresh VM with
  history restored, but background work that was running is not.
- Only your repo's `CLAUDE.md`, `.claude/` directory, and committed files come along. Anything
  configured only on your machine doesn't.

### The prompt to actually use

"Do session 1" won't work — `CLAUDE.md` loads automatically, but `docs/06` is just another
file Claude hasn't read. Name it:

> Read `docs/06-claude-code-playbook.md` and find "Session N — {title}".
>
> Run its Prep prompt first: list the exact commands and the files you'll create or modify,
> and wait for my approval before writing anything.
>
> Then work the numbered steps in order, running each step's verification before moving on.
> Stop and tell me if any verification fails.

---

## Session 0 — Environment setup

**Goal:** a running Laravel app with docs in place and Claude Code connected.
**Depends on:** nothing. **Where:** 💻 local — this one can't be done in the cloud, since
cloud sessions clone an existing GitHub repo and there isn't one yet.

**0.1** Verify toolchain (PowerShell):
```powershell
php -v          # need >= 8.3
composer -V
node -v         # need >= 20
mysql --version
```
- [ ] All four report versions. If PHP is missing, install [Laravel Herd](https://herd.laravel.com).

**0.2** Install Claude Code:
```powershell
npm install -g @anthropic-ai/claude-code
claude --version
```
- [ ] Version prints.

**0.3** Scaffold:
```powershell
laravel new undangyu
# Starter kit: None    (AdminLTE replaces it)
# Database: MySQL
# Testing: Pest
cd undangyu
```
- [ ] Project created.

**0.4** Create the database:
```powershell
mysql -u root -e "CREATE DATABASE undangyu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**0.5** Configure `.env`, then migrate:
```
APP_NAME=Undangyu
APP_URL=http://undangyu.test
DB_DATABASE=undangyu
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```
```powershell
php artisan key:generate
php artisan migrate
```
- [ ] Migration runs clean.

**0.6** Copy the docs package in:
```
undangyu/
├── CLAUDE.md          ← repo ROOT, not inside docs/
└── docs/
    ├── 01-scope-and-vision.md
    ├── 02-functional-spec.md
    ├── 03-database-erd.md
    ├── 04-flowcharts.md
    ├── 05-technical-architecture.md
    ├── 06-claude-code-playbook.md   ← this file
    └── 07-roadmap.md
```
- [ ] `CLAUDE.md` is at the repo root.

**0.7** Record your open decisions in `CLAUDE.md` § "Decisions still open":
- [ ] Payment gateway chosen — see [05 § 6](05-technical-architecture.md#6-payments)
- [ ] Guest link format confirmed (default `?to={token}`)
- [ ] **Database engine** — MySQL 8 or PostgreSQL 16. See
  [§ The database decision](#the-database-decision). Deciding this at Session 14 instead
  of now means rewriting migrations.

**0.7b** If you plan to use cloud sessions, set the environment up now — GitHub connection,
setup script, and environment variables — per [§ Cloud vs local](#cloud-vs-local). Push the
repo to GitHub before any cloud session; the cloud VM clones your remote, not your local
checkout.

**0.8** Initial commit and remote. This one goes straight to `main` — there's nothing to
review yet, and every later session branches from it:
```powershell
git init
git add .
git commit -m "chore: scaffold + project documentation"
gh repo create undangyu --private --source=. --remote=origin --push
```
- [ ] The repo exists on GitHub and `main` has your scaffold and docs.

**0.8b** Protect `main` so sessions can't bypass their own PRs:
```powershell
gh api -X PUT repos/:owner/undangyu/branches/main/protection `
  -F required_pull_request_reviews.required_approving_review_count=0 `
  -F enforce_admins=false -F required_status_checks=null -F restrictions=null
```
Optional, but it turns "I'll just commit straight to main this once" from a habit into a
deliberate override.

**0.9** Start Claude Code and verify comprehension:
```powershell
claude
```
> Read `CLAUDE.md`, then `docs/01-scope-and-vision.md` and `docs/05-technical-architecture.md`.
>
> Summarise back to me in under 200 words:
> 1. The three surfaces and which UI approach each uses
> 2. Why we are not using Inertia
> 3. How templates are rendered
> 4. What `invitations.entitlements` is and why it's snapshotted
>
> Do not write any code.

- [ ] The summary is correct. **If it's wrong, correct it now** — every later session inherits this understanding.

### Done when
Laravel runs at `undangyu.test`, docs are committed, Claude answered 0.9 correctly.

---

## Session 1 — Install & configure packages

**Goal:** every dependency installed, route files split, tooling configured.
**Depends on:** Session 0. **Where:** 💻 local.

### Prep prompt
> Read `CLAUDE.md` and `docs/05-technical-architecture.md` § 3 and § 4.
> Session 1: install packages and set up project structure. List the exact commands and the
> files you'll create or modify. Wait for my approval.

**1.1** Install PHP packages:
```powershell
composer require spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-activitylog spatie/laravel-backup spatie/laravel-sluggable
composer require laravel/horizon laravel/sanctum
composer require maatwebsite/excel simplesoftwareio/simple-qrcode barryvdh/laravel-dompdf intervention/image propaganistas/laravel-phone sentry/sentry-laravel
composer require --dev laravel/pint larastan/larastan barryvdh/laravel-debugbar
```
- [ ] No dependency conflicts.

**1.2** > Publish config and migrations for spatie/laravel-permission, medialibrary,
> activitylog, backup, horizon and sanctum. Then run `php artisan migrate` and show me the
> resulting table list.
- [ ] Migration succeeds.

**1.3** > Create `routes/admin.php`, `routes/client.php`, `routes/public.php`,
> `routes/webhooks.php`. Register them in `bootstrap/app.php`:
> - `admin.php` → prefix `admin`, middleware `web,auth,role:super-admin|admin|support`
> - `client.php` → prefix `dashboard`, middleware `web,auth,role:client|reseller`
> - `public.php` → no prefix, middleware `web`
> - `webhooks.php` → prefix `webhooks`, middleware `api` only (**no CSRF**)
>
> Put one placeholder route in each so I can verify registration.
```powershell
php artisan route:list
```
- [ ] All four appear with the correct prefixes and middleware.

**1.4** > Create empty directories with `.gitkeep`:
> `app/Actions/{Invitations,Orders,Guests}`, `app/Enums`, `app/Observers`,
> `app/Services/{Payment,Whatsapp,Media,Entitlements}`, `app/Support`.

**1.5** > 1. Create `pint.json` using the `laravel` preset.
> 2. Create `phpstan.neon` for larastan at level 5, scanning `app/`.
> 3. In `AppServiceProvider::boot()`, add `Model::preventLazyLoading(!app()->isProduction())`
     >    and `Model::shouldBeStrict(!app()->isProduction())`.

**1.5b** > Create `.github/workflows/ci.yml` running pint, phpstan and `php artisan test` on
> pull requests against `main`, with MySQL 8 and Redis 7 service containers. Use the workflow
> in [§ Add CI](#add-ci-so-the-prs-mean-something) as the starting point.

- [ ] The workflow file exists. It'll first run on this session's own PR.

**1.6** Frontend packages:
```powershell
npm install vue @vitejs/plugin-vue admin-lte@^4 bootstrap @popperjs/core axios @vueuse/core vuedraggable@next html5-qrcode swiper aos dayjs
npm install -D tailwindcss @tailwindcss/vite
```
- [ ] No peer-dependency errors.

### Verify
```powershell
php artisan route:list          # 4 placeholder routes, correct prefixes
php artisan migrate:status      # vendor migrations ran
./vendor/bin/pint --test        # clean
./vendor/bin/phpstan analyse    # no errors
npm run build                   # builds
```

### Commit & PR

Branch: `session/01-setup-packages`

```powershell
git add -A && git commit -m "chore(setup): install packages, split routes, configure tooling"
git push -u origin session/01-setup-packages
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### Done when
All five verify commands pass. Nothing renders yet — expected.

---

## Session 2 — Auth, roles & permissions

**Goal:** working auth with the six roles from M1.4, enforced by middleware.
**Depends on:** Session 1. **Where:** ☁️ cloud.

### Prep prompt
> Read `CLAUDE.md`, `docs/02-functional-spec.md` § M1, and `docs/04-flowcharts.md` § 11
> (role matrix). Session 2: auth + roles. List the files you'll create. Wait for approval.

**2.1** > Migration adding to `users`, per `docs/03-database-erd.md` § 3.1: `phone` (unique),
> `phone_verified_at`, `avatar`, `status` enum, `referred_by` (nullable, FK later),
> `last_login_at`, plus `softDeletes`. Add the indexes from that section. Update the `User`
> model: `$fillable`, casts, `HasRoles`, `SoftDeletes`.
```powershell
php artisan migrate:fresh
```
- [ ] Clean.

**2.2** > Create `app/Enums/UserStatus.php` — backed string enum: `Active`, `Suspended`,
> `Banned`. Cast it on the User model.

**2.3** > Install Laravel Breeze, Blade stack (we restyle with AdminLTE in Session 3). Add
> `phone` as a required registration field, validated and normalised to E.164 via
> propaganistas/laravel-phone with default region ID.
- [ ] `/register` and `/login` load.

**2.4** > Create `RolePermissionSeeder` implementing the matrix in `docs/04-flowcharts.md` § 11.
> Roles: `super-admin`, `admin`, `support`, `client`, `reseller`, `usher`.
> Permissions named `{resource}.{action}` — e.g. `invitations.viewAny`, `orders.verify`,
> `settings.manage`, `withdrawals.approve`.
> `super-admin` gets everything via `Gate::before`, not by assigning every permission.

**2.5** > Create `AdminUserSeeder` reading `ADMIN_EMAIL` and `ADMIN_PASSWORD` from env,
> assigning `super-admin`. Never hardcode credentials. Register both seeders in
> `DatabaseSeeder`.
```powershell
php artisan migrate:fresh --seed
```
- [ ] Seeds without error.

**2.6** > After login, redirect by role: admin roles → `/admin`, client/reseller →
> `/dashboard`, usher → their scanner. Add placeholder routes for each.

**2.7** > Feature tests: registration assigns the `client` role; a client hitting `/admin`
> gets 403; an admin gets 200; login redirects by role.

### Verify
```powershell
php artisan migrate:fresh --seed
php artisan test --filter=Auth
php artisan tinker --execute="dd(Spatie\Permission\Models\Role::pluck('name'));"
```
- [ ] Six roles listed. Tests pass.
- [ ] Manually register, log in, land on `/dashboard`.

### Commit & PR

Branch: `session/02-auth-roles`

```powershell
git add -A && git commit -m "feat(M1): auth, roles and permissions"
git push -u origin session/02-auth-roles
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### Done when
You can register, log in, and are correctly blocked from `/admin` as a client.

---

## Session 3 — AdminLTE layout & Vite

**Goal:** the shell every dashboard page extends, plus a proven Vue island mount.
**Depends on:** Session 2. **Where:** 💻 local — the AdminLTE + Vue check must be seen in a browser.

### Prep prompt
> Read `CLAUDE.md` and `docs/05-technical-architecture.md` § 2 (the AdminLTE + Vue islands
> decision). Session 3: layout and asset pipeline.
> Confirm you understand that AdminLTE owns the page chrome and Vue mounts only into
> dedicated container divs. Then list the files you'll create.

**3.1** > Configure `vite.config.js` with two separate input groups:
> - Dashboard: `resources/css/dashboard.css` (Bootstrap + AdminLTE), `resources/js/dashboard.js`
> - Public: `resources/css/public.css` (Tailwind), `resources/js/public.js`
>
> Add the Vue plugin and an `@` alias to `resources/js`. These bundles must never load on the
> same page.

**3.2** > Create `resources/views/layouts/adminlte.blade.php`: AdminLTE 4 shell with navbar,
> sidebar, content wrapper, footer. Sections for `title`, `content`, `breadcrumb`. Stacks for
> `styles` and `scripts`. Sidebar items come from a `config/menu.php` array filtered by the
> user's permissions — not hardcoded in Blade.

**3.3** > Create `layouts/admin.blade.php` and `layouts/client.blade.php`, both extending
> `adminlte.blade.php` with their own menu config key. The client layout collapses the sidebar
> by default on mobile — clients edit on phones.

**3.4** > Create `Admin\DashboardController` and `Client\DashboardController` with index views
> extending their layouts. Wire up the Session 1 routes.
- [ ] `/admin` and `/dashboard` render with AdminLTE styling.

**3.5** > Prove the island pattern with a throwaway: `resources/js/islands/hello.js` mounting
> `resources/js/components/shared/HelloIsland.vue` (a `<script setup>` counter) into
> `<div id="hello-island" data-message="...">` on the client dashboard. Register the entry in
> `vite.config.js`, push it via `@push('scripts')`.
```powershell
npm run dev
```
- [ ] The counter increments **and** the AdminLTE sidebar still toggles. **Both must work** — this is the exact thing that breaks if the pattern is wrong.

**3.6** > Restyle the Breeze auth pages with AdminLTE's login/register look. Delete the
> Tailwind-based Breeze views.

**3.7** > Delete the hello island files and mount div. Keep the vite.config.js pattern as a
> reference comment.

### Verify
```powershell
npm run build
php artisan test
```
- [ ] `/admin`, `/dashboard`, `/login` styled. Sidebar toggles. No console errors.

### Commit & PR

Branch: `session/03-adminlte-layout`

```powershell
git add -A && git commit -m "feat(M11): AdminLTE layout, Vite dual-bundle, Vue island pattern"
git push -u origin session/03-adminlte-layout
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### Done when
Both dashboards render, the sidebar works, and you've seen Vue mount without breaking AdminLTE.

---

## Session 4 — Settings module

**Goal:** cached, typed global settings with an admin UI.
**Depends on:** Session 3. **Where:** ☁️ cloud.

**4.1** > Migration + model for `settings` per `docs/03-database-erd.md` § 3.9. Unique on
> (`group`,`key`).

**4.2** > Create `App\Support\Settings` with `get($key,$default)`, `set($key,$value)`,
> `group($group)`. Cast values by the `type` column. Cache forever, flush on write. Register
> as a singleton with a `Setting` facade.

**4.3** > Seed defaults in groups: `site` (name, tagline, logo, contact email, WA number),
> `payment` (driver name only — keys stay in env), `invitation` (default active days, slug
> blocklist), `media` (max upload MB, allowed mimes), `seo` (default meta).

**4.4** > Admin settings page: tabbed by group, fields rendered from the `type` column,
> validation in a FormRequest, `settings.manage` permission required.

**4.5** > Feature test: settings persist, cache flushes on write, missing permission → 403.

### Verify
```powershell
php artisan test --filter=Setting
php artisan tinker --execute="Setting::set('site.name','Undangyu'); dd(Setting::get('site.name'));"
```
- [ ] The admin page saves and the value survives a reload.

### Commit & PR

Branch: `session/04-settings`

```powershell
git add -A && git commit -m "feat(M11.8): settings module"
git push -u origin session/04-settings
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 5 — Event types & template categories

**Goal:** the taxonomy that makes the product event-agnostic.
**Depends on:** Session 4. **Where:** ☁️ cloud.

**5.1** > Migrations + models for `event_types` and `template_categories` per § 3.3.
> `person_roles` and `default_sections` are JSON casts.

**5.2** > Seed 8 event types: pernikahan (bride, groom), tunangan (bride, groom), ulang-tahun
> (celebrant), aqiqah (child, parents), khitanan (child, parents), wisuda (graduate),
> corporate (host), umum (host) — each with sensible `default_sections` ordering.
> Seed 8 template categories: floral, minimalis, luxury, islami, rustic, jawa, modern, dark.

**5.3** > Admin CRUD for both: index with sort-order drag, create/edit forms, `is_active`
> toggle. Permission-gated.

**5.4** > Feature test: only permitted users can write; a deactivated event type disappears
> from public listings.

### Verify
```powershell
php artisan migrate:fresh --seed
php artisan tinker --execute="dd(App\Models\EventType::pluck('slug'));"
```
- [ ] 8 event types. Admin CRUD works end to end.

### Commit & PR

Branch: `session/05-event-types`

```powershell
git add -A && git commit -m "feat(M3.1,M3.2): event types and template categories"
git push -u origin session/05-event-types
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 6 — Templates

**Goal:** template catalog with admin CRUD and a public gallery.
**Depends on:** Session 5. **Where:** 🔀 either.

**6.1** > Migrations + models for `templates`, `template_screenshots`, and the
> `event_type_template` pivot per § 3.3. `config_schema`, `default_config`, `demo_data` are
> JSON casts. Slug via spatie/laravel-sluggable.

**6.2** > `App\Enums\TemplateStatus`: `Draft`, `Published`, `Archived`.

**6.3** > Admin template CRUD: name, category, `view_key`, version, supported event types
> (multi-select), thumbnail, sortable screenshots, `is_premium`, `extra_price`, status.
> `config_schema` and `default_config` as validated JSON textareas — a visual editor is out
> of scope.

**6.4** > Public gallery at `/templates`: grid with event-type, category and tier filters.
> Paginated, eager-loaded, cached 1h.

**6.5** > Detail page at `/templates/{slug}` with screenshots and a preview button (the
> preview route itself lands in Session 21).

**6.6** > Factory + seeder creating 3 draft templates with realistic `config_schema` matching
> the example in `docs/05-technical-architecture.md` § 5.

**6.7** > Feature tests: gallery shows only published templates; filters work; archived
> templates 404 publicly.

### Verify
```powershell
php artisan test --filter=Template
```
- [ ] `/templates` lists seeded templates, filters work, no N+1 (check the Debugbar query count).

### Commit & PR

Branch: `session/06-templates`

```powershell
git add -A && git commit -m "feat(M3.3-M3.5): template catalog and public gallery"
git push -u origin session/06-templates
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 7 — Packages & entitlements

**Goal:** packages with feature flags and a resolver that snapshots them.
**Depends on:** Session 6. **Where:** ☁️ cloud.

**7.1** > Migrations + models for `packages` and `package_features` per § 3.2. Unique on
> (`package_id`,`feature_key`).

**7.2** > `App\Enums\FeatureKey` with every key from `docs/02-functional-spec.md` M2.2:
> `max_guests`, `max_photos`, `max_video_mb`, `whatsapp_quota`, `custom_domain`,
> `remove_watermark`, `rsvp`, `guestbook`, `checkin`, `music`, `live_stream`, `export`,
> `analytics_advanced`.

**7.3** > Create `App\Services\Entitlements\EntitlementResolver`:
> - `resolve(Package $package): array` → flat key→value map; `is_unlimited` becomes `null`
> - `check(array $entitlements, FeatureKey $key, int $current = 0): bool`
>
> Cache per package for 24h, flush on package save. This output gets snapshotted onto
> `invitations.entitlements` later, so it must be stable and serialisable.

**7.4** > Admin package CRUD with a repeatable feature-flag editor (key dropdown from the
> enum, value input, unlimited checkbox).

**7.5** > Seed 4 packages — Free, Basic, Premium, Exclusive — with full feature sets and
> realistic IDR pricing.

**7.6** > Public pricing page at `/harga`: comparison table generated from the feature flags,
> not hardcoded.

**7.7** > Unit tests for `EntitlementResolver`: unlimited handling, missing keys, boolean
> coercion, `check()` at and over the boundary.

### Verify
```powershell
php artisan test --filter=Entitlement
php artisan tinker --execute="dd(app(App\Services\Entitlements\EntitlementResolver::class)->resolve(App\Models\Package::where('slug','premium')->first()));"
```
- [ ] Resolver returns a flat array. `/harga` renders the comparison table.

### Commit & PR

Branch: `session/07-packages-entitlements`

```powershell
git add -A && git commit -m "feat(M2.1-M2.3): packages, feature flags, entitlement resolver"
git push -u origin session/07-packages-entitlements
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### 🔍 Checkpoint — Phase 1 review
Start a fresh context first (`/clear` locally, or a new session from the sidebar in cloud).

> Review all code since the initial commit against `CLAUDE.md` conventions. Report,
> prioritised, **without fixing anything**:
> 1. Convention violations
> 2. Routes touching user data with no authorization check
> 3. N+1 risks
> 4. Missing validation
> 5. Duplicated logic

Fix categories 2 and 4 before continuing.

---

## Session 8 — Orders & checkout

**Goal:** an order can be created from a package + template selection.
**Depends on:** Session 7. **Where:** 🔀 either.

**8.1** > Migrations + models for `orders`, `order_items`, `invoices` per § 3.2.
> `order_items` is polymorphic (`itemable`).

**8.2** > `App\Enums\OrderStatus`: `Pending`, `Paid`, `Provisioned`, `Expired`, `Cancelled`,
> `Refunded`.

**8.3** > `App\Actions\Orders\CreateOrder`, inside a DB transaction: generate `order_number`
> as `UDY-{Ymd}-{padded sequence}`; **snapshot** package name and price into `order_items`
> (see the warning in § 3.2 — historical prices must not change); compute
> subtotal/discount/tax/total; set `payment_deadline` to +24h.

**8.4** > Checkout: `/checkout?package={slug}&template={slug}` → summary → POST creates the
> order → redirect to a payment placeholder (real gateway in Session 9). Auth required;
> guests redirect to login and return.

**8.5** > Client order list and detail under `/dashboard/orders`, policy-scoped.

**8.6** > Scheduled command `orders:expire` setting `pending` orders past `payment_deadline`
> to `expired`. Register hourly.

**8.7** > Feature tests: order-number uniqueness under concurrency; totals correct;
> cross-client access blocked; expiry command works.

### Verify
```powershell
php artisan test --filter=Order
php artisan orders:expire
```
- [ ] Checkout creates a `pending` order. The list shows only your own.

### Commit & PR

Branch: `session/08-orders-checkout`

```powershell
git add -A && git commit -m "feat(M2.4,M2.9,M2.10): orders and checkout"
git push -u origin session/08-orders-checkout
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 9 — Payment gateway abstraction

**Goal:** a gateway-agnostic interface with one concrete driver.
**Depends on:** Session 8. **Blocked until you've recorded a gateway choice in `CLAUDE.md`.** **Where:** 🔀 either.

**9.1** > Create the contract and DTOs from `docs/05-technical-architecture.md` § 6:
> `App\Services\Payment\Contracts\PaymentGateway`, plus `PaymentSession`, `PaymentUpdate`,
> `RefundResult` value objects.

**9.2** > Migration + model for `payments` per § 3.2. **Unique index on `gateway_ref`** — the
> idempotency key, and the single most important index in the schema.

**9.3** > `App\Enums\PaymentStatus`: `Pending`, `Settled`, `Failed`, `Expired`, `Refunded`.

**9.4** > Implement the driver for the chosen gateway. Create `config/payment.php` with
> `driver`, per-driver credentials from env, and `is_production`. Bind the concrete class in a
> service provider from `config('payment.driver')`.

**9.5** > Wire checkout: `CreateOrder` → `PaymentGateway::createTransaction()` → redirect to
> the hosted page or render the popup.

**9.6** > Add a `fake` driver that settles instantly, registered in the config map, so feature
> tests never hit the network.

**9.7** > Tests using the fake driver: transaction creation persists a `pending` payment with
> a `gateway_ref`.

### Verify
```powershell
php artisan test --filter=Payment
```
- [ ] Sandbox credentials in `.env`; checkout reaches the real gateway page.
- [ ] Flipping `config('payment.driver')` actually swaps implementations.

### Commit & PR

Branch: `session/09-payment-gateway`

```powershell
git add -A && git commit -m "feat(M2.5): payment gateway abstraction and driver"
git push -u origin session/09-payment-gateway
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 10 — Webhook handler (test-first)

**Goal:** a bulletproof, idempotent webhook endpoint.
**Depends on:** Session 9. **Where:** ☁️ cloud — pure test-driven, ideal cloud fit.

> ⚠️ **Write the tests before the implementation here.** This endpoint handles money and gets
> replayed by the gateway. It's the one place where test-first genuinely pays.

**10.1** Tests first:
> Write **failing** feature tests for `POST /webhooks/{gateway}`, per `docs/04-flowcharts.md` § 5:
> 1. Invalid signature → 403, nothing persisted
> 2. Valid settlement → payment `settled`, order `paid`, provisioning job dispatched
> 3. **Same payload delivered 5× → exactly one paid order, one dispatched job**
> 4. Unknown `gateway_ref` → 404, logged
> 5. Settlement for an already-expired order → handled gracefully, flagged for review
> 6. Malformed JSON → 400, no exception leaked
>
> Use `Bus::fake()` and `Queue::fake()`. Do not implement the handler yet.
- [ ] All six tests exist and fail.

**10.2** > Now implement `Webhooks\PaymentWebhookController` to pass them, in this order:
> 1. Log the raw payload to `payments.raw_payload` before anything else
> 2. Verify the signature; abort 403 on failure
> 3. Open a transaction; `SELECT ... FOR UPDATE` on the payment by `gateway_ref`
> 4. If already `settled`, return 200 immediately (no-op)
> 5. Update payment and order status
> 6. Dispatch `ProvisionInvitationJob` (Session 11 — stub it for now)
> 7. Return 200
>
> No CSRF, no auth middleware on this route.

**10.3** > Add `payments:reconcile` — a daily command comparing local settled payments against
> the gateway's settlement report, logging discrepancies. Webhooks do get lost.

### Verify
```powershell
php artisan test --filter=Webhook
```
- [ ] **All six green.** Especially #3.
- [ ] Fire a real sandbox payment; confirm the webhook lands (check `payments.raw_payload`).

### Commit & PR

Branch: `session/10-payment-webhook`

```powershell
git add -A && git commit -m "feat(M2.6): idempotent payment webhook handler"
git push -u origin session/10-payment-webhook
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 11 — Provisioning job

**Goal:** a paid order becomes a draft invitation with snapshotted entitlements.
**Depends on:** Session 10 **and** Session 14's schema. **Where:** ☁️ cloud.

> **Ordering note:** Sessions 12–13 don't block this. If you'd rather not work against a stub,
> do Session 14 first, then come back here.

**11.1** > Create `App\Jobs\ProvisionInvitationJob` — idempotent (check for an existing
> invitation on the order first). Inside a transaction:
> 1. Resolve entitlements via `EntitlementResolver`
> 2. Create the invitation: status `draft`, `template_version` pinned from the template,
     >    `entitlements` snapshotted, `expires_at` null until publish
> 3. Seed `invitation_sections` from the event type's `default_sections`
> 4. Set order status to `provisioned`
> 5. Dispatch the notification

**11.2** > `InvitationProvisioned` notification — email + database channel, linking to the
> builder.

**11.3** > Tests: running twice creates one invitation; entitlements match the package;
> sections seed in order; order status transitions.

### Verify
```powershell
php artisan test --filter=Provision
php artisan queue:work --once
```
- [ ] End to end: sandbox payment → webhook → job → draft invitation with entitlements.

### Commit & PR

Branch: `session/11-provisioning`

```powershell
git add -A && git commit -m "feat(M2.9): invitation provisioning job"
git push -u origin session/11-provisioning
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 12 — Manual payment verification

**Goal:** bank-transfer path with admin approval.
**Depends on:** Session 11. **Where:** 🔀 either.

**12.1** > At checkout, offer "Transfer Bank Manual" alongside the gateway. Selecting it
> creates a `manual` payment and shows the destination account from settings.

**12.2** > Client uploads proof (image/PDF, max 5MB, mime-validated). Stored on a **private**
> disk.

**12.3** > Admin queue at `/admin/payments/pending`: proof preview, amount-match check,
> approve/reject with a note.

**12.4** > Approving runs the **same** path as a settled webhook — set `verified_by`, settle
> the payment, dispatch `ProvisionInvitationJob`. Do not duplicate provisioning logic.

**12.5** > Tests: upload validation; only permitted roles approve; approval provisions exactly
> one invitation.

### Verify
- [ ] Full manual flow: checkout → upload proof → admin approves → invitation exists.

### Commit & PR

Branch: `session/12-manual-payment`

```powershell
git add -A && git commit -m "feat(M2.7): manual payment verification"
git push -u origin session/12-manual-payment
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 13 — Invoices

**Goal:** downloadable PDF invoices.
**Depends on:** Session 12. **Where:** 🔀 either.

**13.1** > On order `paid`, queue `GenerateInvoiceJob`: sequential `invoice_number`, render a
> Blade view to PDF via dompdf, store privately, persist the path.

**13.2** > Invoice Blade template: company details from settings, line items, totals, IDR
> formatting, payment method and date.

**13.3** > Download route via signed URL, policy-gated to the order owner and admins.

**13.4** > Tests: invoice-number sequence has no gaps; another client gets 403.

### Verify
- [ ] Download a generated invoice. Check formatting and IDR amounts.

### Commit & PR

Branch: `session/13-invoices`

```powershell
git add -A && git commit -m "feat(M2.8): invoice generation"
git push -u origin session/13-invoices
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### 🔍 Checkpoint — Phase 2 review
Start a fresh context first (`/clear` locally, or a new session from the sidebar in cloud).

> Review the commerce module (Sessions 8–13). Report only:
> 1. Any path where an order could be provisioned twice
> 2. Any place reading a live package price instead of the order snapshot
> 3. Missing authorization on order/invoice/payment routes
> 4. Money handled as float anywhere

---

## Session 14 — Invitation schema

**Goal:** every invitation table, model, relationship and factory.
**Depends on:** Session 7. **Where:** ☁️ cloud.

> The largest schema session. Work the sub-steps — do not do this in one prompt.

**14.1** > Migration + model for `invitations` per § 3.4. Every column, every index from § 5,
> `theme_config`/`settings`/`entitlements` as JSON casts, uuid, softDeletes. Slug via
> spatie/laravel-sluggable with a reserved-word blocklist from settings.
```powershell
php artisan migrate
```
- [ ] Clean.

**14.2** > `App\Enums\InvitationStatus` (`Draft`, `Published`, `Expired`, `Suspended`) and
> `InvitationVisibility` (`Public`, `Unlisted`, `Password`). Cast both.

**14.3** > Migrations + models for `invitation_sections` and `invitation_persons`. Relations
> both ways. `person.role` validated against the parent event type's `person_roles`.

**14.4** > Migrations + models for `invitation_events` and `invitation_stories`. Datetimes
> stored UTC, with an accessor rendering in the invitation's timezone.

**14.5** > Migrations + models for `invitation_media` and `invitation_gifts`.
> `gifts.account_number` uses the **`encrypted` cast** — see § 3.4.

**14.6** > Factories for all seven models with realistic Indonesian data: names, `+62` phone
> numbers, Surabaya/Jakarta venues, IDR amounts, plausible Akad/Resepsi timings.

**14.7** > A `DemoInvitationSeeder` producing one complete published invitation — this powers
> template previews in Session 21.

### Verify
```powershell
php artisan migrate:fresh --seed
php artisan tinker --execute="dd(App\Models\Invitation::with(['persons','events','media','gifts','sections'])->first()->toArray());"
```
- [ ] The full object graph loads. `entitlements` and `theme_config` deserialise as arrays.

### Commit & PR

Branch: `session/14-invitation-schema`

```powershell
git add -A && git commit -m "feat(M4): invitation schema, models, factories"
git push -u origin session/14-invitation-schema
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 15 — Policies & tenant isolation

**Goal:** provable isolation between clients.
**Depends on:** Session 14. **Where:** ☁️ cloud — pure test-driven, ideal cloud fit.

> Do not postpone this. Retrofitting authorization after the UI exists means auditing every
> controller you've already written.

**15.1** > Create `InvitationPolicy` with `viewAny`, `view`, `create`, `update`, `delete`,
> `publish`. Owner OR `created_by` (reseller) OR admin role. Register it.

**15.2** > Policies for every child model — `InvitationPersonPolicy`, `InvitationEventPolicy`,
> `InvitationMediaPolicy`, `InvitationGiftPolicy`, `InvitationSectionPolicy` — each delegating
> to the parent invitation's policy. Don't duplicate the logic.

**15.3** > Add a `ScopedToUser` trait applying a global scope for non-admin users. Policies
> remain the primary gate; the scope is defence in depth.

**15.4** > **Cross-tenant test suite** — `tests/Feature/TenantIsolationTest.php`. Two clients,
> each with an invitation and children. Assert client A gets 403 or 404 on every read and
> write route for client B's invitation, persons, events, media, gifts and sections. Use a
> data provider so adding a route means adding one line.

**15.5** > Add `Gate::before` for `super-admin`, covered by a test.

### Verify
```powershell
php artisan test --filter=TenantIsolation
```
- [ ] Every case passes. This suite runs in CI forever.

### Commit & PR

Branch: `session/15-policies-isolation`

```powershell
git add -A && git commit -m "feat(M1.4): invitation policies and tenant isolation"
git push -u origin session/15-policies-isolation
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 16 — Builder shell

**Goal:** the client's invitation list and builder page skeleton.
**Depends on:** Session 15. **Where:** 💻 local.

**16.1** > `/dashboard/invitations` — list with status badge, event date, guest count, view
> count, quick actions. Policy-scoped. Empty state links to `/harga`.

**16.2** > `/dashboard/invitations/{invitation}/edit` — builder shell with tab nav (Dasar,
> Mempelai, Acara, Cerita, Galeri, Hadiah, Tema, Pengaturan), content pane, sticky preview
> button. On mobile the tabs collapse to a dropdown.

**16.3** > "Dasar" tab (plain Blade, no Vue): title, slug with live availability check,
> language, timezone, meta title/description.

**16.4** > `PATCH /api/invitations/{invitation}` autosave endpoint returning validation errors
> as JSON, policy-gated. Every Vue island in Sessions 17–19 calls this.

**16.5** > A completeness indicator computed from the publish gate in `docs/04-flowcharts.md`
> § 4, showing which requirements are still unmet.

### Verify
- [ ] Builder loads for your own invitation, 403s for someone else's.
- [ ] Slug availability check works. Basic info saves.

### Commit & PR

Branch: `session/16-builder-shell`

```powershell
git add -A && git commit -m "feat(M4.1,M4.2): builder shell and invitation list"
git push -u origin session/16-builder-shell
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 17 — Builder: persons & event sessions

**Goal:** the first real Vue islands with autosave.
**Depends on:** Session 16. **Where:** 💻 local.

**17.1** > Vue island `PersonsEditor`: repeatable person cards, roles restricted to the event
> type's `person_roles`, photo upload, drag-reorder via vuedraggable, debounced autosave
> (800ms), per-field validation errors, visible save-state indicator.

**17.2** > API endpoints for person CRUD + reorder. FormRequest validation. Policy-gated.

**17.3** > Vue island `EventSessionsEditor`: repeatable sessions with datetime pickers, venue,
> address, Maps URL, dress code, live stream URL, drag-reorder.

**17.4** > Google Maps URL parsing — paste a share link, extract lat/lng where possible, fall
> back to manual entry. No API key needed for the basic case.

**17.5** > API endpoints for session CRUD + reorder.

**17.6** > Tests: autosave persists; invalid role rejected; cross-tenant write 403s.

### Verify
```powershell
php artisan test --filter=Person
php artisan test --filter=EventSession
```
- [ ] Add two persons and two sessions, reload, data persists.
- [ ] Reorder, reload, order persists.

### Commit & PR

Branch: `session/17-builder-persons-events`

```powershell
git add -A && git commit -m "feat(M4.3,M4.4): persons and event sessions builder"
git push -u origin session/17-builder-persons-events
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 18 — Builder: media pipeline

**Goal:** uploads that don't fall over on 8MB phone photos.
**Depends on:** Session 17. **Where:** 💻 local.

**18.1** > Client-side compression before upload (canvas resize, max 2000px, quality 0.85).
> Show original vs compressed size.

**18.2** > Upload endpoint: mime allowlist, size cap, quota check against
> `invitations.entitlements` (`max_photos`, storage), private disk.

**18.3** > `ProcessMediaJob`: generate thumb (400px), medium (1000px), full (2000px) and WebP
> variants. Persist paths to `conversions`. Update `file_size`.

**18.4** > Vue island `GalleryEditor`: drag-drop upload, progress bars, grid with reorder,
> caption editing, cover selection, delete with confirm.

**18.5** > Video: YouTube/Vimeo URL input with embed-ID extraction and thumbnail fetch. Direct
> upload only where the package allows it.

**18.6** > Audio: pick from a curated library seeded in settings, or upload where permitted.

**18.7** > Tests: quota enforced at the boundary; oversized rejected; wrong mime rejected;
> conversions generated.

### Verify
```powershell
php artisan queue:work --once
```
- [ ] Upload 5 photos. Conversions exist on disk. Quota blocks at the limit with an upsell message.

### Commit & PR

Branch: `session/18-builder-media`

```powershell
git add -A && git commit -m "feat(M12): media upload and conversion pipeline"
git push -u origin session/18-builder-media
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 19 — Builder: gifts, story, sections

**Goal:** the remaining content editors plus section ordering.
**Depends on:** Session 18. **Where:** 💻 local.

**19.1** > Vue island `GiftsEditor`: repeatable, type switch (bank / e-wallet / QRIS /
> address), conditional fields per type, QRIS image upload, reorder.

**19.2** > Vue island `StoryEditor`: repeatable timeline entries with date, title,
> description, image, reorder.

**19.3** > Vue island `SectionsManager`: list all sections, drag to reorder, visibility
> toggles, title overrides. Custom sections: add/remove with a rich text body.

**19.4** > "Tema" tab: render form controls dynamically from the template's `config_schema`
> (color pickers, selects, toggles). Save to `theme_config`. **Validate server-side against
> the schema** — reject any key or value not declared there.

**19.5** > "Pengaturan" tab: RSVP on/off, guestbook on/off, moderation mode, music on/off,
> visibility, password.

**19.6** > Tests: `theme_config` rejects out-of-schema values; encrypted cast verified at the
> DB level; section reorder persists.

### Verify
```powershell
php artisan tinker --execute="dd(DB::table('invitation_gifts')->value('account_number'));"
```
- [ ] The raw value is ciphertext, not a readable account number.
- [ ] All builder tabs save and reload correctly.

### Commit & PR

Branch: `session/19-builder-gifts-sections`

```powershell
git add -A && git commit -m "feat(M4.5-M4.12): gifts, story, sections, theme, settings editors"
git push -u origin session/19-builder-gifts-sections
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 20 — Payload resource & caching

**Goal:** one cached JSON payload the public renderer consumes.
**Depends on:** Session 19. **Where:** ☁️ cloud.

**20.1** > Create `App\Http\Resources\InvitationPayload` — the complete public shape:
> invitation meta, resolved theme (`theme_config` merged over `default_config`), ordered
> visible sections, persons, events, story, media with conversion URLs, gifts, template
> identity. Exclude everything private (entitlements, order, owner details).

**20.2** > Create `App\Services\InvitationPayloadService::forSlug(string $slug)` — a single
> eager-loaded query wrapped in a Redis cache tagged `invitation:{id}`, TTL 1h.

**20.3** > Create `app/Observers/InvalidatesInvitationCache.php` and register it on
> `Invitation` and **every** child model. Flush the tag on `saved` and `deleted`.

> ⚠️ Every child model. Miss one and a client edits their venue while guests keep seeing the
> old address. See `docs/05-technical-architecture.md` § 7.

**20.4** > Tests: payload shape snapshot; editing any child busts the cache; a cold cache
> build runs in a bounded number of queries (assert with `DB::listen`).

### Verify
```powershell
php artisan test --filter=Payload
```
- [ ] Cold payload build query count is in single digits.
- [ ] Edit a gift → payload reflects it immediately.

### Commit & PR

Branch: `session/20-payload-caching`

```powershell
git add -A && git commit -m "feat(M4): invitation payload resource and cache invalidation"
git push -u origin session/20-payload-caching
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 21 — Public renderer shell

**Goal:** the Blade shell with correct SSR meta tags. **The WhatsApp preview session.**
**Depends on:** Session 20. **Where:** 💻 local — needs a real phone for the WhatsApp preview check.

**21.1** > Route `GET /{slug}` in `public.php`, registered **last** so it doesn't swallow
> other routes. Add a reserved-slug blocklist.

**21.2** > `Public\InvitationController@show` implementing the status branches from
> `docs/04-flowcharts.md` § 10: draft → 404 unless a valid signed preview token; expired →
> "invitation has ended" page; suspended → notice; published → render.

**21.3** > `resources/views/public/invitation.blade.php`: server-rendered `<head>` with
> `og:title`, `og:description`, `og:image` (absolute URL), `og:type`, `twitter:card`,
> canonical, and the Tailwind public bundle. The Vue app mounts into `<div id="invitation">`
> with the payload as a data attribute.

> This is the highest-leverage detail in the product. WhatsApp does not execute JavaScript —
> if these tags are client-rendered, every shared link looks broken.

**21.4** > Password gate for `visibility = password`: form, session-scoped unlock,
> rate-limited.

**21.5** > Preview routes: `/preview/{template:slug}` using the Session 14.7 demo invitation,
> and a signed `/preview/invitation/{uuid}` for unpublished drafts (M4.15).

**21.6** > `RecordInvitationViewJob` dispatched after response. **Never write synchronously on
> a page view.**

**21.7** > Tests: draft 404s without a token and 200s with one; expired renders the ended page;
> OG tags present in the raw HTML response body.

### Verify
```powershell
curl -s http://undangyu.test/demo-slug | Select-String "og:image"
```
- [ ] OG tags present in the **raw HTML**, before any JS runs.
- [ ] Paste a published URL into WhatsApp Web. **A preview card with the image appears.**
- [ ] Test on a real iOS device and a real Android device.

### Commit & PR

Branch: `session/21-public-renderer`

```powershell
git add -A && git commit -m "feat(M4.16): public renderer shell with SSR meta tags"
git push -u origin session/21-public-renderer
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 22 — First invitation template

**Goal:** one complete, beautiful Vue template.
**Depends on:** Session 21. **Where:** 💻 local — needs Lighthouse and a real Android device.

> Budget more time than you think. This sets the visual bar for every template after it.

**22.1** > Create shared section components under `resources/js/invitation/sections/`:
> `CoverSection`, `PersonsSection`, `EventsSection`, `CountdownSection`, `StorySection`,
> `GallerySection`, `GiftsSection`, `RsvpSection`, `WishesSection`, `ClosingSection`. Each
> takes payload data and theme tokens as props.

**22.2** > Create `resources/js/invitation/templates/floral-elegant/` composing those sections
> with its own styling. Mobile-first at a 375px baseline.

**22.3** > Cover behaviour: full-screen, guest name from the token, "Buka Undangan" button,
> scroll locked until opened, music starts on that interaction — **never autoplay**, browsers
> block it and it's hostile.

**22.4** > Scroll animations via AOS or IntersectionObserver. Gallery via Swiper with
> lazy-loaded WebP `srcset`. No layout shift.

**22.5** > Utility actions: copy-to-clipboard on gift accounts with a toast, "Open in Maps"
> deep link, add-to-calendar generating a `.ics` and a Google Calendar URL.

**22.6** > Template registry: a dynamic-import map from `view_key` to component, so only the
> needed bundle ships.

**22.7** > Seed the template row with a real `config_schema` and `default_config`.

### Verify
```powershell
npm run build
```
- [ ] Lighthouse mobile: performance ≥ 85, LCP < 2.5s.
- [ ] Test on a real mid-range Android over throttled 4G.
- [ ] Change a colour in the Tema tab → the invitation reflects it.

### Commit & PR

Branch: `session/22-first-template`

```powershell
git add -A && git commit -m "feat(M3): first invitation template (floral-elegant)"
git push -u origin session/22-first-template
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 23 — Publish flow

**Goal:** draft → published, with the validation gate and post-publish jobs.
**Depends on:** Session 22. **Where:** 💻 local.

**23.1** > Create `App\Actions\Invitations\PublishInvitation` implementing every check in the
> publish validation gate table (`docs/04-flowcharts.md` § 4). Return structured per-check
> results, not a boolean — the UI needs to say what's missing.

**23.2** > Publish UI: a checklist showing each gate item as met/unmet, each linking to the tab
> that fixes it. Publish button disabled until all pass.

**23.3** > On publish: set `published_at`, compute `expires_at` from the package's
> `active_days`, then dispatch `GenerateOgImageJob` → `WarmInvitationCacheJob` →
> `ScheduleExpiryWarningsJob` → `InvitationPublished` notification.

**23.4** > `GenerateOgImageJob` — composite cover image + names into 1200×630 via Intervention
> Image. Store publicly, persist to `og_image_path`.

**23.5** > Unpublish (back to draft) and admin suspend/reinstate, both audit-logged.

**23.6** > Expiry: `invitations:expire` scheduled command, plus expiry-warning notifications
> at H-7 and H-1.

**23.7** > Tests: each gate item blocks publish independently; publish dispatches all four
> jobs; expiry transitions status; an expired invitation renders the ended page.

### Verify
```powershell
php artisan test --filter=Publish
php artisan queue:work --once
php artisan invitations:expire
```
- [ ] Publish blocked with a clear checklist when incomplete.
- [ ] After publish: OG image exists, WhatsApp preview shows it.

### Commit & PR

Branch: `session/23-publish-flow`

```powershell
git add -A && git commit -m "feat(M4.14,M4.18): publish flow, OG generation, expiry"
git push -u origin session/23-publish-flow
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

### 🔍 Checkpoint — Phase 3 review
Start a fresh context first (`/clear` locally, or a new session from the sidebar in cloud).

> Review Sessions 14–23. Report only:
> 1. Any child model missing the cache-invalidation observer
> 2. Any builder endpoint without a policy check
> 3. N+1 in the payload query
> 4. Any place `theme_config` is trusted without schema validation
> 5. Synchronous DB writes on the public render path

---

## Session 24 — Guest CRUD & groups

**Goal:** guest list management with unique tokens.
**Depends on:** Session 23. **Where:** 🔀 either.

**24.1** > Migrations + models for `guest_groups` and `guests` per § 3.5. Unique index on
> `guests.token`.

**24.2** > `App\Actions\Guests\GenerateGuestToken`: slugified name + 4-char random suffix
> (`budi-santoso-x7f2`). Retry on collision. **Never sequential** — tokens must not be
> enumerable.

**24.3** > Guest CRUD API with quota enforcement against `entitlements.max_guests`, checked in
> the FormRequest.

**24.4** > Vue island `GuestTable`: server-side pagination, search, group filter, inline edit,
> bulk select, bulk delete. Must handle 1000+ rows.

**24.5** > Group management: create, colour-pick, assign, filter.

**24.6** > Tests: token uniqueness under concurrency; quota blocks at the boundary;
> cross-tenant 403.

### Verify
```powershell
php artisan test --filter=Guest
```
- [ ] Seed 1000 guests; the table paginates without lag.

### Commit & PR

Branch: `session/24-guests`

```powershell
git add -A && git commit -m "feat(M5.1,M5.2,M5.4): guest management and groups"
git push -u origin session/24-guests
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 25 — Guest import

**Goal:** queued spreadsheet import with a per-row error report.
**Depends on:** Session 24. **Where:** ☁️ cloud.

**25.1** > Migration + model for `guest_imports` per § 3.5.

**25.2** > Downloadable CSV/XLSX template with headers and one example row.

**25.3** > Upload endpoint storing the file and dispatching `ProcessGuestImportJob`.

**25.4** > The job, per `docs/04-flowcharts.md` § 6: chunk 100 rows at a time; per-row
> validation (name required, phone normalised to E.164, duplicate detection within the
> invitation); **partial success** — 488 of 500 import, 12 errors reported. Never
> all-or-nothing. Stop cleanly on quota exhaustion.

**25.5** > Progress UI polling import status; downloadable error report naming row numbers and
> reasons.

**25.6** > Guest export to XLSX.

**25.7** > Tests: a 500-row file with 12 bad rows imports 488 and reports 12; quota exhaustion
> stops cleanly; a malformed file fails without leaking an exception.

### Verify
```powershell
php artisan test --filter=Import
php artisan queue:work --once
```
- [ ] Import a real 500-row file. Check timing and the error report.

### Commit & PR

Branch: `session/25-guest-import`

```powershell
git add -A && git commit -m "feat(M5.3,M5.9): guest import and export"
git push -u origin session/25-guest-import
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 26 — Guest links & WhatsApp share

**Goal:** the actual distribution workflow clients use.
**Depends on:** Session 25. **Where:** 💻 local.

**26.1** > Migration + model for `message_templates` per § 3.6. Seed default Indonesian
> invitation and reminder texts with `{guest_name}`, `{invitation_url}`, `{event_date}`,
> `{couple_names}`.

**26.2** > Message template editor with a variable picker and live preview against a real guest.

**26.3** > Per-guest link generation: `{invitation_url}?to={token}`, with a copy button.

**26.4** > Per-guest `wa.me/{phone}?text={urlencoded}` deep link using the resolved template.

**26.5** > Bulk workflow: select guests → "Copy all messages" and "Open WhatsApp for each"
> with a tracked position, so a client can work through 400 guests without losing their place.
> Mark `sent_at` as they go.

**26.6** > Tests: variable resolution; URL encoding; phone normalisation.

### Verify
- [ ] Copy a guest's WhatsApp link, open it on your phone, confirm the prefilled text and working link.

### Commit & PR

Branch: `session/26-guest-links`

```powershell
git add -A && git commit -m "feat(M5.5-M5.7): guest links and WhatsApp share workflow"
git push -u origin session/26-guest-links
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 27 — Personalization on the public page

**Goal:** guests see their own name.
**Depends on:** Session 26. **Where:** 💻 local.

**27.1** > Resolve `?to={token}` in `Public\InvitationController`. An invalid token renders the
> generic greeting, **never an error** — a guest seeing an error page is a lost RSVP.

**27.2** > Pass the resolved guest into the payload; render "Kepada Yth. {title} {name}" on the
> cover.

**27.3** > `RecordGuestOpenJob` — set `opened_at` on first open, increment `open_count`.
> Queued, rate-limited per token.

**27.4** > "Non-openers" filter in the guest table, for follow-up.

**27.5** > Tests: valid token personalises; invalid token falls back gracefully; open tracking
> increments once per session, not per scroll.

### Verify
- [ ] Open a personalized link → your name appears, `opened_at` populates.
- [ ] Open with a garbage token → generic greeting, HTTP 200.

### Commit & PR

Branch: `session/27-personalization`

```powershell
git add -A && git commit -m "feat(M5.5,M5.11): guest personalization and open tracking"
git push -u origin session/27-personalization
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 28 — RSVP

**Goal:** attendance collection, with and without tokens.
**Depends on:** Session 27. **Where:** 🔀 either.

**28.1** > Migration + model for `rsvps` per § 3.5. `ip_hash`, never raw IP.

**28.2** > Public RSVP endpoint: name prefilled from the token, attendance enum, pax validated
> against `max_pax`, session selection where multiple exist. **Rate-limited per IP per
> invitation.** Handles anonymous submissions (M6.9).

**28.3** > RSVP form component in the invitation template with a success state, and edit-own-
> response via the same token.

**28.4** > Client RSVP dashboard: totals by attendance, total pax, breakdown by session and
> group, searchable list, export.

**28.5** > `NewRsvpNotification` to the client, **throttled** — 400 individual notifications is
> hostile.

**28.6** > Tests: over-`max_pax` rejected; anonymous accepted; rate limit fires; deleting a
> guest nullifies rather than deletes their RSVP.

### Verify
```powershell
php artisan test --filter=Rsvp
```
- [ ] Submit an RSVP from a phone. Counts update on the dashboard.

### Commit & PR

Branch: `session/28-rsvp`

```powershell
git add -A && git commit -m "feat(M6.1-M6.3,M6.7,M6.9): RSVP collection and dashboard"
git push -u origin session/28-rsvp
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## Session 29 — Wishes & moderation

**Goal:** the guestbook, safely.
**Depends on:** Session 28. **Where:** ☁️ cloud.

**29.1** > Migration + model for `wishes` per § 3.5.

**29.2** > Public submission endpoint: rate-limited, honeypot field, max length, optional
> profanity filter. Status set by the invitation's moderation setting.

**29.3** > Wishes feed component: paginated, newest first, pinned first, cached 60s.
> **Escaped output — never `v-html`.**

**29.4** > Client moderation queue: approve, reject, pin, delete, bulk actions.

**29.5** > Tests: an XSS payload renders escaped; rate limit fires; moderation mode respected;
> export works.

### Verify
```powershell
php artisan test --filter=Wish
```
- [ ] Submit `<script>alert(1)</script>` as a wish. It renders as literal text, no execution.

### Commit & PR

Branch: `session/29-wishes`

```powershell
git add -A && git commit -m "feat(M6.4-M6.6,M6.8): wishes and moderation"
git push -u origin session/29-wishes
gh pr create --fill --base main
```

Then [close out the session](#close-out-a-session): review the diff, merge, sync `main`.

---

## 🚩 MVP CUT LINE

Run the pre-launch checklist in
[07-roadmap.md](07-roadmap.md#-mvp-cut-line--launch-here). The essentials:

- [ ] 5–8 polished templates (repeat Session 22 per template)
- [ ] Load test: 500 concurrent on one published invitation
- [ ] WhatsApp preview verified on real iOS and Android
- [ ] Real payment, real money, small amount, end to end
- [ ] Backup taken **and restored** into a scratch database
- [ ] Privacy policy and terms published
- [ ] Sentry alerting to your phone
- [ ] 3–5 real invitations run for friends before charging strangers

---

## Sessions 30–34 — Operations

Same protocol. Condensed, because the patterns are established by now.

### Session 30 — Analytics
1. Migrations for `invitation_views` and `invitation_stats_daily` (§ 3.7)
2. `RecordInvitationViewJob` writing raw rows with hashed IPs
3. `analytics:rollup` daily command aggregating into the stats table
4. `analytics:prune` removing raw views older than 90 days
5. Client analytics tab: views over time, RSVP funnel, device breakdown — **querying only the rollup table**
6. Tests: rollup accuracy, prune boundary

**Verify:** charts render from the rollup; the UI never queries the raw table.
**Commit & PR:** branch `session/30-analytics`, message `feat(M9): analytics tracking and rollups`, then [close out](#close-out-a-session).

### Session 31 — Admin dashboard
1. Revenue widgets: today, MTD, by package, by template
2. Order queue with filters and manual-verification shortcuts
3. User management: search, suspend, role assignment
4. Impersonation — audit-logged, banner'd, time-limited
5. Invitation management: search, suspend, extend expiry
6. Activity log viewer
7. Horizon dashboard, admin-gated

**Verify:** impersonate a client; confirm the banner and the audit entry.
**Commit & PR:** branch `session/31-admin-dashboard`, message `feat(M11): admin dashboard and management tools`, then [close out](#close-out-a-session).

### Session 32 — Notifications
1. Transactional emails: order created, payment received, invitation published, expiry H-7/H-1
2. In-app notification centre
3. Per-user notification preferences
4. Branded email templates driven from settings

**Verify:** trigger each email in Mailpit; check mobile rendering.
**Commit & PR:** branch `session/32-notifications`, message `feat(M8.1,M8.6,M8.7): notifications`, then [close out](#close-out-a-session).

### Session 33 — Scheduled tasks
1. Consolidate the schedule: `orders:expire` hourly · `invitations:expire` daily ·
   `analytics:rollup` daily · `analytics:prune` weekly · `payments:reconcile` daily ·
   `media:cleanup-orphans` weekly · `guests:purge-expired` daily · `backup:run` daily
2. Failure notifications on every scheduled command
3. Health check endpoint
4. Configure `spatie/laravel-backup` to off-server storage

**Verify:** `php artisan schedule:list`; run each command manually; **restore a backup**.
**Commit & PR:** branch `session/33-scheduled-tasks`, message `feat(M13.2,M13.6): scheduled tasks and backups`, then [close out](#close-out-a-session).

### Session 34 — Hardening & load test
1. Rate limits on every public endpoint (view, RSVP, wishes, token lookup)
2. Security headers: CSP, HSTS, X-Frame-Options, Referrer-Policy
3. Load test 500 concurrent against one published invitation (k6 or Artillery)
4. Fix what breaks — usually cache misses and N+1 in the payload
5. Cloudflare edge caching for non-personalized invitation views
6. Sentry performance monitoring with alerting configured
7. `guests:purge-expired` implementing the retention policy (M13.10)

**Verify:** load test passes with p95 under 500ms; security headers present.
**Commit & PR:** branch `session/34-hardening`, message `chore: security hardening and performance tuning`, then [close out](#close-out-a-session).

---

## Sessions 35–41 — Growth

Build in **this order** — distribution before features. Same branch-and-PR workflow: `session/NN-slug`, one PR each, merged before the next starts.

| # | Session | Key steps | Branch / commit prefix |
|---|---|---|---|
| 35 | QR generation | QR per guest token; downloadable sheet; embed in the guest's invitation view | `feat(M7.1)` |
| 36 | Usher scanner | `checkins` table; scoped token access (no login); html5-qrcode scanner; **manual name-search fallback**; actual-pax entry; souvenir tracking; live counter | `feat(M7.2-M7.6,M7.8)` |
| 37 | WhatsApp blast | Provider interface + driver; `message_logs`; job batch with **rate limiting**; quota metering; progress UI; delivery webhooks; scheduled reminders. Read Meta's business messaging policy first — see the M8.3 warning | `feat(M8.3-M8.5)` |
| 38 | Affiliate program | `affiliates`, `affiliate_referrals`, **append-only `affiliate_ledger`**, `withdrawals`; referral attribution; 14-day hold before approval; dashboard; withdrawal approval. Balance = sum of ledger rows, never a mutable column | `feat(M10)` |
| 39 | Coupons | `coupons`, `coupon_redemptions`; discount engine with unit tests for percent/fixed/max/min; admin CRUD; checkout integration | `feat(M2.12)` |
| 40 | Custom domains | Subdomain and custom-domain routing; DNS verification flow; SSL provisioning; entitlement-gated | `feat(M13.9)` |
| 41 | Support tickets | `support_tickets`, `ticket_replies`; client submission; admin queue; internal notes; email notifications | `feat(M11.10)` |

---

## Where am I?

Lost track? Run these:

```powershell
git checkout main && git pull
git log --oneline -15        # one squashed commit per completed session
gh pr list --state open      # anything still unmerged
git branch -a                # stale session branches
```

With squash-merges, `main`'s log reads as a list of completed sessions. Then:

1. Find the last session in that log. Mark it ✅ in the [progress tracker](#progress-tracker)
   and record its PR number.
2. Read its **Done when**. Is it actually true? If not, close the gap before moving on.
3. If `gh pr list` shows an open PR, that session is mid-flight. Finish or close it before
   starting anything new — see [merge promptly](#merge-promptly--dont-stack-prs).
4. Move to the next ⬜ session and follow the [session protocol](#session-protocol).

**An open PR you don't remember:**
```powershell
gh pr diff <number>          # what's in it
gh pr checks <number>        # did CI pass
```
If the diff matches a session's Done-when and checks pass, merge it. If it's half-finished,
close it and delete the branch — redoing one session is cheaper than reconstructing intent.

**Working tree dirty on a session branch:**
```powershell
git diff --stat
git stash
php artisan test             # is the committed state healthy?
```
If tests pass on the committed state, `git stash drop` and redo the session cleanly.

**A session went badly:**
```powershell
git checkout main && git pull
git branch -D session/NN-slug
git push origin --delete session/NN-slug
```
You lose one session, and `main` was never touched. Start a fresh context, re-read the
session, begin again with a tighter prep prompt.

**Stale branches piling up:** `--delete-branch` on merge handles the common case. To clean up
what's left: `git fetch --prune` then delete anything in `git branch` that isn't in
`gh pr list --state merged`.

---

## Working practices

**One branch, one PR, one merge per session.** No exceptions — it's what makes the tracker
meaningful and keeps `main` readable as a build log. Merge before starting the next session.

**Run the code.** After every slice. Claude Code writes plausible Laravel; plausible is not
working. The gap shows at runtime, not in review.

**Fresh context between sessions.** `/clear` locally; in cloud, start a new session from the
sidebar. Long contexts drift and start referencing decisions that no longer apply.

**Keep `CLAUDE.md` current.** Decided on Xendit mid-build? Add it immediately. That file is
what makes Session 20 start correctly instead of re-litigating Session 9.

**Decline scope creep in-session.** If Claude offers to also build three adjacent features,
say no. Scope creep inside a session is how slices stop being verifiable.

**Don't let it invent schema.** If a column isn't in `docs/03-database-erd.md`, update the doc
first. Drift between doc and DB makes the doc worthless within two weeks.

---

## Common failure modes

| Symptom | Cause | Fix |
|---|---|---|
| Code references non-existent models | Working ahead of schema | Schema-first, one module per session |
| AdminLTE JS dead after Vue mounts | Vue replaced DOM AdminLTE initialised | Mount only into dedicated containers (Session 3.5) |
| Bootstrap and Tailwind fighting | Both bundles on one page | Separate Vite inputs (Session 3.1) |
| Invitation shows stale data | Missing cache observer | Session 20.3 — every child model |
| Slow invitation page | N+1 in payload | `preventLazyLoading` (Session 1.5) |
| Duplicate invitations from one payment | Non-idempotent webhook | Session 10 tests, especially #3 |
| Guest import times out | Synchronous processing | Queue it (Session 25.4) |
| WhatsApp preview blank | Client-rendered `<head>` | Session 21.3 — SSR the meta tags |
| Tests pass, browser broken | Never clicked through it | Every Verify block has manual checks. Do them |
