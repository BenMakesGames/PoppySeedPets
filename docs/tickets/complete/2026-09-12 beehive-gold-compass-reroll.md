# Beehive: Gold Compass Re-roll

## Context
**Current behavior**: (After `beehive-hex-spaces-and-per-bar-harvest`.) A beehive's 19 terrain spaces are rolled once, when the beehive is created or backfilled by migration, and never change.

**New behavior**: From the Beehive page, a player holding a Gold Compass can consume it to re-roll every space's terrain type (independent weighted draws, 7/5/4/3, same as a new beehive). Harvested flags are untouched. No cooldown, no limit - one compass per re-roll, as often as they like.

## Prerequisites
- `beehive-hex-spaces-and-per-bar-harvest` (terrain enum, `spaces` column, roll helper, hex-grid component).

## Scope
### In scope
- `POST /beehive/reroll` endpoint that consumes one Gold Compass and re-rolls terrain types.
- Beehive page: a re-roll button shown when the player owns at least one Gold Compass in a consumable location.

### Out of scope
- Resetting harvested flags (deliberately not - it would let players buy extra harvests per cycle).
- Any `use_actions` entry on the Gold Compass item itself; the action lives on the beehive page only.
- Explaining the re-roll on the Poppyopedia beehive help page - it stays a "More?!" surprise (see `beehive-help-page-hex-spaces`).
- Consuming a compass a pet is holding/wearing - Home/Basement only.

## Relevant Docs & Anchors
- **Code anchors**: `InventoryHelpers::findOneToConsume` (finds one item by name in `Inventory::ConsumableLocations`, i.e. Home + Basement); `FeedController::feedItem` (beehive endpoint shape: unlock guard, `PlayerLogFactory::create` tagged `Beehive`, flash message, `ResponseService::success` with `MY_BEEHIVE` + `HELPER_PET`); `BeehiveService` roll helper from the prerequisite ticket; `BeehiveComponent::postInteraction`.
- Gold Compass is item id 674, an equippable tool (no `use_actions`); it's the crafted-from-gold compass, distinct from `Compass` and `Enchanted Compass`.

## Constraints & Gotchas
- Re-roll must reuse the *same* weighted roll as `createBeehive` - don't duplicate the weights. A run of bad luck (e.g. 12 jungle) is acceptable by design.
- Harvested flags survive the re-roll; a harvested space may change terrain, which is fine.
- The webapp needs to know whether the player has a compass; see Decisions.
- `ResponseService` already sets `reloadInventory` when items are removed via `$em->remove` - confirm the compass disappears from the player's inventory view without a manual reload (mirror what `FeedController` relies on).

