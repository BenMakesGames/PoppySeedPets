# Reference-Data & Caching Strategy (from scratch)

> **Status:** Options analysis / proposal. Researches the "version-locked definition data"
> problem for the .NET rewrite and lays out the full option space with a recommendation.
> Nothing here is ratified yet — the **open decision** is at the end.
>
> Companion to `current-architecture-and-gotchas.md` (see gotcha **G1**).

---

## 1. The problem, stated precisely

PSP has two very different kinds of data, and today they're both just "Doctrine entities":

| | **Definition data** (this doc) | **Player data** |
|---|---|---|
| Examples | Item, ItemFood/Tool/Hat/Treasure, PetSpecies, Enchantment, Spice, Aura, Merit, Plant, ItemGroup, HollowEarthTile… | User, Pet, Inventory, Vault, GreenhousePlant, … |
| Changes when | **only on deploy** (shipped in `db/seed/base.sql`) | constantly, per request |
| Size | tiny — ~1,400 items (+ ~530 food / tool / hat / treasure satellites), ~111 species, ~149 enchantments, ~63 merits, ~50 plants, … the *entire* seed is 836 KB of SQL | grows with players |
| Read pattern | **hot** — every Inventory row → an Item; every Pet → a PetSpecies; read on nearly every request | hot, but naturally per-user |
| Write pattern (runtime) | **never** | frequent |

The defining property: **definition data is effectively immutable and locked to the app
version.** It changes only when we ship a new seed, which is also a process restart.

**What we're trying to solve:** serve this hot, static data on every request without
"troubling the DB" with the same lookups forever, and without the redundant transfer of
unchanging item/species columns joined onto every player row.

### The reframe that matters

This is usually called a "caching" problem, but *caching* drags in the hard parts — TTLs,
invalidation, coherence, stampedes — **none of which apply here**, because the data cannot
change without a redeploy. So the honest framing is:

> **This is a "load a small, static, version-locked dataset into memory once at startup"
> problem — not a cache-coherence problem.**

That reframe is what tilts the options below. Anything with a TTL or an invalidation story is
solving a problem we don't have.

---

## 2. What PHP does today, and why it only half-works ✅ (verified in code)

Two moving parts:

1. **Doctrine query *result cache*** (`->enableResultCache(24h, deterministicKey)`, backed by
   Redis) on ~13 definition-fetch queries in the `*Repository.php` static helpers.
2. **Two `postLoad` listeners** (`Doctrine/EventListeners/InventoryLoadListener.php`,
   `PetLoadListener.php`) that intercept an **uninitialized Item/PetSpecies proxy** on a just-
   loaded Inventory/Pet and hydrate it *from the result cache* via
   `UnitOfWork::registerManaged()` + `$proxy->__load()`.

**Why it half-works — the proxy gate (`InventoryLoadListener.php:35`):**

```php
$itemProxy = $inventory->getItem();
if(!$itemProxy instanceof Proxy) return;      // ← only fires when Item is a LAZY PROXY
if ($itemProxy->__isInitialized()) return;
```

- **Fetch-join path bypasses the cache entirely.** Any query that does
  `->addSelect('item')` (a real fetch-join) returns a *hydrated* Item, not a proxy — the
  `instanceof Proxy` check early-returns and the item columns came straight from the DB via
  the join. The result cache is never consulted.
- **Even the proxy path is an N+1 — against Redis instead of MySQL.** When code joins Item
  only for filtering/sorting *without* `addSelect` (e.g.
  `InventoryFilterService::createQueryBuilder()` — `leftJoin('i.item','item')`, no
  `addSelect`), Item stays a proxy, so the listener fires **once per row**: N cached-query
  lookups + N DQL executions + N proxy hydrations for a page of N inventory rows. Better than
  N DB hits, but it's still N round-trips, not one set-based fetch — and a cold/missing key
  falls through to the DB per item.

So the current design caches on the *lazy* path (as N+1) and is *bypassed* on the *join*
path — exactly the "joins don't use the cache" intuition. It also leans on deep, brittle
UnitOfWork internals (`registerManaged`) that **have no EF Core equivalent** (G1).

---

## 3. The .NET tool landscape (research)

What modern .NET actually offers for this shape of problem:

