# Frosted Medusa Head Cracker

## Context
**Current behavior**: Bells stocks a themed treat for several holidays - `Jelephant Aminal Crackers` on Jelephant Day, `Odori 0.0%` during Awa Odori, `Pi Pie` on Pi Day - each added by a date check in `GrocerService::computeInventory()`. The Perseids have no grocer item. Bells' dialog is entirely static and says nothing about any holiday.

**New behavior**: During the Perseids (August 11-13), Bells stocks a new `Frosted Medusa Head Cracker` for 8 moneys, alongside whatever else the day carries - including the Jelephant crackers on August 12, when the two holidays overlap. She also gains a line of dialog, shown only during the shower, explaining why she bakes them: the Perseids are usually rained out on the island, and Perseus slew Medusa, and Medusa heads are cooler than Perseus heads.

## Prerequisites
- `perseids-meteor-shower.md`, which adds `CalendarFunctions::isPerseidPeakOrAdjacent()` and `HolidayEnum::Perseids`. Both are needed here: the former gates the grocer stock, the latter is the string the frontend matches to decide whether Bells says her line.

## Scope
### In scope
- A migration adding the `Frosted Medusa Head Cracker` item, its food row, its grammar row, and its item-group memberships.
- One date-gated entry in `GrocerService::computeInventory()`.
- A holiday-conditional paragraph in Bells' dialog, driven client-side from the existing weather feed.

### Out of scope
- **Any way to obtain the cracker other than buying it.** No recipe, no pet craft, no drop.
- **Any API change to the grocer endpoint.** The frontend already has everything it needs to know what day it is; see Implementation.
- **Artwork.** `proprietary-assets/images/items/dessert/aminal-cracker/medusa-head.svg` already exists.
- **Updating `db/seed/base.sql`.** Migration only.
- **Holiday-conditional dialog for any other Bells holiday.** Jelephant Day and Awa Odori keep their current silence; this ticket adds one line for one shower, not a general mechanism.

## Relevant Docs & Anchors
- **Item migration pattern**: `api/migrations/2026/07/Version20260728023546.php` - the `Bec de Corbin` / `Raven's Beak` migration. Shows the `item` / `item_grammar` / `item_group_item` inserts with explicit ids and `ON DUPLICATE KEY UPDATE id = id`, and `INSERT IGNORE` for the group links.
- **The item being mirrored**: `Jelephant Aminal Crackers`, item 1208, food row 435. Read it out of the local database rather than the seed; see Constraints.
- **Grocer stock**: `GrocerService::computeInventory()` - the run of holiday `if` blocks at the top, and `createInventoryData()` which derives the recycling price and the `special` flag.
- **Bells' dialog**: the `<app-npc-dialog name="Bells" npc="bells">` block in `webapp/src/app/module/grocer/page/grocer/grocer.component.html`, and the `hotBarItems` assembly in `grocer.component.ts`.
- **Reading today's holidays client-side**: `hello-dialog.component.ts` in the plaza module. It subscribes to the shared `WeatherService`, finds today's entry in the forecast, and reads its `holidays` array - exactly the pattern this ticket needs. Note it also strips a leading "the" from holiday names for use mid-sentence.

## Constraints & Gotchas
- **Read the mirrored item's values from the live database, not `db/seed/base.sql`.** The seed is a periodically-regenerated baseline that migrations run on top of, so its rows can lag. This matters concretely here: the seed shows `Jelephant Aminal Crackers` in one item group, while the live database shows two. Connection string is in `api/.env.local`; query with `php bin/console dbal:run-sql "..."` from `api/`.
- **Pick ids by querying `MAX(id)` at implementation time.** At the time of writing the next free ids were item 1528, `item_food` 545, and `item_grammar` 1608, but do not trust those numbers - re-check.
- **The item name is singular while its shelf-mates are plural.** This is deliberate. It has two consequences: `item_grammar.article` must be `a`, not the `some` the Jelephant crackers use; and Bells' auto-generated hot-bar sentence will read "...we have Jelephant Aminal Crackers, Frosted Medusa Head Cracker, and ..." Write the new dialog line knowing that sentence sits right above it.
- **Bells already names the item before your new line runs.** `grocer.component.ts` pushes every inventory entry flagged `special` into `hotBarItems`, and the template lists those in the opening "Today at the hot bar, we have ..." sentence. Holiday items carry that flag, so the cracker is announced automatically. The new line should read as an aside that adds the *reason*, not as a fresh announcement.
- **`item.name` is `varchar(45)`.** `Frosted Medusa Head Cracker` is 27 characters, so there is room, but keep it in mind if the name changes.
- **The `image` column omits the `images/items/` prefix**, storing paths like `dessert/aminal-cracker/jelephant`. The new row stores `dessert/aminal-cracker/medusa-head`.
- **There is an `app:upsert-item` console command, but do not rely on it here.** It is dev-only and interactive, and it writes straight to the database through Doctrine rather than emitting a migration. This ticket writes the migration directly.
- **Grocer inventory is cached for a day** under a `Grocery Store <day>` key, so a mid-day deploy will not change today's shelf until the cache turns over.
- **Text style**: ASCII hyphens only (no em or en dashes), American spelling.

