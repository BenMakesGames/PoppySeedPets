# Remove Pet Guilds Feature

## Summary
Remove the pet-guilds feature in its entirety: the `Guild` and `GuildMembership` entities, the `GuildEnum`, every guild-gated activity branch, the frontend guild pages, and the courier system used by letters. Letters themselves are preserved — only the Correspondence-gated courier lookup is removed.

## Context
**Current behavior**: Pets can be members of one of 9 guilds (`Guild` + `GuildMembership` entities). Guild membership enables guild-specific pet activities (`GuildService`, `GizubisGardenService`), gates bonus outcomes in ~10 other pet-activity services via `Pet::isInGuild()`, and is surfaced in the Poppyopedia as a directory, member lists, and a help page. `LetterService::findRandomCourier()` looks up a Correspondence member (level ≥10, active account) to use as the in-fiction courier for letter deliveries, producing a second pet-activity log for that courier pet.

**New behavior**: Guilds do not exist. No guild entities, no guild enum, no guild-gated activity branches, no guild UI. Letters are still delivered (all four senders, all quest gating intact), but they always arrive via an unknown courier — no courier pet, no courier activity log. The guild-gated "bonus" content (Light-and-Shadow spirit hunting, High-Impact deep-sea/icy-moon branches, Gizubi's Garden adventures, etc.) is deleted outright rather than made universal.

## Acceptance Criteria
- [ ] `Guild.php` and `GuildMembership.php` entities no longer exist; no code in `api/src` references them.
- [ ] `GuildEnum.php` no longer exists; no code references `GuildEnum` or `isInGuild()`.
- [ ] A new Doctrine migration exists that drops the `guild_membership` and `guild` tables and the `guild_membership_id` FK column on `pet`.
- [ ] No historical migration files under `api/migrations/` are modified.
- [ ] `LetterService` compiles and runs without the `Guild`/`GuildMembership` join; all four letter sender flows (`doAnniversary`, `doSharuminyinka`, `doKatica`, `doHyssop`) still deliver letters and their quest-step advancement still works.
- [ ] Letter delivery produces a single activity log on the recipient pet (no courier log). The recipient-pet log no longer references the word "Correspondence" or a named courier pet.
- [ ] `GET /guild`, `GET /guild/{guild}`, and `POST /pet/{pet}/selfReflection/changeGuild` all return 404 (routes removed). `GET /pet/{pet}/selfReflection` still responds but no longer includes `membership` or `guilds` in its payload. `POST /pet/{pet}/selfReflection/reconcile` and `GET /pet/typeahead/troubledRelationships` continue to work.
- [ ] Poppyopedia no longer lists "Guilds" on the index, in the help glossary, or anywhere else; no broken links.
- [ ] Pet relationships panel (`pet-friends`) does not render a guild section.
- [ ] Pet activity runs to completion without errors for pets that previously had `guildMembership` rows (the migration handles the data removal; runtime code no longer looks at guild state).
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` pass in `api/`.
- [ ] Frontend compiles (`ng build` / dev server starts) without dangling imports.

## Scope
Backend: delete 2 entities, 1 enum, 2 services (`GuildService`, `GizubisGardenService`), 1 filter service (`GuildMemberFilterService`), the `Controller/Guilds/` directory, and half of `SelfReflectionController`. Rewrite `LetterService::doDeliverLetter` to drop the courier system. Strip guild-gated branches from ~11 pet activity files. Remove 3 serialization-group enum entries. Add one new drop-tables migration.

Frontend: delete guild directory/detail/help components + templates, 3 guild serialization-group models, the self-reflection "change guild" UI, guild sections in shared components, guild entries in the encyclopedia index and help glossary, 4 route entries, and several imports.

## Implementation

### 1. Add the drop migration
Migrations are append-only in this repo — do not edit any file under `api/migrations/`. Create a new timestamped migration that drops `guild_membership` first (it has the FK to `guild`), then `guild`, and drops the `guild_membership_id` column on `pet` (whose FK constraint must be dropped before the column). Follow the style of existing migrations under `api/migrations/2025/` — plain `up()` with `$this->addSql(...)` statements, a matching `down()` is optional/nominal since these migrations are forward-only in practice (check a recent migration to confirm the convention).

Also drop the row in `pet_activity_log_tag` whose name is `Guild` — it was created historically and is no longer referenced once `LetterService` stops tagging courier logs with it. Do this via `DELETE FROM pet_activity_log_tag WHERE name = 'Guild'` in the same migration.

### 2. Rewrite `LetterService` to remove the courier system
**Why**: The Correspondence guild is the only reason `findRandomCourier()` exists. With guilds gone, letters should always arrive via an unknown courier. The user has confirmed: no replacement courier logic, no replacement log text on a secondary pet.

**File**: `api/src/Service/PetActivity/LetterService.php`

- Delete `findRandomCourier()` entirely (lines ~281-322).
- In `doDeliverLetter()`:
  - Delete the entire `$courier = $this->findRandomCourier($pet);` block and the `if/else` that branches on whether a courier was found. Unconditionally set `$descriptionForPet = 'some pet they didn\'t recognize.';` — this matches the current null-courier path.
  - Delete the whole relationship-switch block (`BrokeUp`, `Dislike`, `FriendlyRival`, `Friend`/`BFF`/`FWB`, `Mate`, default) — those only fire when there's a courier pet.
  - Delete the `$courierActivity = PetActivityLogFactory::createUnreadLog(...)` call that produces the courier's log entry, and the `$courierChanges`/`$relationship` variables around it.
  - Remove the `use App\Enum\RelationshipEnum;` import if no other references remain.
- Leave `giveNextLetter`, `findBySenderIndex`, `getNumberOfLettersFromSender`, `getNumberOfLettersToUserFromSender`, `adventure()`, `doAnniversary()`, `doSharuminyinka()`, `doKatica()`, `doHyssop()`, and the `LetterResponse` class untouched.
- Double-check: the recipient-pet activity log string (`'While %pet:X.name% was thinking about what to do, a courier delivered them a Letter from Y! The courier was ' . $descriptionForPet`) does not mention Correspondence and does not need changes. Keep it as-is.

### 3. Strip guild-gated branches from pet-activity services
**Why**: The user chose "delete the gated branches entirely" rather than making bonus outcomes universal. Every `isInGuild()` check corresponds to a block that should be removed along with its guild-specific prose and item rewards.

For each site below, remove the `if($pet->isInGuild(...))` block **and its body**. If the block is one arm of an `if/elseif` chain where the non-guild arm is a baseline outcome, preserve the baseline arm by promoting it out of the chain. If removing the guild arm leaves a dangling `else` with no `if`, clean up syntax. Remove the `use App\Enum\GuildEnum;` import from each file once its last reference is gone.

Sites (see `grep isInGuild api/src` for the authoritative list at implementation time).
### 4. Delete the guild-joining adventure branches
**Why**: The user chose to delete these outright rather than keep the adventure with a neutral outcome.

- `api/src/Service/PetActivity/Protocol7Service.php`: remove the adventure branch that calls `$this->guildService->joinGuildProjectE($pet);` (around line 108). Work outward — delete any upstream picker code that only exists to reach that branch, and remove the `GuildService` constructor dependency if no other call sites remain in this service. Also handle the seven other `isInGuild()` hits in this file (lines ~126, 166, 168, 364, 380, 411, 809, 882) per step 3.
- `api/src/Service/PetActivity/UmbraService.php`: remove the adventure branch around line 265 that calls `$this->guildService->joinGuildUmbra($petWithSkills);`. Same cleanup rule for the picker code and the `GuildService` constructor dependency.

### 5. Remove guild wiring from `PetActivityService`
**File**: `api/src/Service/PetActivityService.php`

- Delete the `use App\Service\PetActivity\GuildService;` import (line ~52).
- Delete the `GuildService $guildService` constructor parameter and its property.
- Delete the "1 in 35 chance to perform guild activity if pet has guild membership" branch (lines ~267-271) that calls `GuildService::doGuildActivity()`.

### 6. Delete guild services and controllers
Delete these files outright:
- `api/src/Service/PetActivity/GuildService.php`
- `api/src/Service/PetActivity/GizubisGardenService.php` (entirely guild-mission code; its `adventure()` requires a `guildMembership`).
- `api/src/Service/Filter/GuildMemberFilterService.php`
- `api/src/Controller/Guilds/GetAllController.php`
- `api/src/Controller/Guilds/GetMembersController.php`
- The now-empty `api/src/Controller/Guilds/` directory.

In `api/src/Service/Filter/PetFilterService.php`:
- Delete the `'guild' => $this->filterGuild(...)` entry in the filter map (~line 50).
- Delete the `filterGuild()` method (~lines 124-132).

### 7. Trim `SelfReflectionController`
**Why**: The `/selfReflection` GET and the `/selfReflection/changeGuild` POST exist only for guilds. The reconcile feature (a separate, non-guild use of self-reflection points) lives in the same controller and must survive — but reconcile's frontend still needs the GET to fetch its troubled-relationships list, so that GET gets trimmed rather than deleted.

**File**: `api/src/Controller/Pet/SelfReflectionController.php`

- Delete the `changeGuild` action (`POST /{pet}/selfReflection/changeGuild`) and its route.
- Keep the `GET /{pet}/selfReflection` route but rename the action method from `getGuildMembership` to something accurate like `getSelfReflectionData` (the old name is a 2020 artifact). In the response payload, drop `membership` and `guilds` — keep only `troubledRelationships` and `troubledRelationshipsCount`. Remove the `SerializationGroupEnum::PET_GUILD` argument from the `success()` call (leave `PET_PUBLIC_PROFILE`).
- Keep `reconcileWithAnotherPet` and `troubledRelationshipsTypeaheadSearch` as-is.
- Remove the `use App\Entity\Guild;` import.

### 8. Remove guild references on the `Pet` entity and relationships controller
**File**: `api/src/Entity/Pet.php`

- Delete the `$guildMembership` property, its Doctrine mapping annotation, `getGuildMembership()`/`setGuildMembership()`, and the serialization-group annotations (`petPublicProfile`, `guildMember`) on that property. Handle cascade relations cleanly.
- Delete the `isInGuild(GuildEnum $guild, int $minTitle = 1): bool` method (~line 1531).
- Remove the `use App\Enum\GuildEnum;` import.

**File**: `api/src/Controller/Pet/RelationshipsController.php`

- Drop guild data from the response payload (the current controller includes `guildMembership` in its response). The frontend `pet-friends` component is being updated to stop rendering the guild panel; the API shouldn't send it either.

### 9. Remove guild enum and serialization-group entries
- Delete `api/src/Enum/GuildEnum.php`.
- In `api/src/Enum/SerializationGroupEnum.php`, remove `PET_GUILD`, `GUILD_ENCYCLOPEDIA`, and `GUILD_MEMBER` entries.
- Grep for remaining `Groups('petGuild')`, `Groups('guildEncyclopedia')`, `Groups('guildMember')` or the constant references across `api/src` and remove those annotations (they live on `Guild` and `GuildMembership` entity fields which are themselves being deleted, so most will disappear naturally — catch any strays on other entities like `Pet`).

### 10. Frontend: delete guild pages and routes
**Files to delete**:
- `webapp/src/app/module/encyclopedia/page/guild-directory/` (component + template).
- `webapp/src/app/module/encyclopedia/page/guild/` (component + template + scss).
- `webapp/src/app/module/encyclopedia/page/help/guilds/` (component + template).
- `webapp/src/app/model/guild/` (the whole `guild/` model directory — `pet-guild.serialization-group.ts`, `guild-member.serialization-group.ts`).
- `webapp/src/app/model/encyclopedia/guild-encyclopedia.serialization-group.ts`.

**File**: `webapp/src/app/module/encyclopedia/encyclopedia-routing.module.ts`

- Remove imports for `GuildsComponent`, `GuildDirectoryComponent`, `GuildComponent`.
- Remove the three route entries: `{ path: 'guild', ... }`, `{ path: 'guild/:guild', ... }`, `{ path: 'help/guilds', ... }`.

**File**: `webapp/src/app/module/encyclopedia/encyclopedia.module.ts`

- Remove the same imports and any NgModule `declarations`/`imports` for these components.

### 11. Frontend: scrub guild references from shared and help UI
- `webapp/src/app/module/shared/component/pet-friends/pet-friends.component.ts` and `.html` — delete the guild block (template lines ~45-53; component fields around lines 11, 68, 102 that source the guild panel). The friends list should still render without it.
- `webapp/src/app/module/home/component/pet-pick-self-reflection/pet-pick-self-reflection.component.ts` and `.html` — strip the guild-picker section entirely (UI, field bindings, and the POST to `/selfReflection/changeGuild`). Keep the reconcile portion intact. Update the component to consume the trimmed `GET /selfReflection` response (no `membership`, no `guilds` — just `troubledRelationships` and `troubledRelationshipsCount`). Remove any guild-related model imports.
- `webapp/src/app/module/encyclopedia/page/pet-profile/pet-profile.component.html` — remove any rendered guild section.
- `webapp/src/app/module/encyclopedia/page/help/help.component.html` — remove the "Guilds" entry from the glossary list.
- `webapp/src/app/module/encyclopedia/page/encyclopedia/encyclopedia.component.html` — remove the "Guilds" tile/link from the Poppyopedia index.
- `webapp/src/app/module/encyclopedia/page/help/groups/groups-help.component.html` — remove the "see also: Guilds" cross-link if one exists.
- `webapp/src/app/module/encyclopedia/page/help/relationships/relationships.component.html` — same cleanup.
- `webapp/src/app/model/public-profile/pet-public-profile.serialization-group.ts` — remove the guild field.

Grep `webapp/src` for `guild` and `Guild` after the edits to catch any remaining imports, template bindings, or interface references.

## Test Plan
- [ ] Run `composer run php-cs-fixer-dry-run` in `api/` — passes.
- [ ] Run `vendor/bin/phpstan --configuration=phpstan.dist.neon` in `api/` — passes with no new errors.
- [ ] Run the new migration on a fresh DB copy — `guild_membership` and `guild` tables are dropped, `pet.guild_membership_id` column is gone, `pet_activity_log_tag` no longer has a `Guild` row, existing data survives.
- [ ] Start the backend and frontend; navigate to `/poppyopedia` — no "Guilds" tile/link on the index; no "Guilds" entry in `/poppyopedia/help`.
- [ ] Visit `/poppyopedia/guild` directly — results in a route miss / 404, not a crash.
- [ ] Open any pet's profile and scroll to the relationships panel — no guild block rendered; the rest of the panel still works.
- [ ] Run the `app:increase-time` cron and visit a home so pets burn accumulated activity time — pets that previously had guild memberships cycle through activities without errors (check logs).
- [ ] Seed an account old enough to receive an anniversary letter; trigger letter delivery — the recipient pet's activity log shows a single log entry saying a courier delivered a letter; no second log for a courier pet; no text containing "Correspondence".
- [ ] Repeat the letter check for the Sharuminyinka, Katica, and Hyssop quest triggers.
- [ ] Open a pet's self-reflection dialog — the UI shows only the reconcile flow (no guild-picker section); the troubled-relationships list loads and a reconcile can still be executed.
- [ ] Exercise at least one representative activity per stripped branch — Light-and-Shadow Burnt Forest content, High-Impact Icy Moon content, Gizubi-Garden Giving Tree content — and confirm the activity resolves using only the non-guild arm.
- [ ] Grep the repo for `guild`/`Guild` (case-insensitive) and confirm only unrelated matches remain (e.g., words like "guided" or comments referring to past work are fine; any live references to the feature are not).

## Learnings

### Architectural decisions

- **Kept the `Guild` row in `pet_activity_log_tag`** rather than deleting it (ticket originally asked for deletion). A ManyToMany join table with no `ON DELETE CASCADE` means historical activity logs reference that tag; deleting the row would have required wiping references in the junction table first, erasing the `Guild` tag from years of old logs. Leaving the tag row is harmless (no runtime code produces new tag references) and lets players filter/find what their pets did "back when guilds existed." The new migration therefore only drops the `guild` and `guild_membership` tables.
- **Ticket mistakenly asserted a `pet.guild_membership_id` FK column**, but `Pet::$guildMembership` is the inverse side (`mappedBy: 'pet'`) — the FK actually lives on `guild_membership.pet_id`. No column drop on `pet` was needed. Verified by grepping all historical migrations for `guild_membership_id` — zero matches.
- **`ItemFood` description about Self-reflection Points** was updated to remove the "or change Guild" clause. Missed-reference hazard: any in-universe copy that still describes guild behavior will surface to players via Poppyopedia / item descriptions. A repo-wide `grep -i guild` after implementation caught it.

### Problems encountered

- **PhpStan baseline references deleted files.** After deleting `GuildService.php`, `GizubisGardenService.php`, `GuildMemberFilterService.php`, `Guild.php`, phpstan refused to run because its `phpstan-baseline.neon` still had `ignoreErrors` entries pointing at those paths. The fix: strip every baseline entry whose `path:` references a deleted file, *and* decrement `count:` on any remaining baseline entry whose error count changed because of our edits (the `filterGuild()` method I deleted contained one of the three `(int)$value` mixed-to-int casts tracked in PetFilterService — had to drop that count from 3 to 2). PhpStan treats unused baseline entries as fatal errors by default.
- **PHP else-block cleanup hazard.** Twice while collapsing `if(guild) { X } else { Y }` into just `Y`, I deleted the outer else-wrapper but left the inner body's indentation and kept a dangling `}`. The compiler catches this, but it's worth being explicit: when unwrapping an else block, fix both indentation and bracket count in the same edit.
- **Orphan helpers from deleted branches must be removed explicitly.** Deleting guild arms in `Protocol7Service` left `doDwarfcraftDigging`, `doTimesArrow`, `deliverMessagesForCorrespondence`, and (in `MagicBeanstalkService`) `foundPegasusNestHighImpact` unreferenced. The constructor dependencies `TransactionService` (Protocol7) and `PetRelationshipService` (GatheringService) also became unused. PhpStan level 10 flags unused constructor parameters — but only if you actually run it.

### Interesting tidbits

- **The `PetActivity` service tree is lazy-loaded** (`config/services.yaml`). Deleting a service class or removing a constructor dependency doesn't break container compilation until that service is actually requested — which is why phpstan is more valuable than a simple boot test for this kind of refactor.
- **Serialization groups live as string literals** in Doctrine `#[Groups([...])]` attributes, not as enum references in most places. Removing `SerializationGroupEnum::PET_GUILD` from the enum file does *not* automatically remove the `'guildMember'`, `'petGuild'`, `'guildEncyclopedia'` string annotations scattered across entity fields on Pet, Item, PetSpecies — these had to be hunted down with grep and deleted manually. Leaving stale string-literal group names won't break serialization (they just never match), but it's dead code.
- **`SelfReflectionController::getGuildMembership()` was a 2020 artifact name** — the endpoint has been the data fetcher for reconcile-with-a-pet for years but kept its guild-era method name. Renamed to `getSelfReflectionData()` as part of this ticket. Lesson: endpoint method names ossify quickly; rename when you touch them.

### Related areas affected

- `phpstan-baseline.neon` (~20 entries removed/decremented).
- `ItemFood::getModifiers()` — in-game copy about Self-reflection Points.
- `api/src/Service/PetActivity/CLAUDE.md` — documentation of `runHour()` priority order had a "Guild Activities" entry that needed removing.
- `SelfReflectionController` — renamed stale method name.
- `Pet`, `Item`, `PetSpecies` entity field annotations — stale `guildMember`/`guildEncyclopedia` strings.

### Rejected alternatives

- **Making the guild-gated "bonus" branches universal** (so every pet gets the nicer outcome). Rejected per ticket direction — the gated branches were deleted outright. Simpler, smaller code, at the cost of some variety in adventure flavor text.
- **Deleting the `Guild` `PetActivityLogTagEnum` entry and its row.** Rejected after user confirmation — keeping both lets old tagged log entries continue to render with their tag, and lets players find "what my pets did back when guilds existed."
- **Adding a replacement courier log** (some neutral pet delivers the letter with a generic log entry). Explicitly rejected by the user in ticket decisions: letters now always arrive via "some pet they didn't recognize" and produce a single log on the recipient only.
