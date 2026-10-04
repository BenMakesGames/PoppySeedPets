# Convert PetSpecies Primary Key to ULID

## Summary
Convert `PetSpecies.id` from an auto-increment `INT` to a `ULID BINARY(16)`, matching the pattern already established by `Vault`, `VaultInventory`, and `ItemTreasure`. Migrate the three FK columns that reference it (`pet.species_id`, `pet_baby.species_id`, `user_species_collected.species_id`) and update every API/frontend call site that reads or sends a species id. As a related cleanup, replace `PetSpecies::getAvailableAtSignup()`'s magic-ID logic with a real boolean column.

## Context
**Current behavior**: `PetSpecies` uses an auto-increment integer primary key (current `AUTO_INCREMENT=115`). Three FK columns point at it. The API serializes `species.id` as a JSON number, and the frontend types it as `number`. `PetSpecies::getAvailableAtSignup()` hardcodes `$this->getId() <= 16 || $this->getId() === 96 || $this->getId() === 100` to decide which species new players can pick from.

**New behavior**: `PetSpecies.id` is a `ULID BINARY(16)` like `Vault`/`VaultInventory`/`ItemTreasure`. Pet, PetBaby, and UserSpeciesCollected FK columns hold ULID binaries. `species.id` is serialized as a Crockford-base32 string (e.g. `01HXAB...`) and consumed as `string` on the frontend. `getAvailableAtSignup()` reads from a new `available_at_signup` boolean column populated by the same migration.