## Open Decisions
1. **The item's `description`** - `Jelephant Aminal Crackers` has none, and "identical stats" does not settle this since a description is not a stat. Default: add one. Perseus is depicted holding Medusa's severed head, and the star marking that head in the constellation Perseus is Algol, a famous eclipsing binary that visibly dims every 2.87 days - so Medusa's eye really does wink at you. That joke is currently unused anywhere in the feature.
2. **Exact wording of Bells' line** - the intent, in the requester's words: "Oh, and because it's usually raining during the Perseids, I also make Medusa Head Crackers. Because, you know, Perseus slays Medusa... but Medusa heads are cooler than Perseus heads?" Adjust as needed so it does not collide with the hot-bar sentence above it, and so the plural reads naturally despite the singular item name.
3. **Whether the dialog line lives in its own `@if` or extends the existing `shopping` step** - default: a conditional paragraph inside the existing `dialogStep == 'shopping'` block, since it is an aside rather than a separate conversation branch.

## Acceptance Criteria
- [ ] A migration creates an `item` row named `Frosted Medusa Head Cracker` with `image` `dessert/aminal-cracker/medusa-head`, `fertilizer` 6, `fuel` 0, `recycle_value` 0, `museum_points` 1, `cannot_be_thrown_out` 0, and null `tool_id`, `hat_id`, `plant_id`, `spice_id`, `treasure_id`, `enchants_id`, and `use_actions`.
- [ ] Its `item_food` row has `food` 3 and `love` 3, with every other column zero or null - matching food row 435 exactly.
- [ ] Its `item_grammar` row uses the article `a`.
- [ ] It belongs to item groups 46 (`Cooking`) and 55 (`Event Exclusive`).
- [ ] The grocer offers it on August 11, 12, and 13, and on no other date.
- [ ] It is offered at 8 moneys, and the derived recycling price is 4.
- [ ] It is flagged `special`, so it appears in Bells' hot-bar sentence.
- [ ] On August 12, the grocer offers both it and `Jelephant Aminal Crackers`, and both remain purchasable.
- [ ] Bells' Perseids line renders only when today's holidays include `The Perseids Meteor Shower`, and no other day.
- [ ] The grocer endpoint's response shape is unchanged.

## Implementation

### 1. Write the item migration
Query the live database for the `Jelephant Aminal Crackers` item row, its `item_food` row, its `item_grammar` article, and its `item_group_item` links, then write a migration creating the parallel rows for the new cracker. Follow `Version20260728023546` for structure: explicit ids, `ON DUPLICATE KEY UPDATE id = id` on the keyed inserts and `INSERT IGNORE` on the group links, all inside one `addSql` heredoc per item.

Every value mirrors the Jelephant crackers except three: the name, the `image` path, and the grammar article (`a` rather than `some`, because the new name is singular). The `description` is a judgment call - see Open Decision 1.

### 2. Stock the cracker during the Perseids
In `GrocerService::computeInventory()`, add a block alongside the existing Jelephant Day, Pi Day, and Awa Odori checks that appends the new cracker at 8 moneys with the `special` flag set. These blocks are independent `if`s that each append, so the August 12 overlap with the Jelephant crackers needs no special handling - both simply appear.

The price is not arbitrary: the comment above `HotBarItems` records the convention as fertilizer value plus 2 plus a bonus-item term, and the Jelephant crackers' `fertilizer` of 6 is exactly why they cost 8. Identical stats give an identical price.

