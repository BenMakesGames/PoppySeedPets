# Bec de Corbin & Raven's Beak pet crafts

## Context

**Current behavior**: The items `Bec de Corbin` (id 1526) and `Raven's Beak` (id 1527) exist in the database — added by migration `Version20260728023546`, with art, grammar, tool effects, item-group memberships (43 `Spear`, 62 `Bird Stuff`), and an attached `of Ravens` enchantment. `RecipeRepository` already carries `Melt Bec de Corbin` and `Melt Raven's Beak`. But no pet can make either one: there is no crafting logic, so the only way to obtain them is by direct DB manipulation.

**New behavior**: Pets can craft both items at home as part of the normal crafting activity. A pet with an Iron Bar and a Hunting Spear can make a Bec de Corbin; a pet with a Bec de Corbin, Glue, and Black Feathers can make a Raven's Beak. Both are meaningfully harder than the Hunting Spear that starts the chain — 3 and 9 points above it respectively.

## Prerequisites

None. Migration `Version20260728023546` and the `RecipeRepository` melt entries are already on the `two-new-items` branch.

## Scope

### In scope

Two new craft methods on `CraftingService`, plus two new gating conditions in `CraftingService::possibilities()`. No new services, no constructor dependencies, no entities, no migrations, no frontend.

### Out of scope

