# Fix "Invalid ULID" 500 on Malformed Species ID Input

## Context
**Current behavior**: Passing a non-ULID string as a species id to any of three endpoints throws a raw `\InvalidArgumentException('Invalid ULID...')` from `Symfony\Component\Uid\Ulid::fromString()`. That exception type is not handled by `ExceptionEventSubscriber`, so it falls through to the generic **500** branch — which logs `critical` and emails Ben. The pet-search species value is serialized into the search URL and read straight back out by the frontend without ULID validation (`pet-search-model.ts`), so any shared, bookmarked, hand-edited, or stale search URL with a malformed `?filter[species]=...` reliably 500s. Bots hitting `/pet` with junk params do the same.

**New behavior**: Malformed ULID input on these endpoints returns a clean **422** (`PSPFormValidationException`) whose message names the offending field, so the user knows what to correct. A new static helper on `App\Functions\ULID` centralizes "validate-and-parse-or-throw," ready for the further int→ULID primary-key conversions on the roadmap.

## Scope
### In scope
- New static helper method on `App\Functions\ULID` that validates a string is a ULID and either returns a `Symfony\Component\Uid\Ulid` or throws a field-specific `PSPFormValidationException`.
- Replace the three unguarded `Ulid::fromString()` call sites with the helper.

### Out of scope
- Frontend-side validation of the species URL param — the server-side guard is the fix; the frontend already forwards whatever is in the URL by design.
- Auditing ULID parsing in code paths outside the three call sites named below.
- Any actual int→ULID PK migration work (tracked separately; this just prepares a reusable helper).

## Relevant Docs & Anchors
- **Analogue ticket**: `docs/tickets/complete/2026-05-16 pet-species-ulid-primary-key.md` — establishes that `PetSpecies.id` is a `Ulid` and that more PKs are slated to follow; context for why a shared helper is worth it.
- **Code anchors**:
  - `App\Functions\ULID` (`api/src/Functions/ULID.php`) — existing ULID helper (currently only *generators*); home for the new method.
  - `App\Service\Filter\PetFilterService::filterSpecies()` — the reported crash site; note it already throws `PSPFormValidationException('Invalid species ID.')` for the non-string case, so the new guard mirrors code already in the method.
  - `App\Controller\Pet\TypeaheadController` — `setSpeciesId(Ulid::fromString($request->query->getString('speciesId')))`, behind the `has('speciesId')` check.
  - `App\Controller\Item\PetAlteration\TransmigrationSerumController` — `Ulid::fromString($speciesIdRaw)`; already guards empty-string via `PSPInvalidOperationException`, but not malformed.
  - `App\EventSubscriber\ExceptionEventSubscriber::onKernelException()` — confirms `PSPFormValidationException` → 422 and that an unmapped exception → 500 + critical log/email.

## Constraints & Gotchas
- **Class-name collision (case-insensitive).** The existing helper class is named **`ULID`**, and PHP class names are case-insensitive — it collides with `Symfony\Component\Uid\Ulid`. The helper file itself returns a Symfony `Ulid`, and any caller that imports both will hit a "Cannot use ... as Ulid because the name is already in use" fatal. Resolve with an alias on the Symfony import (e.g. `use Symfony\Component\Uid\Ulid as SymfonyUlid;`) in `ULID.php` and in any call site that needs both. At the three call sites, prefer routing entirely through the helper so the Symfony `Ulid` import can be dropped where it's no longer referenced directly.
- `PSPFormValidationException`'s constructor takes a single client-facing message string — the message is shown verbatim to the user, so it must read as user-facing copy, not a developer string.

## Open Decisions
1. **Message wording** — e.g. `'"species" is not a valid ID.'`. Default: a short, field-named sentence consistent with existing `PSPFormValidationException` copy (see the messages in `Pet`/account controllers). Implementer's call.
2. **Nullable/optional variant** — `TypeaheadController` only parses when `has('speciesId')`, so a single required-value helper suffices. Add a nullable convenience overload only if it reads more cleanly; otherwise keep one method. Default: one required-value method.
3. **Field-label source** — caller passes a friendly label string (`'species'`, `'speciesId'`). Default: caller-supplied literal, not derived.

## Acceptance Criteria
- [ ] `App\Functions\ULID` has a static method that takes a string value plus a field label, returns a `Symfony\Component\Uid\Ulid` for valid input, and throws `PSPFormValidationException` (naming the field) for invalid input. Validation uses `Ulid::isValid()`; no raw `\InvalidArgumentException` escapes.
- [ ] `PetFilterService::filterSpecies()`, `TypeaheadController`, and `TransmigrationSerumController` all obtain their `Ulid` via the new helper; none call `Ulid::fromString()` directly on request-supplied input.
- [ ] A request to `GET /pet` with a non-ULID `filter[species]` returns HTTP 422 with a field-named message — not 500, and no `critical` log/email is emitted.
- [ ] The same holds for `GET` typeahead with a malformed `speciesId` and for the transmigration-serum POST with a malformed `species`.
- [ ] Valid ULID species ids continue to filter/typeahead/transmigrate exactly as before (no behavior change on the happy path).

