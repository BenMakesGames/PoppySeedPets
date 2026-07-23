# The .NET Rewrite — Current Architecture & Migration Gotchas

> **Status:** Discovery pass #1. This document catalogues **how the current Symfony/PHP
> API works today**, with a hard focus on load-bearing patterns and the places where
> .NET / EF Core has no clean equivalent.
>
> **What this document is NOT:** it does not decide what we *do* about each item in C#.
> That is deliberately a separate step. Where a decision is implied, it is called out as
> an **open decision**, not a recommendation.
>
> Scope: `api/` (Symfony 7.3 / PHP 8.4 / Doctrine ORM / MySQL / Redis). Every claim below
> was gathered from the current tree; file references are `path:line` against `api/src/`
> unless noted. Verified facts are marked ✅; things worth re-checking during
> implementation are marked ⚠️.

---

## 0. How to read this

The rewrite cost is **not** evenly distributed. Ranked roughly by "how much does this
bleed into the port":

1. **The pet-activity system** — ~37k lines of intentionally imperative game logic. The
   bulk of the *volume*.
2. **Serialization / response construction** — the wire contract is scattered across 60+
   serialization groups with no manifest, and "serialize" is really a side-effecting
   request-finalization step.
3. **Auth** — bespoke DB-token scheme; must stay byte-compatible or every logged-in client
   is kicked at cutover.
4. **Persistence infrastructure** — no L2 cache in EF, Redis-based locking, mixed INT/ULID
   PKs, a PHP-`serialize()` column type.
5. **Request-gating pipeline** — four kernel-event mechanisms, custom HTTP status codes,
   one confirmed bug.
6. **The long tail** — cron heartbeat, a homegrown JSON logic evaluator, Patreon, a
   lunisolar calendar. Plus a lot of *near-dead* dependencies that must not be over-scoped.

---

## 1. System at a glance

| Dimension | Count | Source |
|---|---:|---|
| Controller classes | **417** (~590 route methods, ~1.4/class) | `src/Controller/` |
| HTTP verb mix | 449 POST · 112 GET · 21 PATCH · 5 DELETE · 1 PUT | route attributes |
| Entities | **94** | `src/Entity/` |
| Doctrine migrations | **665** (foldered `YYYY/MM/`) | `migrations/` |
| `#[Groups(...)]` attribute lines | **508** across 74 files | entities + models |
| Serialization groups | **60** enum consts + **26** string literals | `SerializationGroupEnum` |
| Response-DTO classes | **0** (DTO == inline `array<string,mixed>`) | — |
| `PetActivity/` | **78 files / ~37,400 lines / 369 outcome methods** | `src/Service/PetActivity/` |
| `IRandom` calls in `PetActivity/` | **~2,510** | — |
| Console commands | **36** (6 cron-driven) | `src/Command/` |
| Seeded reference data | ~1,399 items · ~530 foods · ~149 enchantments · ~111 species · ~63 merits | `db/seed/base.sql` |
| Entity managers | **2** (primary + readonly replica) | `config/packages/doctrine.yaml` |

**The core split that shapes everything:** *reference data* is richly data-driven (the
~1,400 items etc., cached in Redis), but *behavior* — activities, crafting, story logic —
is intentionally **imperative code, not config rows**. There are **no** recipe/craft rows
in the seed at all.

---

## 2. Subsystem map — how it works today

### 2.1 HTTP pipeline, routing & request binding

- **One endpoint per controller class**, organized by vertical slice (game feature), not
  technical layer. Routes are pure PHP-8 attributes (`#[Route]` class prefix + method
  route); auto-registered, no route table. A long tail of item-interaction controllers
  carry several sub-actions (up to 7 in `Item/Pinata/SeekingClaymoreController.php`).
