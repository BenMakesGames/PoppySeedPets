# Fix Species Typeahead Returning Each Result Twice

## Context
**Current behavior**: When filtering pets by species (the species typeahead in the pet-search dialog), each matching species appears **twice** in the suggestion list. Other typeaheads (item, user, pet, pet-relationship) are unaffected.

The duplication comes from the shared `App\Service\Typeahead\TypeaheadService::search()`. It runs a two-pass lookup: a prefix match (`name LIKE 'x%'`, limit 5), and — if that returns fewer than 5 rows — a substring match (`name LIKE '%x%'`) that is *supposed* to exclude the already-found rows via `e.id NOT IN (:ids)`, where `$ids = array_map(fn($e) => $e->getId(), $entities)`. For `PetSpecies` — the **only** typeahead entity whose `id` is a `Ulid` (binary) rather than an `int` (see `docs/tickets/complete/2026-05-16 pet-species-ulid-primary-key.md`) — Doctrine's array-parameter inference binds the `Ulid` objects as their base32 strings, which never match the `BINARY(16)` column. The exclusion silently matches nothing, the second pass re-returns the prefix matches, and `array_merge` yields each species twice. Int-keyed entities bind correctly, which is why only species misbehaves.

**New behavior**: The species typeahead returns each matching species at most once, still up to 5 results, with prefix matches ordered ahead of substring-only matches. The fix lives in the shared base class, so the de-duplication is type-agnostic and protects every typeahead regardless of id type.

## Scope
### In scope
- Make `TypeaheadService::search()` de-duplicate its merged result set by entity id, so no entity can appear twice regardless of whether its id is `int` or `Ulid`.
- Remove the dead, no-op `concat()` operator from the species typeahead component's RxJS pipe (opportunistic in-pass cleanup — not the bug's cause, but dead code in the file being fixed's frontend counterpart).

### Out of scope
- A regression/unit test for `TypeaheadService` (deliberately deferred).
- Any change to the species-specific `PetSpeciesTypeaheadService` (its `addQueryBuilderConditions` is a no-op and is not involved).
- The single-query rewrite alternative (prefix-first ordering via a HIDDEN `CASE` expression) — rejected in favor of the smaller dedup change.
- Fixing the `NOT IN` ULID binding "properly" at the SQL/parameter-type layer — the PHP-side dedup supersedes it; the now-redundant clause is removed rather than repaired.

## Relevant Docs & Anchors
- **Root-cause origin**: `docs/tickets/complete/2026-05-16 pet-species-ulid-primary-key.md` — established that `PetSpecies.id` is a `Ulid`; that change is what exposed this latent bug. Note its Learnings already flagged the species typeahead component as a touch point.
- **Related**: `docs/tickets/complete/2026-05-28 fix-invalid-ulid-species-filter-500.md` — another fallout of the same ULID migration; useful context for how species ids flow through request handling.
- **Code anchors**:
  - `App\Service\Typeahead\TypeaheadService::search()` (`api/src/Service/Typeahead/TypeaheadService.php`) — the fix site; the two-pass merge with the `e.id NOT IN (:ids)` block.
  - `PetSpeciesTypeaheadService`, `ItemTypeaheadService`, etc. — the subclasses that inherit `search()`; confirm none override it.
  - `FindPetSpeciesByNameComponent` (`webapp/src/app/module/shared/component/find-pet-species-by-name/find-pet-species-by-name.component.ts`) — the `concat()` removal site, in the `ngOnInit` `fromEvent(...).pipe(...)` chain.

## Constraints & Gotchas
- **Dedup key must work for both `int` and `Ulid` ids.** `getId()` returns `int` for item/user/pet/relationship and `Ulid` for species. Casting to `(string)` normalizes both (`Ulid::__toString()` yields canonical base32), so a string-keyed seen-set is type-agnostic. Don't reach for an id type that only fits one entity.
- **Preserve "prefix matches first" ordering.** Pass 1 (prefix) results must keep priority over pass-2 (substring-only) results after dedup. Merge with pass-1 first and dedup in a way that keeps first-seen order.
- **Result count.** With the old `NOT IN` gone, pass 2 should fetch the full `$maxResults` (over-fetch) rather than `$maxResults - count(pass1)`; after merge + dedup, trim back to `$maxResults`. Otherwise species searches with overlapping passes could return fewer than 5 distinct rows.
- **`php-cs-fixer` + `phpstan`** must stay green (run in `api/`).

