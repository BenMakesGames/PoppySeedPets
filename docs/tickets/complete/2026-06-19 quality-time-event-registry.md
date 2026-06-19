# Quality Time: Event Registry Refactor

## Context
**Current behavior**: `QualityTimeService` is one ~600-line file holding the orchestrator (`doQualityTime`), the event-picker (`getRandomQualityTimeDescription`), and ~15 private flavor methods (`readAStory`, `playCharades`, `carveGourds`, …). New events are added by appending one private method and threading a new entry — and possibly a new `if` block — into `getRandomQualityTimeDescription`. The if/else chain in the picker is already unwieldy and will grow without limit as the design doc's grab-bag of new events ships.

**New behavior**: Each quality-time event lives in its own class in a new `App\Service\QualityTime\` directory, implementing a tagged interface (`QualityTimeEvent`). `QualityTimeService` keeps only the orchestrator and a tiny picker that filters an injected `iterable<QualityTimeEvent>` by per-event availability and uniformly random-picks one. Adding a new event becomes "drop a new file into `Service/QualityTime/`"; no edits to `QualityTimeService`, no central registration list.

The chosen architecture mirrors the existing `App\Service\PetActivity\` system (`IPetActivity` + `#[AutoconfigureTag('app.petActivity')]` + `#[AutowireIterator]` in `PetActivityService`), which is the codebase's established pattern for "iterable family of variant strategies".