- **`FrozenDictionary<K,V>` / `FrozenSet<T>`** (`System.Collections.Frozen`, .NET 8+) — built
  **once**, then read-only; higher build cost, **fastest possible lookups** (~47% faster than
  `Dictionary` in read-only benchmarks, less memory at scale), inherently thread-safe. MS's
  own guidance: use it "when a dictionary is created once at application startup and used
  throughout the application's lifetime" — literally naming "country/currency/locale lookup
  tables loaded at startup" as the fit. This *is* our definition data.
- **`HybridCache`** (`Microsoft.Extensions.Caching.Hybrid`, .NET 9+) — unifies an in-process
  L1 (no serialization) with an optional distributed L2 (`IDistributedCache`/Redis) and adds
  **stampede protection** (one factory call per key; the thundering herd becomes one query).
  A strict upgrade over `IMemoryCache`; call sites don't change when you later add Redis.
- **`EFCoreSecondLevelCacheInterceptor`** (VahidN) — the de-facto EF L2 cache. Caches
  arbitrary LINQ (incl. joins/projections) via `.Cacheable()` or `CacheAllQueries()`; can
  target specific tables (`CacheQueriesContainingTableNames`); providers include in-memory,
  Redis (MessagePack), FusionCache, **HybridCache**, EasyCaching. **Caveats:** auto-
  invalidation fires on `SaveChanges` CRUD but **not** on `ExecuteUpdate`/`ExecuteDelete` or
  any **out-of-band write** (stored proc, another app, **or a seed import**) → manual clear;
  queries inside explicit transactions aren't cached by default; per-*user* caching risks
  memory (irrelevant if we only cache definitions).