### 3. Teach the grocer page what day it is
The grocer's API payload carries no date or holiday information, and it does not need to - the shared `WeatherService` already holds the week's forecast, each day carrying its `holidays` array. Follow `hello-dialog.component.ts`: inject the service, subscribe to its `weather` subject, find the entry whose date matches today, and expose a boolean for whether its holidays include the Perseids. Unsubscribe in `ngOnDestroy` alongside the component's existing subscriptions.

### 4. Add Bells' line
Add a conditional paragraph to the `dialogStep == 'shopping'` branch of Bells' dialog, gated on that boolean. Content per Open Decision 2. Place it after the existing hot-bar sentence, which will already have named the cracker among the day's specials, so it reads as an explanation rather than a repeat.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `php vendor/bin/phpstan` (in `api/`) passes.
- [ ] Run the migration and confirm it applies cleanly, then re-run it to confirm the `ON DUPLICATE KEY` and `INSERT IGNORE` guards make it idempotent.
- [ ] Verify the created rows against the Jelephant crackers with a query joining `item`, `item_food`, `item_grammar`, and `item_group_item` for both items side by side - every column except name, image, and article should match.
- [ ] Manual: with the clock set inside the window, visit the Grocer and confirm the cracker is listed at 8 moneys, that switching to recycling points shows 4, and that Bells' Perseids line appears.
- [ ] Manual: buy one and confirm it lands in the inventory with the right artwork, that its item-details dialog reads correctly with the article "a", and that it can be fed to a pet.
- [ ] Manual: set the clock to August 12 specifically and confirm both the Medusa cracker and the Jelephant crackers are on the shelf, that Bells' hot-bar sentence names both, and that the new line still reads well underneath it.
- [ ] Manual: set the clock to August 10 and August 14 and confirm the cracker is absent and Bells says nothing about the Perseids.
- [ ] Regression: on a non-holiday date, confirm the grocer's shelf and Bells' dialog are unchanged, and that the daily purchase limit still behaves.

## Learnings

### Architectural decisions