## Scope
### In scope
- New interface `App\Service\QualityTime\QualityTimeEvent` with the `#[AutoconfigureTag('app.qualityTimeEvent')]` attribute.
- New directory `api/src/Service/QualityTime/` with one class per existing event. Migrate all current events: `readAStory`, `playHideAndSeek`, `playTag`, `practiceTricks`, `bakeCookies`, `buildAPillowFort`, `playCharades`, `stretchTogether`, `singTogether`, `goOnAWalk`, `goFishing`, `exploreTidePools`, `stargaze`, `makeApricotPies`, `carveGourds`, `decorateTheHouseForStockingStuffingSeason`.
- Move `QualityTimeResult` out of `QualityTimeService.php` into `App\Service\QualityTime\QualityTimeResult` (its own file).
- Rewrite `QualityTimeService::getRandomQualityTimeDescription` to filter the injected iterable by `$event->isAvailable($user, $pets)` and uniform-pick via `$this->rng->rngNextFromArray(...)`.
- `services.yaml`: register `App\Service\QualityTime\` as a subdirectory block. **Do not** add `lazy: true` — see Constraints & Gotchas for the analysis.
- Preserve current odds exactly — see Open Decision 1 for how the two low-frequency holiday events (`carveGourds` 1-in-7 on Halloween-crafting days, `decorateTheHouseForStockingStuffingSeason` 1-in-6 on Stocking-stuffing season days) carry over.

### Out of scope
- Adding new events. This ticket migrates the existing 16; new events from `docs/features/quality-time-event-ideas.md` ship separately.
- Refactoring `doQualityTime` itself — only `getRandomQualityTimeDescription` and the private flavor methods move. The post-pick loop (affection, hunger top-up, activity log creation, `$user->setLastPerformedQualityTime()`) stays put.
- Changing the `QualityTimeResult` shape, the activity-log tagging, or the `foodBased` hunger mechanic.
- Frontend changes, migrations, new enum values, new activity-log tags.
- Generalizing to other parts of the codebase (e.g., the `goFishing` rng / `playCharades` field-guide expansions). Each event class keeps whatever ad-hoc behavior its current method has.

## Relevant Docs & Anchors
- **Established pattern (the model to mirror)**: `api/src/Service/PetActivity/IPetActivity.php` (the tagged interface) and `api/src/Service/PetActivityService.php` — see the constructor's `#[AutowireIterator('app.petActivity')] private readonly iterable $petActivities` parameter. `api/src/Service/PetActivity/CLAUDE.md` documents the broader system; the lazy-loading pattern at the top is directly applicable.
- **Service registration**: `api/config/services.yaml` — the `App\Service\PetActivity\:` block with `lazy: true`. Add a sibling `App\Service\QualityTime\:` block in the same shape.
- **Current file being broken up**: `api/src/Service/QualityTimeService.php` — read end-to-end. Each `private function fooBar(User $user, array $pets): QualityTimeResult` is one future event class. The gating cascade in `getRandomQualityTimeDescription` is the source of truth for each event's `isAvailable` predicate (weather, holiday, rng-modulated holiday).
- **Sibling event design doc**: `docs/features/quality-time-event-ideas.md` — has the **Conventions block** that names the helpers every flavor method uses (`ActivityHelpers::PetName`, `ActivityHelpers::UserName`, `ArrayFunctions::list_nice`, `$this->rng->rngNextFromArray`). The flavor-content rules (no anatomy assumptions; no implying pets know human language) carry over into the new files unchanged.
- **Recent analogue tickets** (drop-one-event-in shape — confirms each existing event is small and self-contained, which is what makes one-class-per-event reasonable here): `docs/tickets/complete/2026-06-19 quality-time-sing-together.md`, `2026-06-19 quality-time-pillow-fort.md`, `2026-06-19 quality-time-tide-pools.md`.
- **`Service/` CLAUDE.md** (read for the lazy-loaded subdirectory pattern and the project's "auto-discovered services" convention): `api/src/Service/CLAUDE.md` §"Lazy-Loaded Services" and §"Creating New Services".

## Constraints & Gotchas
- **Preserve odds exactly.** The current picker uniformly picks from `$possibleMessages` *after* the if-cascade has decided which events to include. Two events use an extra rng coin-flip to be included at all (`carveGourds`: 1-in-7 on Halloween-crafting; `decorateTheHouseForStockingStuffingSeason`: 1-in-6 on Stocking-stuffing season). The migrated registry must produce the same per-event probabilities — see Open Decision 1.
- **`isAvailable` may not depend on `Pet[]`.** No current event gates on pet state; the parameter is there for shape-symmetry with `generate(User, Pet[])` but should not be used unless a future event genuinely needs it. Document this in the interface docblock; don't add Pet[] to `isAvailable` "just in case".
- **`QualityTimeService` constructor will lose most of its dependencies.** `PetExperienceService`, `CravingService`, `UserStatsService`, `EntityManagerInterface` are still needed by `doQualityTime`. `Clock` is still needed if the orchestrator uses it. `IRandom` is still needed (the picker uses `rngNextFromArray`). Each migrated event class receives only what *that* event needs — `playCharades` needs `$user->hasFieldGuideEntry(...)` (no service injection); `playHideAndSeek` needs nothing extra. Pure value methods: no `EntityManagerInterface`. Keep constructors tight.
- **`IRandom` injection into event classes**: events that pick flavor lines need `IRandom`. Inject via constructor; do not pass the picker's `IRandom` instance through `generate()`. Mirror how each `IPetActivity` impl receives its own `IRandom`.
- **`Clock` for holiday-gated events**: `carveGourds`, `makeApricotPies`, `decorateTheHouseForStockingStuffingSeason` use `CalendarFunctions::is...($this->clock->now)`. Each of those event classes needs `Clock` injected. Don't centralize "current time" in the picker — keep each event self-checking so future events can add their own calendar predicates without picker changes.
- **Weather lookup**: current picker calls `WeatherService::getSky($this->clock->now)` once and reuses the value across the if-cascade. With per-event `isAvailable`, each weather-gated event class would re-call `WeatherService::getSky($this->clock->now)`. That's fine — `getSky` is cheap (a deterministic computation from a `DateTimeImmutable`). Don't add a context object or pre-compute the sky in the picker; leave each event self-contained.
- **`QualityTimeResult` is consumed by `doQualityTime`**, which references `->message` and `->foodBased`. Moving the DTO to its own file under the new namespace requires updating `doQualityTime`'s use list (the class is in the same namespace as `QualityTimeService` today; after the move, an explicit `use App\Service\QualityTime\QualityTimeResult;` is needed). Verify no other files reference `QualityTimeResult` first; if any do, update them too.
- **Auto-discovery**: `services.yaml` already has `App\: resource: '../src/'` which auto-registers everything in `src/`. `autoconfigure: true` in `_defaults` plus the `#[AutoconfigureTag]` attribute on the interface will tag every implementation automatically — no per-class registration needed. A separate `App\Service\QualityTime\:` block is therefore **not required for tagging**; if added at all, it's for future-proofing parity with the `App\Service\PetActivity\:` / `App\Service\Holidays\:` directory blocks. Implementer may omit it entirely — see next bullet.
- **Why no `lazy: true` here.** `lazy: true` returns a proxy that defers real construction until first method call. The picker iterates the `iterable<QualityTimeEvent>` and calls `isAvailable($user)` on *every* event on every `doQualityTime` invocation — so the deferral never pays off and we eat the proxy-class autoload + per-call indirection cost on top of normal construction. Iterator laziness (`#[AutowireIterator]` already returns a `RewindableGenerator` that only constructs items as iteration visits them) gives us the property we actually want: requests that never call `doQualityTime` never construct any event class. That works without `lazy: true`. Adding it would be net-negative work here. (The same arguably applies to `App\Service\PetActivity\:`'s `lazy: true`, since `PetActivityService::pickActivity` also iterates all and calls `groupDesire` on each — out of scope for this ticket to revisit, but worth a follow-up audit if perf ever becomes a question there.)
- **Event class naming**: one class per event. Suggested form `XxxEvent` (`ReadAStoryEvent`, `PlayCharadesEvent`, `BuildAPillowFortEvent`, …). Final naming is local taste — see Open Decision 2.
- **Don't break the unread-activity-log delivery path.** `doQualityTime` writes one `PetActivityLog` per pet via `PetActivityLogFactory::createReadLog` and tags it with `PetActivityLogTagEnum::QualityTime`. None of that changes. Spot-check after refactor that activity logs still appear in the response.
- **Keep event constructors cheap.** The picker iterates every tagged event and calls `isAvailable` on each, which forces construction of every lazy proxy on every `doQualityTime` call. At today's 16 events that's free; at 100 it stays free *only if* constructors are trivial. **No DB queries, no service-tree walks, no eager static initialization in any event constructor.** Same rule the PetActivity CLAUDE.md documents for `IPetActivity` impls — applies here for the same reason. Expensive lookups belong inside `generate()` (called for exactly one event per quality-time tick), not in `__construct` or `isAvailable`.

## Open Decisions
1. **How to preserve the 1-in-7 / 1-in-6 holiday-rng odds.** Two reasonable shapes:
   - **(a)** Bake the rng coin-flip into the event's `isAvailable()` — e.g. `CarveGourdsEvent::isAvailable()` returns `CalendarFunctions::isHalloweenCrafting($this->clock->now) && $this->rng->rngNextInt(1, 7) === 1`. Preserves exact prior odds; `isAvailable` becomes non-deterministic but that's already the case in the current code's effective logic.
   - **(b)** Add a `weight(): int` method (default 1) and have the picker do weighted random selection. The two rare events return `weight = 1` while everything else returns `weight = 7` (or whatever the lcm is) to preserve current per-event probabilities. More principled, but introduces a second knob and requires arithmetic to land on the same odds.

   **Default: (a).** It's the smaller change, preserves exact odds, and is what the current code does conceptually — the rng was always part of "is this event in the pool right now". (b) is a follow-up refactor if/when more weighted events show up.
2. **Class naming convention.** `ReadAStoryEvent` vs. `ReadAStoryQualityTime` vs. `ReadAStory` (bare). The PetActivity directory uses `XxxService` for everything (which is a Symfony-ism, but inconsistent — they're not really services). Default: `XxxEvent` suffix. Reads as "what it is" without colliding with the existing `*Service` convention. Implementer may pick the bare form (`ReadAStory`) if the directory makes the context obvious.
3. **Where `QualityTimeResult` lives.** Either keep it inline at the bottom of `QualityTimeService.php` (status quo) or extract to `App\Service\QualityTime\QualityTimeResult` (its own file). Default: extract — it's the return type of every event class in the new directory, and an inline class definition in `QualityTimeService.php` is the kind of leftover-cruft that ages badly. Cheap move.
4. **Picker location and visibility.** The new picker logic is small (~5 lines: filter by `isAvailable`, uniform-pick). Keep it as a private method on `QualityTimeService` (replacing `getRandomQualityTimeDescription`) rather than extracting a `QualityTimeEventPicker` class — there's only one caller, and the orchestrator already holds `IRandom`. Default: inline.
5. **What to do with `playTag`'s `public` visibility.** `playTag` is the only `public` event method in the current service; nothing calls it externally that grep finds, so the visibility is almost certainly accidental. After migration, `PlayTagEvent::generate` is public per the interface contract anyway — the question becomes moot. No action required; flagging in case implementer finds an external caller during research.

## Acceptance Criteria
- [ ] Interface `App\Service\QualityTime\QualityTimeEvent` exists with `#[AutoconfigureTag('app.qualityTimeEvent')]`, and declares two methods: `isAvailable(User $user): bool` and `generate(User $user, array $pets): QualityTimeResult`. The `array` parameter on `generate` has a `@param Pet[] $pets` docblock.
- [ ] Every event currently implemented as a private method on `QualityTimeService` exists as its own class in `api/src/Service/QualityTime/` implementing `QualityTimeEvent`. Migrated set: `ReadAStory`, `PlayHideAndSeek`, `PlayTag`, `PracticeTricks`, `BakeCookies`, `BuildAPillowFort`, `PlayCharades`, `StretchTogether`, `SingTogether`, `GoOnAWalk`, `GoFishing`, `ExploreTidePools`, `Stargaze`, `MakeApricotPies`, `CarveGourds`, `DecorateTheHouseForStockingStuffingSeason` (final class names per Open Decision 2).
- [ ] `QualityTimeResult` lives in its own file at `api/src/Service/QualityTime/QualityTimeResult.php` (or unchanged in `QualityTimeService.php` if Open Decision 3 is overridden); all usages updated.
- [ ] `QualityTimeService` no longer contains any of the private flavor methods, no longer contains the if-cascade picker, and no longer references `WeatherService`, `CalendarFunctions`, `WeatherSky`, `UnlockableFeatureEnum`, or any constants used only by flavor methods. Its constructor lists only dependencies actually used by `doQualityTime` + the picker.
- [ ] `QualityTimeService` accepts an `iterable<QualityTimeEvent>` via `#[AutowireIterator('app.qualityTimeEvent')]` in its constructor. The (replacement for) `getRandomQualityTimeDescription` filters that iterable by `$event->isAvailable($user)` into an array, then uniform-picks via `$this->rng->rngNextFromArray(...)` and calls `$picked->generate($user, $pets)`.
- [ ] `api/config/services.yaml` does **not** add `lazy: true` for the QualityTime directory. Whether to add an explicit `App\Service\QualityTime\:` resource block at all is implementer's call — both "no block (rely on `App\:` catch-all)" and "block without `lazy: true`" satisfy this criterion.
- [ ] Per-event odds match the pre-refactor behavior: weather-gated events appear only under the same `WeatherSky` predicates; `MakeApricotPies` appears only during `isApricotFestival`; `CarveGourds` and `DecorateTheHouseForStockingStuffingSeason` retain their 1-in-7 / 1-in-6 inclusion odds within their respective holiday windows (Open Decision 1). `goFishing` is excluded on `Stormy`; `exploreTidePools` is included only on `Clear`/`Cloudy`; `stargaze` and `goOnAWalk` are included only on `Clear`.
- [ ] `composer run php-cs-fixer-dry-run` passes from `api/`.
- [ ] `php vendor/bin/phpstan --configuration=phpstan.dist.neon` passes from `api/`.
- [ ] No event class injects services it doesn't use. (E.g., `StretchTogetherEvent` should not inject `Clock`; `MakeApricotPiesEvent` should.)
- [ ] Each event class's `generate` produces an identical message *shape* to the pre-refactor private method (same opening sentence template, same flavor pool, same `foodBased` value, same conditional appendices like `playHideAndSeek`'s fireplace / basement / cooking-buddy branches, same `bakeCookies` cooking-buddy substitution). Wording may not drift; only the class housing changes.

## Implementation

### 1. Create the interface
Create `api/src/Service/QualityTime/QualityTimeEvent.php`. Mirror `App\Service\PetActivity\IPetActivity` in structure: namespace `App\Service\QualityTime`, attribute `#[AutoconfigureTag('app.qualityTimeEvent')]` from `Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag`. Declare `isAvailable(User $user): bool` and `generate(User $user, array $pets): QualityTimeResult` with a `@param Pet[] $pets` docblock on `generate`. Add a one-line interface docblock pointing readers to `QualityTimeService` for the registry semantics.

### 2. Extract `QualityTimeResult` to its own file
Create `api/src/Service/QualityTime/QualityTimeResult.php` with the same readonly two-field shape as the inline class at the bottom of `QualityTimeService.php` today (`public readonly string $message`, `public readonly bool $foodBased`). Delete the inline class definition. Grep for `QualityTimeResult` across `api/src/` and update `use` statements wherever it appears (likely only `QualityTimeService`).

### 3. `services.yaml` — usually nothing to do
The existing `App\: resource: '../src/'` block plus `autoconfigure: true` plus the interface's `#[AutoconfigureTag]` attribute together tag every implementation automatically. Skip adding an `App\Service\QualityTime\:` block unless there's a concrete reason to (and **do not** add `lazy: true` per Constraints & Gotchas). If implementer prefers an explicit directory block for parity with `App\Service\PetActivity\:` / `App\Service\Holidays\:`, that's fine — just omit `lazy: true`.

### 4. Migrate the always-available events
Create one class per event in `api/src/Service/QualityTime/`, in the order the current `$possibleMessages` literal lists them, so progress is easy to spot-check against the source. For each:
- Class name per Open Decision 2 (`XxxEvent`).
- Constructor injects `IRandom` (and any other dependency the current method uses — see step 7 for the audit list).
- `isAvailable(User $user): bool` returns `true` for always-available events.
- `generate(User $user, array $pets): QualityTimeResult` body is a near-verbatim copy of the corresponding private method from `QualityTimeService`, with `$this->rng` and `$this->em` etc. pointing at this class's constructor-injected dependencies instead.

Always-available batch: `ReadAStoryEvent`, `PlayHideAndSeekEvent`, `PlayTagEvent`, `PracticeTricksEvent`, `BakeCookiesEvent`, `BuildAPillowFortEvent`, `PlayCharadesEvent`, `StretchTogetherEvent`, `SingTogetherEvent`.

### 5. Migrate the weather-gated events
Create `GoFishingEvent`, `ExploreTidePoolsEvent`, `StargazeEvent`, `GoOnAWalkEvent`. Each takes `Clock` in its constructor and implements `isAvailable` by calling `WeatherService::getSky($this->clock->now)` and returning the appropriate predicate per the current cascade in `getRandomQualityTimeDescription`:
- `GoFishingEvent`: `$sky !== WeatherSky::Stormy`.
- `ExploreTidePoolsEvent`: `$sky === WeatherSky::Clear || $sky === WeatherSky::Cloudy`.
- `StargazeEvent`, `GoOnAWalkEvent`: `$sky === WeatherSky::Clear`.

### 6. Migrate the holiday-gated events (with rng preservation)
- `MakeApricotPiesEvent`: `Clock`-injected, `isAvailable` returns `CalendarFunctions::isApricotFestival($this->clock->now)`.
- `CarveGourdsEvent`: `Clock` and `IRandom` injected, `isAvailable` returns `CalendarFunctions::isHalloweenCrafting($this->clock->now) && $this->rng->rngNextInt(1, 7) === 1` (Open Decision 1, default (a)).
- `DecorateTheHouseForStockingStuffingSeasonEvent`: `Clock` and `IRandom` injected, `isAvailable` returns `CalendarFunctions::isStockingStuffingSeason($this->clock->now) && $this->rng->rngNextInt(1, 6) === 1` (Open Decision 1, default (a)).

### 7. Constructor-dependency audit per event class
Read each private method in the current `QualityTimeService.php` and copy *only* the dependencies it actually uses. Quick survey to guide the migration (verify by reading each method during implementation):
- `IRandom`: every event that picks a flavor line — most of them.
- `Clock`: only the four weather-gated and three holiday-gated events.
- `EntityManagerInterface`: none of the flavor methods use it directly; do **not** inject into event classes.
- `WeatherService`: static `getSky` call — no injection needed (it's a static method on the service class).
- `ActivityHelpers`, `ArrayFunctions`, `CalendarFunctions`: all static — no injection.
- `UnlockableFeatureEnum`: enum constants — no injection.
Each event class constructor should be as small as possible. If two events end up with identical constructors that's fine — don't share a base class for this; the PetActivity directory doesn't and it works out fine.

### 8. Rewrite `QualityTimeService::getRandomQualityTimeDescription`
Replace the entire if-cascade body with: collect available events into an array via `array_filter` over `$this->qualityTimeEvents` calling `$event->isAvailable($user)`; pick one via `$this->rng->rngNextFromArray($availableEvents)`; return `$picked->generate($user, $pets)`. Method becomes ~5 lines. Update the constructor: drop `WeatherService` / `CalendarFunctions` / `WeatherSky` / `UnlockableFeatureEnum` imports if nothing else in the file references them. Add `iterable<QualityTimeEvent>` constructor parameter with `#[AutowireIterator('app.qualityTimeEvent')]` and matching `@var` docblock — mirror `PetActivityService`'s declaration verbatim in shape.

### 9. Delete the migrated private methods
Delete every `private function fooBar(User $user, array $pets): QualityTimeResult` method from `QualityTimeService` (and the public `playTag`). The file should shrink dramatically — `doQualityTime` and the new tiny picker are all that remain.

### 10. Lint + static analysis
Run `composer run php-cs-fixer-dry-run` and `php vendor/bin/phpstan --configuration=phpstan.dist.neon` from `api/`. Fix until clean. PHPStan will catch missing constructor wiring, mismatched docblocks, and any forgotten dead `use` statements.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `php vendor/bin/phpstan --configuration=phpstan.dist.neon` (in `api/`) passes.
- [ ] Spot-check Symfony container: `php bin/console debug:container --tag=app.qualityTimeEvent` lists all 16 event classes.
- [ ] Manual: trigger Quality Time several times from the home page on a clear-weather, non-holiday day with a test account that has 1 pet at home; confirm a representative mix of always-available + clear-weather events appears across rolls. Repeat with 3+ pets to confirm multi-pet branches (e.g. `StretchTogetherEvent`'s leader / non-leader split, `PlayCharadesEvent`'s "no one" vs "you" branch) still read naturally.
- [ ] Manual (weather gating regression): on a `Stormy` day, confirm `GoFishingEvent`, `ExploreTidePoolsEvent`, `StargazeEvent`, and `GoOnAWalkEvent` never appear. On `Cloudy`, confirm `ExploreTidePoolsEvent` *can* appear but `StargazeEvent` / `GoOnAWalkEvent` cannot. On `Clear`, all four are eligible. (Use a clock override or wait for matching weather; the existing weather system is deterministic from time.)
- [ ] Manual (holiday gating regression): on an `isApricotFestival` day, confirm `MakeApricotPiesEvent` appears in rotation. On a `isHalloweenCrafting` day, re-roll many times and confirm `CarveGourdsEvent` appears occasionally (not on every roll). On `isStockingStuffingSeason`, same check for `DecorateTheHouseForStockingStuffingSeasonEvent`. Off-season, neither holiday event ever fires.
- [ ] Manual (activity log delivery): confirm each rolled event still writes a `PetActivityLog` per pet, tagged with `PetActivityLogTagEnum::QualityTime`, and that the message text in the activity feed matches the pre-refactor wording for that event (no template drift).
- [ ] Manual (`foodBased` regression): on a `BakeCookiesEvent` / `GoFishingEvent` / `MakeApricotPiesEvent` / `CarveGourdsEvent` roll, confirm pet `food` increases by `floor(hours / 4)` per the `doQualityTime` math. On any other event, confirm pet `food` is unchanged. Other stats (`safety`, `love`, `esteem`, affection) increment regardless.
- [ ] Manual (`playHideAndSeek` branching regression): with a test account that has a cold fireplace, the chimney-hiding line should be eligible. With a basement unlocked, the basement line should be eligible. With a `robot/mega-cooking` cooking buddy, that line should be eligible. With none, only the baseline "exceptional seeker" line should appear.
- [ ] Manual (`buildAPillowFort` fireplace appendix): with a warm fireplace, the `\n\n`-separated fireplace sentence should still append. With cold or no fireplace, it should not.
- [ ] Manual (`playCharades` field-guide expansion): on an account with the `Cosmic Goat` / `Huge Toad` / `Nang Tani` / `Infinity Imp` / `Drizzly Bear` field-guide entries, the corresponding objects should be eligible to mime. On an account without them, they should not.

## Learnings

### Architectural decisions
- **Open Decision 1 (rng odds)**: Took default (a). `CarveGourdsEvent::isAvailable` and `DecorateTheHouseForStockingStuffingSeasonEvent::isAvailable` bake the `1-in-7` / `1-in-6` rng coin into the predicate. Exact odds preserved; weighted-picker (b) deferred until a second weighted event shows up.
- **Open Decision 2 (naming)**: `XxxEvent` suffix used for all 16 classes.
- **Open Decision 3 (`QualityTimeResult` location)**: Extracted to own file `App\Service\QualityTime\QualityTimeResult`. It's the return type of every event class — keeping it as a tail-class inside `QualityTimeService.php` would be cruft.
- **Open Decision 4 (picker location)**: Picker stayed as a 5-line private method `QualityTimeService::pickQualityTimeEvent`. No separate `QualityTimeEventPicker` class — only one caller, orchestrator already holds `IRandom`.
- **Open Decision 5 (`playTag` visibility)**: Old `public function playTag` had no external callers; visibility was accidental. Migrated to `PlayTagEvent::generate` (public per interface) — moot.
- **services.yaml left untouched.** `App\: resource: '../src/'` catch-all plus `autoconfigure: true` plus the interface's `#[AutoconfigureTag]` is enough to tag every implementation. No `App\Service\QualityTime\:` block added — would only have been parity-cosmetics. Verified via `php bin/console debug:container --tag=app.qualityTimeEvent` (all 16 classes listed).
- **No `lazy: true`.** Picker calls `isAvailable` on every tagged event each tick, so a per-class lazy proxy would never pay off. `#[AutowireIterator]` already gives us the laziness that matters: requests that never call `doQualityTime` never iterate.

### Picker iteration
`array_filter([...$this->qualityTimeEvents], ...)` materializes the `RewindableGenerator` into a list, then filters. `Xoshiro::rngNextFromArray` uses `array_slice($array, $offset, 1)[0]`, which is index-agnostic — preserved `array_filter` keys don't trip it.

### Constructor-dependency audit (final)
- `IRandom` only: `BuildAPillowFortEvent`, `PlayCharadesEvent`, `PlayHideAndSeekEvent`, `PlayTagEvent`, `PracticeTricksEvent`, `ReadAStoryEvent`, `SingTogetherEvent`, `StretchTogetherEvent`.
- Zero deps: `BakeCookiesEvent` (no rng — message is deterministic given pets).
- `IRandom` + `Clock`: all four weather-gated (`GoFishingEvent`, `ExploreTidePoolsEvent`, `StargazeEvent`, `GoOnAWalkEvent`), `CarveGourdsEvent`, `DecorateTheHouseForStockingStuffingSeasonEvent`.
- `Clock` only: `MakeApricotPiesEvent` (deterministic message body — no rng inside).

`QualityTimeService` constructor dropped to: `PetExperienceService`, `CravingService`, `EntityManagerInterface`, `UserStatsService`, `IRandom`, and the tagged `iterable`. `Clock` removed (the orchestrator never read it — only the old picker did).

### Related areas
- `PetActivityService` arguably has the same `lazy: true` over-config — its `pickActivity` also iterates and calls `groupDesire` on every impl. Out of scope here; flagged in original ticket constraints.

### Rejected
- Adding `App\Service\QualityTime\:` resource block "for parity" — pure ceremony when auto-discovery already covers it.
- Pre-computing `WeatherSky` once in the picker and threading it through `isAvailable`. Per ticket constraints, `WeatherService::getSky` is cheap; keeping each event self-contained means future events can add their own calendar/weather predicates without picker changes.