## Implementation
### 1. Add the validate-and-parse helper to `App\Functions\ULID`
Intent: one place that converts a request string into a `Ulid` or fails loudly-but-cleanly, so the remaining int→ULID conversions reuse it. In `api/src/Functions/ULID.php`, add a static method accepting the raw string and a field label. Guard with `Ulid::isValid()`; on failure throw `PSPFormValidationException` with a message naming the field; on success return `Ulid::fromString()`. Because this file declares class `ULID` and must reference `Symfony\Component\Uid\Ulid`, alias the Symfony import (see Constraints). Add the `App\Exceptions\PSPFormValidationException` import.

### 2. Route `PetFilterService::filterSpecies()` through the helper
Replace the direct `Ulid::fromString($value)` (the line right after the existing `is_string` guard) with a call to the new helper, passing `'species'` as the field label. The existing `if(!is_string($value))` guard can stay or be folded into the helper path — implementer's call, but the binary/`toBinary()` usage downstream must keep working unchanged.

### 3. Route `TypeaheadController` through the helper
Inside the existing `if($request->query->has('speciesId'))` block, replace `Ulid::fromString(...)` with the helper, field label `'speciesId'`. Drop the now-unused direct `Ulid` import if nothing else in the file needs it.

### 4. Route `TransmigrationSerumController` through the helper
Replace `Ulid::fromString($speciesIdRaw)` with the helper, field label `'species'`. Leave the existing empty-string `PSPInvalidOperationException` guard in place (it gives a more specific "you didn't pick a species" message); the helper covers the malformed-but-non-empty case.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass (run in `api/`).
- [ ] `GET /pet?filter[species]=not-a-ulid` → 422 with a message naming the species field; confirm no `critical` entry appears in the log and no error email is triggered.
- [ ] `GET /pet?filter[species]=<valid PetSpecies ULID>` → still returns the filtered pet list as before.
- [ ] Pet typeahead with `?speciesId=garbage` → 422; with a valid ULID → typeahead suggestions as before.
- [ ] Transmigration serum POST with `species=garbage` → 422; with empty `species` → still the existing "A species to transmigrate to was not selected." message; with a valid ULID → serum proceeds as before.

## Learnings

### Architectural decisions
- **Helper name & signature**: `ULID::fromUserInput(string $value, string $fieldLabel): SymfonyUlid`. Picked `fromUserInput` over `parse`/`fromString` because the name communicates the *intent* (this is the request-input variant that fails cleanly), and it can't be confused with Symfony's `Ulid::fromString`. Chose the **single required-value method** (Open Decision #2 default) — both real callers either guard `has()`/empty-string upstream or genuinely require a value, so a nullable overload would have been dead weight.
- **Message wording** (Open Decision #1): `'"species" is not a valid ID.'` — short, field-named, double-quoted field label. Reads as user-facing copy, consistent with the existing `'Invalid species ID.'` in the same method.
- **Field label is caller-supplied literal** (Open Decision #3 default): callers pass `'species'` / `'speciesId'` verbatim; no derivation from route/param metadata.
- **Kept `PetFilterService`'s `is_string` guard**: the helper's signature is `string`, but `filterSpecies()` receives `mixed` from the filterer, so the upstream `if(!is_string($value))` guard is still required before calling the helper. Left its existing `'Invalid species ID.'` message untouched.

### Problems encountered
- **Class-name collision is real and bites at the import line, not the call site.** `App\Functions\ULID` and `Symfony\Component\Uid\Ulid` are the same name to PHP (case-insensitive). Inside `ULID.php` the Symfony import is aliased `as SymfonyUlid`. At the three call sites the fix was cleaner: routing entirely through the helper let us **drop** the `use Symfony\Component\Uid\Ulid;` import everywhere, so no alias was needed in callers — the local `$speciesId` variable still calls `->toBinary()` / `->equals()` / is passed to `find()` exactly as before (the helper returns a real Symfony `Ulid`).

### Interesting tidbits
- `ExceptionEventSubscriber` maps `PSPFormValidationException` to **422** but **overrides the message to a generic "required fields were missing"** *only* for raw `HttpException(422)`; the PSP exception branch (line 87-90) passes `$e->getMessage()` through verbatim, so our field-named message reaches the user intact.
- The transmigration controller's empty-string guard throws `PSPInvalidOperationException` (also 422, different copy) and runs *before* the helper, so "didn't pick a species" and "picked a malformed species" stay distinct messages.

### Verification notes
- `php-cs-fixer` (dry-run, 0 changes) and `phpstan` (level config, 0 errors over 912 files) both pass.
- The 422-vs-500 HTTP behavior is verified by **code inspection** of the throw → subscriber mapping, not by a live request — there's no automated HTTP/functional test harness exercised here, and the ticket's Test Plan is manual. The malformed-input paths now all throw `PSPFormValidationException` (mapped to 422, no `critical` log), and the happy path is byte-for-byte the old `Ulid::fromString` behavior.

### Related areas affected
- The new helper is the intended landing spot for the roadmap's further **int→ULID primary-key conversions** (see analogue ticket `2026-05-16 pet-species-ulid-primary-key.md`). Future request-input ULID parsing should call `ULID::fromUserInput` rather than `Ulid::fromString`.

### Rejected alternatives
- **Handling `\InvalidArgumentException` globally in `ExceptionEventSubscriber`**: rejected — too broad (would swallow genuine internal misuse as 422) and doesn't name the offending field for the user.
- **Frontend ULID validation of the species URL param**: explicitly out of scope; the frontend forwards the URL by design, so the server-side guard is the correct fix.