## Decisions
1. **How the page learns "you have a compass"**: wrapped payload. Add `BeehiveService::getResponseData(User): array` returning `[ 'beehive' => $beehive, 'canReroll' => bool ]`, and have every beehive controller that returns the hive (`GetController`, `FeedController`, `HarvestController`, `AssignHelperController`, `RerollController`) return that wrapper. This mirrors `HollowEarthService::getResponseData()` (player + map + dice). Rejected: a non-persisted flag on the `Beehive` entity (request state on an entity; silently `false` if a controller forgets to set it) and a separate inventory query from the webapp (count drifts after a re-roll).
2. **Confirm dialog**: yes. Use the existing `AreYouSureDialog.open(this.matDialog, title, message)` (see `inventory-details.dialog.ts` and `greenhouse.component.ts`), which emits a `boolean`. Message: "Consume 1 Gold Compass and re-roll all 19 spaces? Harvested spaces stay harvested." This is also the only place the harvested-flag rule is explained to the player (the help page deliberately doesn't mention it).

## Acceptance Criteria
- [ ] `POST /beehive/reroll` removes exactly one Gold Compass from Home/Basement and re-rolls all 19 terrain types with the 7/5/4/3 weighted draw; harvested flags are unchanged; response is the updated beehive.
- [ ] The endpoint throws a user-facing exception (no state change) when the player has no Gold Compass in a consumable location or has not unlocked the Beehive.
- [ ] Repeated calls each consume one more compass; there is no cooldown or cap.
- [ ] The Beehive page shows a re-roll button only when the player owns a Gold Compass in a consumable location; after a successful re-roll the hex grid updates in place and the button disappears if that was the last compass.
- [ ] A `Beehive`-tagged player activity log entry records the re-roll.
- [ ] `php vendor/bin/phpstan` and `composer run php-cs-fixer-dry-run` pass; `ng build` passes.

## Implementation

### 1. Re-roll endpoint
New `App\Controller\Beehive\RerollController` with `#[Route("/reroll", methods: ["POST"])]` under the `/beehive` prefix. Mirror `FeedController::feedItem`'s guard (note: `FeedController` checks the *Fireplace* unlock by mistake - use `UnlockableFeatureEnum::Beehive` here, and feel free to fix `FeedController` in passing). Find a compass via `InventoryHelpers::findOneToConsume($em, $user, 'Gold Compass')`; throw `PSPInvalidOperationException` if null. `$em->remove()` it, call the shared roll helper on the beehive (types only), `PlayerLogFactory::create` an entry tagged `Beehive`, flush, add a flash message ("The needle spins... and the bees rearrange themselves!" or similar - keep ASCII), return the beehive with `MY_BEEHIVE` + `HELPER_PET`.

### 2. Expose compass availability
Per Decision 1: add `BeehiveService::getResponseData(User $user): array` returning `[ 'beehive' => $user->getBeehive(), 'canReroll' => InventoryHelpers::findOneToConsume($em, $user, 'Gold Compass') !== null ]` (or a cheaper count/exists query if `findOneToConsume` hydrates more than needed). Change `GetController`, `FeedController`, `HarvestController`, `AssignHelperController`, and the new `RerollController` to `return $responseService->success($beehiveService->getResponseData($user), [ MY_BEEHIVE, HELPER_PET ])`. The serialization groups still apply to the nested `beehive`.

### 3. Webapp
Add a `MyBeehiveResponse` (or similar) interface `{ beehive: MyBeehiveSerializationGroup; canReroll: boolean }` and update `BeehiveComponent`'s load and `postInteraction` success handlers to unpack it (`this.beehive = data.beehive; this.canReroll = data.canReroll`). Add a "Re-roll spaces (uses 1 Gold Compass)" button near the hex grid, visible only when `canReroll` is true and not while `deploying`. On click, open `AreYouSureDialog` with the Decision 2 message; if it emits `true`, call `postInteraction('reroll')`. The existing success handler replaces `this.beehive`, which refreshes the grid, and `canReroll` from the same response hides the button after the last compass.

## Test Plan
- [ ] `cd api && php vendor/bin/phpstan && composer run php-cs-fixer-dry-run`; `ng build` in `webapp/`.
- [ ] With no Gold Compass: Beehive page shows no re-roll button; `POST /beehive/reroll` via the API returns a user-facing error and the `spaces` column is unchanged.
- [ ] Give the test account two Gold Compasses at Home; mark a few spaces harvested in the DB. Click re-roll, confirm: the compass count drops to one, terrain types change (compare `spaces` before/after), harvested flags identical, grid re-renders without a page reload, the button is still visible.
- [ ] Re-roll again: second compass consumed, button disappears.
- [ ] Put the only Gold Compass in a pet's hands (tool slot): button hidden, endpoint rejects.
- [ ] Journal/player log shows a Beehive-tagged re-roll entry.

## Learnings

### Architectural decisions
- `BeehiveService::rerollSpaceTypes(Beehive)` maps the existing spaces to new `BeehiveSpace`s via the same `BeehiveSpaceTypeEnum::roll()` used by `rollSpaces()`, carrying each `harvested` flag across - one weight table, two callers.
- `getResponseData()` throws `PSPNotUnlockedException` if the user has no beehive, which lets it return a non-nullable `beehive` (phpstan otherwise rejects the array shape).
- Page UI diverged from the ticket at the user's direction: instead of a "Re-roll spaces" button, a `compass` section (item graphic + "Use a Gold Compass to find new land." + "Do it!") sits under the grid, hidden while deploying. The `AreYouSureDialog` copy is as decided.

### Problems encountered
- **The ticket's `reloadInventory` premise was wrong.** Nothing in `ResponseService` reacts to `$em->remove()`; `FeedController` removes flowers without setting the flag either. `RerollController` calls `$responseService->setReloadInventory()` explicitly, as `SellController` does. (FeedController's missing flag is pre-existing and left alone.)

### Test notes
- Verified via in-process kernel requests on user 704: no compass -> 422 with message; compass in Wardrobe (location 3) -> `canReroll` false and endpoint rejects; compass in Basement (1) -> accepted; two compasses -> two re-rolls, 11/19 types changed on the first, harvested indexes identical, third call rejected; `reloadInventory: true` in the response; `Beehive`-tagged player log written.