## Acceptance Criteria
- [ ] `PetSpecies::$id` is `Ulid` (not `?int`), constructed with `new Ulid()` in `__construct()`, and `getId()` returns `Ulid` — matching `Vault`/`VaultInventory`/`ItemTreasure` exactly.
- [ ] `Pet::$species`, `PetBaby::$species`, and `UserSpeciesCollected::$species` all join via ULID FK columns; no `species_id INT` column remains in any of those tables.
- [ ] A new Doctrine migration converts existing rows in place, preserving every existing FK relationship (no orphaned pets, pregnancies, or zoologist entries). Migration is forward-only; `down()` throws like `Version20260302120000::down()`.
- [ ] No historical migration files under `api/migrations/` are modified.
- [ ] `db/seed/base.sql` is regenerated (or hand-edited) so the schema matches the new entity definitions; a fresh `docker compose up` produces a working database with seeded species data using ULID IDs.
- [ ] `PetSpecies` has a new `available_at_signup BOOLEAN NOT NULL` column. `getAvailableAtSignup()` reads from it. The migration backfills it as `true` for species whose old integer id was `<= 16` OR `=== 96` OR `=== 100` (i.e. the exact set the magic-number method currently returns), `false` for all others.
- [ ] Every API endpoint that returned `species.id` as a number now returns it as a ULID base32 string. Every endpoint that *accepted* a numeric species id in a request body or query string now accepts a ULID string instead.
- [ ] Every webapp TypeScript model/component that typed species id as `number` now types it as `string`.
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` both pass in `api/`.
- [ ] Frontend builds (`ng build`) without type errors.

## Scope
Backend: 1 entity (`PetSpecies`) plus 3 entities holding the FK (`Pet`, `PetBaby`, `UserSpeciesCollected`). 1 normalizer, 1 Doctrine event listener, 1 repository helper, 1 typeahead service, 1 filter service. ~6 controllers that read or write a species id. 1 raw-SQL controller (`GetPetsOfUndiscoveredSpeciesController`). 1 export command. 1 new migration. 1 seed SQL regen.

Frontend: 1 serialization-group model, 1 search model, 1 typeahead component, plus 4–5 component .ts/.html files that pass `species.id` around (zoologist, transmigration-serum, pet-search).

## Implementation

### 1. Add the migration — the heart of this ticket
**Why**: This is a destructive, in-place schema change against three FK relationships. Mirror `api/migrations/2026/03/Version20260302120000.php` exactly — that's the validated precedent for "auto-increment INT → ULID BINARY(16)" with FK rewiring. Re-read it; the comment-numbered steps in that file map directly to this migration.

Create a new timestamped migration under `api/migrations/2026/<month>/`. Follow the same `up()` / `postUp()` / `down()` split: `up()` does DDL prep (drop FKs/indexes, add `new_id` / `new_species_id` shadow columns); `postUp()` does the row-by-row UUID assignment + FK rewiring + final rename + re-add of constraints; `down()` throws `RuntimeException` because integer IDs are gone forever.

Steps the migration must perform (extending the precedent for *three* dependent FK columns instead of one):

- **In `up()`**:
  - Drop FK constraints on `pet.species_id`, `pet_baby.species_id`, and `user_species_collected.species_id` (look up the constraint names via `SHOW CREATE TABLE` or by grepping seed SQL — the current names are `FK_E4529B85B2A1D860` on pet, `FK_9C246454B2A1D860` on pet_baby, `FK_681CA342B2A1D860` on user_species_collected). Drop the indexes that back them too (`IDX_E4529B85B2A1D860`, `IDX_9C246454B2A1D860`, `IDX_681CA342B2A1D860`), and the `user_species_idx` composite unique on `user_species_collected (user_id, species_id)`.
  - Add `BINARY(16) NULL` shadow columns: `pet_species.new_id`, `pet.new_species_id`, `pet_baby.new_species_id`, `user_species_collected.new_species_id`.
  - Add `pet_species.available_at_signup TINYINT(1) NOT NULL DEFAULT 0` (so existing rows get `false` and we'll backfill `true` for the chosen IDs in `postUp`). This is part of the "Add available_at_signup column" change-of-shape decision — bundling it here means we don't need two migrations against the same table.

- **In `postUp()`**:
  - Backfill `available_at_signup`: `UPDATE pet_species SET available_at_signup = 1 WHERE id <= 16 OR id = 96 OR id = 100`. Do this *before* you drop the old `id` column, while the integer IDs are still meaningful.
  - Generate `Ulid::generate()` per `pet_species` row; build `[oldId => binary]` map; populate `pet_species.new_id`.
  - For each entry in the map, set `pet.new_species_id`, `pet_baby.new_species_id`, `user_species_collected.new_species_id` (three separate `UPDATE ... WHERE old_id` statements per row, like the precedent's single-FK example, but tripled).
  - Drop old columns: `ALTER TABLE pet DROP COLUMN species_id`, `ALTER TABLE pet_baby DROP COLUMN species_id`, `ALTER TABLE user_species_collected DROP COLUMN species_id`, `ALTER TABLE pet_species DROP PRIMARY KEY, DROP COLUMN id`.
  - Rename shadow columns to canonical names and lock NOT NULL: `pet_species.new_id → id BINARY(16) NOT NULL`; `pet.new_species_id → species_id BINARY(16) NOT NULL`; `pet_baby.new_species_id → species_id BINARY(16) NOT NULL`; `user_species_collected.new_species_id → species_id BINARY(16) NOT NULL`.
  - Re-add primary key on `pet_species(id)`.
  - Re-add the three indexes and three FK constraints (use the original names from the seed schema so phpstan baselines / future migrations don't see drift).
  - Re-add the `user_species_idx` composite unique on `user_species_collected (user_id, species_id)`.

> 🧚‍♀️ **Watch out for `pet.species_id NOT NULL`.** Look at the seed SQL — these FK columns are NOT NULL today. The migration must therefore populate every row in `pet.new_species_id` *before* the rename that locks NOT NULL, or MySQL will reject the rename. Same for `pet_baby` and `user_species_collected`. The precedent migration handled this implicitly because every `item.treasure_id` had a value too; do not assume the same in production data — if any row is missing a species (it shouldn't be, given the NOT NULL constraint, but verify with a `SELECT count(*) FROM pet WHERE species_id IS NULL` first), fail loudly rather than silently inserting a NULL.

### 2. Update `PetSpecies` entity
**Why**: Switch to ULID PK and add `available_at_signup` column. The other field definitions are unchanged.

**File**: `api/src/Entity/PetSpecies.php`

- Replace the imports + `$id` declaration to mirror `Vault.php` lines 14–48 exactly: `use Symfony\Bridge\Doctrine\Types\UlidType;` and `use Symfony\Component\Uid\Ulid;`; the property is `private Ulid $id;` with `#[ORM\Id] #[ORM\Column(type: UlidType::NAME)]`; constructor assigns `$this->id = new Ulid();`; `getId(): Ulid`.
- Remove the `#[Groups([...])]` from the `$id` property (none of the Vault-pattern entities serialize id via groups — frontend gets it through the standard normalizer; verify after wiring that `species.id` still shows up in the encyclopedia/typeahead responses, and add a group if needed).
- Drop the `?int` nullable / `\LogicException` pattern; ULIDs are assigned at construction time, so the not-yet-persisted check is moot.
- Add a new `private bool $availableAtSignup;` property with `#[ORM\Column(type: 'boolean')]` and the same Groups annotation as `availableFromBreeding` (which is `petEncyclopedia`).
- Rewrite `getAvailableAtSignup(): bool` to simply `return $this->availableAtSignup;`. Drop the `getId() <= 16 || ...` logic. Drop the `#[Groups(["petEncyclopedia"])]` attribute on the *method* and put it on the new property instead.
- Add a `setAvailableAtSignup(bool): self` mirror of the other setters.
- The existing `pets` `OneToMany` collection on `$pets` keeps its mapping; Doctrine handles the type change transparently because the FK is just a column type change.