- **EF projections + `AsNoTracking`** — `.Select(e => new {...})` pulls minimal columns;
  `AsNoTracking` skips change-tracking for read-only reads. Reduces per-query cost but does
  **not** share results across requests (EF's first-level cache is per-`DbContext`/request).

---

## 4. The option space

Evaluated against PSP specifically. "Lose join" below means: can we still filter/sort player
queries *by definition attributes in SQL* (e.g. inventory `WHERE item.food.spiciness > X`,
market `ORDER BY item.name`)?

### Option A — In-memory reference store; definitions **out of EF**
Load every definition table into `FrozenDictionary<int, ItemDef>` (etc.) at startup. Player
entities (Inventory, Pet) hold a raw **FK id** (`int ItemId`), **not** an EF navigation
property. "Joining" a definition = an O(1) dictionary lookup in app code after the query.

- **+** Zero DB/network for definitions, ever. O(1) in-process reads. **No invalidation logic
  at all** (restart = reload = deploy). Thread-safe, trivially testable, no ORM magic, no
  Redis dependency for this. The whole dataset is single-digit MB.
- **+** Makes the definition-vs-player distinction explicit in the type system — aligns with
  the project's "project into bespoke objects, don't do entities-everywhere" stance.
- **−** **Loses SQL-side filtering/sorting on definition attributes.** PSP's filter services
  *do* filter/sort by `item.food.*`, `item.tool.*`, `itemGroups`, `item.name`
  (`InventoryFilterService`, `MarketFilterService`). Those endpoints need a different plan
  (see D).

### Option B — Definitions stay EF entities; **EF second-level cache** (interceptor)
Keep nav properties (`Inventory.Item`); register `EFCoreSecondLevelCacheInterceptor` and mark
the definition tables cacheable (Redis or HybridCache provider).

- **+** Keeps SQL joins/filtering working *and* caches them (incl. join/projection shapes).
  Smallest change to a conventional EF model; transparent.
- **−** Still serializes/deserializes cached rows per query shape (MessagePack) and does a
  Redis round-trip unless fronted by HybridCache's L1. Caches *per query shape*, so a join and
  a lookup cache separately (the same non-sharing as today — it caches the join, but doesn't
  unify).
- **−** **Invalidation footgun for us specifically:** the seed import is an "out-of-band"
  write the interceptor can't see → must clear on deploy (or key by app version). We'd be
  buying an invalidation engine we then have to override, to solve a problem (runtime writes)
  we don't have.

### Option C — No cache; EF projections + `AsNoTracking`, "just query"
Trust MySQL: the reference tables live in the InnoDB buffer pool; `.Select()` minimal columns;
rely on EF's per-request identity map to dedupe within a request.

- **+** Simplest possible; no cache infra; nothing to invalidate. A legitimate *baseline*.
- **−** Every request still round-trips the DB for definitions and re-transfers unchanging
  item/species columns on every joined player row. For a constantly-polled game this leaves
  real, free performance on the table — the dataset is small enough that keeping it in process
  is nearly cost-free, so paying the DB tax forever is hard to justify.

### Option D — **Hybrid (recommended shape):** in-memory store for the hot path + SQL for the few real search endpoints
Definition *data* lives in the in-memory `Frozen*` store (Option A) and is the source of truth
for **reads/stitching** on the 90 % hot path. Keep the definition **rows in the DB too** (they
already exist from the seed) purely so the handful of *search/filter* endpoints can filter by
definition attributes. Per endpoint, choose:

- **Hot path** (render inventory, load a pet, resolve an item by name/id): project player rows
  with FK ids, stitch definitions from the in-memory store. No definition join.
- **Small candidate set** (a user's inventory filtered by food-ness): filter **in-memory**
  after loading the user's rows — a user holds tens–hundreds of items, so this is trivial.
- **Large mutable set filtered by static attributes** (market listings filtered by item
  attributes): **prefer a plain SQL JOIN** to the in-DB definition rows — it's the simplest
  path, the optimizer uses the item indexes directly, and the redundant-transfer concern
  barely applies because search endpoints are low-frequency and paginated. The id-set-IN
  pattern (compute matching item ids in memory → `WHERE listing.item_id IN (:ids)`) is a
  **fallback**, worth it only if definitions ever leave the DB, or for a predicate that's
  awkward in SQL. See §5.1 on why the IN list can't blow up here.

- **+** Option A's speed and zero-invalidation on the hot path; keeps SQL power exactly where
  it's actually needed; no L2-cache serialization/invalidation machinery.
- **−** Two access styles to hold in your head (in-memory stitch vs. id-set-then-SQL). But the
  boundary is clear: *static predicates resolve in memory; mutable rows resolve in SQL.*

---

## 5. Recommendation

**Adopt Option D**, built on `FrozenDictionary` reference stores loaded at startup, with player
entities holding FK ids rather than EF navigation properties to definitions.

Rationale:
1. The data's defining trait is *version-locked immutability*, so the "cache" concerns that
   make B and the current PHP design complex (TTL, invalidation, coherence, the UoW proxy
   graft) simply **evaporate** — restart is the only invalidation, and it's automatic.
2. The dataset is **single-digit MB** — there is no memory reason not to hold it all in
   process, and `FrozenDictionary` is purpose-built for exactly "build once at startup, read
   forever."
3. It fixes the current design's actual failure (N+1 / bypassed-on-join) by **removing the
   join** on the hot path entirely, rather than caching around it.
4. It preserves SQL filtering for the few endpoints that genuinely need it — a plain JOIN to
   the in-DB definition rows (id-set-IN only as a bounded, safe fallback; §5.1) — so we don't
   lose anything real.
5. It needs **no Redis** for definition data and no third-party cache library — less infra
   than today.

### 5.1 Filter-joins vs. id-set-IN — why the IN list won't blow up

The one soft spot in D is the search endpoints that filter player/market rows by definition
attributes. Two ways to answer the definition predicate:

- **SQL JOIN** (`listing JOIN item JOIN item_food WHERE food.spiciness > :x`) — needs the
  definition rows in the DB. Simplest; the optimizer uses item indexes; one query.
- **id-set-IN** — resolve the matching item ids from the in-memory store, then
  `WHERE listing.item_id IN (:ids)`. Needed if definitions are *not* in the DB.

**The "huge IN list" worry does not materialize at PSP's cardinalities.** The id list is
bounded by the size of the *definition* table, not the player data — there are only ~1,400
items, so any `item_id IN (...)` is **≤ ~1,400 ints** in the absolute worst case (a filter
matching every item), and realistically dozens–to–low-hundreds. That's a few KB of query
text; MySQL doesn't get unhappy with `IN` until the low *tens of thousands*. So the IN list is
safe here — but it's still usually the *second* choice, because:

- If the definition rows are in the DB anyway (they are — from the seed), a **JOIN is simpler
  and typically at least as fast**: no round-tripping an id set app→DB, and the optimizer
  picks indexes. Expressing the predicate as a pre-computed IN list mostly matches the JOIN's
  performance rather than beating it, while adding a moving part.
- The id-set pattern earns its keep only when (a) definitions have left the DB (pure in-memory
  model), or (b) the predicate is a computed/rule-based property that's painful in SQL. For
  (b) you can precompute the id sets at startup (secondary indexes like `foodIdsByHeatTier`,
  `itemIdsByGroup`), making the in-memory lookup O(1) and the IN list small.

**The load-bearing insight:** the hot render path and the search path have **opposite optimal
strategies**, and you don't pick one globally — hot path stitches from memory (no join),
search path joins in SQL. Which points at the model in §6: keep definitions **DB-queryable**
so search JOINs just work, but give player rows a bare `int ItemId` (no nav property) so the
hot path *cannot* accidentally join and is forced to stitch from memory.

#### Growth ceiling & the pathological near-total case

*Is a big `IN` list ever DoS-level here? No — not on any realistic horizon.* The list is
bounded by the *item table* size, which grows slowly (~1,400 items over ~8 years ≈ ~175/yr;
~50 years to reach 10,000). MySQL's relevant thresholds:

- **~200 elements** — `eq_range_index_dive_limit`: above this, cardinality estimation switches
  from per-value index dives to index statistics. A planning heuristic (cheaper, slightly less
  accurate), **not** a performance cliff.
- **~tens of thousands** — measurable planning/parse overhead, still completes.
- **~hundreds of thousands** — can exceed `range_optimizer_max_mem_size` (8 MB default) →
  optimizer abandons range access, may full-scan. The genuinely "sad" zone — far beyond PSP.
- Query text is a non-issue (`max_allowed_packet` 64 MB default; 10k ids ≈ 60 KB).

A worst-case full-table `id IN (~2,800)` a decade out is ~15 KB of text and low-single-digit
ms. So numerically we're fine for the game's plausible lifetime.

**The `id IN (every id except a handful)` case is a design smell, not a scaling threat**, and
is neutralized three ways:

1. **Prefer the JOIN (D2).** A JOIN never materializes an id list — "matches 95% of items" is
   a cheap scan with a WHERE. The giant-IN failure mode is *unique to id-set-IN*; keeping
   definitions DB-queryable avoids it entirely. This is the strongest argument for D2.
2. **Emit the smaller side.** When using id-set-IN, choose `id IN (matches)` vs
   `id NOT IN (complement)` by whichever is smaller → caps the list at ≤ half the table;
   "all but a handful" becomes `NOT IN (handful)`.
3. **Bail when near-total.** If the in-memory predicate matches ≥ ~80 % of items, drop the
   item filter from SQL entirely (it's ~a no-op) and, if needed, exclude the handful in app
   code after the query.

**Backstop:** the predicate space is bounded by fixed item attributes (list ≤ item count), and
the rate-limiter + single-flight gating already cap query rate — so there's no unbounded
amplification and no DoS vector even from a hostile user.

### 5.2 ULID keys interact with this design (and are safe)

We want more keys — **especially static/content data** — on ULIDs, because content creators
collide on auto-increment ids when authoring in parallel branches (ULIDs are generated
client-side, no central sequence, no merge renumbering). This is a well-targeted use of ULIDs
and it's **orders of magnitude below any performance concern**, with a few hygiene items. See
also main doc **G16** (mixed INT/ULID migration).

**Where the extra bytes land** (ULID = 16 B `BINARY(16)` / 26-char base32, vs INT 4 B):

- **Definition table PKs themselves:** trivial — a few thousand rows.
- **FK columns in high-row player tables** (`inventory.item_id`, …): *the only line that's more
  than a rounding error.* InnoDB stores the PK in every secondary index and FK columns must
  match the referenced PK width, so 4→16 B multiplies by row count. Even generously (~10 M
  inventory rows, in a couple of indexes) that's low-hundreds of MB — real but not a latency or
  capacity concern at PSP scale.
- **id-set-`IN` lists (§5.1):** 16 raw bytes/id → ~22 KB at 1,400 ids, ~3× an int list, still
  far below the tens-of-thousands-of-elements zone. Non-issue.
- **JSON wire / in-memory store key:** 26-char strings add ~KB to big inventory payloads (and
  base32 is high-entropy, compresses poorly, but the absolute size is small);
  `FrozenDictionary<Ulid, …>` on a 16-byte struct key is negligible.

**ULIDs dodge the classic UUID pitfall:** random `UUIDv4` PKs fragment the clustered index on
insert; **ULIDs are time-ordered**, so inserts stay ~monotonic (locality near auto-increment) —
and for static data, insert locality is irrelevant anyway (seed once). So the content-data case
is the *best* case for ULIDs.

**Do it right (correctness/hygiene, not perf):**
1. Store `BINARY(16)`, never `CHAR(26/36)` (text is ~2.25× bytes, loses byte-sortability).
2. Keep canonical big-endian (timestamp-first) layout so the index stays insert-ordered —
   `Ulid::toBinary()` already does this.
3. **Bind raw bytes** in queries (esp. id-set-`IN`) — a ULID bound as base32 silently matches
   **zero rows** against `BINARY(16)` with no error (G16).
4. Scope PK conversion to the tables with the collision problem (content/definition tables +
   the FK columns referencing them). Leave high-write *player* PKs as `bigint` auto-increment
   unless they share the parallel-authoring motivation — avoids widening their hottest indexes.

**Tooling note:** map `Ulid ↔ BINARY(16)` via an EF value converter. .NET 9's
`Guid.CreateVersion7()` (UUIDv7) is a native, same-properties alternative (time-ordered,
`BINARY(16)`, no third-party dep) — but the API already emits **base32 ULIDs** consumed by the
Angular client, so staying on ULID keeps the wire contract consistent.

**Where the other options still fit:**
- Start hot paths at **C** (just query) if we want to defer the in-memory store — it's a valid
  baseline — but D is cheap enough that I'd do it up front.
- Keep **HybridCache** in the toolbox for *player-facing computed* caching later (e.g.
  expensive aggregates), where its L1+L2+stampede protection shines. It's the right tool for
  *that* problem, just not for static definitions.
- **B** (the interceptor) only becomes attractive if we decide definitions must stay first-
  class EF navigation properties for ergonomics — a real but separate call (see open
  decision).

### Loading & source-of-truth note
Load the stores **from the seeded DB at startup** (`SELECT * FROM item …` → `Frozen*`), not
from a separate JSON file — that keeps one source of truth (the DB, seeded from
`db/seed/base.sql`). Loading from JSON is possible but duplicates the source of truth; only
worth it if we ever decide to drop the definition *tables* entirely (which would also forfeit
SQL search — not recommended).

---

## 6. Open decision (to ratify)

1. **Adopt Option D?** (in-memory `Frozen*` reference stores + FK-id player entities + id-set
   SQL for search endpoints). Y/N.
2. **Do definitions remain EF entities at all?** Two sub-shapes if D is adopted:
   - **D2 (leaning recommended):** definitions stay EF-mapped, so the startup load *and* the
     search-endpoint JOINs are ordinary EF — search "just works" in SQL (§5.1). Player entities
     carry a bare `int ItemId` (**no** nav property to definitions), so the hot path can't
     accidentally join and must stitch from the in-memory store. Keeps SQL power where it's
     needed while structurally preventing hot-path joins.
   - **D1:** definitions are *not* EF-mapped at all; the `DbContext` bulk-reads them at startup
     (or raw SQL). Cleanest separation, but search endpoints then *must* use id-set-IN (they
     have no definition table to JOIN), so only pick D1 if we're committed to that pattern.
3. **Baseline-first?** Ship hot paths as Option C initially and add the in-memory store once
   there's a measurement to beat — or build D from the start? (Recommendation: build D; it's
   low-cost and the current pain is already understood.)

---

## Sources

- [FrozenDictionary<TKey,TValue> — Microsoft Learn](https://learn.microsoft.com/en-us/dotnet/api/system.collections.frozen.frozendictionary-2?view=net-8.0)
- [Exploring Frozen Collections in .NET 8 With Benchmarking — C# Corner](https://www.c-sharpcorner.com/article/exploring-frozen-collections-in-net-8-with-benchmarking/)
- [FrozenDictionary vs ImmutableDictionary vs Dictionary — codingdroplets](https://codingdroplets.com/frozendictionary-vs-immutabledictionary-vs-dictionary-dotnet)
- [HybridCache in ASP.NET Core — Milan Jovanović](https://milanjovanovic.tech/blog/hybrid-cache-in-aspnetcore-new-caching-library)
- [HybridCache in .NET 9: One Caching API, Stampede Protection Included — Adrian Bailador](https://adrianbailador.github.io/blog/61-hybrid-cache-dotnet/)
- [Second-Level Caching in EF Core 10: The Complete Guide — codewithmukesh](https://codewithmukesh.com/blog/ef-core-second-level-caching)
- [EFCoreSecondLevelCacheInterceptor — GitHub (VahidN)](https://github.com/VahidN/EFCoreSecondLevelCacheInterceptor)
- [AsNoTracking() Performance in EF Core — Coding Bolt](https://codingbolt.net/2023/08/23/asnotracking-performance-in-ef-core/)
