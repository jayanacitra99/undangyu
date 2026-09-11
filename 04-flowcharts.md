# 04 — Flowcharts & State Machines

All diagrams are Mermaid. They render in GitHub, VS Code (with the Mermaid extension), and
most Markdown viewers.

---

## 1. System architecture

```mermaid
graph TB
    subgraph Clients
        B1[Client browser<br/>dashboard]
        B2[Guest phone<br/>public invitation]
        B3[Usher phone<br/>QR scanner]
        B4[Admin browser]
    end

    subgraph Edge
        CF[Cloudflare<br/>CDN + WAF + cache]
    end

    subgraph App["Laravel application"]
        WEB[Web routes<br/>Blade + AdminLTE]
        PUB[Public renderer<br/>SSR head + Vue app]
        API[Internal API<br/>Sanctum]
        Q[Queue workers<br/>Horizon]
        SCH[Scheduler]
    end

    subgraph Data
        DB[(MySQL 8)]
        RD[(Redis<br/>cache + queue + session)]
        S3[(S3 / R2<br/>media)]
    end

    subgraph External
        PG[Payment gateway<br/>Midtrans / Xendit]
        WA[WhatsApp provider]
        MAIL[Mail service]
        SEN[Sentry]
    end

    B1 --> CF --> WEB
    B2 --> CF --> PUB
    B3 --> CF --> API
    B4 --> CF --> WEB
    WEB --> API
    PUB --> API
    API --> DB
    API --> RD
    API --> S3
    Q --> DB
    Q --> WA
    Q --> MAIL
    SCH --> Q
    PG -->|webhook| API
    App --> SEN
```

---

## 2. Client journey — purchase to published

```mermaid
flowchart TD
    A[Land on site] --> B[Browse template gallery]
    B --> C{Preview a template}
    C --> B
    C --> D[Choose package]
    D --> E{Logged in?}
    E -->|No| F[Register / login]
    F --> G[Create order]
    E -->|Yes| G
    G --> H{Payment method}
    H -->|Gateway| I[Redirect to Midtrans/Xendit]
    H -->|Manual transfer| J[Show bank details<br/>upload proof]
    I --> K[Gateway webhook]
    J --> L[Admin verifies]
    K --> M{Payment settled?}
    L --> M
    M -->|No| N[Order expires after 24h]
    M -->|Yes| O[Queue: ProvisionInvitation]
    O --> P[Invitation created as DRAFT<br/>entitlements snapshotted]
    P --> Q[Builder: fill sections]
    Q --> R[Live preview]
    R --> S{Happy?}
    S -->|No| Q
    S -->|Yes| T{Validation passes?}
    T -->|No| U[Show missing required fields]
    U --> Q
    T -->|Yes| V[PUBLISH]
    V --> W[Generate OG image<br/>warm cache<br/>set expires_at]
    W --> X[Add guest list]
    X --> Y[Generate per-guest links]
    Y --> Z[Blast via WhatsApp]
    Z --> AA[Monitor RSVP dashboard]
```

---

## 3. Guest journey

```mermaid
flowchart TD
    A[Receives WhatsApp message<br/>with link + preview card] --> B[Taps link]
    B --> C{Token in ?to= ?}
    C -->|Yes| D[Resolve guest<br/>record open event]
    C -->|No| E[Generic greeting]
    D --> F[Cover screen<br/>Kepada Yth. Bapak Budi]
    E --> F
    F --> G[Tap 'Buka Undangan']
    G --> H[Music starts<br/>scroll unlocked]
    H --> I[Browse sections:<br/>couple, date, venue, story, gallery]
    I --> J{Action?}
    J -->|Open Maps| K[Deep link to Google Maps]
    J -->|Add to calendar| L[Generate .ics / Google Calendar link]
    J -->|Send gift| M[Copy account number<br/>show QRIS]
    J -->|RSVP| N[Submit attendance + pax]
    J -->|Leave wish| O[Submit message]
    N --> P{Moderation on?}
    O --> P
    P -->|Auto-approve| Q[Appears in live feed]
    P -->|Hold| R[Queued for client review]
    N --> S[Notify client<br/>update RSVP counters]
    K & L & M & Q & R & S --> T[Guest shares link onward]
```

---

## 4. Invitation lifecycle state machine

```mermaid
stateDiagram-v2
    [*] --> draft: order provisioned
    draft --> draft: edit sections
    draft --> published: publish (validation passes)
    published --> draft: unpublish
    published --> published: edit (live changes)
    published --> expired: expires_at reached
    expired --> published: renewal purchased
    published --> suspended: admin action / ToS breach
    suspended --> published: admin reinstates
    expired --> [*]: purged after retention
    suspended --> [*]: deleted
```

