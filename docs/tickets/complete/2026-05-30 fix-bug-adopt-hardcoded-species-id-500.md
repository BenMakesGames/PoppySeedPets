# Fix 500 When Adopting a Sentient Beetle (Hardcoded Integer Species ID)

## Context
**Current behavior**: `BugController::adopt()` (route `POST /item/bug/{inventory}/adopt`) looks up the pet species to create with `$em->getRepository(PetSpecies::class)->find(40)`. Since `PetSpecies.id` was migrated from auto-increment `INT` to `ULID` (see `2026-05-16 pet-species-ulid-primary-key.md`), binding the integer `40` to the now-`ulid`-typed primary key throws `Could not convert PHP value 40 to type ulid` from Doctrine's `AbstractUidType`. The exception is unmapped, so it falls through to a generic **500** (critical log + email to Ben). This means **every** Sentient Beetle "adopt" action has failed since the ULID migration shipped — it is fully broken, not an edge case.

**New behavior**: Adopting a Sentient Beetle creates the correct pet again. The species is resolved by name through the existing cached helper `PetSpeciesRepository::findOneByName($em, PetSpeciesName::SentientBeetle)` instead of by a hardcoded integer id.

## Prerequisites
- None. The `PetSpecies` ULID migration this fixes is already complete (`2026-05-16 pet-species-ulid-primary-key.md`).

## Scope
### In scope
- Add a `SentientBeetle` case to the `PetSpeciesName` enum.
- Replace the single hardcoded `->find(40)` lookup in `BugController::adopt()` with the name-based cached helper.

### Out of scope
- Any other `BugController` behavior (feeding, the queen-talk path, etc.).
- A broad audit of other hardcoded integer entity IDs — this ticket fixes the one known straggler; no other `PetSpecies::class)->find(<int>)` call exists (verified by grep).

## Relevant Docs & Anchors
- **Analogue / cause ticket**: `docs/tickets/complete/2026-05-16 pet-species-ulid-primary-key.md` — established `PetSpecies.id` as a `Ulid`; enumerated the request-driven species call sites but missed this hardcoded internal literal.
- **Sibling fix**: `docs/tickets/complete/2026-05-28 fix-invalid-ulid-species-filter-500.md` — fixed *request-input* ULID parsing via `ULID::fromUserInput`. Note that helper is **not** applicable here: this is an internal constant, not user input, so the name-lookup path is the correct shape.
- **Code anchors**:
  - `App\Controller\Item\BugController::adopt()` (`api/src/Controller/Item/BugController.php`) — the `->find(40)` call passed as the species argument to `$petFactory->createPet(...)`.
  - `App\Functions\PetSpeciesRepository::findOneByName()` — existing static helper; 24h result cache keyed on the species name; throws `PSPNotFoundException` if absent. Already used ~15 places (e.g. `GreenhouseService`, `AdoptionService`, `EggController`, `BeeLarvaController`, `BetaBugController`).
  - `App\Enum\PetSpeciesName` (`api/src/Enum/PetSpeciesName.php`) — string-backed enum of species names; partial (only species referenced from code). No beetle case yet.

## Constraints & Gotchas
- The migration assigned **random** ULIDs per row (`new Ulid()` in `Version20260516094631::postUp`) and is irreversible — there is no deterministic `40 → <ulid>` mapping. Looking up by a stable attribute (the name) is the only correct approach; do not substitute a literal ULID.
- The enum's backing string must exactly match the `pet_species.name` value. Per the request, the species name is `"Sentient Beetle"` (also the item name). If the row were missing/renamed, `findOneByName` throws a clean `PSPNotFoundException` rather than the raw DBAL fatal — a strictly better failure mode, but the happy path depends on the exact string.

