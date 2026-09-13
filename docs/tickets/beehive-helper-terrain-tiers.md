# Beehive: Helper Rewards Vary by Terrain

## Context
**Current behavior**: Deploying the helper bar onto a hex ignores the hex's terrain. `HarvestController::helperHuntsOrGathers` uses two fixed gather tables and one fixed hunt table, each with four skill tiers (base / medium / high / super-high), regardless of whether the pet went to a jungle, beach, grassy, or rocky space.

**New behavior**: The helper's hunt/gather reward tables are chosen by the terrain of the space it was sent to. Three dimensions decide what comes back: terrain (4) x hunt-or-gather (2) x skill tier (4). The pet's skill still decides the tier exactly as today; terrain decides *which* tier tables are in play.

## Scope
### In scope
- Eight per-terrain tier-table methods in `BeehiveService` (one per terrain x hunt/gather), each returning all four tiers.
- `HarvestController::helperHuntsOrGathers` takes the space's terrain and pulls its tables from the service.
- Green Thumb: two independent draws from the terrain's gather tiers (replaces the dedicated second gather table).

### Out of scope
- Any change to the tier roll (`PetAssistantService::getExtraItem`), skill formulas, or the gather-vs-hunt decision.
- Spicing helper rewards (they still go through `petCollectsItem`, unspiced).
- Changes to the bees' own terrain goods (`getGoodsForTerrain`) or to the Beehive help page.
- Activity-log wording, tags, and the `BeeNana` badge - unchanged.