### 3. Update FK-holding entities
**Why**: Doctrine needs to know the join column is now binary, otherwise it'll keep trying to map an `INT`. With proper `ManyToOne` Doctrine type-resolution, this is mostly implicit — but explicit verification matters here.

**Files**: `api/src/Entity/Pet.php`, `api/src/Entity/PetBaby.php`, `api/src/Entity/UserSpeciesCollected.php`

Each has a `#[ORM\ManyToOne(targetEntity: PetSpecies::class)]` block. Doctrine infers the FK column type from the target entity's PK type, so once `PetSpecies::$id` is `Ulid`, the FK columns are inferred as binary. **No code changes** to these three entity files should be needed beyond verifying that no developer hand-wrote a `#[ORM\JoinColumn(type: ...)]` on the species relation that pins it to integer. Grep each file for `JoinColumn.*species` to confirm.

### 4. Update `PetSpeciesRepository::findOneById`
**Why**: `int $speciesId` is now meaningless. Per user decision, accept only `Ulid`.

**File**: `api/src/Functions/PetSpeciesRepository.php`

- Change signature: `public static function findOneById(EntityManagerInterface $em, Ulid $speciesId): PetSpecies`.
- Update the cache key: `'PetSpeciesRepository_FindOneById_' . $speciesId->toBase32()` — `__toString()` would also work since Ulid renders to base32, but `->toBase32()` is explicit and matches the canonical `Vault` pattern.
- Update the not-found message: `'There is no species #' . $speciesId->toBase32() . '.'`.
- Add `use Symfony\Component\Uid\Ulid;`.

### 5. Update `PetLoadListener`
**Why**: It's the only caller of `findOneById` (sort of — it inlines its own query, but does the same thing) and grabs `$speciesId` from a Doctrine proxy.

**File**: `api/src/Doctrine/EventListeners/PetLoadListener.php`

- `$speciesId = $petSpeciesProxy->getId();` now returns `Ulid` instead of `int` — the rest of the method works as-is because Doctrine accepts a Ulid for `setParameter`.
- Update the cache key to use `$speciesId->toBase32()` instead of `$speciesId` (string-concat with a Ulid works via `__toString` but base32 is the canonical surface).

### 6. Update controllers that read/write species id
For each controller, the request parameter or response field changes from int to ULID string. Use `$request->request->get('species')` (returns string|null) + `Ulid::fromString()` instead of `getInt`. Add try/catch on `Ulid::fromString` to throw `PSPFormValidationException` for malformed input.

