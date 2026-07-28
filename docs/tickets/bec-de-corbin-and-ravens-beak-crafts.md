# Bec de Corbin & Raven's Beak Pet Crafts

## Context
**Current behavior**: The `Bec de Corbin` (item 1526) and `Raven's Beak` (item 1527) exist in the database — added by migration `Version20260728023546`, complete with tool stats, grammar, item-group membership, and artwork — but nothing in the game can produce either one. They are unobtainable.

**New behavior**: Pets can make both. A pet smithing at home turns an `Iron Bar` and a `Hunting Spear` into a `Bec de Corbin`; a pet doing mundane crafting then turns that `Bec de Corbin`, plus `Glue` and `Black Feathers`, into a `Raven's Beak`. Two new craft methods and two new `possibilities()` gates — no data, schema, or frontend changes.

## Prerequisites
- Migration `Version20260728023546` applied (already committed at `api/migrations/2026/07/Version20260728023546.php`). Both items and their artwork must exist before either craft can resolve an item by name.

## Scope
### In scope
- One new method on `IronSmithingService` (Bec de Corbin) plus its gate in `SmithingService::possibilities()`.
- One new method on `CraftingService` (Raven's Beak) plus its gate in `CraftingService::possibilities()`.

### Out of scope
- Any DB, migration, seed, or artwork work — all already done.
- A `Melt Raven's Beak` entry in `RecipeRepository`. `Melt Bec de Corbin` already exists there; the Raven's Beak has no melt recipe, which is plausibly deliberate (glue and feathers wouldn't survive a forge). Flagged as a possible separate follow-up, not a gap this ticket closes.
- Player-side kitchen recipes in `RecipeRepository` for either item. These are pet crafts only.
- The `of Ravens` enchantment (`enchantment` id 160) that the Raven's Beak carries via its `enchants_id`. That binding path is data-driven and needs no code here.
- Rebalancing the existing spear line (`Hunting Spear`, `Overly-long Spear`, `Decorated Spear`, etc.).

## Relevant Docs & Anchors
- **Activity-system overview**: `api/src/Service/PetActivity/CLAUDE.md` — how `IPetActivity`, `groupKey()`, `groupDesire()`, and `possibilities()` fit together. There is no design doc for individual crafts; the code is the recipe book.
- **Difficulty anchor**: `StickCraftingService::createHuntingSpear` — the pet craft for a `Hunting Spear`, DC 13. Every DC in this ticket is derived from it. (A separate player-side kitchen recipe for `Hunting Spear` also exists in `RecipeRepository`, but that system has no DC and is irrelevant here.)
- **Roll mechanic**: `IRandom::rngSkillRoll`, implemented in `Xoshiro`. Read it before reasoning about difficulty — see Constraints.
- **Closest analogue for the Bec de Corbin**: `IronSmithingService::createMushketeer` — an `Iron Bar` plus exactly one other item, single success band, no crit band. `IronSmithingService::createScythe` is the same shape. `IronSmithingService::createMeatSeekingClaymore` shows the burn-fumble band at a higher DC.
- **Closest analogues for the Raven's Beak**: `CraftingService::createDecoratedSpear` (`Feathers` + `Hunting Spear` → `Decorated Spear`, DC 12) is the flavor and structure twin — a spear upgraded with feathers, living in the crafting group. `CraftingService::createLaserGuidedSword` is the gating twin — `Glue` plus a weapon plus a third ingredient, nested inside the existing `Glue` block. `CraftingService::createCrowsEye` is the only other `Black Feathers` craft (DC 20).
- **Item names, images, and tool stats**: `api/migrations/2026/07/Version20260728023546.php`.

## Constraints & Gotchas
- **The DCs are nominal, not calibrated — do not "fix" them.** `rngSkillRoll($bonus)` returns `rngNextInt(1, 20 + $bonus)` — a uniform roll over a widened range, *not* d20-plus-bonus. So `P(success) = 1 - (DC - 1) / (20 + $bonus)`, and a DC is only comparable to another DC drawn against the *same* skill pool. These three crafts use three different pools:
  - `Hunting Spear` (DC 13): `INT + DEX + max(Crafts, Brawl)`
  - `Bec de Corbin` (DC 16): `INT + STA + Crafts + SmithingBonus`
  - `Raven's Beak` (DC 22): `INT + DEX + Crafts`

  DC 16 and DC 22 were chosen as "+3" and "+6" on the nominal scale the codebase already writes everywhere, with the cross-pool incomparability accepted deliberately. A reader later measuring real success rates will find the gaps are not 3 and 6; that is expected.
