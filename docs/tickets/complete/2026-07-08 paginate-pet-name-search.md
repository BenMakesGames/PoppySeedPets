# Paginate the Pet Name Search (`app-select-pet`)

## Context
**Current behavior**: The pet name typeahead (`app-select-pet` — the "Name (or part thereof)" box used in many item/dialog flows) returns only the first 5 matching pets and has no way to see the rest. The backend `TypeaheadService::search()` applies a hard `LIMIT` (default 5) and returns a flat array with no total count; the frontend renders whatever it receives.

**New behavior**: The pet name search returns 10 results per page and shows pagination controls (via the deprecated-but-fit-for-purpose `app-paginator`) so players can page through *all* matches. The two backend endpoints that feed `app-select-pet` return a paginated `FilterResults` shape (`page`, `pageCount`, `results`) instead of a flat array, and the component re-queries the backend when the player changes pages. The base `TypeaheadService` contract and the other typeaheads (user, item, species) are left unchanged.

## Prerequisites
None.

## Scope
### In scope
- Backend: a new paginated search path on the `TypeaheadService` base (additive — existing `search()` stays), plus converting the **two** pet-typeahead endpoints that back `app-select-pet` to return `FilterResults`.
- Frontend: `app-select-pet` handles the `FilterResults` shape, tracks page state, renders `app-paginator`, and re-fetches on page change.

### Out of scope
- The other typeaheads that share the `TypeaheadService` base — user search (`UserTypeaheadService`), item search (`ItemTypeaheadService`), species search (`PetSpeciesTypeaheadService`). Their `search()` calls and response shapes stay exactly as they are.
- The unrelated advanced-filter component `app-pet-search` (`shared/component/pet-search`) — not touched.
- Switching `app-select-pet` to the URL-based `app-url-paginator`. It's deliberately staying on `app-paginator` because the component lives in dialogs/inline flows with no route of its own.

## Relevant Docs & Anchors
Read these before coding:
- **Established paginated-response pattern**: `App\Controller\Vault\GetVaultContentsController` — the canonical example of building a `FilterResults` with Doctrine's `Paginator` (`->count()` for the total, `setFirstResult`/`setMaxResults` for the page window, clamping `page` into range) and serializing it with the `SerializationGroupEnum::FILTER_RESULTS` group.
- **Response DTO**: `App\Model\FilterResults` (`page`, `pageCount`, `pageSize`, `resultCount`, `results`; scalar fields gated behind the `filterResults` group).
- **The service to extend**: `App\Service\Typeahead\TypeaheadService` (base, `@template T`) and its subclasses `PetTypeaheadService`, `PetRelationshipTypeaheadService`. Note the ranked single-query approach (`HIDDEN prefixRank` computed select + `orderBy prefixRank` then name) — a prior ticket, `docs/tickets/complete/2026-05-30 fix-species-typeahead-duplicate-results.md`, established this shape; preserve it.
- **Endpoints to convert**: `App\Controller\Pet\TypeaheadController` (`GET /pet/typeahead`) and the `troubledRelationships` action in `App\Controller\Pet\SelfReflectionController` (`GET /pet/typeahead/troubledRelationships`).
- **Frontend component**: `shared/component/select-pet/select-pet.component.ts` + `.html`. Consumers pass an optional `petMapper` (see `home/component/pet-pick-self-reflection`, which uses the `troubledRelationships` endpoint and maps each `{ pet, possibleRelationships }` row down to its `pet`).
- **Paginator**: `shared/component/paginator/paginator.component` (`app-paginator`) — zero-based `page`, two-way `[(page)]`, `(change)` event; **does not hide itself** at low page counts.

