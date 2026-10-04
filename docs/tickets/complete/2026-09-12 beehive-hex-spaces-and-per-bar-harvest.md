# Beehive: Hex Spaces & Per-Bar Harvest

## Context
**Current behavior**: The Beehive has three progress bars (Royal Jelly, Honeycomb, Normal Bee Stuff) and one combined **Harvest** button that cashes in every full bar at once. Honeycomb harvests grant +6 Basement size. A helper pet has no bar of its own; its hunt/gather reward piggybacks on the Normal Bee Stuff harvest.

**New behavior**: Each bar gets its own Harvest button, and harvesting means *deploying* that bar onto one space of a 19-hex grid whose terrain (jungle / beach / grassy / rocky) decides the base reward. A helper pet gets a fourth bar that fills at Honeycomb speed and deploys with the existing helper hunt/gather logic. Each space can be harvested once per cycle; harvesting the 19th space resets the grid and grants +60 Basement size (replacing the old +6-per-Honeycomb).

## Scope
### In scope
- `Beehive` entity: 19-space terrain grid (non-nullable JSON column) + `helperProgress` column; migration with a fixed backfill layout.
- Cron (`BuzzBuzzCommand`): grow the helper bar alongside Honeycomb.
- `HarvestController` rewrite: one endpoint, parameterized by bar + space; terrain-based reward tables; cycle reset + Basement bonus.
- `PetAssistantService::stopAssisting`: reset the helper bar.
- Beehive page: four bars with individual Harvest buttons; new hex-grid component with choose-a-space mode.

### Out of scope
- Gold Compass re-roll of terrain types (separate ticket: `beehive-gold-compass-reroll`).
- Poppyopedia beehive help page copy (separate ticket: `beehive-help-page-hex-spaces`).
- Removing the apparently-dead `Beehive/DiceController` (spin-off; leave it alone here).
- New proprietary art for terrains - Font Awesome icons + CSS tints only.
- Any change to Flower Power, worker growth, feeding, or the Royal Jelly / Honeycomb / misc growth rates.

## Relevant Docs & Anchors
- **Analogue tickets**: `docs/tickets/complete/2026-06-19 quality-time-pillow-fort.md` (small feature touching service + enum shape); `docs/tickets/complete/2026-05-16 pet-species-ulid-primary-key.md` (multi-step schema migration with backfill).
- **Code anchors**: `Beehive` entity; `HarvestController::harvest` (the whole reward + helper block is the source material for the new per-bar logic); `BuzzBuzzCommand::execute` (raw-SQL growth); `PetAssistantService::stopAssisting` (sole `setHelper(null)` site for the beehive); `BeehiveService::createBeehive`; `FeedController::feedItem` (endpoint shape, flash messages, `PlayerLogFactory` usage); `MyBeehiveSerializationGroup` (webapp model); `BeehiveComponent` (page + `postInteraction` helper); `HollowEarthMoveDirectionEnum` (backed-enum shape); `Dragon::$greetings` (JSON column shape).
- **Request DTO pattern**: `docs/architecture/Project Patterns.md §Use #[MapRequestPayload] for Request DTOs`; exemplar `ApplyAuraController` + its request class.