- **`Black Feathers` is its own item**, distinct from `Feathers` and `White Feathers`, each of which gates different crafts in `CraftingService::possibilities()`. Gate on the exact name.
- **`Raven's Beak` contains an apostrophe.** In single-quoted PHP strings it needs escaping — `'Raven\'s Beak'` — matching existing usage like `'Bug-catcher\'s Net'` and `'Paper\'s Bane'`.
- **Every method on `IronSmithingService` consumes an `Iron Bar`.** That invariant is why the Raven's Beak, which uses no metal, does not belong in that helper even though its input is a smithed item.
- **Log icons take an `items/` prefix** that the DB `image` column omits: the migration stores `tool/spear/bec-de-corbin`, so `setIcon` needs `items/tool/spear/bec-de-corbin` (and `items/tool/spear/ravens-beak`).
- **Text style**: ASCII hyphens only (no em/en dashes), American spelling. Keep failure and success flavor text body-neutral — pet species vary wildly, so no assumptions about hands, paws, beaks, or arms doing the work.

## Open Decisions
1. **Burn-fumble band on the Bec de Corbin** — `if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)` → `increaseSafety(-rngNextInt(2, 24))`, icon `icons/activity-logs/burn`. Not universal in `IronSmithingService`: `createIronKey`, `createBasicIronCraft`, and `createMeatSeekingClaymore` have it; `createScythe`, `createMushketeer`, and `createWaterStrider` go straight to the success check. Default: include it — this is genuine forge work at a DC above both burn-free analogues. The Raven's Beak gets no burn band; there is no forge involved.
2. **Exp skills** — the `Hunting Spear` line awards `[PetSkillEnum::Crafts, PetSkillEnum::Brawl]`, and both new items are brawl tools (+2 and +3). But `createDecoratedSpear` and every smithing craft award `[PetSkillEnum::Crafts]` alone. Default: `[Crafts]` for both, matching their host groups rather than the spear lineage.
3. **Crit band** — several crafts add a second, higher threshold for a bonus output (`createIronKey` at 27, `createBasicIronCraft` at `difficulty + 10`). Default: neither craft gets one; both are single-success-band.
4. **Exact esteem, exp, and time values** — pick from the analogues. Roughly: 2-4 esteem, 2-4 exp, `rngNextInt(60, 75)` minutes on smithing success and `rngNextInt(45, 75)` on failure; `rngNextInt(45, 75)` / `rngNextInt(30, 60)` on the crafting side.
5. **Flavor text** — the item descriptions in the migration run a joke about whether the thing is really a raven's beak or a falcon's (`Bec de Faucon`). Worth echoing in a success or failure line, but not required.