## Constraints & Gotchas
- **Don't change the base `search()` signature or return type.** It's shared by 5 subclasses (Pet, User, Item, PetSpecies, PetRelationship); user/item/species callers pass a `$maxResults` and expect a flat array. Add pagination as a *new* method on the base rather than mutating the existing contract.
- **Serialization groups must be combined.** `FilterResults`' scalar fields (`page`, `pageCount`, …) only serialize when the `filterResults` group is present. The pet payload inside `results` needs its own groups too. So `/pet/typeahead` serializes with `[FILTER_RESULTS, MY_PET, MY_PET_LOCATION]` and `troubledRelationships` with `[FILTER_RESULTS, PET_PUBLIC_PROFILE]`. Omitting `FILTER_RESULTS` silently drops `pageCount`/`page` and the paginator gets `undefined`.
- **`troubledRelationships` returns a *different* row shape** — `array_map` wraps each pet as `{ pet, possibleRelationships }` (that's why `app-select-pet` has a `petMapper` input). Preserve that mapping, but apply it to the *paginated* page of results, and wrap the mapped array in `FilterResults.results`.
- **Doctrine `Paginator` + the `HIDDEN prefixRank` select**: these are single-entity queries with no to-many fetch-joins, so `Paginator` should count correctly, but sanity-check the count during testing (see Test Plan). If the computed-column select trips the count walker, mirror the count via a sibling query using the same `addQueryBuilderConditions` WHERE.
- **Debounce vs. paging**: the typeahead input is debounced (400ms) via a `fromEvent(...).pipe(debounceTime, distinctUntilChanged, ...)` stream. A page-change must re-fetch immediately at the new page, *not* go through that keyup stream.

## Open Decisions
1. **Where `PAGE_SIZE = 10` lives** — a const on the paginated service method's default arg vs. a controller constant (as `GetVaultContentsController::PAGE_SIZE`). Default: follow the vault's controller-constant convention, or a default param on the new service method — implementer's call.
2. **New method name/signature on `TypeaheadService`** — e.g. `searchPaginated(field, search, page, pageSize = 10): FilterResults`. Default: mirror the existing `search()` param order, adding `page`/`pageSize`.
3. **Count strategy** — reuse the ranked `QueryBuilder` inside a Doctrine `Paginator`, or run a dedicated `COUNT` query sharing `addQueryBuilderConditions`. Default: Doctrine `Paginator` (matches the vault); fall back to a sibling count query only if the `HIDDEN` select misbehaves.

## Acceptance Criteria
- [ ] `GET /pet/typeahead?search=<q>` returns a `FilterResults`-shaped payload: `page`, `pageCount`, and `results` (an array of at most 10 pets serialized under `MY_PET` + `MY_PET_LOCATION`).
- [ ] `GET /pet/typeahead/troubledRelationships?...&search=<q>` returns a `FilterResults`-shaped payload whose `results` are the `{ pet, possibleRelationships }` rows (pet serialized under `PET_PUBLIC_PROFILE`), at most 10 per page.
- [ ] Requesting a page beyond the last returns the clamped last page rather than erroring or returning an empty result set for an in-range query.
- [ ] The base `TypeaheadService::search()` and its user/item/species callers are unchanged — those endpoints still return their existing flat arrays.
- [ ] In `app-select-pet`, a search with more than 10 matches shows `app-paginator`; clicking a page loads that page's pets from the backend for the *same* search text.
- [ ] `app-paginator` is not rendered when there is 0 or 1 page of results.
- [ ] Editing the search text resets to page 0.
- [ ] Selecting a pet still works on every page (including after paging and with a `petMapper`-using consumer such as pet self-reflection).

## Implementation

### 1. Add a paginated search to `TypeaheadService`
Intent: provide page/pageCount/total without disturbing the existing flat-array `search()`. In `App\Service\Typeahead\TypeaheadService`, add a new method (e.g. `searchPaginated`) that builds the *same* ranked query as `search()` (prefix-rank `HIDDEN` select, `LIKE` WHERE, `addQueryBuilderConditions`, `orderBy prefixRank` then the field), but instead of `setMaxResults(5)` returns a populated `FilterResults`: wrap the QB in a Doctrine `Paginator` for the count, set `page`/`pageSize`/`pageCount`/`resultCount`, clamp the requested page into `[0, pageCount-1]`, then window with `setFirstResult(page * pageSize)` / `setMaxResults(pageSize)`. Model the mechanics on `GetVaultContentsController`. Keep `search()` as-is. Consider factoring the shared QB construction so both methods stay in sync.

### 2. Convert `/pet/typeahead` to return `FilterResults`
In `App\Controller\Pet\TypeaheadController::typeaheadSearch`, read a `page` query param (default 0), call the new paginated method on `PetTypeaheadService`, and return the `FilterResults` via `ResponseService::success` serialized with `[SerializationGroupEnum::FILTER_RESULTS, SerializationGroupEnum::MY_PET, SerializationGroupEnum::MY_PET_LOCATION]`. Preserve the existing `speciesId` / user-scoping setup.

### 3. Convert `troubledRelationships` to return `FilterResults`
In the `troubledRelationships` action of `App\Controller\Pet\SelfReflectionController`, read a `page` param and call the paginated method on `PetRelationshipTypeaheadService`. Keep the existing `array_map` that turns each matched pet into `{ pet, possibleRelationships }`, but apply it to the paginated page's `results` and place the mapped array back into `FilterResults.results` (page/pageCount from the paginated call). Serialize with `[SerializationGroupEnum::FILTER_RESULTS, SerializationGroupEnum::PET_PUBLIC_PROFILE]`.

### 4. Teach `app-select-pet` the `FilterResults` shape
In `select-pet.component.ts`, change the API result handling (in both `reload()` and the `ngOnInit` keyup subscription's `next`, plus the `suggest()` return type) so `r.data` is a `FilterResults`: set the raw pet array from `r.data.results` (this is what `results`/`doSelect`'s `.find` and `petMapper.map` operate on — keep `results` as the array so the template's empty-state check `results.length === 0` still holds), and store `page`/`pageCount` from `r.data`. Add a `page` field (default 0) and include it in the outgoing query `data` object alongside `search`/`additionalFilters`.

### 5. Reset to page 0 on new search; re-fetch on page change
Intent: paging and typing are distinct triggers. When the search text changes (the debounced keyup stream), set `page = 0` before issuing the request. Add a method (e.g. `goToPage(p)`) that sets `page = p` and issues the same request immediately — reading the current input value directly, bypassing the debounce stream — so a page click doesn't wait on `debounceTime`/`distinctUntilChanged`.

### 6. Render the paginator in the template
In `select-pet.component.html`, inside the results block, add `<app-paginator [pageCount]="pageCount" [(page)]="page" (change)="goToPage(page)" />` (or the `(pageChange)`-callback form — mirror an existing consumer such as `assemble-team.dialog.html`). Guard it with `@if(pageCount > 1)` so single-page and empty searches don't render a lone "Page 1". Place it below the results `<ul>`.

## Test Plan
- [ ] API (`api/`): `composer run php-cs-fixer-dry-run` and `php vendor/bin/phpstan` pass.
- [ ] `GET /pet/typeahead?search=a` (as a user with >10 name matches) returns `pageCount > 1`, `results.length === 10`, and correct `page`. Request `page=1` and confirm a different, non-overlapping set of pets. Confirm `resultCount` equals the true number of matches (verify the `Paginator` count against a manual DB count with the same `LIKE`).
- [ ] Request a `page` past the end (e.g. `page=999`) and confirm it clamps to the last page rather than erroring/empty.
- [ ] `GET /pet/typeahead/troubledRelationships` (via the pet self-reflection flow) returns `FilterResults` whose `results` still carry `possibleRelationships`, paginated 10/page.
- [ ] Regression: user search, item search, and species search typeaheads still return their existing flat arrays and behave unchanged.
- [ ] Frontend: open a dialog using `app-select-pet` (e.g. Renaming Scroll), search a common substring — confirm 10 results, paginator appears, clicking page 2 loads the next 10 for the same text, and selecting a pet on page 2 works.
- [ ] Frontend: search a rare substring returning ≤10 matches — confirm no paginator renders. Then edit the text to a broad match — confirm it jumps back to page 1.
- [ ] Frontend: exercise a `petMapper` consumer (pet self-reflection's troubled-relationships picker) — confirm paging and selection both work through the mapper.

## Learnings

### How the Open Decisions resolved
1. **`PAGE_SIZE = 10`** — implemented as a default arg (`$pageSize = 10`) on `TypeaheadService::searchPaginated()` rather than a controller constant. The size is intrinsic to the shared search behavior, and both endpoints want the same value, so the service is the single source of truth. (The vault keeps its 50 as a controller constant because that page size is vault-specific; here the constant would have had to be duplicated across two controllers.)
2. **New method** — `searchPaginated(string $fieldToSearch, string $searchString, int $page = 0, int $pageSize = 10): FilterResults`, mirroring `search()`'s param order with `page`/`pageSize` appended.
3. **Count strategy** — Doctrine `Paginator` (the ticket's default), and it worked without a sibling COUNT query. See the count-walker note below for the one non-obvious tweak that made it safe.

### Architectural decisions
- **Factored the ranked query into `private buildRankedQuery()`.** `search()` and `searchPaginated()` now both build the identical prefix-rank/`LIKE`/`addQueryBuilderConditions` query and only differ in how they finish it (`setMaxResults` + execute vs. wrap in a `Paginator`). This keeps the two paths from drifting — a change to ranking or escaping lands in one place.
- **`new Paginator($qb, fetchJoinCollection: false)`.** These typeahead queries fetch a single entity with no to-many collection pulled into the SELECT (the `PetRelationship` join is filtered by a `WITH` to one row per pet and isn't selected), so there's nothing for the fetch-join walker to de-duplicate. Turning `fetchJoinCollection` off keeps Doctrine on the simple `CountWalker`, which strips the `ORDER BY` — and with it the `HIDDEN prefixRank` alias — before counting. That sidesteps the gotcha the ticket flagged (a computed-column `ORDER BY` tripping the output walker) without needing a fallback sibling COUNT query.

### Problems encountered
- **PHPStan `argument.type` on the `troubledRelationships` `array_map`.** `FilterResults::$results` is typed as a bare `array` (mixed), so a closure declaring `fn(Pet $otherPet) => …` is *narrower* than the `callable(mixed)` PHPStan infers — an error. Fixed exactly as `GetVaultContentsController` does it: pull the paginated results into a `/** @var Pet[] $matchedPets */` local before mapping. This is the established pattern in this codebase for typing `iterator_to_array($paginator)` / `FilterResults::$results` output; it is not a "silence the analyzer" cast.

### Interesting tidbits
- The frontend already had `FilterResultsSerializationGroup<T>` (`webapp/src/app/model/filter-results.serialization-group.ts`) and a working `app-paginator` consumer to copy from (`assemble-team.dialog`). `app-select-pet` now stores `page`/`pageCount` and funnels all three response handlers (`reload`, the keyup stream, and the new `goToPage`) through a single private `applyResults()` so the FilterResults-unwrapping logic lives in one place.
- **Debounce vs. paging** was handled with a `tap(() => this.page = 0)` in the keyup pipe (typing resets to page 0) and a separate `goToPage()` that reads the input value directly and re-fetches immediately, bypassing `debounceTime`/`distinctUntilChanged`.
- All ~20 `app-select-pet` consumers hit either the default `/pet/typeahead` or the `troubledRelationships` endpoint — both converted — so no consumer is left receiving the old flat array. The component's encapsulation (consumers only bind `(selected)`) meant zero consumer-side changes were needed.

### Related areas affected / follow-ups
- `SelfReflectionController::troubledRelationshipsTypeaheadSearch()` injects `PetRelationshipService $petRelationshipService` but only ever calls it statically (`PetRelationshipService::…`), so the injected param is dead — and was already dead before this ticket. Left untouched to hold scope; a trivial follow-up could drop the unused parameter.

### Rejected alternatives
- **Sibling `COUNT` query sharing `addQueryBuilderConditions`.** The ticket's fallback for if the `HIDDEN` select tripped the count walker. Not needed once `fetchJoinCollection: false` put counting on the simple `CountWalker`; adding it would have duplicated the WHERE-building for no benefit.
- **Making `search()` itself paginated / changing its signature.** Explicitly out of scope and hazardous — five subclasses and the user/item/species callers depend on the flat-array contract. Pagination was added as a purely additive sibling method.
