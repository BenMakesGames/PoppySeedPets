## Architecture Decisions & Patterns

### Most POST URLs should read like actions to be taken

Examples of actions:

* `POST /florist/tradeForGiftPackage`
* `POST /fireplace/feedWhelp`
* `POST /pet/{petId}/feed`
* `PATCH /letter/{letterId}/read`

If you only ever use GET, POST, and maybe DELETE, that's fine - in most cases there's not much benefit to getting technical and using PATCH or PUT. (For example there's a PATCH endpoint for reading a letter that's kind of silly; may as well be a POST.)

> 🧚‍♀️ **Hey, listen!** It is still super-true that GET requests must not modify data (except for side-effects like logging or tracking the time a player was last active).

> **💻 Note for experienced web devs:** CRUD has its place, but PSP, like many complex web apps, has _business rules_ that need to be followed. Making PATCH endpoints that try to handle every operation is a path that leads to madness. When in doubt, go RPC-style; when & if you _know_ CRUD-style is correct, then go CRUD-style.

### Controller endpoints MAY contain plenty of logic

1. Start by putting all logic into a controller's endpoint.
2. Pull logic out of controller endpoints _when/if_ it needs to be shared between two endpoints.

> **💻 Note for experienced web devs:** YAGNI. KISS. The web API is _the_ API. We don't need to separate business logic from the web for imagined future use-cases.

### Use `#[MapRequestPayload]` for Request DTOs

Modern Symfony request handling. Migrate old code to use this when touching it.

### ResponseService (Critical)
Every API endpoint must return via `ResponseService`. It:
- Injects current user data into every response
- Delivers unread pet activity logs as "flash messages"
- Sets reload flags (`reloadInventory`, `reloadPets`) for the frontend
- Normalizes response structure: `{ success, data, activity, user, reloadInventory, reloadPets }`

### Pet Activity System
Core game loop documented in detail at `api/src/Service/PetActivity/CLAUDE.md`. Key flow:
1. Cron increments `activity_time` every minute (max 2880 min / 48 hours)
2. Player visits house → pets with 60+ minutes consume time and perform activities
3. Activities implement `IPetActivity` interface with `groupDesire()` (weighted random selection) and `possibilities()`
4. Results tracked via `PetActivityLog` → delivered as flash messages through `ResponseService`

### Lazy-Loaded Services
`PetActivity/` and `Holidays/` service trees are configured as lazy in `config/services.yaml`.

### Testability Abstractions
- Use `Clock` service instead of `new \DateTime()` / `new \DateTimeImmutable()`
- Use `IRandom` service instead of `rand()` / `random_int()`
- Both are mockable for deterministic tests

### Service Layer Details
See `api/src/Service/CLAUDE.md` for ResponseService patterns, activity log creation, and service conventions.

### PhpStan baseline hygiene

PhpStan runs with a baseline (`api/phpstan-baseline.neon`). When you delete a file or refactor code so that previously-ignored errors disappear, the baseline must be updated in the same commit, or phpstan will fail:

The two cases below earn a mention only because their *fix* isn't obvious — they are **not** a catalog of triggers to extend:

- Reducing error occurrences: if the `count:` on a remaining entry is now too high (e.g., you deleted a method containing one of three `(int)$mixed` casts), decrement `count:` accordingly. PhpStan reports an "expected N times, occurred M times" error.
- Changing a shaped-array return type: baseline messages for `return.type` errors embed the *full inferred array shape* (e.g. `but returns array{greenhouse: ..., fertilizer: ...}`). Adding/removing a key to the returned array changes that shape, so the baselined pattern no longer matches and phpstan fails with `ignore.unmatched`. Update the message to mirror the new shape (escaping `:` `{` `}` `|` `<` `>` as the existing entries do). When in doubt, run phpstan once, copy the new "but returns …" text from the reported error, and re-escape it.

**Everything else follows one rule** — so don't grow the list above unless a case carries a genuinely new fix mechanic. Any change that makes a suppressed error vanish, multiply, or change shape will break its baseline entry, and a stale entry (an unmatched ignore, an orphaned `path:`, a wrong `count:`) is itself a non-ignorable error. After any refactor — including deleting a file — grep `phpstan-baseline.neon` for the affected file/symbol and reconcile every match: delete orphaned entries, fix counts.

### Serialization group strings live as literals

Most `#[Groups([...])]` annotations on entity fields use **string literals** (`'petGuild'`, `'guildMember'`, `'myPet'`), not enum references. Removing an entry from `SerializationGroupEnum` does NOT remove literal-string references scattered across `Pet`, `Item`, `PetSpecies`, etc. After renaming or deleting a group:

```
# catch string-literal references (both single- and double-quoted)
grep -rn "'petGuild'\|\"petGuild\"" api/src
```

Stale group strings don't break serialization (they just never match), but they're dead code and actively misleading.

### Pet entity: one-to-one inverse side