## Acceptance Criteria
- [ ] With an `Iron Bar` and a `Hunting Spear` in the house, the Bec de Corbin craft appears among the smithing group's possibilities; with either missing, it does not.
- [ ] A successful Bec de Corbin craft consumes exactly one `Iron Bar` and one `Hunting Spear` and yields exactly one `Bec de Corbin`, on a roll of 16 or higher against `INT + STA + Crafts + SmithingBonus`.
- [ ] With a `Bec de Corbin`, `Glue`, and `Black Feathers` in the house, the Raven's Beak craft appears among the crafting group's possibilities; with any one missing, it does not.
- [ ] A successful Raven's Beak craft consumes exactly one of each of those three and yields exactly one `Raven's Beak`, on a roll of 22 or higher against `INT + DEX + Crafts`.
- [ ] Neither craft consumes any ingredient on the ordinary failure branch. (A burn fumble, if implemented per Open Decision 1, costs safety but still no ingredients.)
- [ ] The Bec de Corbin craft charges time to `PetActivityStatEnum::SMITH` and tags its logs `Smithing`; the Raven's Beak craft charges `PetActivityStatEnum::CRAFT` and tags `Crafting`.
- [ ] Both success logs call `addInterestingness(PetActivityLogInterestingness::HoHum + <DC>)` with the craft's own DC, and set icons `items/tool/spear/bec-de-corbin` and `items/tool/spear/ravens-beak` respectively.
- [ ] Both failure branches call `spendTime(..., $success: false)` and award strictly less exp than their success branch.
- [ ] `createRavensBeak` lives on `CraftingService`, not on `IronSmithingService` or any smithing helper.

## Implementation

### 1. Add `IronSmithingService::createBecDeCorbin`
The Bec de Corbin is a polearm forged by welding an iron head onto an existing spear shaft, so it belongs with the other Iron Bar crafts. Add a public method alongside `createMushketeer` — same signature shape as its neighbors, taking `ComputedPetSkills` and returning `PetActivityLog`.

Follow `createMushketeer` for the body: roll `rngSkillRoll` against `INT + STA + Crafts + SmithingBonus`; on `roll >= 16`, lose one `Iron Bar` and one `Hunting Spear`, bump esteem, build the success log with icon and interestingness per Acceptance Criteria, collect the `Bec de Corbin`, grant exp, and spend time with `$success: true`. The ordinary `else` branch produces a confused-icon log, minimal exp, and a shorter `$success: false` time spend. Add the burn-fumble band ahead of the success check per Open Decision 1, copying the guard and safety hit from `createMeatSeekingClaymore`.

### 2. Gate the Bec de Corbin craft in `SmithingService::possibilities()`
Inside the existing `if($this->houseSimService->hasInventory('Iron Bar'))` block — the same block that holds the `createScythe` and `createMushketeer` gates — add a nested check for `Hunting Spear` that pushes `$this->ironSmithingService->createBecDeCorbin(...)` onto `$possibilities`. `IronSmithingService` is already a constructor dependency of `SmithingService`; no wiring changes.

### 3. Add `CraftingService::createRavensBeak`
Gluing black feathers onto a finished polearm is mundane crafting, not metalwork, which is why this method lives on `CraftingService` and not in any smithing helper. Add a public method near `createDecoratedSpear`.

Follow `createDecoratedSpear` for the body, adjusted for three ingredients and the higher DC: roll against `INT + DEX + Crafts` (the crafting group's default pool — note `createDecoratedSpear` itself uses the narrower `DEX + Crafts`, which would make DC 22 punishing); on `roll >= 22`, lose one each of `Bec de Corbin`, `Glue`, and `Black Feathers`, bump esteem, build the success log with icon and interestingness, collect the `Raven's Beak`, grant exp, and spend time with `$success: true`. No burn band — a plain `else` failure branch with a confused icon, minimal exp, and `$success: false`.

### 4. Gate the Raven's Beak craft in `CraftingService::possibilities()`
Nest the gate inside the existing `if($this->houseSimService->hasInventory('Glue'))` block, alongside the `createLaserGuidedSword` gate that it structurally mirrors: check for both `Bec de Corbin` and `Black Feathers`, then push `$this->createRavensBeak(...)`. Do not add it to the `Feathers` block further down — that block gates on the plain `Feathers` item, which is a different thing.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `php vendor/bin/phpstan` (in `api/`) passes.
- [ ] Manual: on a test account, place an `Iron Bar` and a `Hunting Spear` in the house, give a pet with high crafts/smithing several hours of activity time, and run them until the Bec de Corbin craft fires. Confirm the success log text, icon, and that both ingredients disappear while one `Bec de Corbin` appears.
- [ ] Manual: with the resulting `Bec de Corbin` plus `Glue` and `Black Feathers` in the house, repeat until the Raven's Beak craft fires. Confirm all three ingredients are consumed and one `Raven's Beak` appears. Expect this to take a while — DC 22 against `INT + DEX + Crafts` is a low success rate for anything but a well-developed pet.
- [ ] Manual: observe at least one failure of each craft and confirm the house inventory is unchanged afterward.
- [ ] Manual: equip the crafted `Raven's Beak` on a pet and confirm it renders and grips correctly (the migration sets `grip_x`/`grip_y`/`grip_angle`/`grip_scale` shared with the Bec de Corbin).
- [ ] Regression: with `Iron Bar` + `Crooked Stick` + `Toadstool` in the house, confirm `Scythe`/`Garden Shovel` and `Mushketeer` still craft — the new gate sits in the same block.
- [ ] Regression: with `Feathers` + `Hunting Spear` in the house, confirm `Decorated Spear` still crafts, and that a house holding `Black Feathers` but no plain `Feathers` does not offer it.