**Publish validation gate (must all pass):**

| Check | Rule |
|---|---|
| Slug | Set, unique, not in blocklist |
| Persons | At least one, matching the event type's required roles |
| Event session | At least one with `start_at` and `venue_name` |
| Cover | Cover image set |
| Template | Published, and within the invitation's package tier |
| Quotas | Media count and storage within entitlements |
| Order | Status is `paid` or `provisioned` |

**On publish, queue these jobs:** generate OG image → warm public cache → schedule expiry
warnings (H-7, H-1) → send "your invitation is live" notification.

---

## 5. Payment & webhook sequence

```mermaid
sequenceDiagram
    participant C as Client
    participant A as Laravel
    participant G as Gateway
    participant Q as Queue
    participant D as MySQL

    C->>A: POST /checkout
    A->>D: create order (pending) + payment (pending)
    A->>G: create transaction
    G-->>A: snap token / invoice URL
    A-->>C: redirect to payment page
    C->>G: completes payment
    G->>A: POST /webhook/{gateway}
    A->>A: verify signature
    A->>D: SELECT payment WHERE gateway_ref = ? FOR UPDATE
    alt already settled
        A-->>G: 200 OK (idempotent no-op)
    else new settlement
        A->>D: payment → settled, order → paid
        A->>Q: dispatch ProvisionInvitationJob
        A-->>G: 200 OK
        Q->>D: create invitation, snapshot entitlements
        Q->>C: email + in-app notification
    end
```

**Rules:**
- Verify the signature before touching the database. Always.
- Return `200` even for duplicate deliveries, or the gateway will retry forever.
- Never provision inline in the webhook handler — gateways time out at ~5–10s.
- Log the raw payload to `payments.raw_payload` before processing, so a dispute is arguable.
- Reconcile daily against the gateway's settlement report; webhooks do get lost.

---

## 6. Guest import flow

```mermaid
flowchart TD
    A[Download CSV template] --> B[Upload filled file]
    B --> C[Store file, create guest_imports row]
    C --> D[Queue: ProcessGuestImport]
    D --> E[Parse rows]
    E --> F{Row valid?}
    F -->|Name empty| G[Record error: missing name]
    F -->|Phone malformed| H[Normalise to E.164<br/>or record error]
    F -->|Duplicate name in invitation| I[Record error: duplicate]
    F -->|Valid| J{Quota remaining?}
    J -->|No| K[Stop, record quota error]
    J -->|Yes| L[Generate unique token]
    L --> M[Insert guest]
    G & H & I --> N[Append to errors JSON]
    M --> O{More rows?}
    N --> O
    O -->|Yes| E
    O -->|No| P[Update import: counts + status]
    P --> Q[Notify client]
    Q --> R[Client downloads error report]
```

**Batch insert.** 500 individual `INSERT`s is slow enough that clients will refresh and
re-upload. Chunk into batches of 100.

---

## 7. WhatsApp blast flow

```mermaid
flowchart TD
    A[Select recipients:<br/>all / group / non-openers] --> B[Pick message template]
    B --> C[Preview with variables resolved]
    C --> D{Quota available?}
    D -->|No| E[Show upsell: buy credits]
    D -->|Yes| F[Create job batch]
    F --> G[Queue per-recipient jobs<br/>rate-limited]
    G --> H[Send via provider API]
    H --> I{Response}
    I -->|Success| J[message_logs: sent + provider_ref]
    I -->|Failure| K[message_logs: failed + error]
    K --> L{Retryable?}
    L -->|Yes| M[Backoff, retry max 3]
    M --> H
    L -->|No| N[Mark permanently failed]
    J --> O[Decrement quota]
    O --> P[Provider delivery webhook]
    P --> Q[Update status: delivered / read]
    J & N --> R[Batch progress UI]
```

**Rate limiting is mandatory.** Blasting 400 messages at full speed is the fastest way to get
a number flagged. Throttle to a conservative per-minute rate and spread the batch.

---

## 8. QR check-in flow

```mermaid
flowchart TD
    A[Usher opens scanner via scoped token link] --> B{Camera available?}
    B -->|No| C[Manual name search]
    B -->|Yes| D[Scan QR]
    D --> E[Extract guest token]
    E --> F{Token valid for this invitation?}
    F -->|No| G[Show error: invalid code]
    F -->|Yes| H{Already checked in?}
    H -->|Yes| I[Warn: already arrived at HH:MM<br/>allow override]
    H -->|No| J[Show guest card:<br/>name, group, RSVP pax, table, VIP]
    C --> J
    J --> K[Usher confirms actual pax]
    K --> L[Write checkin row]
    L --> M{Souvenir configured?}
    M -->|Yes| N[Mark souvenir given]
    M -->|No| O[Done]
    N --> O
    O --> P[Update live attendance counter]
    P --> Q[Ready for next scan]
```