- **`api/src/Controller/Item/PetAlteration/TransmigrationSerumController.php` (lines 51–62)**: Replace `$request->request->getInt('species', 0)` with `$request->request->get('species', '')`, parse via `Ulid::fromString()`, compare with `->equals()` instead of `===`. The species lookup `$em->getRepository(PetSpecies::class)->find($speciesId)` accepts a Ulid.
- **`api/src/Controller/Zoologist/ReplaceSpeciesController.php` (line 57)**: `'species' => $pet->getSpecies()->getId()` — pass the Ulid; Doctrine will accept it for the `findOneBy`. No code change needed at the call site since Ulid is the entity-comparable value.
- **`api/src/Controller/Zoologist/ShowPetsController.php` (line 66)**: `$petSpecies = array_map(fn(Pet $pet) => $pet->getSpecies()->getId(), $pets);` — this collects Ulids now. Verify downstream consumers (probably a serializer for the response) handle Ulid → string via `->toBase32()` if needed, or accept the Symfony serializer's default ULID stringification.
- **`api/src/Controller/Item/Scroll/SummoningSomethingFriendlyController.php` (line 96)**: Replace `$species->getId() != $pet->getSpecies()->getId()` with `!$species->getId()->equals($pet->getSpecies()->getId())`. Ulid does not support `!=` correctly because both sides become objects.
- **`api/src/Controller/Account/RegisterController.php`**: No species-id parameter handling — uses `image` lookup. No change beyond ensuring the species entity round-trips correctly.
- **`api/src/Controller/Encyclopedia/GetSpeciesController.php` / `GetSpeciesByFamilyController.php`**: Lookups are by `name` or `family`; no id parameter. No code change.
- **`api/src/Controller/Pet/TypeaheadController.php` (line 40)**: `$petTypeaheadService->setSpeciesId($request->query->getInt('speciesId'));` — switch to `$request->query->get('speciesId')` + `Ulid::fromString()`. See next step for the service signature.

### 7. Update `PetTypeaheadService::setSpeciesId`
**File**: `api/src/Service/Typeahead/PetTypeaheadService.php`

- Change `setSpeciesId(int $speciesId)` to `setSpeciesId(Ulid $speciesId)` (look at the existing file — it's around line 39).
- The DQL `$qb->andWhere('e.species=:species')->setParameter('species', $this->speciesId);` works as-is because Doctrine binds Ulid to BINARY(16) automatically.

### 8. Rewrite `GetPetsOfUndiscoveredSpeciesController`'s raw SQL
**Why**: This is the trickiest controller in the change because it uses `SimpleDb` (raw PDO) rather than Doctrine. The query joins `pet.species_id=species.id` and selects `species.id AS speciesId` to plug into the response. With ULID binary IDs, the raw SELECT will return raw bytes; the response needs a base32 string.

**File**: `api/src/Controller/Zoologist/GetPetsOfUndiscoveredSpeciesController.php`

- The SQL joins themselves stay correct — both sides of `pet.species_id=species.id` are now BINARY(16), and MySQL compares them fine.
- For the SELECT that pulls `species.id AS speciesId`: wrap the column as `BIN_TO_UUID` won't work (ULIDs aren't UUIDs), so either:
  - Wrap in MySQL: `HEX(species.id) AS speciesId` and convert HEX → ULID base32 in PHP via `Ulid::fromBinary(hex2bin($row['speciesId']))->toBase32()` in the `mapResults` callback; OR
  - Pull as `species.id AS speciesId` (raw binary string from PDO), and do `Ulid::fromBinary($row['speciesId'])->toBase32()` in `mapResults`.
- The second option is simpler. Update the `mapResults` closure to call `Ulid::fromBinary($speciesId)->toBase32()` before placing it in the response payload.

### 9. Drop or migrate the `'guildEncyclopedia'`/legacy serialization annotations
**File**: `api/src/Entity/PetSpecies.php`

Grep the file for `'guildMember'` / `'guildEncyclopedia'` / `'petGuild'` — these are dead string literals left by the guild removal (see [Departures](docs/architecture/Departures from Symfony Standard.md) on serialization groups living as literals). If present on PetSpecies, drop them. Out of scope to chase across other entities — only touch the strings on `PetSpecies` while you're in the file.

### 10. Update `db/seed/base.sql`
**Why**: New checkouts initialize the DB from `db/seed/base.sql` (see `CLAUDE.md` § Database). After the migration, the schema for `pet_species`, `pet`, `pet_baby`, and `user_species_collected` differs from what's seeded. New developers need a working DB on first `docker compose up`.

