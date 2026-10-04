# Fix: Bird bath collectibles vanish after any greenhouse interaction

## Context
**Current behavior**: When the bird bath has collectible items (Bubblegum and/or Oil), they show on the bird bath in the Greenhouse. As soon as the player performs *any* other greenhouse interaction (harvest, fertilize/care, feed composter, plant a seed, assign a helper), the Bubblegum/Oil graphics and the "Clean" button disappear from view. Reloading the page brings them back. No items are actually lost — it is purely a stale-view bug.

**New behavior**: The bird bath collectibles remain visible after any greenhouse interaction, exactly as they appear on a fresh page load — until the player actually cleans the bird bath. The fix centralizes the `hasBubblegum`/`hasOil` data on the backend so every greenhouse-returning endpoint reports them consistently.

## Root Cause
The Greenhouse component (`greenhouse.component.ts`) renders the collectibles from two flags, `hasBubblegum` and `hasOil`, and `processGreenhouseResponse()` unconditionally assigns them from the response (`this.hasBubblegum = data.hasBubblegum`).

Only `GetGreenhouseController` (`GET /greenhouse`) populates those two keys — it computes them *after* calling `GreenhouseService::getGreenhouseResponseData()`, which itself does not include them. Every other greenhouse endpoint returns `getGreenhouseResponseData()` directly (`HarvestPlantController`, `FertilizePlantController`, `FeedComposterController`, `PlantSeedController`, `AssignHelperController`), so their responses omit `hasBubblegum`/`hasOil`. When the frontend processes one of those responses, `undefined` overwrites the previously-`true` flags, hiding the collectibles until the next full `GET /greenhouse`.

## Scope
### In scope
- Move the bird bath collectible computation into `GreenhouseService::getGreenhouseResponseData()` so the two keys are part of the canonical greenhouse response payload returned by every endpoint.
- Remove the now-duplicated computation (and its `hasItemInBirdbath` helper) from `GetGreenhouseController`.

### Out of scope
- Any frontend change. `greenhouse.component.ts` / `.html` already consume `hasBubblegum`/`hasOil` correctly once the backend always supplies them; do not touch them.
- Changing how the bird bath is cleaned, how collectibles are produced, or the bird bath visiting-bird logic.

## Relevant Docs & Anchors
- **Code anchors (backend)**:
  - `GreenhouseService::getGreenhouseResponseData` (`api/src/Service/GreenhouseService.php`) — the shared response builder to extend.
  - `GetGreenhouseController::getGreenhouse` and its private `hasItemInBirdbath` helper (`api/src/Controller/Greenhouse/GetGreenhouseController.php`) — current home of the computation to relocate.
  - `Greenhouse::getHasBirdBath` (`api/src/Entity/Greenhouse.php`) — gate for whether the keys should be present.
  - `LocationEnum::BirdBath`, `ItemRepository::getIdByName` — used by the existing helper.
- **Code anchors (frontend, read-only context)**:
  - `GreenhouseComponent.processGreenhouseResponse` (`webapp/src/app/module/greenhouse/page/greenhouse/greenhouse.component.ts`) — the consumer that overwrites the flags.
  - The `@if(hasBubblegum)` / `@if(hasOil)` blocks in `greenhouse.component.html`.

## Constraints & Gotchas
- **Preserve the `hasBirdBath` gate.** Today the keys are only added when `greenhouse->getHasBirdBath()` is true. Decide and document the resulting shape (see Open Decisions); whatever shape is chosen, a greenhouse with a bird bath but no collectibles must still hide the graphics/Clean button (i.e. `false`, not "key missing while collectibles exist").
- `getGreenhouseResponseData()` returns a raw array that the controllers hand to `ResponseService::success(..., [groups])` for normalization. The two booleans are plain scalars and need no serialization group — just add them to the returned array.
- `GreenhouseService` already has an injected `EntityManagerInterface`; reuse it for the two `count()` queries rather than passing `$em` around.

## Open Decisions
1. **Key presence when no bird bath** — match current behavior (keys present only when `hasBirdBath`) vs. always emit the two booleans (defaulting to `false` without a bird bath). Default: always emit `false` when there's no bird bath — simpler, and the frontend already gates the whole block on `greenhouse.hasBirdBath`, so extra `false` keys are harmless and make the contract uniform.
2. **Helper placement** — inline the two `count()` queries in `getGreenhouseResponseData()` vs. keep a private `hasItemInBirdbath`-style helper on the service. Default: private helper on the service, mirroring the existing controller helper.