---

## 9. Affiliate commission flow

```mermaid
flowchart TD
    A[Affiliate shares link with ?ref=CODE] --> B[Visitor lands, cookie set 30d]
    B --> C[Visitor registers]
    C --> D[users.referred_by set]
    D --> E[Visitor places order]
    E --> F{Order paid?}
    F -->|No| G[No commission]
    F -->|Yes| H[Create affiliate_referrals: pending]
    H --> I[Hold for refund window: 14 days]
    I --> J{Refunded within window?}
    J -->|Yes| K[Referral → void]
    J -->|No| L[Referral → approved]
    L --> M[Ledger: CREDIT commission_amount]
    M --> N[Affiliate requests withdrawal]
    N --> O{Balance >= minimum?}
    O -->|No| P[Reject: below threshold]
    O -->|Yes| Q[Withdrawal: requested]
    Q --> R[Admin reviews]
    R --> S{Approve?}
    S -->|No| T[Rejected with note]
    S -->|Yes| U[Manual/API transfer]
    U --> V[Ledger: DEBIT amount]
    V --> W[Withdrawal: paid]
```

**The hold window matters.** Paying commission immediately on a paid order means refunds
create negative balances and awkward clawbacks. Hold until the refund window closes.

---

## 10. Public invitation request path (performance-critical)

```mermaid
flowchart TD
    A[GET /:slug?to=token] --> B{Cloudflare edge cache hit?}
    B -->|Yes, no token| C[Serve cached HTML]
    B -->|No / has token| D[Laravel]
    D --> E{Redis: invitation payload cached?}
    E -->|Yes| F[Hydrate from cache]
    E -->|No| G[Query invitation + eager-load<br/>sections, persons, events, media, gifts]
    G --> H[Cache payload, TTL 1h,<br/>tagged for invalidation]
    H --> F
    F --> I{Status check}
    I -->|draft| J[404 unless valid preview signature]
    I -->|expired| K[Render 'invitation has ended' page]
    I -->|suspended| L[Render suspended notice]
    I -->|published| M{Password protected?}
    M -->|Yes, no session| N[Password gate]
    M -->|No / authorised| O[Resolve guest token if present]
    O --> P[Render SSR head:<br/>OG tags, title, preview image]
    P --> Q[Ship Vue app + template bundle]
    Q --> R[Dispatch async: RecordView job]
    R --> S[Lazy-load gallery below fold]
```

**Cache invalidation:** bust the invitation's cache tag on any write to the invitation or any
of its child tables. Use a model observer on each child so nobody has to remember.

---

## 11. Role & permission matrix

```mermaid
graph LR
    SA[super-admin] --> ALL[Everything + settings + roles]
    AD[admin] --> A1[Orders, users, templates,<br/>packages, coupons, invitations]
    SU[support] --> S1[View orders/users,<br/>reply tickets, impersonate]
    CL[client] --> C1[Own invitations,<br/>guests, RSVPs, blasts]
    RS[reseller] --> R1[client perms +<br/>credits, customer invitations,<br/>commission dashboard]
    US[usher] --> U1[Check-in scanner for<br/>one assigned invitation only]
```

| Permission group | super-admin | admin | support | client | reseller | usher |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Manage settings & roles | ✅ | — | — | — | — | — |
| Manage templates/packages | ✅ | ✅ | — | — | — | — |
| Manage all orders | ✅ | ✅ | 👁 | — | — | — |
| Verify manual payments | ✅ | ✅ | — | — | — | — |
| Manage all users | ✅ | ✅ | 👁 | — | — | — |
| Impersonate client | ✅ | ✅ | ✅ | — | — | — |
| Own invitations CRUD | ✅ | ✅ | — | ✅ | ✅ | — |
| Guest list CRUD | ✅ | ✅ | — | ✅ | ✅ | — |
| Send blasts | ✅ | ✅ | — | ✅ | ✅ | — |
| Check-in scanner | ✅ | ✅ | — | ✅ | ✅ | ✅ |
| Affiliate dashboard | ✅ | 👁 | — | — | ✅ | — |
| Approve withdrawals | ✅ | ✅ | — | — | — | — |

👁 = read-only