## Acceptance Criteria
- [ ] `PetSpeciesName` has a `SentientBeetle` case whose backing value is exactly `'Sentient Beetle'`.
- [ ] `BugController::adopt()` resolves the species via `PetSpeciesRepository::findOneByName($em, PetSpeciesName::SentientBeetle)`; no integer literal is passed to a `PetSpecies` lookup anywhere in the method.
- [ ] Adopting a Sentient Beetle creates a pet of the Sentient Beetle species and no longer raises the `Could not convert PHP value 40 to type ulid` error (no 500, no critical log/email).
- [ ] The rest of `adopt()` is unchanged: random name/colors/flavor/merit, daycare-vs-home placement based on `getMaxPets()`, item consumption, and the flash messages all behave as before.

## Implementation
### 1. Add the `SentientBeetle` case to `PetSpeciesName`
In `api/src/Enum/PetSpeciesName.php`, add `case SentientBeetle = 'Sentient Beetle';`, keeping the existing alphabetical-ish ordering (alongside `Sentinel` / `Sneqo`). The backing value must match the DB `name` exactly.

### 2. Replace the hardcoded lookup in `BugController::adopt()`
In `api/src/Controller/Item/BugController.php`, swap the `$em->getRepository(PetSpecies::class)->find(40)` argument to `createPet(...)` for `PetSpeciesRepository::findOneByName($em, PetSpeciesName::SentientBeetle)`. Add the `App\Functions\PetSpeciesRepository` and `App\Enum\PetSpeciesName` imports if not already present. Leave every other argument to `createPet(...)` and the rest of the method untouched.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass (run in `api/`).
- [ ] In-game: use a Sentient Beetle item's "adopt" action with room at home — confirm a Sentient Beetle pet appears at home with the "finds a nice corner" message and the item is consumed.
- [ ] Repeat with a full house (pets at home ≥ max pets) — confirm the beetle goes to daycare with the "trundles happily into the daycare" message and pets do not reload.
- [ ] Confirm no `Could not convert PHP value 40 to type ulid` error and no critical log/error email is produced by the adopt action.

## Learnings

### Architectural decisions
- Resolved the species by name via `PetSpeciesRepository::findOneByName($em, PetSpeciesName::SentientBeetle)`, exactly as the ticket prescribed. Confirmed against `db/seed/base.sql` that `pet_species` id 40 is the row named `'Sentient Beetle'`, so the new name-based lookup targets the same species the old `->find(40)` did.

### Problems encountered
- **Obsolete phpstan baseline entry (in-scope side effect).** The old `$em->getRepository(PetSpecies::class)->find(40)` returned `PetSpecies|null`, and a baseline entry in `api/phpstan-baseline.neon` suppressed the resulting `argument.type` error on `createPet()`'s `$species` param for `BugController.php`. `findOneByName()` returns a non-null `PetSpecies` (it throws `PSPNotFoundException` otherwise), so the suppressed error vanished and phpstan failed with `ignore.unmatched`. Fixed by deleting that baseline block. There was a *second*, identical baseline entry (line ~4798) for a different file — left untouched; it still matches.
- The `use App\Entity\PetSpecies;` import became unused once the literal lookup was gone (the entity was referenced nowhere else in the file). Removed it to keep `php-cs-fixer`'s `no_unused_imports` happy, and added `App\Functions\PetSpeciesRepository` + `App\Enum\PetSpeciesName` in their alphabetical slots.

### Interesting tidbits
- `findOneByName` caches per-name for 24h (`enableResultCache`), so the fix carries no per-adopt query cost after the first lookup.

### Verification limits
- Both `php-cs-fixer` (dry-run) and `phpstan` pass. The in-game Test Plan items (adopt with room at home, adopt with a full house → daycare, no 500/critical email) require a running game instance and were not exercised here — they remain manual checks for the user.

### Rejected alternatives
- Substituting a literal ULID for `40` was explicitly wrong: the ULID migration (`Version20260516094631`) assigned random ULIDs per row, so no stable `40 → <ulid>` mapping exists. Name lookup is the only correct resolution.