- **Request DTOs are barely adopted:** `#[MapRequestPayload]` in only **5** controllers,
  `#[MapQueryString]` in **0**. **153** controllers read `$request->request->get(...)`
  directly. This works only because `ControllerActionSubscriber::convertJsonStringToArray()`
  (`EventSubscriber/ControllerActionSubscriber.php:96-109`) decodes the JSON body into the
  request "bag" so JSON POST fields read like form params. ⚠️ ASP.NET Core has no such
  auto-injection — either replicate via middleware or convert all 153 to bound DTOs.

### 2.2 Authentication & authorization

- **Opaque 40-char session token** (`[A-Za-z0-9]`, `random_int`, DB-unique), stored in the
  `user_session` table. **No JWT, no claims, no server session store** — the token is a
  random DB key. `src/Security/SessionAuthenticator.php`, `src/Service/SessionService.php`,
  `src/Entity/UserSession.php`.
- **Dual transport:** cookie `sessionId` (must be exactly 40 chars — load-bearing check in
  `supports()`) **or** `Authorization: Bearer <token>`; cookie wins.
- **DB lookup every request**, then **slides `sessionExpiration`** + stamps `lastActivity`
  + `flush()` — i.e. every authenticated request writes to the DB. Server-side expiry is
  **separate** from the 7-day cookie TTL.
- **Cookie is emitted as a side effect of `ResponseService`**, not the login endpoint
  (`ResponseService::success()` sets the cookie on every response). Domain hard-coded per
  env (`localhost` dev / `poppyseedpets.com` prod, Secure+HttpOnly).
- **Passwords: argon2i** (`security.yaml`).
- **Authorization is coarse:** `#[IsGranted('IS_AUTHENTICATED_FULLY')]` ×553, `ROLE_ADMIN`
  ×2, **zero custom Voters**. Real feature-gating is game-progression "unlocks" checked
  *in-controller* (`$user->hasUnlockedFeature(...)` → `PSPNotUnlockedException` → 403).
  Admin actions are additionally gated by **`ADMIN_IP_REGEX`** (an IP allowlist), not roles.
- **`UserAccessor`** (used in **411** files) returns the live, mutable Doctrine `User`
  entity as "current user" — downstream code persists through it. Not a claims principal.

### 2.3 Serialization & response construction