## Constraints & Gotchas
- **Balance shift is intentional**: measured against today's +6/Honeycomb, the +60/clear is roughly a 2x faster Basement growth when all four bars are harvested promptly. Do not "fix" it.
- **Existing beehives**: the `spaces` column is `NOT NULL`. MySQL can't put a literal default on a JSON column, so the migration must add nullable → `UPDATE` all rows → `MODIFY ... NOT NULL`. Every existing beehive receives the *same* fixed layout containing exactly 7 jungle / 5 beach / 4 grassy / 3 rocky.
- **New beehives roll per-space** with weights 7/5/4/3 (i.e. each of the 19 spaces is an independent weighted draw; counts will drift from 7/5/4/3 - that's expected). Use the injected `IRandom`, not `mt_rand`/`ArrayFunctions::pick_one_weighted` (the former is what the rest of the beehive code seeds through). Drawing `rngNextFromArray` from a 19-entry bag is an acceptable way to express the weights.
- **Basement bonus keeps its guard**: only `if($user->hasUnlockedFeature(UnlockableFeatureEnum::Basement))`, exactly as the old +6 did; `User::increaseBasementSize` already clamps to `MaxBasementSize`.
- **Spice roll carries over, plus rain**: for every *base-reward* item that is Crooked Stick or food (all bee types; not Royal Jelly, not helper rewards - those go through `petCollectsItem`, unchanged): if it's raining (`WeatherService::getWeather(new \DateTimeImmutable())->isRaining()`, as `WeedController` does), 1-in-3 → `Rain-scented`; otherwise (or if that roll misses) the existing 1-in-20 → `of Queens`, else `Anthophilan`.
- **Reward lists are methods, not constants**: `BeehiveService::getGoodsForTerrain(BeehiveSpaceTypeEnum $type): string[]` is a `match` routing to `getJungleGoods()` / `getBeachGoods()` / `getGrassyGoods()` / `getRockyGoods()`; each returns item names and may consult `WeatherService::getWeather(...)->isHoliday(HolidayEnum::…)` to append seasonal entries. Draw with the injected `IRandom` (`rngNextFromArray`).
- **Helper reward text/logs/badge stay as-is**: the `PetActivityLogFactory` log, `Add_on_Assistance` + `Beehive` tags, and the `BeeNana` badge on `Naner` must survive the move from the misc branch to the helper bar.
- **C# migration prep**: terrain type is a PHP backed enum; the per-space shape is a small typed model (not loose arrays passed around the controller).
- The `helperPet` serialization group is unchanged; only the `myBeehive` group grows.

## Decisions (resolved 2026-09-12)
1. **Base reward lists per terrain** (every draw is uniform over the list; duplicates = extra weight). All item names verified in `db/seed/base.sql`:
   - Jungle: Sugar, Glue, Crooked Stick, Honeycomb, Antenna, Cacao Fruit, Chanterelle; **+ Apricot** during `HolidayEnum::ApricotFestival`
   - Beach: Sand Dollar, Feathers, Seaweed, Crooked Stick, Scales
   - Grassy: Sugar, Sweet Beet, Honeycomb, Fluff, Moth, Rosemary; **+ 1-leaf Clover** during `HolidayEnum::SaintPatricks`
   - Rocky: Silica Grounds, Crooked Stick, Rock Candy
2. **Space storage shape**: one JSON array of 19 `{ "type": "jungle", "harvested": false }` objects, index 0..18 row-major over rows of 3/4/5/4/3.
3. **"Double base reward"**: two independent draws from the terrain list, each spice-rolled separately.
4. **Hex CSS**: flex rows (3/4/5/4/3) + `clip-path: polygon(...)` hexagons; must render cleanly at ~400px width.
5. **Endpoint shape**: `POST /beehive/harvest` with a `{ bar, space }` DTO (one `#[MapRequestPayload]` validation path).

## Acceptance Criteria
- [ ] `Beehive` has a non-nullable JSON `spaces` column holding 19 entries, each a terrain type (`jungle` | `beach` | `grassy` | `rocky`) and a harvested flag; `BeehiveService::createBeehive` populates it with weighted-random types (7/5/4/3).
- [ ] After the migration, every pre-existing beehive row has the same 19-space layout with exactly 7 jungle, 5 beach, 4 grassy, 3 rocky, all unharvested.
- [ ] `Beehive` has a `helper_progress` float column; the hourly cron grows it by the same amounts as Honeycomb (`LOG(workers)*2` always, `+LOG(workers)*3` while working) only for beehives with a helper.
- [ ] `PetAssistantService::stopAssisting` on a beehive helper resets `helperProgress` to 0.
- [ ] `GET /beehive` (`myBeehive` group) exposes the spaces, a `helperPercent` (0..1, same rounding as the other percents), and the existing three percents.
- [ ] A harvest request names one bar (royalJelly / honeycomb / misc / helper) and one space index 0..18; it is rejected (`PSPInvalidOperationException` or form-validation exception) when the bar is below 100%, the space is already harvested, the index is out of range, or `helper` is requested with no helper assigned.
- [ ] On success the named bar resets to 0 and the space is marked harvested. Rewards: misc = one base reward from the space's terrain table; honeycomb = double base reward; royalJelly = one Royal Jelly and no base reward; helper = the existing hunt/gather (`GREEN_THUMB` two-item variant included) helper reward and no base reward.
- [ ] Base-reward items that are Crooked Stick or food receive a spice: `Rain-scented` 1-in-3 when raining, otherwise `of Queens` 1-in-20, else `Anthophilan`.
- [ ] `BeehiveService::getGoodsForTerrain` returns the Decision 1 lists; Jungle includes Apricot only during the Apricot Festival and Grassy includes 1-leaf Clover only on Saint Patrick's (both via `WeatherService::getWeather(...)->isHoliday(...)`).
- [ ] Harvesting the last unharvested space clears every harvested flag (terrain types unchanged) and, if the Basement is unlocked, grants +60 Basement size; no other harvest changes Basement size.
- [ ] Beehive page shows a Harvest button per bar (helper bar only when a helper is assigned), each enabled only when its bar is at 100%; clicking one puts the hex grid into choose-a-space mode where only unharvested spaces are clickable.
- [ ] The hex grid renders 19 spaces in rows of 3/4/5/4/3 with a distinct Font Awesome icon + tint per terrain and a visibly "spent" style for harvested spaces.
- [ ] `php vendor/bin/phpstan` and `composer run php-cs-fixer-dry-run` pass; `ng build` passes.

## Implementation

### 1. Terrain enum + space model
Give the four terrains a home the C# port can mirror. Add `App\Enum\BeehiveSpaceTypeEnum` (string-backed, mirror `HollowEarthMoveDirectionEnum`; values `jungle`, `beach`, `grassy`, `rocky`) with a static helper that returns the 7/5/4/3 weight for each case. Add a small model (`App\Model\BeehiveSpace` or similar) with `type` + `harvested` and `toArray`/`fromArray` (or equivalent) so the entity stores JSON but the controller works with typed objects.

### 2. Entity changes
On `Beehive`: add `spaces` (`type: 'json'`, **not** nullable, `myBeehive` group) and `helperProgress` (`float`, default 0, mirror `honeycombProgress`). Add `getHelperPercent()` in the `myBeehive` group using the same `/2000` + `round(…, 2)` + `min(1, …)` shape as `getHoneycombPercent()`. Add typed accessors: get all spaces, get one by index, mark harvested, reset all harvested flags, count unharvested, and a `rollSpaces(IRandom)` (or have `BeehiveService` do the roll - implementer's call) that fills 19 weighted draws. Constructor must leave `spaces` populated (call the roll from `BeehiveService::createBeehive`, which already receives `IRandom`).

### 3. Migration
New `DoctrineMigrations` version under `api/migrations/2026/09/`: `ALTER TABLE beehive ADD helper_progress DOUBLE PRECISION NOT NULL DEFAULT 0` (drop the default after, or keep it - Doctrine's diff will tell you; match the other progress columns); `ADD spaces JSON NULL`; `UPDATE beehive SET spaces = '<fixed 19-entry layout>'`; `ALTER TABLE beehive MODIFY spaces JSON NOT NULL`. The fixed layout is a hand-written array with exactly 7 jungle / 5 beach / 4 grassy / 3 rocky, all `harvested: false`; arrange it so no terrain forms one solid block (aesthetic only). Run `php bin/console doctrine:schema:validate` afterward to confirm the mapping matches.

### 4. Cron growth for the helper bar
In `BuzzBuzzCommand::execute`, extend both `UPDATE beehive SET …` statements: add `helper_progress = helper_progress + LOG(workers) * 2` to the unconditional statement and `+ LOG(workers) * 3` to the working-only one, but only where `helper_id IS NOT NULL`. Simplest is two extra `UPDATE`s with `WHERE helper_id IS NOT NULL` (and the flower-power condition on the second) rather than `CASE` expressions inside the existing ones.

### 5. Reset the helper bar on recall
In `PetAssistantService::stopAssisting`, the `PetLocationEnum::BEEHIVE` branch: alongside `setHelper(null)`, set helper progress to 0. This is the only path that removes a beehive helper (verified), so no other site needs touching.

### 6. Harvest request DTO
Add a request class (mirror `ApplyAuraController`'s DTO) with `bar` (string, constrained to the four names - a small `BeehiveBarEnum` or constant list) and `space` (int 0..18). Map it with `#[MapRequestPayload]` in the controller.

### 7. Rewrite `HarvestController::harvest`
Keep the unlock guard, `ResponseService` return with `MY_BEEHIVE` + `HELPER_PET` groups, and the flash-message shape ("You received …" / basement variant with the four `$howNice` strings). Replace the three `if(percent >= 1)` blocks with:
1. Resolve the bar; throw if not full, or if `helper` and `$beehive->getHelper()` is null.
2. Resolve the space; throw if out of range or already harvested.
3. Reset the bar's progress to 0 and mark the space harvested.
4. Reward by bar: `misc` → one draw from `BeehiveService::getGoodsForTerrain($space->type)`; `honeycomb` → two independent draws (Decision 3); `royalJelly` → `receiveItem('Royal Jelly', …)`; `helper` → move the existing helper block verbatim (both the `GREEN_THUMB` two-item branch and the hunt/gather branch, log, tags, `BeeNana` badge).
5. Apply the spice loop (rain 1-in-3 `Rain-scented`, else 1-in-20 `of Queens`, else `Anthophilan`) to the base-reward `Inventory` objects only; fetch the weather once per request.
6. If no unharvested spaces remain: reset all harvested flags and, when Basement is unlocked, `increaseBasementSize(60)`. Remove the old `increaseBasementSize(6)`.
7. Record a `PlayerLogFactory::create` entry tagged `Beehive` (as `FeedController` does) naming the bar, terrain, and items - optional but cheap.

Reward lists live in `BeehiveService` (`getGoodsForTerrain` + the four per-terrain methods), not inline in the controller.

### 8. Webapp model + page
Extend `MyBeehiveSerializationGroup` with `helperPercent` and `spaces: { type: 'jungle'|'beach'|'grassy'|'rocky'; harvested: boolean }[]`. In `BeehiveComponent`:
- Render four `app-progress-bar`s (helper bar only `@if(beehive.helper)`, label like `'Helper (' + name + ')'` with the same ready/producing suffix logic); replace the single Harvest button with one button per bar, disabled unless that bar's percent is `>= 1`.
- Clicking a bar button sets a `deploying: 'royalJelly'|'honeycomb'|'misc'|'helper'|null` state and shows a prompt line ("Choose a space to deploy the … on."); clicking an unharvested hex while deploying calls `postInteraction('harvest', { bar, space })` and clears `deploying`. A cancel affordance (second click on the same bar button or an explicit Cancel) clears it too.
- Keep `postInteraction`'s existing success/error handling; the response already returns the updated beehive.

### 9. Hex grid component
New standalone component under `webapp/src/app/module/beehive/component/` (e.g. `beehive-spaces`): inputs `spaces` and `selectable: boolean`; output `spaceChosen: number`. Render rows of 3/4/5/4/3 from the flat 19-array (row-major). Each hex is a `<button>` (disabled when harvested or not selectable) styled via `clip-path` hexagon + terrain tint, with an icon per terrain (`fa-tree` jungle, `fa-umbrella-beach` beach, `fa-seedling` grassy, `fa-mountain` rocky - confirm the icon names exist in the bundled Font Awesome set; `fa-regular fa-bee` is already used on this page, so check that style's availability). Harvested spaces render dimmed with a check/strike marker. Must fit at ~400px width. Declare it in `BeehiveModule`.

## Test Plan
- [ ] `cd api && php vendor/bin/phpstan && composer run php-cs-fixer-dry-run`; `php bin/console doctrine:migrations:migrate` on the local DB then `doctrine:schema:validate` reports in sync.
- [ ] After migrating, `SELECT spaces FROM beehive LIMIT 3` shows identical 19-entry layouts; count the types: 7/5/4/3.
- [ ] `php bin/console app:buzz-buzz` twice on a beehive with a helper: `helper_progress` and `honeycomb_progress` advance by the same delta; a beehive without a helper stays at 0.
- [ ] Load `/beehive` in the webapp: four bars visible with a helper assigned, three without; hex grid renders 3/4/5/4/3 at desktop and ~400px widths.
- [ ] Set `misc_progress = 2000` in the DB, reload, click the Normal Bee Stuff Harvest, pick a jungle hex: receive one jungle-list item (spiced if Crooked Stick/food), bar resets, hex shows spent, other bars untouched.
- [ ] Temporarily force `isRaining()` true (or pick a rainy hour): repeated misc harvests produce `Rain-scented` roughly 1 in 3; force the Apricot Festival / Saint Patrick's date: Apricot appears from jungle hexes / 1-leaf Clover from grassy hexes, and never outside those dates.
- [ ] Set `honeycomb_progress = 2000`, harvest onto a beach hex: two beach-table items; Basement size unchanged.
- [ ] Set `royal_jelly_progress = 2000`, harvest: exactly one Royal Jelly, no base item.
- [ ] With a helper assigned and `helper_progress = 2000`, harvest: helper's activity log appears as a flash message, item lands in the pet's inventory, no base item. Take the helper home; `helper_progress` is 0 and the bar disappears.
- [ ] Try to harvest an already-harvested space and an out-of-range index via the API directly: both rejected with a user-facing error, no state change.
- [ ] Mark 18 spaces harvested in the DB, harvest the last one: all spaces reset to unharvested, terrain types unchanged, flash message mentions the Basement, `basement_size` +60 (or capped at max). Repeat with Basement not unlocked on a test account: no size change.

## Learnings

### Architectural decisions
- **Roll lives in the enum + service, not the entity.** `BeehiveSpaceTypeEnum::roll(IRandom)` expresses the 7/5/4/3 weights as a 19-entry bag; `BeehiveService::rollSpaces()` builds 19 fresh `BeehiveSpace`s and `Beehive::__construct` takes them as a required arg, so the entity never exists with an empty grid and never needs an `IRandom`.
- **Typed model with serializer groups.** `BeehiveSpace` is a tiny `final class` (`type` enum + `harvested`), stored via `toArray`/`fromArray`. `Beehive::getSpaces()` (not the raw property) carries `#[Groups(['myBeehive'])]`, and the model's promoted props carry the same group, so the serializer emits `{type, harvested}` objects with the enum flattened to its string value. The entity's mutators (`markSpaceHarvested`, `resetHarvestedSpaces`, `countUnharvestedSpaces`) work on the raw array to avoid round-tripping.
- **Bar is a backed enum in the request DTO.** `HarvestRequest { BeehiveBarEnum $bar; int $space }` - Symfony's serializer rejects unknown bar strings with a 422 before the controller runs; range/harvested/helper checks stay in the controller so they get user-facing messages.
- **`getHelperProgress()` was dropped** - nothing reads it, and phpstan flags `int` getters on `float` columns (the sibling getters are baselined).
- **Flash message backticks removed.** The old Basement message had a stray `` -` ... !` `` pair that read like a typo; the rewrite uses plain ASCII.

### Problems encountered
- **Cron ordering matters.** The helper bar's "+3 while working" statement must run *before* the existing goods statement, because that statement subtracts `LOG(workers)` from `flower_power` and the working check would otherwise see the post-decrement value (a hive between 1x and 2x `LOG(workers)` would get Honeycomb's bonus but not the helper's).
- **`make:migration` picked up local drift.** Ten migrations recorded in the local `doctrine_migration_versions` exist on no branch, so the generated file also wanted to drop `dream`/`library`/`song`/`pet_activity_log_pet`/`user_unlocked_song` and two columns. Trimmed to the beehive `ALTER` (user's call); the drift itself is a local-DB cleanup, tracked outside this ticket. See `api/migrations/CLAUDE.md`.
- **`ADD spaces JSON NOT NULL` on a populated table works** in MySQL 8 - existing rows get JSON `null` (not SQL NULL), so no add-nullable/modify dance was needed; the generated single `ALTER` plus a hand-added backfill `UPDATE` is the whole migration.
- **Git Bash mangles `/beehive` into `C:/Program Files/Git/beehive`** when passed as an argv to PHP. Pass paths without the leading slash and re-add it in the script.
- **PHP heredoc-in-bash-heredoc**: an unquoted bash heredoc eats `$this` in embedded PHP. Use `<<'EOF'` or the Write tool for PHP that contains `$`.

### Interesting tidbits
- Symfony's exception subscriber maps `#[MapRequestPayload]` validation failures to a generic 422 message; controller-level `PSPInvalidOperationException` gives the friendlier text.
- `Inventory::ConsumableLocations` = Home + Basement; Wardrobe (3) is where pet-held tools live, so `findOneToConsume` correctly ignores them.
- In-process API testing without a browser: boot `App\Kernel`, persist a `UserSession` for a test user (user 704 was used - has a helper, isn't the maintainer's account), and `$kernel->handle(Request::create(...))` with a `Bearer` header. Users whose home pets have 60+ activity minutes get `PSPHoursMustBeRun` first.

### Related areas affected
- All beehive controllers now return `BeehiveService::getResponseData()` (`{ beehive, canReroll }`) - done here rather than in the re-roll ticket because the webapp model had to change anyway.
- `FeedController` was checking the *Fireplace* unlock; fixed to Beehive.
- `db/seed/base.sql` is untouched by this work (a pre-existing local diff to `field_guide_entry.action_requirements` was already in the tree).

### Rejected alternatives
- `CASE` expressions inside the existing cron `UPDATE`s - two extra `UPDATE ... WHERE helper_id IS NOT NULL` statements are simpler and were what the ticket suggested.
- `Assert\Range` on the DTO's `space` - would produce the generic 422 text; the controller's own check gives a real message.
- Storing `spaces` as a `#[Groups]` raw array property - would have worked, but the typed `getSpaces()` path keeps the C# port's shape (a list of typed space objects) visible in PHP.