## Relevant Docs & Anchors
- **Analogue ticket**: `docs/tickets/complete/2026-09-12 beehive-hex-spaces-and-per-bar-harvest.md` (introduced terrain + `getGoodsForTerrain`'s match-to-private-method shape - mirror it).
- **Code anchors**: `HarvestController::helperHuntsOrGathers` (the three inline tables being replaced); `BeehiveService::getGoodsForTerrain` + `getJungleGoods` etc. (shape to mirror); `PetAssistantService::getExtraItem` (consumer of the four tier lists); `BeehiveSpaceTypeEnum`.

## Constraints & Gotchas
- **Naner stays reachable from every gather table** - the `BeeNana` badge check (`$extraItem === 'Naner'`) must remain meaningful on all terrains.
- **Duplicates are weight**: as with `getGoodsForTerrain`, listing an item twice in a tier doubles its odds within that tier.
- **Tier lists may not be empty**: `getExtraItem` does `rngNextFromArray` on whichever tier the roll lands in; an empty tier would throw. The tables below are complete, but if a tier is trimmed during balancing it must keep at least one entry.
- **C# migration prep**: tables live in the service as methods (not constants or controller-inline arrays), and the four tiers travel as one typed value rather than four loose arrays (see Open Decisions).

## Open Decisions
1. **Tier container shape** - a small `App\Model` value object (e.g. `ExtraItemTiers` with `base` / `medium` / `high` / `superHigh` string-array props) vs. a shape-documented associative array. Default: the value object; add a `getExtraItem` overload or a thin wrapper that accepts it so the existing four-array signature keeps working for the other helper sites.
2. **Method naming** - `getHelperJungleGatherTiers()` / `getHelperJungleHuntTiers()` (user's suggested names use "Harvest"; the existing verb in this code path is "gather"). Default: `Gather`, to match the `$verb = 'gather'` log text.

## Decisions (resolved 2026-09-13)
1. **Green Thumb draws twice from the same terrain gather tiers** (two independent `getExtraItem` calls, same tables). The separate second gather table is dropped.
2. **Tier tables** (item names only from lists already in use by the beehive):

### Jungle - gather
| Tier | Items |
|---|---|
| base | Naner, Orange |
| medium | Paper, Cacao Fruit, Coriander Flower, Spicy Peps |
| high | Apricot, Chanterelle, Mango, Pineapple |
| super-high | Goodberries, Honeycomb |

### Jungle - hunt
| Tier | Items |
|---|---|
| base | Feathers, Egg, Fluff |
| medium | Talon, Toad Legs |
| high | Jar of Fireflies, Scales |
| super-high | Quintessence, Dark Scales |

### Beach - gather
| Tier | Items |
|---|---|
| base | Silica Grounds, Crooked Stick, Seaweed, Paper Boat |
| medium | Feathers, Sand Dollar, Coconut |
| high | Plastic Bottle, Glass, Yeast |
| super-high | Silver Ore, Gold Ore, Mermaid Egg |

### Beach - hunt
| Tier | Items |
|---|---|
| base | Scales, Silica Grounds, Fish |
| medium | Talon, Feathers, Egg |
| high | Tentacle, Jellyfish Jelly |
| super-high | Quintessence, Little Strongbox |

### Grassy - gather
| Tier | Items |
|---|---|
| base | Tea Leaves, Agrimony, Blueberries, Blackberries, Crooked Stick, Red |
| medium | Onion, Tomato, Sweet Beet, Beans, Celery |
| high | Rosemary, Melowatern, Honeydont, Grass Jelly |
| super-high | Goodberries, Honeycomb |

### Grassy - hunt
| Tier | Items |
|---|---|
| base | Feathers, Egg, Snail Shell, Fluff |
| medium | Creamy Milk, Toad Legs |
| high | Jar of Fireflies, Moth |
| super-high | Quintessence |

### Rocky - gather
| Tier | Items |
|---|---|
| base | Grandparoot, Silica Grounds, Crooked Stick, Toadstool, Tea Leaves, Cobweb |
| medium | Iron Ore, Rock Candy, Limestone, Blueberries |
| high | Gypsum, Silver Ore, Rock |
| super-high | Iris, Gold Ore, Liquid-hot Magma, Everice, Blackonite |

### Rocky - hunt
| Tier | Items |
|---|---|
| base | Scales, Egg, Fluff, Feathers |
| medium | Talon, Creamy Milk, Toad Legs |
| high | Tiny Scroll of Resources, Gold Bar, Silver Bar |
| super-high | Lightning in a Bottle, Quintessence |

## Acceptance Criteria
- [ ] `BeehiveService` exposes eight tier-table methods, one per (`BeehiveSpaceTypeEnum`, gather|hunt), each returning four non-empty tiers matching Decision 2.
- [ ] A helper-bar harvest onto a space of terrain T draws only from T's gather or hunt tiers; the tier reached is decided by the same `getExtraItem` roll as today.
- [ ] A Green Thumb helper receives two items, each an independent draw from the space terrain's gather tiers.
- [ ] Every gather table contains Naner in at least one tier, and drawing it still awards `BeeNana`.
- [ ] No inline item tables remain in `HarvestController`.
- [ ] `php vendor/bin/phpstan` and `composer run php-cs-fixer-dry-run` pass.

## Implementation

### 1. Tier container
Give the four tiers one typed home so the service methods have a return type the C# port can mirror (Open Decision 1). Add it under `App\Model`; make `PetAssistantService::getExtraItem` accept it (overload or unpack at the call site - implementer's call).

### 2. Eight tier methods in `BeehiveService`
Mirror `getGoodsForTerrain`'s shape: two public entry points, `getHelperGatherTiers(BeehiveSpaceTypeEnum)` and `getHelperHuntTiers(BeehiveSpaceTypeEnum)`, each a `match` routing to a private per-terrain method (`getHelperJungleGatherTiers()`, `getHelperJungleHuntTiers()`, ...). Populate from Decision 2. No holiday additions for now.

### 3. Thread terrain into the helper reward
`helperHuntsOrGathers` currently receives no space; pass it the `BeehiveSpace` (or its `type`) and the `BeehiveService`. Replace the three inline tables: the Green Thumb branch calls `getExtraItem` twice with the terrain's gather tiers; the other branch picks gather or hunt tiers by terrain after the existing skill-weighted coin flip. Everything else in the method (log text, `petCollectsItem` comments, tags, badge) is untouched.

## Test Plan
- [ ] `cd api && php vendor/bin/phpstan && composer run php-cs-fixer-dry-run`.
- [ ] With a helper assigned, set `helper_progress = 2000` in the DB and deploy onto a rocky hex several times (reset progress between): every item is from the rocky gather or hunt tables; repeat for beach - no ores/magma appear, seaweed/sand dollars do.
- [ ] Repeat with a Green Thumb helper on a jungle hex: two items per harvest, both from the jungle gather tables.
- [ ] Give a test pet very low skills and harvest a few times: only base-tier items appear.
- [ ] Non-helper bars (Royal Jelly / Honeycomb / misc) behave exactly as before on the same hexes.