- **The `NULL` `description` on `Raven's Beak`** (item 1527) and the **`NULL` `focus_skill` on its tool** (id 538, where `Bec de Corbin`'s tool 537 has `focus_skill = 'brawl'`). Both were noticed during design and explicitly deferred by the requester — do not "fix" them in this ticket.
- The `of Ravens` enchantment. It is fully data-driven through `Item::getEnchants()` — see the `getEnchants()` branches in `CookAndCombineController` and `InventoryModifierFunctions` — and needs no code.
- Melt recipes. Already present in `RecipeRepository`.
- Any smithing-tree involvement. See Constraints.

## Relevant Docs & Anchors

- **`docs/architecture/Project Patterns.md` §Pet Activity System** and **`api/src/Service/PetActivity/CLAUDE.md`** — how `possibilities()` / `groupDesire()` selection works.
- **Primary analogue — roll formula, exp skills, interestingness:** `StickCraftingService::createHuntingSpear`. This is the baseline both new difficulties are defined against.
- **Primary analogue — shape of a Hunting-Spear upgrade living in `CraftingService`:** `CraftingService::createDecoratedSpear` (consumes `Feathers` + `Hunting Spear`).
- **Precedent for consuming an `Iron Bar` outside the smithing tree:** `CraftingService::createGrabbyArm` (plain crafting roll, `loseItem('Iron Bar', 1)`).
- **The migration that defines the items:** `api/migrations/2026/07/Version20260728023546.php` — read it for the tool stats (Bec de Corbin: brawl 2, fishing 1; Raven's Beak: brawl 3, fishing 1, climbing 1, and a `when_gather` of Black Feathers plus Quintessence).

## Constraints & Gotchas

- **Both crafts belong in `CraftingService`, not the smithing tree.** This was decided deliberately. The smithing roll pool (`int + stamina + crafts + smithingBonus`, per `IronSmithingService::createIronKey`) is a different scale, and the stated difficulties are defined relative to `createHuntingSpear`'s crafting-scale threshold of 13. Putting the Iron Bar craft under `SmithingService` would make the numbers below meaningless. `createGrabbyArm` establishes that an Iron Bar in the crafting tree is not novel.
- **`maybeSpotAStickBug()` is `private` to `StickCraftingService`.** `createHuntingSpear` uses it to award 3 exp instead of 2 on a bug sighting. That bonus does **not** carry over — use a flat exp value and do not make the helper public or duplicate it.
- **`CraftingService` already imports everything needed** — `PetActivityLogInterestingness`, `PetActivityStatEnum`, `PetSkillEnum`. `PetSkillEnum::Brawl` exists. No new `use` statements and no constructor changes should be required.
- **`Black Feathers` and `Feathers` are distinct items.** Raven's Beak uses `Black Feathers` (id 343). The neighboring `createDecoratedSpear` uses plain `Feathers` — do not copy that string across.
- **There is no test coverage for pet crafting** anywhere under `api/tests/`, so nothing will fail loudly if a string is wrong. Verify item names against the migration and the seed data rather than trusting recall.

## Open Decisions

1. **Esteem gain** — `createHuntingSpear` gives +2, `createDecoratedSpear` gives +1, and high-threshold smithing crafts give +6. Default: +2 for Bec de Corbin, +3 for Raven's Beak, scaling with difficulty. Adjust if it feels off next to neighbors.
2. **Exp awarded on success** — `createHuntingSpear` gives 2 (3 with a stick bug), `createDecoratedSpear` gives 2. Default: 2 and 3 respectively.
3. **`spendTime` ranges** — neighbors use roughly 15–30 minutes for a light upgrade and 45–60 for a full build. Default: 45–60 for Bec de Corbin, 45–60 for Raven's Beak, with a shorter range on the failure branch as the neighbors do.
4. **Success/failure log wording** — follow the house voice. The Bec de Corbin item description already jokes about the name being French for "raven's beak"; leaning into that is optional, not required.

## Acceptance Criteria

- [ ] `CraftingService::possibilities()` offers `createBecDeCorbin` when, and only when, the house has both an `Iron Bar` and a `Hunting Spear`.
- [ ] `CraftingService::possibilities()` offers `createRavensBeak` when, and only when, the house has `Glue`, `Black Feathers`, and a `Bec de Corbin`.
- [ ] `createBecDeCorbin` rolls `rngSkillRoll(intelligence + dexterity + max(crafts, pet's brawl skill))` and succeeds on a roll of **16 or higher**.
- [ ] `createRavensBeak` rolls the same formula and succeeds on a roll of **22 or higher**.
- [ ] On success, `createBecDeCorbin` removes exactly one `Iron Bar` and one `Hunting Spear` from the house, and the pet collects one `Bec de Corbin`.
- [ ] On success, `createRavensBeak` removes exactly one `Bec de Corbin`, one `Glue`, and one `Black Feathers` from the house, and the pet collects one `Raven's Beak`.
- [ ] Both methods award exp in `[PetSkillEnum::Crafts, PetSkillEnum::Brawl]` on both the success and failure branches, matching `createHuntingSpear`.
- [ ] Both success logs call `addInterestingness(PetActivityLogInterestingness::HoHum + N)` where `N` is that craft's threshold (16 and 22), following the `createHuntingSpear` convention.
- [ ] Both methods spend time as `PetActivityStatEnum::CRAFT` — `true` on success, `false` on failure — and tag their logs `PetActivityLogTagEnum::Crafting` and `PetActivityLogTagEnum::Location_At_Home`.
- [ ] Failure branches consume no items.
- [ ] `php vendor/bin/phpstan` passes with no new baseline entries.

## Implementation

### 1. Add `createBecDeCorbin` to `CraftingService`

A pet reinforces a Hunting Spear with iron to make a heavier polearm. Add the method next to `createDecoratedSpear`, which is the existing "upgrade a Hunting Spear" craft in this class.

Mirror `StickCraftingService::createHuntingSpear` for the roll and reward shape rather than `createDecoratedSpear` — the difficulty was specified relative to it. Differences from `createHuntingSpear`: threshold is 16 rather than 13; ingredients are one `Iron Bar` and one `Hunting Spear` rather than String/Crooked Stick/Talon; there is no stick-bug exp bonus (see Constraints), so award a flat exp value.

Use the two-branch success/failure structure both neighbors use. On failure, set the icon to `icons/activity-logs/confused`, award 1 exp, and spend time with the gain flag `false`.

### 2. Add `createRavensBeak` to `CraftingService`

A pet dresses a Bec de Corbin with black feathers, glued on, to make the finished weapon. Place it immediately after `createBecDeCorbin` so the chain reads in order.

Same roll formula and same two-branch structure as step 1, with a threshold of 22. Consumes one `Bec de Corbin`, one `Glue`, and one `Black Feathers`.

### 3. Gate `createBecDeCorbin` in `possibilities()`

`CraftingService::possibilities()` has no top-level `Iron Bar` block today — the only existing `Iron Bar` check is nested inside the `Plastic` block that feeds `createGrabbyArm`. Add a new top-level condition requiring both an `Iron Bar` and a `Hunting Spear`.

Place it near the existing `Feathers` block that gates `createDecoratedSpear`, so the two Hunting Spear upgrades sit together and are easy to compare.

### 4. Gate `createRavensBeak` in `possibilities()`

Nest this inside the existing top-level `hasInventory('Glue')` block — the one that already gates `createFabricMache`, `createGoldTrifecta`, `createLSquare`, and `createLaserGuidedSword`. Add a condition requiring `Black Feathers` and a `Bec de Corbin`.

### 5. Verify no other registration is needed

`CraftingService` is an existing `IPetActivity` with its own `groupDesire()`; adding possibilities requires no `services.yaml` change and no tag registration. Confirm no constructor dependency was added in steps 1–2 — if one was, reconsider, because both neighbors work with the services already injected.

## Test Plan

- [ ] `cd api && php vendor/bin/phpstan` passes clean.
- [ ] `cd api && composer run php-cs-fixer-dry-run` reports no violations in `CraftingService.php`.
- [ ] Grep `CraftingService.php` for `'Bec de Corbin'`, `'Raven\'s Beak'`, `'Iron Bar'`, `'Hunting Spear'`, `'Black Feathers'`, and `'Glue'` and confirm every string exactly matches the item names in `Version20260728023546` and `db/seed/base.sql` — a typo here fails silently at runtime.
- [ ] In a local run, give a pet's house an Iron Bar and a Hunting Spear, run pet activity until the crafting group is selected, and confirm a Bec de Corbin can be produced and that both ingredients disappear.
- [ ] Repeat with a Bec de Corbin, Glue, and Black Feathers; confirm a Raven's Beak is produced and all three ingredients disappear.
- [ ] Confirm a house holding an Iron Bar but no Hunting Spear (and vice versa) never offers the Bec de Corbin craft — i.e. the pet does other things and no craft attempt is logged for it.
- [ ] Spot-check that a failed attempt at either craft leaves the ingredient counts unchanged.
- [ ] Regression: confirm `createDecoratedSpear` and `createGrabbyArm` still fire, since steps 3–4 edit the blocks around them.
