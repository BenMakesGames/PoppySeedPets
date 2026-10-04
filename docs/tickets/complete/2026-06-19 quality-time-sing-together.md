# Quality Time: Sing Together

## Context
**Current behavior**: `QualityTimeService` offers 13 quality-time events — 7 always-available, plus weather- and holiday-gated extras. There is no event involving the household singing together.

**New behavior**: A new always-available quality-time event, "sing together", is added to the pool. When picked, the household sings together and a featured pet joins in with a randomly-selected delightful musical contribution.

## Scope
### In scope
- One new private method `singTogether(User $user, array $pets): QualityTimeResult` in `QualityTimeService`.
- Append the new event unconditionally to `$possibleMessages` inside `getRandomQualityTimeDescription`.
- Six flavor lines per `docs/features/quality-time-event-ideas.md §2`.

### Out of scope
- Holiday variants (carols during `isStockingStuffingSeason`, sea shanties during `isTalkLikeAPirateDay`). The design doc lists these as optional; deferred to a separate ticket if/when desired.
- The other 10 event ideas in `docs/features/quality-time-event-ideas.md` (sand castle, paint pictures, pillow fort, geodes, lagoon, beach picnic, tide pools, bonfire, flower leis, watch the research base, buried-treasure pirate-day event).
- Frontend changes, migrations, new enum values, or new activity-log tags. The existing `PetActivityLogTagEnum::QualityTime` tag is applied by the calling loop in `doQualityTime`.

## Relevant Docs & Anchors
- **Design doc**: `docs/features/quality-time-event-ideas.md §2 "Sing together"` — flavor lines and content constraints (no body-part assumptions; no implication that pets understand human language).
- **Conventions block** in that same doc — names via `ActivityHelpers::PetName($pet)` + `ActivityHelpers::UserName($user, true)`, joined with `ArrayFunctions::list_nice([...])`, featured pet picked via `$this->rng->rngNextFromArray($pets)`, flavor line picked the same way.
- **Closest analogue methods** in `QualityTimeService`:
  - `readAStory` — same shape this ticket wants: build "everyone's names" list, pick featured pet, append one line from an array of endings.
  - `goOnAWalk` — same shape, slightly larger ending pool.
  - `practiceTricks` — same shape, ending pool embeds the featured pet name in each line.
- **Result DTO**: `QualityTimeResult` at the bottom of `QualityTimeService.php` — `new QualityTimeResult($message, foodBased: false)`.
- **Event registration**: the `$possibleMessages = [...]` array in `getRandomQualityTimeDescription`. Always-available events are unconditional entries; weather- and holiday-gated events are appended in `if` blocks below.

## Constraints & Gotchas
- **Two content constraints from the design doc apply to every line**:
  1. Don't assume anatomy — pet species vary; no tails/paws/necks/mouths-specifically. The doc-spec lines already comply (humming, harmonizing, bobbing, hitting a note, trilling).
  2. Don't imply pets know human language — pets join in by sound/behavior, never by knowing lyrics or words. The doc-spec lines already comply.
- **`foodBased: false`** — no eating involved. Setting `true` here would incorrectly grant hunger top-up in `doQualityTime`.
- **Featured-pet name embedding**: each ending line starts mid-sentence after the pet name (e.g., `"$randomPet hummed along to every tune."`). Mirror `readAStory` / `goOnAWalk` patterns — pick `$randomPet` from `$petNamesList` (a string list), not from `$pets` (entity list), so the line interpolates cleanly.

## Open Decisions
1. **Intro sentence wording** — design doc suggests `"[User] sang songs while [pets] joined in."`. `readAStory` uses `"$everyonesNames curled up and read a story together."` (everyone in one list). Either reads fine. Default: follow design-doc phrasing — user leads, pets join — since the line "[Pet] joined in" reads more naturally than "[everyone] sang" for a household where the user is the song-picker.
2. **Position in `$possibleMessages` array** — purely cosmetic. Default: append next to `stretchTogether` at the end of the always-available block.
3. **Exact flavor lines** — use the six from `docs/features/quality-time-event-ideas.md §2` verbatim. The implementer may polish wording if a line reads awkwardly when interpolated.

## Acceptance Criteria
- [ ] `QualityTimeService` has a new private method `singTogether(User $user, array $pets): QualityTimeResult`.
- [ ] The method returns a `QualityTimeResult` with `foodBased: false`.
- [ ] The returned `message` starts with a sentence naming the user and the pets together (via `ActivityHelpers::UserName` and `ArrayFunctions::list_nice`), then appends one of six flavor lines featuring a single pet chosen via `$this->rng->rngNextFromArray`.
- [ ] The six flavor lines correspond to the six in `docs/features/quality-time-event-ideas.md §2` (humming along; harmonizing mostly; chiming in with drowning-out gusto; bobbing-beat; a windows-humming high note; trilling an own melody).
- [ ] `getRandomQualityTimeDescription` unconditionally appends a call to `$this->singTogether($user, $pets)` to `$possibleMessages` — no weather, calendar, or feature gating.
- [ ] All flavor lines satisfy the design-doc content constraints: no body-part assumptions, no implication that pets understand human language.

## Implementation

### 1. Add the `singTogether` method
Add a private method on `QualityTimeService` next to `stretchTogether` / `goOnAWalk`. Follow the structure of `readAStory`: build `$petNamesList` (mapped pet names), build `$everyonesNames` (user + pet names via `list_nice`), open with the intro sentence per Open Decision 1, pick a featured pet name via `$this->rng->rngNextFromArray($petNamesList)`, pick a flavor line via `$this->rng->rngNextFromArray($singing)` where `$singing` holds the six lines from the design doc with `$randomPet` interpolated. Concatenate intro + " " + featured line. Return `new QualityTimeResult($message, foodBased: false)`.

### 2. Register the event in the pool
In `getRandomQualityTimeDescription`, append `$this->singTogether($user, $pets)` to the initial `$possibleMessages` array literal alongside the other always-available events. No gating block.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `vendor/bin/phpstan --configuration=phpstan.dist.neon` (in `api/`) passes.
- [ ] Manual: trigger Quality Time from the home page multiple times against a test account with 1 pet at home and again with 3+ pets at home; confirm the new event appears in rotation and the message reads naturally in both single-pet and multi-pet branches.
- [ ] Manual: confirm no hunger top-up occurs after a sing-together event (since `foodBased: false`) — pet `food` stat unchanged before/after, while other stats (`safety`, `love`, `esteem`, affection) increment per the existing `doQualityTime` logic.
- [ ] Regression: confirm the other 13 events still fire (rotate Quality Time several times; spot-check the activity log entries).

## Learnings

- **Open Decision 1 resolved per default**: intro phrasing follows the design-doc "[User] sang songs while [pets] joined in." (user leads, pets join), not the `readAStory` "everyone curled up" group-frame. Reads more naturally with the per-pet flavor line that follows.
- **Open Decision 2 resolved per default**: registered next to `stretchTogether` at the end of the always-available block in `getRandomQualityTimeDescription`.
- **Open Decision 3 resolved per default**: used the six design-doc lines verbatim — no polishing needed; all interpolate cleanly with `$randomPet` at the start.
- **Method placement**: dropped the new private method between `stretchTogether` and `goOnAWalk` in the file — matches the locality the ticket suggested.
- **Manual verification deferred to user**: lint + phpstan are clean; the in-game rotation, hunger-unchanged, and other-events-still-fire spot-checks require running the app and a populated test account, which I can't do here.