## Acceptance Criteria
- [ ] `GreenhouseService::getGreenhouseResponseData()` includes `hasBubblegum` and `hasOil` boolean entries in its returned array.
- [ ] `GetGreenhouseController` no longer computes `hasBubblegum`/`hasOil` itself and no longer defines a private bird bath inventory helper; its response still carries both keys (now via the service).
- [ ] For a greenhouse with a bird bath holding Bubblegum and/or Oil, the response from `harvest`, `fertilize`, `feed composter`, `plant seed`, and `assign helper` endpoints each include `hasBubblegum`/`hasOil` reflecting the actual bird bath contents.
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass in `api/`.

## Implementation

### 1. Add bird bath collectible flags to the shared response builder
In `GreenhouseService::getGreenhouseResponseData()`, extend the returned array with `hasBubblegum` and `hasOil` booleans. Compute each by counting `Inventory` rows for the user at `LocationEnum::BirdBath` matching the item id for `'Bubblegum'` / `'Oil'` (mirror the logic currently in `GetGreenhouseController::hasItemInBirdbath`). Gate on `$user->getGreenhouse()->getHasBirdBath()` per Open Decision 1 (default: emit `false` when there is no bird bath). Prefer a private helper on the service for the per-item count (Open Decision 2).

### 2. Remove the duplicated computation from `GetGreenhouseController`
Delete the post-normalization block that sets `$data['hasBubblegum']`/`$data['hasOil']` and the private `hasItemInBirdbath` method. The keys now arrive from the service via the normalized response. Drop any imports left unused by the deletion (e.g. `Inventory`, `LocationEnum`, `ItemRepository`, the `User` type-hint on the removed helper) only if nothing else in the file uses them.

### 3. Verify the response shape is unchanged for the GET endpoint
Confirm `GET /greenhouse` still returns the same JSON keys it did before (the two booleans now originate from the service rather than the controller), so the frontend sees no contract change on initial load.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass in `api/`.
- [ ] In-game: with a bird bath that has Bubblegum and/or Oil to collect, harvest a plant (or fertilize/feed composter/plant a seed/assign a helper) and confirm the Bubblegum/Oil graphics and the "Clean" button stay visible afterward (no reload needed).
- [ ] Click "Clean"; confirm the collectibles disappear and stay gone after a subsequent unrelated interaction.
- [ ] Reload the page in both states (with and without collectibles) and confirm the view matches the pre-reload state.
- [ ] Regression: in a greenhouse *without* a bird bath, confirm no errors and the Add-ons section renders as before.

## Learnings

### Architectural decisions
- **Open Decision 1 (key presence)** — resolved to the default: `hasBubblegum`/`hasOil` are now *always* emitted as booleans, defaulting to `false` when there is no bird bath. This makes the response contract uniform across every greenhouse endpoint and avoids the original "key missing → `undefined` overwrites `true`" stale-view bug.
- **Open Decision 2 (helper placement)** — resolved to the default: a private `hasItemInBirdBath(User, string): bool` helper on `GreenhouseService`, mirroring the controller helper it replaced, using the service's already-injected `EntityManagerInterface`.

### Problems encountered
- **Stale PHPStan baseline entries.** `getGreenhouseResponseData()` already had a baselined `return.type` mismatch (`greenhouse: Greenhouse` vs `Greenhouse|null`). Adding the two keys changed the inferred "but returns …" shape, so the baseline pattern stopped matching and PHPStan failed with `ignore.unmatched`. Fix: update that baseline message to include `, hasBubblegum\: bool, hasOil\: bool` before the closing brace.
- Removing the controller's `$data['hasBubblegum']` offset access also orphaned a second baseline entry (`offsetAccess.nonOffsetAccessible` on `GetGreenhouseController`). That entry had to be deleted, not edited — the error no longer occurs at all.
- **Lesson:** when you change a method's return-array shape or delete code, grep `phpstan-baseline.neon` for the symbol/offset and reconcile stale entries; an unmatched ignore is itself a (non-ignorable) error.

### Interesting tidbits
- The two booleans are plain scalars passed *into* `$normalizer->normalize(...)` rather than tacked on afterward. The Symfony serializer passes scalars through untouched regardless of serialization groups, so no group annotation is needed — the GET endpoint's JSON shape is unchanged.
- `getGreenhouseResponseData()` is the single shared builder behind all six greenhouse endpoints (GET, harvest, fertilize, feed composter, plant seed, assign helper). Centralizing the flags there fixes every mutating endpoint at once.

### Related areas affected
- None beyond the greenhouse response. No frontend change was needed; `greenhouse.component.ts` already gates the whole bird bath block on `greenhouse.hasBirdBath`, so the always-`false` keys are harmless.