`Pet` is the inverse side of several 1:1 relations (e.g., `mappedBy: 'pet'`). The FK column therefore lives on the *other* table, not on `pet`. When writing a "drop feature X" migration, verify this by grepping historical migrations for the column name before adding ALTER TABLE pet DROP COLUMN statements that will fail.

### INT → ULID PK conversions

When migrating an entity's PK from auto-increment INT to `BINARY(16)` ULID (precedent: `Version20260302120000` for `item_treasure`, single FK; `Version20260516094631` for `pet_species`, three FKs), follow these patterns:

- **`Ulid` is generated PHP-side via `new Ulid()`** in the migration's `postUp()`. MySQL has no native ULID generator (`UUID()` is RFC4122 v1, structurally different).
- **`up()` does DDL prep** (drop FKs/indexes, add `new_id` / `new_<fk>_id` shadow columns as `BINARY(16) NULL`). **`postUp()` does row-by-row UUID assignment + FK rewiring + final rename + re-add constraints.** **`down()` throws `RuntimeException`** — integer IDs are gone forever.
- **Re-use the original constraint and index names** (look them up via `SHOW CREATE TABLE` or grep the seed SQL) so phpstan baselines and future migrations don't see drift.
- **Verify there are no NULL FK rows in `postUp()` before renaming shadow columns to NOT NULL** (`SELECT COUNT(*) FROM <table> WHERE <fk_col> IS NULL`). The source columns are typically NOT NULL today, but verify rather than silently inserting a NULL.
- **Doctrine infers BINARY(16) on the FK side automatically** from `ManyToOne(targetEntity: ...)`'s PK type. No `JoinColumn(type: ...)` change is needed on the dependent entities unless someone hand-wrote one (grep `JoinColumn.*<rel_name>` to confirm).
- **`PDO::FETCH_FUNC` callback parameters in raw-SQL controllers (`SimpleDb::mapResults`)** receive the binary column as a 16-byte PHP string. Convert with `Ulid::fromBinary($raw)->toBase32()` for JSON responses. Don't wrap in `HEX(...)` SQL-side — the round-trip is dead weight.
- **The default Symfony `UidNormalizer` serializes `Ulid` → base32 string** (e.g. `01HXAB...`, 26 chars) with no extra config.
- **A `Ulid` bound into a DQL comparison silently matches nothing against a `BINARY(16)` column.** Doctrine binds the `Ulid` as its base32 *string*, which never equals the binary PK — the clause matches zero rows with no error (int PKs bind fine, so this only bites migrated entities). Bind the raw bytes instead: `->setParameter('species', $ulid->toBinary(), ParameterType::BINARY)` (see `PetTypeaheadService::addQueryBuilderConditions()`), and `array_map(fn($u) => $u->toBinary(), $ulids)` for an `IN (:ids)` array. This is why the old species-typeahead `e.id NOT IN (:ids)` exclusion failed; the fix collapsed the two-pass search into a single ranked query so no id is ever bound for exclusion at all — prefer designing the query so it can't return a row twice over excluding ids after the fact.

### ULID input parsing and PhpStan

When a controller, filter, or service accepts a ULID string from user input, PhpStan will reject `Ulid::fromString($value)` if `$value` is `mixed`, `string|float|int|bool|null` (the default return of `$request->request->get`), or anything wider than `string`. Three idioms:

- **Controllers**: use `$request->request->getString('key', '')` (or `getString` on query) — returns `string`, narrowed.
- **Filter services / anywhere with `mixed $value`**: guard with `if(!is_string($value)) throw new PSPFormValidationException(...);` before `Ulid::fromString`.
- **Raw-SQL `mapResults` closures**: type-hint each parameter explicitly (`fn(int $id, string $name, ...) => [...]`); PDO `FETCH_FUNC` returns string columns as strings and integer columns as ints/strings depending on driver flags, but explicit hints make phpstan happy and document the schema.

**For malformed (non-empty but non-ULID) input, use the shared helper `App\Functions\ULID::fromUserInput($value, $fieldLabel)`** — it guards with `Ulid::isValid()` and throws a field-named `PSPFormValidationException` (→ 422) instead of letting `Ulid::fromString()`'s raw `\InvalidArgumentException` fall through to a 500 (which `critical`-logs and emails Ben). Prefer this over an inline `try/catch`. The `$fieldLabel` (e.g. `'species'`, `'speciesId'`) is a caller-supplied literal, surfaced verbatim to the user.

**Class-name collision gotcha**: `App\Functions\ULID` and `Symfony\Component\Uid\Ulid` are the same name under PHP's case-insensitive class resolution. Inside `ULID.php` the Symfony import is aliased (`use Symfony\Component\Uid\Ulid as SymfonyUlid;`). At call sites, route entirely through the helper so you can **drop** the `use Symfony\Component\Uid\Ulid;` import — the returned object is a real Symfony `Ulid`, so `->toBinary()`/`->equals()`/`find($ulid)` all keep working without the import.