Regenerate `base.sql` by running the migration against a fresh seeded DB, then `mysqldump`-ing the result. Alternatively, hand-edit the affected `CREATE TABLE` and `INSERT INTO` statements:
- `pet_species` CREATE: `id BINARY(16) NOT NULL`, add `available_at_signup TINYINT(1) NOT NULL`, drop `AUTO_INCREMENT`.
- `pet_species` INSERT: regenerate each row's ULID via a quick PHP script that emits `UNHEX('...')` literals; set `available_at_signup` to 1 for rows where the original id was ≤16, =96, or =100.
- `pet` / `pet_baby` / `user_species_collected` CREATE statements: change `species_id INT NOT NULL` → `species_id BINARY(16) NOT NULL`. FK definitions stay textually identical.
- INSERT rows on those three tables need their `species_id` values rewritten to the matching binaries — easiest done programmatically via the same script that generates the pet_species ULIDs.

The dump-and-replace strategy is less error-prone than hand-editing, and the existing seed file is already a dump. Prefer that.

### 11. Update `ExportSpeciesForToolCommand`
**File**: `api/src/Command/ExportSpeciesForToolCommand.php`

The exported array does not include `id` (it's keyed by `image`), so no functional change. Skim the file to confirm and move on.

### 12. Frontend type changes
**Why**: API responses and request payloads now carry ULID strings. TypeScript still says `number` in several places.

Change `id: number` → `id: string` (or `species: number|null` → `species: string|null`) in these files (grep `species` in webapp/src to verify the complete list; this is the set found during research):

- `webapp/src/app/model/pet-species-encyclopedia/pet-species-encyclopedia.serialization-group.ts` — `id: number` → `id: string`.
- `webapp/src/app/module/shared/component/find-pet-species-by-name/find-pet-species-by-name.component.ts` — `PetSpeciesTypeaheadModel.id: number` → `id: string`; `@Input() value: number|null` → `value: string|null`; `@Output() valueChange = new EventEmitter<number|null>()` → `string|null`; `speciesCache` typing follows.
- `webapp/src/app/model/search/pet-search-model.ts` — `species: number|null` → `species: string|null`.
- `webapp/src/app/module/zoologist/page/zoologist/zoologist.component.ts` — `doReplaceEntry(speciesId: number)` → `string`.
- `webapp/src/app/module/zoologist/page/zoologist/zoologist.component.html` — `track pet.species.id` and `doReplaceEntry(pet.species.id)` — no type annotation in template; verify request payload still works (ULIDs are strings, which serialize to JSON cleanly).
- `webapp/src/app/module/zoologist/page/show-species/show-species.component.ts` — `this.results.results[i].species.id === pet.species.id` — equality comparison on strings works as-is.
- `webapp/src/app/module/home/page/transmigration-serum/transmigration-serum.component.ts` — adjust the `id: species.id` and `species: species.id` references for the new string type (`species.id` is already typed via the serialization-group model; the change cascades).
- `webapp/src/app/module/encyclopedia/page/species/species-details/species-details.component.html` — `[queryParams]="{'filter.species': species.id }"` — string id flows through query params; verify the encyclopedia pet filter still parses correctly with a ULID string (it should, since `PetSpeciesFilterService::filterSpecies` accepts mixed and Doctrine binds it).
- `webapp/src/app/module/shared/dialog/pet-search/pet-search.dialog.ts` and the other files surfaced by `grep PetSpecies webapp/src` — same pattern: any `species: number` becomes `species: string`.

### 13. Verify `PetSpeciesFilterService::filterSpecies` and `PetFilterService::filterSpecies`
**Why**: `PetFilterService` at lines 47, 92–93 accepts a `species` filter and binds it as `setParameter('speciesId', $value)` where `$value` is whatever came off the query string. If callers now send ULID strings, Doctrine needs to bind to a BINARY(16) column.

**File**: `api/src/Service/Filter/PetFilterService.php`

- Find `filterSpecies` (around line 92–93). Convert `$value` to `Ulid::fromString($value)` before `setParameter`, throwing `PSPFormValidationException` on malformed input. Doctrine will then bind the Ulid to the binary column.
- `PetSpeciesFilterService::filterHasPet` / `filterHasDiscovered` (lines 65–88) use subqueries with `IDENTITY(pet.species)` returning the FK; nothing here treats the value as an integer, so no change.

## Test Plan
- [ ] Run the new migration against a fresh copy of production-shaped data. Confirm: zero rows in `pet`, `pet_baby`, or `user_species_collected` end up with `species_id IS NULL`; `pet_species.available_at_signup = 1` for exactly the original rows whose `id` was ≤16, =96, or =100.
- [ ] Run `composer run php-cs-fixer-dry-run` — passes.
- [ ] Run `vendor/bin/phpstan --configuration=phpstan.dist.neon` in `api/` — passes; if the baseline has any entries referencing `PetSpecies::getAvailableAtSignup` or `findOneById`, update per [PhpStan baseline hygiene](docs/architecture/Project Patterns.md).
- [ ] Run `ng build` in `webapp/` — no type errors.
- [ ] Start the dev environment (`run.bat`) — backend and frontend boot.
- [ ] Visit `/poppyopedia/species` — the list loads; click a species, the detail page loads, the "X pets in the game are of this species" link works.
- [ ] Visit `/poppyopedia/pet?filter.species=<some ULID>` — pet filter results return correctly.
- [ ] On `/zoologist`, view "showable pets" — undiscovered species pet list loads (this exercises `GetPetsOfUndiscoveredSpeciesController`'s raw SQL); "Update" buttons send a request that succeeds.
- [ ] Use a Transmigration Serum on a pet, picking a target species in the same family — pet's species changes, no JSON parse errors.
- [ ] Use a Summoning Scroll (Friendly) several times — sometimes summons new pets without errors; sometimes triggers the "altered by the energies of the wilds" species swap (line 105) without errors.
- [ ] Register a new account — only species available at signup appear in the picker (the same ones as before), and registration succeeds with a pet whose `species_id` is now a binary ULID.
- [ ] Run the `app:increase-time` cron, then visit a house — pets cycle through activities (this exercises `PetLoadListener` heavily); no errors.
- [ ] Spot-check `db/seed/base.sql` by tearing down volumes and running `docker compose up` from scratch — initial schema is the ULID version, seed inserts succeed.

## Notes / Out of Scope
- **Inventory/Item IDs** stay integer. This ticket changes only `PetSpecies` and its three FK references.
- **PetBaby itself** is not converted to ULID — only its `species_id` FK column is.
- **`Pet.id`** stays integer; only `Pet.species_id` changes.
- **`pet_activity_log` and other indirect references** do not store species ids directly.

## Learnings

### Architectural decisions

- **Kept the `Groups([...])` annotation on the new `Ulid $id` property** rather than dropping it to match `Vault.php`/`ItemTreasure.php` literally. Vault/ItemTreasure aren't serialized through group-filtered responses (Vault has its own DTO mapping; ItemTreasure ships via the `dragonTreasure` group on its other fields, never its id), so they don't need an id group. `PetSpecies.id` *is* surfaced via `petEncyclopedia`, `zoologistCatalog`, and `typeahead` groups — dropping the annotation would have silently removed `species.id` from those response payloads and broken the encyclopedia detail page (`/poppyopedia/pet?filter.species=...` link), the zoologist "Update" buttons, and the species typeahead. The ticket called this out as "add a group if needed" and it was needed.
- **`available_at_signup` defaults to `false` at the column level (`DEFAULT 0`)** so `up()` can add it before backfilling — no transient state where existing rows have an undefined value. Backfill runs in `postUp()` *before* the old integer `id` is dropped, while the magic-id rule (`<=16 OR =96 OR =100`) is still meaningful.
- **Did not migrate the FK side's join column type explicitly.** Once `PetSpecies::$id` is `Ulid` with `UlidType::NAME`, Doctrine infers BINARY(16) for every `ManyToOne(targetEntity: PetSpecies::class)` join column. No `JoinColumn(type: ...)` was hand-written on any of the three FK-holding entities, so they Just Worked.
- **Seed regeneration deferred to the user.** Three of the four affected tables are empty in `db/seed/base.sql`; only `pet_species` carries 111 INSERT rows. User opted to run the migration against the seed DB and re-export rather than have me bake stable ULIDs into the file from this side.

### Problems encountered

- **PhpStan caught three type narrowings the precedent migration (Version20260302120000, single-FK) didn't need.** `$request->request->get('species', '')` returns `string|float|int|bool|null` — passing it to `Ulid::fromString()` (which expects `string`) trips `argument.type`. Same shape in `PetFilterService::filterSpecies` (the `mixed $value` from the filterer can't be cast to string blindly) and in the raw-SQL `mapResults` callback (parameters default to `mixed` from PDO `FETCH_FUNC`). Fixed by using `$request->request->getString('species', '')` in the controller, `is_string($value)` guard before `Ulid::fromString` in the filter, and explicit parameter type-hints on the `mapResults` closure.
- **Pre-existing copy-paste bug in `find-item-by-name.component.ts`** surfaced once `PetSpeciesTypeaheadModel.id` diverged from `ItemTypeaheadModel.id`: `doSelect(result: PetSpeciesTypeaheadModel)` was annotated with the wrong model and was importing it from the species component. Build was green before because both models had `id: number`; after this change the types no longer line up. Fixed inline (correct type is `ItemTypeaheadModel`, no cross-import needed) per the project's opportunistic-tech-debt-cleanup convention.

### Interesting tidbits

- **The three FK-holding tables (`pet`, `pet_baby`, `user_species_collected`) are empty in seed.sql** — no `INSERT` data, just empty `LOCK TABLES; ALTER TABLE ... DISABLE KEYS; ALTER TABLE ... ENABLE KEYS; UNLOCK TABLES;` shells. Only `pet_species` carries seed rows. So a future seed regen only needs four `CREATE TABLE` updates and one rewritten `INSERT` block.
- **Symfony's default `UidNormalizer` serializes `Ulid` → base32 canonical string** (e.g. `01HXAB...`, 26 chars) with no extra configuration. No custom normalizer registration needed. The ticket's worry about `BIN_TO_UUID` not working for ULIDs is real but irrelevant — Doctrine reads the binary into a `Ulid` PHP object, and the serializer takes it from there.
- **`SimpleDb::mapResults()` uses `PDO::FETCH_FUNC`**, which calls the callback with one positional argument per `SELECT` column. The binary `species.id` arrives as a PHP `string` of 16 raw bytes — `Ulid::fromBinary($speciesId)->toBase32()` converts it to the canonical 26-char form for the JSON response.

### Workarounds / limitations

- **MySQL has no native ULID generator** (it has `UUID()` for v1 RFC4122 UUIDs and `UUID_TO_BIN()`, but no ULID equivalent — ULIDs have a different structure: 48-bit timestamp + 80-bit random). All ULID generation in the migration happens in PHP via `new Ulid()`. For future seed regeneration, this means either baking stable ULIDs into the SQL file (one-time generated via a PHP script) or accepting that each fresh `docker compose down -v` produces new values. Not a permanent limitation — just a fact of the ULID spec.

### Related areas affected

- **`find-item-by-name.component.ts`** was touched even though it has nothing to do with species — its type annotation pointed at `PetSpeciesTypeaheadModel`. Worth a follow-up grep for other components importing from `find-pet-species-by-name` to confirm none of them are actually about items.
- **`PetFilterService::filterSpecies` is the only filter that now needs to parse a Ulid string.** If/when other FK columns convert to ULID, the same `is_string + Ulid::fromString + PSPFormValidationException` shape will need to be repeated.

### Rejected alternatives

- **Switching `Pet.species_id` to nullable mid-migration so the rename could happen without populating every row first.** Rejected: the seed schema declares it NOT NULL today; relaxing it (even transiently) means the rename can succeed against a partially-migrated dataset and silently mask data corruption. Instead, the migration actively checks for NULL `species_id` rows in `postUp()` and throws `RuntimeException` if any exist — fail loudly rather than rename a corrupt state.
- **Wrapping the raw-SQL `SELECT species.id AS speciesId` in `HEX(species.id)` and decoding `hex2bin` in PHP.** Rejected: PDO returns the binary column as a PHP string of raw bytes directly, which `Ulid::fromBinary()` consumes natively. The extra HEX/`hex2bin` round-trip is dead weight.
- **Dropping the `Groups([...])` annotation on `$id` to match `Vault.php` strictly.** Rejected — see Architectural decisions above. The Vault/ItemTreasure pattern is correct *for entities not serialized via groups*; PetSpecies is.