- **Open Decision 1 (description)** resolved as "add one", using the Algol joke: Perseus is depicted holding Medusa's severed head, the star marking that head dims by a magnitude every 2.87 days, so Medusa's eye really does wink at you. Written in the same first-person narrator voice the `Bec de Corbin` and `Raven's Beak` descriptions use, rather than a neutral encyclopedia tone.
- **Open Decision 2 (Bells' wording)**: `Oh - the Medusa Head Crackers are a Perseids thing. It's almost always raining here during the shower, so if we can't watch it, we can at least bake it. Perseus slays Medusa, you see... and Medusa heads are way cooler than Perseus heads.` Two deliberate choices: it opens with "Oh -" so it reads as an aside to the hot-bar sentence directly above rather than a second announcement, and it calls them "Medusa Head Crackers" in the plural, which sidesteps the awkwardness of the singular item name appearing in the auto-generated list one line up.
- **Open Decision 3**: taken as defaulted - a conditional `<p>` inside the existing `dialogStep == 'shopping'` block, placed immediately after the hot-bar sentence.
- The holiday name is matched against a module-level `const PerseidsHoliday = 'The Perseids Meteor Shower'` rather than an inline literal, so the string that couples this component to `HolidayEnum` is named and greppable. There is no shared frontend holiday-name constant to reuse; `explain-holiday.component.html` matches bare literals, so a local named constant is as close to the convention as the codebase offers.
- The "find today's entry in the forecast" logic was briefly extracted to a private static helper and then **inlined again** - one call site did not justify the indirection, and inlining also dropped the `WeatherDataModel` import (the subscribe callback infers its parameter type from the `BehaviorSubject`).

### Problems encountered

- **`doctrine:migrations:execute --up` cannot be used to test idempotency.** Re-running an already-applied migration fails with a duplicate-key error on `doctrine_migration_versions.PRIMARY` before the guards are meaningfully exercised, so a green/red result there says nothing about the SQL. What actually proves the guards is re-issuing the statements through `dbal:run-sql` with **deliberately wrong values** (a junk name, `fertilizer` 99, `museum_points` 99) and confirming both "0 rows affected" and that a follow-up SELECT still shows the original data. That distinguishes `ON DUPLICATE KEY UPDATE id = id` working from the statement simply not running.
- **The August 12 overlap could not be observed directly**, because PHP on this machine runs in **UTC** and server-now had already rolled over to August 13 while local time was still August 12. The grocer's shelf correctly showed the Medusa cracker and `Odori 0.0%` (Awa Odori runs 12-15) but not the Jelephant crackers, whose predicate is the exact-day `format('nd') === '812'`. Verified the criterion instead by evaluating all three predicates against a fixed `2026-08-12`: all true. Since the blocks are independent `if`s appending to one array, all three items appear together. **`GrocerService::computeInventory()` reads `new \DateTimeImmutable()` directly rather than the injectable `Clock`**, so there is no way to force a date without mocking - worth remembering before trying to test any date-gated grocer behavior.

### Interesting tidbits

- **The database is reachable from this machine via `php bin/console dbal:run-sql`,** even though the `docker` CLI is not on `PATH` - Doctrine connects straight to MySQL using `api/.env.local`. The previous ticket recorded the DB as unreachable and fell back to grepping migrations; that conclusion was wrong, and `dbal:run-sql` should be the first thing tried.
- That also settled the previous ticket's open question: `SELECT MAX(id) FROM pet_activity_log_tag` is **104**, confirming 105 was the correct id for the `Perseids` tag. More interestingly, ids 101-103 (`Isekai Location: Bug Army`, `Mad Inventor`, `Celestial Temple`) exist in the live database but appear in **no migration at all** - they were written directly, presumably by the dev-only `app:upsert-item`-style tooling. This is exactly why the ticket insists on querying the live database rather than reasoning from the seed or the migration history: neither is complete.
- The ticket's warning about stale seed data was accurate and checkable: `db/seed/base.sql` shows `Jelephant Aminal Crackers` in one item group, the live database shows two (46 `Cooking`, 55 `Event Exclusive`). Copying the seed would have silently produced an item missing from `Event Exclusive`.
- `MAX(id)` matched the ticket's guesses exactly (item 1527, food 544, grammar 1607, so 1528/545/1608), because the `Bec de Corbin` migration that created 1526/1527 was the last item work.
- `item_group_item` is a pure join table with a composite primary key and **no `id` column**, which is why the group links use `INSERT IGNORE` rather than the `ON DUPLICATE KEY UPDATE id = id` idiom the keyed tables use.
- `groups` is a reserved word in MySQL 8 and cannot be used as a bare column alias.
- Comparing two rows for equality including NULLs is much easier with the null-safe equality operator `<=>` than with `IFNULL` juggling: `a.granted_skill <=> b.granted_skill` returns 1 when both are NULL. A single `CONCAT_WS` of those comparisons gives a one-glance "all columns match" readout.

### Related areas affected

- `GrocerService::computeInventory()` gained a fifth date-gated block. All of them resolve their item by name through `ItemRepository::findOneByName`, which throws `PSPNotFoundException` when the name is missing - so **if this code ships to an environment before its migration runs, the entire grocer page 500s for the three days of the window**, not just the one item. Migration-before-code ordering matters here the same way it did for the `Perseids` activity-log tag.
- `GrocerComponent` now subscribes to the shared `WeatherService`. It is `providedIn: 'root'`, so no module wiring was needed, and the new subscription is torn down in `ngOnDestroy` alongside the existing two.
- **Grocer inventory is cached for a day** under `Grocery Store <day>`, so a deploy inside the window leaves today's shelf unchanged until the cache turns over. Same class of problem as the weather forecast's per-date Redis cache noted in the Perseids ticket.

### Rejected alternatives

- **Adding a permanent test that every hard-coded grocer item name resolves.** This is the exact failure mode that would 500 the page, and there is clear precedent for that shape of test (`ValidateHollowEarthAdventuresTest`, `ValidateMonsterOfTheWeekPrizesTest` both validate hard-coded names against the database). It was left out because it is outside this ticket's scope, but it is a good candidate for a small follow-up ticket covering `HotBarItems`, `getItems()`, and all five holiday blocks at once.
- **Extending the API payload with the day's holidays.** Explicitly out of scope, and unnecessary: the shared `WeatherService` already holds the week's forecast client-side, so the grocer page can answer "is it the Perseids?" without touching the endpoint. Response shape is unchanged.
- **A general holiday-dialog mechanism for Bells.** The ticket asks for one line for one shower. A registry keyed by holiday name would be speculative until there is a second line to put in it.