- **`ResponseService` is the universal envelope** (`itemActionSuccess()` called ~366×,
  `success()` ~289×). Shape:
  `{ success, data?, activity?, user?, reloadInventory?, reloadPets? }`. ⚠️ It is **not a
  pure serializer** — building a response also:
  - sets the `sessionId` **cookie**;
  - fetches unread pet-activity logs as `FlashMessage`s **and DELETEs those rows via DQL +
    flush** (a destructive write during response building; comment: *"fewer serialization
    deadlocks"*);
  - injects current-user + computed menu (extra DB traversal);
  - double-passes: inner `normalize()` per group, then outer `serialize()` with the
    top-level groups.
- **Serialization groups are the de-facto read schema and there is no manifest.** 60 enum
  names + 26 string literals, unioned per-request; a single `Pet` property can belong to
  **11** groups. `SerializationGroupEnum` is `@deprecated`. Each group name corresponds to
  a hand-written Angular TS model — the frontend models are the closest thing to a spec.
- **Five custom normalizers do DB I/O and branch on group identity** (`src/Serializer/`):
  `UserNormalizer` and `PetSpeciesNormalizer` fire `COUNT`/lookup queries *during
  normalization*; three normalizers run `CommentFormatter::format()`, a serialize-time
  templating engine that resolves `%pet:123.name%` / `%user:456.Name%` placeholders via DB
  lookups with English-grammar rules.
- **"Explicit response DTO" today == inline associative array.** There are **zero** DTO
  classes; the migration target is hand-built arrays passed to `success()` without a group.
  So the port must *invent* the DTO types the frontend already implicitly depends on.
- **Pagination:** `Model/FilterResults.php` (`page/pageCount/pageSize/resultCount/`
  `unfilteredTotal/results`), serialized by combining `FILTER_RESULTS` + the row group.
  Constructed by hand at each call site (page size `20` is a repeated magic literal).

### 2.4 Request-gating pipeline (kernel events)

Four mechanisms, **all via Symfony kernel events** (no middleware equivalent in the app):

| Subscriber | Event(s) | Role |
|---|---|---|
| `MaintenanceModeSubscriber` | REQUEST (prio 249) | short-circuit JSON when `APP_MAINTENANCE` |
| `ControllerActionSubscriber` | CONTROLLER, RESPONSE | blocking rate-limit · house-hours gate · JSON-body decode · vanity header |
| `OneNonIdempotentRequestPerUserSubscriber` | CONTROLLER, TERMINATE, EXCEPTION | per-user single-flight cache lock |
| `ExceptionEventSubscriber` | EXCEPTION | central exception → JSON + status mapping |

- **Blocking token-bucket throttle** (`ControllerActionSubscriber:57-68`): per-user
  `reserve(1,15)->wait()` — **blocks the worker up to 15s to queue** rather than rejecting.
  Config `psp_default`: token_bucket, limit 8, refill 3 / 2s (`rate_limiter.yaml`).
- **"House hours" gate** (`ControllerActionSubscriber:70-94` + `HouseService`): on every
  controller, if the user has unsimulated pet time and no run-lock, throws
  `PSPHoursMustBeRun` → **HTTP 470**, forcing the client to `POST /house/runHours` first.
  Opt out per-endpoint with `#[DoesNotRequireHouseHours]` (**40** controllers do). The
  attribute is the **only** custom attribute in `src/Attributes/`.
- **Exception → JSON** envelope `{ success:false, errors:[...] }` with a custom status map
  (see gotcha G9).

### 2.5 Persistence (Doctrine)

- **Two entity managers** over two connections: `default` (RW) and `readonly` (replica,
  `READONLY_DATABASE_URL`), chosen explicitly per query (~12 direct `'readonly'` sites +
  the raw-SQL helper).
- **No repository classes, no inheritance, no embeddables.** The `*Repository.php` files in
  `src/Functions/` are **static-function helpers**, not `EntityRepository` subclasses.
- **Query styles:** magic finders (`find/findBy`, ~252 calls) dominate; QueryBuilder in 101
  files; DQL rare (8 sites, incl. bulk DELETE/UPDATE); **`SimpleDb`** raw-PDO helper
  (`src/Functions/SimpleDb.php`, readonly-only, no transactions) in ~11 controllers for
  report/list endpoints whose JSON shape is defined by **SQL aliases**, not any class.
- **Projection is used for reads** (36 `getArrayResult/getScalarResult/...` sites,
  `IDENTITY()` FK projection) but mutation paths hydrate full entities.
- **Transactions are implicit:** ~**402** `flush()` calls; **no** `beginTransaction`/
  `commit`/`wrapInTransaction` in app code; **no** request-wrapping transaction. Some
  requests flush **multiple times** (e.g. `Market/BuyController` flushes twice → two
  transactions, not one atomic unit).
- **Custom column type** `pet_changes_summary` → `Doctrine/Types/PetChangesSummaryType`
  stores a value via PHP `serialize()`/`unserialize()` on one `PetActivityLog` column.
- **One `#[HasLifecycleCallbacks]` entity** (`PetSkills`) but its callback is annotated via
  **docblock**, which is **ignored** under attribute mapping — it never fires via Doctrine;
  it survives only because it's called manually. ✅ Treat working ORM lifecycle callbacks
  as effectively zero. The real load-time hooks are two `postLoad` listeners (see G1).

### 2.6 Pet-activity system

- **Hybrid, not a pure registry.** `PetActivityService::runHour()` is a ~176-line imperative
  priority ladder (`if(...) return;`) of special cases (pregnancy, poison, fairy godmother,
  holidays, tool adventures…) with **23 directly-injected services**. The tagged registry
  is only the **fallback branch**.
- **Registry pattern:** `IPetActivity` carries `#[AutoconfigureTag('app.petActivity')]`;
  the consumer injects `#[AutowireIterator('app.petActivity')] iterable`. **16** activity
  classes; adding one = drop a class in, no central list (catch-all autoconfigure tags it).
  `pickActivity()` materializes **every** member each tick, calls `groupDesire()` (soft
  weight) and `possibilities()` (hard gate = inventory-checked callable list), then
  weighted-random selects. `QualityTime/` mirrors the pattern with a *uniform* pick.
- **Intentionally imperative variety:** availability, odds, rewards, XP, flavor text, side
  effects are all inline branching. The "infinity imps" case
  (`Crafting/ProgrammingService.php:563`) even encodes a set-theory joke as control flow
  (`2 × Infinity Imp = Infinity Imp + Infinity Vault Blueprint`). No schema would express it.
- **Determinism seams:** `IRandom` (~2,510 calls; the reproducibility crown jewel) and
  `Clock`. Every roll/gate goes through `IRandom`.
- **State model:** activities mutate the shared `Pet` aggregate + house inventory
  (`HouseSimService` scoped state) and write `PetActivityLog` rows as side effects. Change
  is reconciled via a `PetChanges` **before/after entity diff**, not an event stream.

### 2.7 Cross-cutting long tail

- **Cron (`crunz`)** — 6 jobs in `tasks/AllTasks.php`, each shelling to a console command.
  The heartbeat is **`app:increase-time` every minute** (raw transactional SQL: +1
  activity_time capped at 2880, social energy, fireplace decay, **expired-session GC**,
  device-stats GC) + `app:run-park-events` every minute. Plus hourly `buzz-buzz` (beehives)
  and 3 daily rollups.
- **Email:** effectively one message (password reset, plain text, SES). No templated email.
- **AWS SDK:** **not S3** — used *only* for an optional CloudWatch metric push
  (`PerformanceProfiler`). No file uploads/blob storage anywhere.
- **Redis:** used purely as a PSR-6 cache pool (Doctrine result cache, app cache, the two
  "locks", rate-limiter state). No native sessions, no pub/sub.
- **Patreon:** OAuth connect + a **webhook whose signature is HMAC-MD5** (keep the MD5).
- **Chinese lunisolar calendar** (`overtrue/chinese-calendar`) feeds seasonal content via
  `CalendarFunctions` (a ~30-predicate holiday oracle).
- **Custom logic evaluator** `JsonLogicParserService` — a homegrown recursive evaluator for
  array-encoded expressions with `%user.*%` variables, used by `StoryService` for
  `hideIf`/`disabledIf` on story choices. **DB content embeds executable conditions.**
- **`symfony/http-client`/guzzle:** no outbound HTTP in app code (guzzle used only as a
  JSON helper). **`symfony/expression-language`, translations, symfony/lock(flock):** all
  configured but effectively **unused**.

---

## 3. The gotcha catalogue

Ranked within tiers. Each: what it is, why it bites, and the **open decision** it forces.

### Tier 1 — No clean .NET/EF equivalent (must be rebuilt from scratch)

**G1. "In-memory definitions" is `enableResultCache` + Redis + UoW proxy-grafting — not L2 cache.** ✅
PSP has **zero** `#[ORM\Cache]` (no Doctrine second-level cache). Instead ~13 sites call
`->enableResultCache(24h, deterministicKey)` on definition-data queries (Item, PetSpecies,
Merit, Spice, Enchantment…), backed by the Redis result-cache pool. The clever part: two
`postLoad` listeners (`Doctrine/EventListeners/InventoryLoadListener.php`,
`PetLoadListener.php`) intercept an uninitialized Item/PetSpecies **proxy** and hydrate it
**from the result cache** via `UnitOfWork::registerManaged()` + `$proxy->__load()`. EF Core
has **no built-in L2 cache** and **no way to graft a cached instance into the change
tracker** like this. Invalidation is *none* — entries just expire after 24h (safe only
because definitions change on deploy, not at runtime).
→ **Open decision:** third-party `EFCoreSecondLevelCacheInterceptor` vs. a hand-rolled
definition-cache layer vs. load-all-definitions-into-memory-at-startup (the seed is small).

**G2. The pet-activity mass — ~37k lines of imperative logic to port literally.** ✅
No schema to extract; exact roll thresholds and item-name string literals *are* the game
balance and the jokes. ~2,510 `IRandom` calls, ~369 outcome methods.
→ **Open decision:** the port strategy for this bulk (mechanical translation vs.
re-expression), and whether to preserve the hybrid orchestrator+registry split (it is
order-sensitive — the `return`-short-circuit ladder is intentional).

**G3. `IRandom` must be reimplemented bit-exact.** ✅
It's the one seam that makes the whole imperative mass reproducible/testable. A .NET port
must reproduce the *exact* sequence and `rngSkillRoll` semantics, or every replay and
golden test diverges. `Clock` ports cleanly to `TimeProvider`; `IRandom` does not port
"cleanly" — it must match.

**G4. Serialization is a side-effecting request-finalization step, not JSON mapping.** ✅
Normalizers issue DB queries and branch on requested group; `ResponseService` deletes rows
and sets cookies while building the body; there's a normalize-then-serialize double pass.
No `System.Text.Json` analogue.
→ **Open decision:** split the concerns explicitly in .NET (serializer vs. action-filter
for cookie/flash/read-mark) — this is a design task, not a translation.

**G5. The wire contract has no manifest.** ✅
The effective shape of a serialized entity is the union of up to 11 groups per property,
across 60 enum + 26 literal group names, with the Angular TS models as the only spec.
Reconstructing per-endpoint response shapes requires whole-graph property scanning.
→ **Open decision:** derive the DTO catalogue from the frontend TS models, from the
group definitions, or from captured live responses (or all three, cross-checked).

**G6. Auth is a bespoke DB-token handler — not cookie-auth, not JWT.** ✅
Custom `AbstractAuthenticator` doing a `UserSession` lookup per request, dual cookie/bearer,
sliding server expiry, argon2i. In .NET this is a custom `AuthenticationHandler<>`.
**Cutover constraint:** to avoid logging out every existing client, the cookie must stay
byte-compatible (name `sessionId`, 40-char value, domain, Secure, HttpOnly) and argon2i
hashes must remain verifiable (needs an argon2i lib).
→ **Open decision:** keep the per-request expiry-slide + `lastActivity` write (a write on
every request), or change it.

**G7. Mutual exclusion is Redis cache-key locks in HTTP subscribers — nothing at the DB layer.** ✅
No pessimistic locking anywhere (`LockMode`/`FOR UPDATE` = 0 hits). Critical sections
(market purchase `'Trading For Sale #id'`, per-user `'One POST #id'`, house-run lock) are
`isHit()`+TTL cache entries — **not atomic** (get-then-set race window exists today).
`symfony/lock` is configured to `flock` and essentially unused.
→ **Open decision:** port as-is (SET-NX-with-expiry / `RedLock.net`) accepting the race, or
introduce real atomicity.

**G8. Homegrown `JsonLogicParserService` + PHP-`serialize()` column — DB carries code/opaque bytes.** ✅
Story `hideIf`/`disabledIf` are authored as JSON expressions in the DB and evaluated at
runtime; the evaluator's grammar + `%user.*%` vocabulary must stay byte-compatible with
existing content. Separately, the `pet_changes_summary` column holds **PHP-serialize
format** — an EF value converter cannot read the existing bytes as JSON.
→ **Open decision:** port the evaluator verbatim; decide whether to migrate the serialized
column data or read-compat it.

### Tier 2 — Behavioral contracts to preserve exactly (client depends on them)

**G9. Custom HTTP status codes.** ✅ **420** (too-many-requests *and* optimistic-lock
conflict) and **470** (house-hours-must-run) are branched on by the Angular client.
Also: **401 vs 403 is deliberately inverted for locked accounts** (`PSPAccountLocked` → 401
so the client auto-logs-out; 403 is reserved for stay-logged-in cases like feature-locked).
Generic framework 404/422 messages are **rewritten to friendly game prose**.

**G10. The blocking throttle queues, it doesn't reject.** ✅ `reserve(1,15)->wait()` sleeps
up to 15s. Naive `429`-immediately rate-limit middleware would change game feel. .NET's
`System.Threading.RateLimiting` supports queuing but the token-bucket params (limit 8, 3/2s,
per user id) and queue semantics must be reproduced deliberately.

**G11. Optimistic locking on 9 entities.** ✅ `#[ORM\Version]` **integer** columns on
Inventory, Vault, VaultInventory, Fireplace, UserQuest, GreenhousePlant, Dragon,
HollowEarthPlayer, Beehive. Conflicts caught centrally → 420, **no retry** (client reloads).
→ EF `[ConcurrencyCheck]`/`IsConcurrencyToken()` on an `int` (not SQL `rowversion`, to match
the existing column type). **Note:** `User` (where currency lives) has **no** version column.

**G12. `#[DoesNotRequireHouseHours]` marker gates a cross-cutting "before" filter.** ✅
40 endpoints opt out of the house-hours simulation gate. In .NET → an endpoint filter that
reflects for a marker attribute. The 470 status is part of the contract (G9).

**G13. JSON body is transparently readable as form params.** ✅ 153 controllers rely on
`convertJsonStringToArray()` injecting the decoded JSON body into the request bag.
→ Replicate via middleware or convert all 153 to model binding.

**G14. CORS with credentials + regex origin.** ✅ `allow_credentials: true` and a regex
origin are mandatory for the cookie-based cross-origin SPA flow. Headers allowed:
`Content-Type, Authorization, X-Requested-With`.

### Tier 3 — Known bugs / traps → decide replicate-vs-fix

**G15. ⚠️→✅ The per-user single-flight guard is inverted.**
`OneNonIdempotentRequestPerUserSubscriber:48` — `if(!isMethodIdempotent()) return;` — early-
returns for **POST/PATCH** (non-idempotent) and only gates **GET/PUT/DELETE**. The class
name and `'One POST #'` cache key say the opposite. **Verified against Symfony's
`isMethodIdempotent()`** (idempotent = HEAD/GET/PUT/DELETE/TRACE/OPTIONS). Net effect: the
mutating requests this was meant to serialize are **not** serialized by it today; they rely
solely on the blocking token-bucket (G10). Currency spend (`TransactionService`) does a
check-then-act with **no lock, no transaction, no version column** — so its only concurrency
protection is this (currently misfiring) layer.
→ **Open decision:** faithfully replicate the current (buggy) behavior, or fix the guard —
and if fixed, decide whether currency needs real protection (a change, not a port).

**G16. Mixed INT/ULID primary keys mid-migration.** ✅ Only 4 entities converted to
`BINARY(16)` ULID (`ItemTreasure`, `PetSpecies`, `Vault`, `VaultInventory`); the other ~89
(incl. Item, Pet, User, Inventory) are still INT. ULIDs are minted **client-side** (`new
Ulid()`) — MySQL has no native generator. Trap: a `Ulid` bound into a DQL `WHERE` against a
`BINARY(16)` column **silently matches zero rows** (binds as base32 string ≠ binary) — bind
raw bytes instead. Pomelo/EF must model both PK families and reproduce the base32 JSON
surface.
→ **Open decision:** finish the ULID conversion as part of the rewrite, or freeze the mixed
state and match it.

**G17. Tolerated MySQL deadlocks.** ✅ `RunHoursController` catches PDO error 1213 and
downgrades to a warning; `ResponseService` uses a raw DQL DELETE "for fewer serialization
deadlocks." EF Core's delete/change-tracking differs — the deadlock profile may change; the
"swallow deadlock on /house/runHours, move on" behavior is intentional current behavior.

**G18. 665 hand-written migrations don't translate.** ✅ EF generates its own migration
history. The existing chain is reference only; **`db/seed/base.sql` is the authoritative
schema snapshot** to model against.

---

## 4. Near-dead surface area — do NOT over-scope

These declared dependencies imply subsystems that **do not really exist**. Porting them
faithfully means porting *almost nothing*:

| Looks like | Reality |
|---|---|
| `aws/aws-sdk-php` ⇒ S3 uploads | Only an optional CloudWatch metric push. No blob storage. |
| `symfony/amazon-mailer` ⇒ templated email | Exactly one plain-text message (password reset). |
| `symfony/translation` + `intl` ⇒ i18n | `translations/` is empty; zero `->trans()` calls. English-only. |
| `symfony/expression-language` ⇒ rules engine | Unused. The real engine is homegrown `JsonLogicParserService` (G8). |
| `symfony/http-client` + guzzle ⇒ outbound APIs | No outbound HTTP in app code (guzzle == JSON helper). |
| `symfony/lock` ⇒ distributed locks | Configured to `flock`, unused. Real locks are cache-key based (G7). |

**Loose end to investigate:** `.env` defines `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` (Web
Push). Not covered in this pass — confirm whether push notifications are live and where
they're sent before assuming there's no push subsystem.

---

## 5. Emerging C# conventions (OPEN — captured, not decided)

Recorded from project owner direction + existing `docs/architecture/`. Listed so the eventual
"what do we do" step has a starting point; **none is ratified**.

- **Strongly-typed response DTOs, one-per-serialization-group as a starting point.** Kill
  serialization groups. Minimize *shared* DTOs; a few earn their keep (pet, inventory).
  Request + response DTOs live **in the same file as the endpoint** unless genuinely shared.
- **One endpoint per class.** (Already the PHP convention.)
- **Logic in controller actions is fine — the web API is *the* application API.** Extract
  only when shared. (Already the PHP convention; `docs/architecture/Project Patterns.md`.)
- **Project ORM queries into bespoke types/anonymous objects for read/intermediate steps;**
  only hydrate full entities when saving. (EF: `.Select(e => new {...})`.)
- **Keep the determinism seams** (`IRandom`, `Clock`) — they're non-negotiable for tests.

---

## 6. Open questions to resolve early

1. **L2 cache strategy (G1)** — the single biggest infra rebuild. Interceptor vs. hand-
   rolled vs. load-definitions-at-startup.
2. **Auth cutover (G6)** — do existing sessions/cookies survive the switch, or is a forced
   re-login acceptable? Determines whether the token/cookie/argon2i formats must be matched.
3. **ULID conversion (G16)** — finish it during the rewrite, or match the mixed state?
4. **The single-flight bug (G15)** — replicate or fix; and does currency get real
   protection?
5. **Activity-port strategy (G2/G3)** — mechanical translation vs. re-expression, and the
   golden-test approach (record PHP outputs against a seeded `IRandom`, replay in .NET).
6. **DTO source of truth (G5)** — frontend TS models vs. group definitions vs. captured
   responses.
7. **Transaction granularity (2.5)** — keep many-small-flushes semantics or move to
   per-request `SaveChanges` (changes atomicity, e.g. `BuyController`'s double flush).
8. **VAPID / Web Push** — confirm whether a push subsystem exists at all.

---

*Generated from a six-way parallel audit of the `api/` tree. All file references are against
the current branch; re-verify `path:line` anchors before relying on them in code, as the
tree moves.*
