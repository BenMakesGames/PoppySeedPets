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

- Deleting a file: every `path: src/...` entry for the deleted file must be removed. Unused baseline entries are fatal.
- Reducing error occurrences: if the `count:` on a remaining entry is now too high (e.g., you deleted a method containing one of three `(int)$mixed` casts), decrement `count:` accordingly. PhpStan reports an "expected N times, occurred M times" error.

### Serialization group strings live as literals

Most `#[Groups([...])]` annotations on entity fields use **string literals** (`'petGuild'`, `'guildMember'`, `'myPet'`), not enum references. Removing an entry from `SerializationGroupEnum` does NOT remove literal-string references scattered across `Pet`, `Item`, `PetSpecies`, etc. After renaming or deleting a group:

```
# catch string-literal references (both single- and double-quoted)
grep -rn "'petGuild'\|\"petGuild\"" api/src
```

Stale group strings don't break serialization (they just never match), but they're dead code and actively misleading.

### Pet entity: one-to-one inverse side

`Pet` is the inverse side of several 1:1 relations (e.g., `mappedBy: 'pet'`). The FK column therefore lives on the *other* table, not on `pet`. When writing a "drop feature X" migration, verify this by grepping historical migrations for the column name before adding ALTER TABLE pet DROP COLUMN statements that will fail.