## Acceptance Criteria
- [ ] A species typeahead search returns each matching `PetSpecies` **at most once** (no entity id appears twice in `search()`'s return value).
- [ ] When at least 5 species match, `search()` returns exactly 5 distinct results; when fewer match, it returns all distinct matches.
- [ ] Prefix matches (`name LIKE 'search%'`) still appear ahead of substring-only matches (`name LIKE '%search%'`) in the returned order.
- [ ] Item, user, pet, and pet-relationship typeaheads continue to return correct, non-duplicated results (no regression for int-keyed entities).
- [ ] The `concat` operator is no longer imported or used in `FindPetSpeciesByNameComponent`; the typeahead still debounces input and issues one suggest request per settled query.
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass in `api/`; `ng build` passes in `webapp/`.

## Implementation
### 1. De-duplicate the merged result set in `TypeaheadService::search()`
**Why**: The two-pass merge can return the same entity from both passes (the `NOT IN (:ids)` exclusion is unreliable for non-int — i.e. `Ulid` — ids). A PHP-side dedup by id is type-agnostic and fixes every typeahead at once.

In `api/src/Service/Typeahead/TypeaheadService.php`, in the `if(count($entities) < $maxResults)` branch:
- Drop the `e.id NOT IN (:ids)` clause and the `$ids = array_map(...)` line — they become redundant once dedup happens in PHP. With them gone, the second query no longer needs the `count($entities) > 0` / else split for choosing a limit.
- Have the second (substring) query fetch the full `$maxResults` (over-fetch) instead of `$maxResults - count($entities)`, keeping its `LIKE '%search%'` filter, `ORDER BY ... ASC`, and the `addQueryBuilderConditions($qb)` call unchanged.
- `array_merge($entities, <pass 2 results>)` as today, but with pass-1 results first so prefix matches retain priority.
- De-duplicate the merged array by entity id, preserving first-seen order, using a string-cast key (`(string)$e->getId()`) so it works for both `int` and `Ulid` ids.
- `array_slice` the de-duplicated array to `$maxResults` before returning.

Keep the early prefix-only path (when pass 1 already returns `>= $maxResults`) exactly as-is — that path never merges, so it can't duplicate.

### 2. Remove the dead `concat()` operator from the species typeahead component
**Why**: `concat()` (imported from `rxjs/operators`) is called with no arguments inside the `fromEvent(...).pipe(...)` chain in `FindPetSpeciesByNameComponent.ngOnInit` — it's a deprecated no-op and contributes nothing. It is **not** the cause of the duplication (results are replaced, not appended), but it's dead code in the same feature's frontend.

In `webapp/src/app/module/shared/component/find-pet-species-by-name/find-pet-species-by-name.component.ts`, remove the `concat()` step from the pipe and drop `concat` from the `rxjs/operators` import. Leave the remaining operators (`filter`, `map`, `debounceTime`, `distinctUntilChanged`, `switchMap`) and their order untouched.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass in `api/`; `ng build` passes in `webapp/`.
- [ ] Start the dev environment, open the pet-search dialog, and type a partial species name (e.g. a couple of letters that match several species) — each species appears **once**; the list is no longer doubled.
- [ ] Type a string that matches species both as a prefix and only as a substring (e.g. where some names *start with* the query and others merely *contain* it) — prefix matches show first, no duplicates, up to 5 results.
- [ ] Regression: exercise another typeahead (e.g. item search) — results are correct and not duplicated.
- [ ] Confirm the species typeahead still debounces (one request after typing settles) and selecting a result still filters the pet list as before.

## Learnings

### Architectural decisions
- **Dedup over NOT-IN repair.** As planned, the two-pass `search()` now over-fetches the substring pass to the full `$maxResults`, `array_merge`es prefix-first, dedups by a first-seen-preserving string key, and `array_slice`s back to `$maxResults`. The unreliable `e.id NOT IN (:ids)` clause and its `count($entities) > 0` limit-split are gone. Because dedup is PHP-side and keyed by `(string)$id`, it's type-agnostic and protects every typeahead, not just species.

### Problems encountered
- **PHPStan level 10 rejected the ticket's literal `(string)$e->getId()`** with `cast.string` ("Cannot cast mixed to string"). `TypeaheadService` is generic over an *unbounded* `@template T`, so `getId()` is typed `mixed` (the class already carries baseline entries for `getId() on mixed` and `array_merge … mixed given`). A bare string cast of `mixed` is a new, un-baselined violation.
  - **Resolution (genuine narrowing, not silencing):** before casting, guard `if(!is_int($id) && !$id instanceof Ulid) throw new \LogicException(...)`. After the guard PHPStan narrows `$id` to `int|Ulid`; `Ulid` is `Stringable`, so `(string)$id` is provably safe. No baseline entry, no `@var`, no `@phpstan-ignore`, no silencing cast. The guard is also honest documentation: every typeahead entity's id really is `int` or `Ulid`.

### Interesting tidbits
- The duplication was invisible for years because every other typeahead entity (`Item`, `User`, `Pet`, `PetRelationship`) has an `int` id, which Doctrine binds correctly in `NOT IN (:ids)`. Only the 2026-05 `PetSpecies` → `Ulid` migration exposed the latent bug — Doctrine binds `Ulid` array params as their base32 strings, never matching the `BINARY(16)` column, so the exclusion silently matched nothing.
- **Doctrine identity map** means the same row returned by both passes is the *same* object instance, so an `spl_object_id`/strict-`in_array` dedup would also have worked — but it relies on object identity rather than value, exactly the subtlety the ticket steered away from. The value-based string key is the more robust choice and is what's shipped.

### Related areas affected
- Frontend: dead no-op `concat()` removed from `FindPetSpeciesByNameComponent.ngOnInit`'s RxJS pipe and from the `rxjs/operators` import (opportunistic in-pass cleanup; never the cause of the doubling — results are *replaced*, not appended).

### Verification status
- `composer run php-cs-fixer-dry-run` ✓, `vendor/bin/phpstan --configuration=phpstan.dist.neon` ✓ (no errors), `ng build` ✓ (exit 0).
- Manual UI test-plan items (open pet-search dialog, type a partial species name, exercise item typeahead) were **not** executed — they require a running dev environment/browser. The code logic satisfies each criterion; left for manual confirmation.

### Rejected alternatives
- **Single-query rewrite** (prefix-first via a HIDDEN `CASE` ordering) — out of scope per ticket; larger blast radius than the dedup.
- **Repairing `NOT IN` at the parameter-type layer** (binding Ulids as `BINARY(16)`) — superseded by the PHP-side dedup; the clause was removed rather than fixed.
- **`spl_object_id`/strict `in_array` dedup** — works via the identity map but is identity- not value-based; less robust than the string key.
