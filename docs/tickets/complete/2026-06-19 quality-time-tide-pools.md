# Quality Time: Tide Pools

## Context
**Current behavior**: `QualityTimeService` offers a pool of quality-time events — 7 always-available, plus weather- and holiday-gated extras (currently `goFishing` for non-`Stormy`, `stargaze` + `goOnAWalk` for `Clear`, and a handful of holiday-gated ones). There is no event involving the household visiting the tide pools.

**New behavior**: A new fair-weather quality-time event, "tide pools", is added to the pool. When picked, the household crouches at the tide pools to see what the sea has left behind, and a featured pet contributes one of four delightful tide-pool moments (including, instead of the original starfish line from the design doc, a pet valiantly shooing off a suspicious-looking seagull).

## Scope
### In scope
- One new private method `exploreTidePools(User $user, array $pets): QualityTimeResult` in `QualityTimeService`.
- Register the new event in `getRandomQualityTimeDescription` behind a `Clear`-or-`Cloudy` sky gate.
- Four flavor lines per the updated `docs/features/quality-time-event-ideas.md §8 "Tide pools"`.

### Out of scope
- The other 9 event ideas still listed in `docs/features/quality-time-event-ideas.md` (sand castle, paint pictures, pillow fort, geodes, lagoon splash, beach picnic, evening bonfire, flower leis, watch the research base, buried-treasure pirate-day event).
- Any revision of the other three tide-pool lines (anemone boop, snail count, crab standoff) for stricter body-neutrality — design doc precedent tolerates the current latitude.
- Frontend changes, migrations, new enum values, or new activity-log tags. The existing `PetActivityLogTagEnum::QualityTime` tag is applied by the calling loop in `doQualityTime`.

## Relevant Docs & Anchors
- **Design doc**: `docs/features/quality-time-event-ideas.md §8 "Tide pools"` — already updated to carry the four final flavor lines (anemone boop / snail count / seagull shoo / crab standoff) and the `Clear`-or-`Cloudy` gating. Source of truth for the flavor lines.
- **Doc-wide constraints block** in the same doc — names via `ActivityHelpers::PetName($pet)` + `ActivityHelpers::UserName($user, true)`, joined with `ArrayFunctions::list_nice([...])`, featured pet picked via `$this->rng->rngNextFromArray(...)`. Plus the two content rules: no anatomy assumptions; no implying pets know human language. (The starfish line was dropped specifically because it violated the second rule.)
- **Analogue ticket**: `docs/tickets/quality-time-sing-together.md` — same shape; reuse its structure for method scaffolding and registration.
- **Closest analogue methods** in `QualityTimeService`:
  - `readAStory` — same shape this ticket wants: build "everyone's names" list, pick featured pet, append one line from an array.
  - `goOnAWalk` — same shape, slightly larger ending pool; also weather-gated (`Clear` only, registered behind `if($sky === WeatherSky::Clear)`).
  - `goFishing` — example of registration behind a single-weather guard (`if($sky !== WeatherSky::Stormy)`).
- **Result DTO**: `QualityTimeResult` at the bottom of `QualityTimeService.php` — `new QualityTimeResult($message, foodBased: false)`.
- **Lore consistency for the seagull line**: seagulls already appear as beach wildlife in `GatheringDistractionService` (clam-smashing, shark-snatched, fox-stealing-the-egg distractions). The defender framing fits established beach texture.

## Constraints & Gotchas
- **Two content rules from the design doc apply to every line**:
  1. Don't assume anatomy. The four chosen lines already comply (poking, counting, shooing-off, staring-down are species-neutral enough; doc precedent tolerates this latitude — see "trilled" in §2).
  2. Don't imply pets know human language. The replacement seagull line replaces a starfish line that violated this rule ("refused to stop talking about it"); the new line uses behavior (shooing), not speech.
- **`foodBased: false`** — tide-pool exploration isn't a meal. Setting `true` here would incorrectly grant a hunger top-up in `doQualityTime`.
- **Featured-pet name interpolation**: pick `$randomPet` from `$petNamesList` (the string list), not from `$pets` (the entity list), so the line interpolates cleanly — mirror `readAStory` / `goOnAWalk`.
- **Gating shape is new**: no existing event is gated on `Clear`-or-`Cloudy`. `goFishing` uses `$sky !== WeatherSky::Stormy` (allows rain/snow); `stargaze` / `goOnAWalk` use `$sky === WeatherSky::Clear` (Clear only). Add a fresh `if($sky === WeatherSky::Clear || $sky === WeatherSky::Cloudy)` block — don't fold into either existing block.
- **Tone**: events frame pets as silly / sleepy / over-enthusiastic / brave, never as bad. The seagull-defender line is brave/protective — affectionate. Keep that register if the wording is polished during implementation.

## Open Decisions
1. **Method name** — `exploreTidePools` vs. `visitTidePools` vs. `pokeTidePools`. Default: `exploreTidePools` (matches the verb-noun cadence of `goFishing` / `goOnAWalk` / `playHideAndSeek`).
2. **Intro sentence wording** — design doc suggests `"[Everyone] crouched at the tide pools to see what the sea had left behind."`. `readAStory` uses `"$everyonesNames curled up and read a story together."`. Either reads fine. Default: follow design-doc phrasing.
3. **Position in `getRandomQualityTimeDescription`** — purely cosmetic. Default: add the new `Clear`-or-`Cloudy` block immediately after the `$sky !== WeatherSky::Stormy` block and before the `$sky === WeatherSky::Clear` block, so the weather guards go widest→narrowest.
4. **Exact flavor lines** — use the four from the updated `docs/features/quality-time-event-ideas.md §8` verbatim. Implementer may polish wording if a line reads awkwardly when interpolated.

## Acceptance Criteria
- [ ] `QualityTimeService` has a new private method (default name `exploreTidePools`) with signature `(User $user, array $pets): QualityTimeResult`.
- [ ] The method returns a `QualityTimeResult` with `foodBased: false`.
- [ ] The returned `message` opens with an intro sentence naming the user and the pets together (via `ActivityHelpers::UserName` + `ArrayFunctions::list_nice`), then appends one of four flavor lines featuring a single pet chosen via `$this->rng->rngNextFromArray` against the pet-names list.
- [ ] The four flavor lines correspond to the four in `docs/features/quality-time-event-ideas.md §8` (anemone gentle-boop; eleven kinds of snail allegedly; valiantly shooing off a suspicious-looking seagull; epic dignified crab standoff). The starfish line from earlier doc revisions does not appear.
- [ ] `getRandomQualityTimeDescription` registers the new event only when the current sky is `Clear` or `Cloudy` — not `Rainy`, not `Snowy`, not `Stormy`.
- [ ] All flavor lines satisfy the design-doc content constraints: no body-part assumptions, no implication that pets understand or speak human language.

## Implementation

### 1. Add the `exploreTidePools` method
Add a private method on `QualityTimeService` next to `goOnAWalk` / `stargaze` (the other weather-gated events). Follow the structure of `readAStory`: build `$petNamesList` (mapped pet names), build `$everyonesNames` (user + pet names via `list_nice`), open with the intro sentence per Open Decision 2, pick a featured pet name via `$this->rng->rngNextFromArray($petNamesList)`, pick a flavor line via `$this->rng->rngNextFromArray($tidePoolMoments)` where `$tidePoolMoments` holds the four lines from `docs/features/quality-time-event-ideas.md §8` with `$randomPet` interpolated. Concatenate intro + " " + featured line. Return `new QualityTimeResult($message, foodBased: false)`.

### 2. Register the event behind a Clear-or-Cloudy gate
In `getRandomQualityTimeDescription`, add a new `if($sky === WeatherSky::Clear || $sky === WeatherSky::Cloudy)` block that appends `$this->exploreTidePools($user, $pets)` to `$possibleMessages`. Place it per Open Decision 3 (between the existing non-`Stormy` and `Clear`-only blocks). Do not fold the new entry into either existing weather block — neither gate matches.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `vendor/bin/phpstan --configuration=phpstan.dist.neon` (in `api/`) passes.
- [ ] Manual: with a test account whose pets are at home and the current sky `Clear` or `Cloudy`, trigger Quality Time several times and confirm the tide-pools event appears in rotation. Re-roll until each of the four flavor lines is seen at least once; confirm all four read naturally with one pet and with 3+ pets.
- [ ] Manual: with the sky forced to `Rainy`, `Snowy`, or `Stormy`, trigger Quality Time several times and confirm the tide-pools event does **not** appear (spot-check the activity log entries — none should mention tide pools).
- [ ] Manual: confirm no hunger top-up occurs after a tide-pools event (`foodBased: false`) — pet `food` stat unchanged before/after, while other stats (`safety`, `love`, `esteem`, affection) increment per the existing `doQualityTime` logic.
- [ ] Regression: confirm the other quality-time events still fire across multiple rolls (rotate Quality Time several times; spot-check the activity log entries for `playHideAndSeek`, `readAStory`, `goFishing`, etc.).

## Learnings

### Architectural decisions
- **Open Decisions resolved per defaults**: method named `exploreTidePools`; intro sentence used design-doc phrasing (`"crouched at the tide pools to see what the sea had left behind."`); the new gate block was placed between the `$sky !== WeatherSky::Stormy` and `$sky === WeatherSky::Clear` blocks so the weather guards read widest→narrowest. Flavor lines used verbatim from `docs/features/quality-time-event-ideas.md §8`; no polishing needed once interpolated.
- **New gating shape (`Clear || Cloudy`)**: first event with this combination. Kept as a separate `if` rather than folded into either existing weather block — neither gate matches. Reads cleanly next to its neighbors.

### Interesting tidbits
- The `readAStory` template (build `$petNamesList` → `$everyonesNames` via `list_nice` → intro → pick featured pet from the *name list* → append one ending) is the right shape for any "household plus one featured pet does X" event. The `$petNamesList` vs `$pets` distinction matters: pick the featured name from the string list so it interpolates directly without a second `PetName()` call.

### Workarounds / limitations
- None. The service already had clean precedents for both the method shape and the registration block.

### Related areas affected
- None beyond `QualityTimeService`. No migration, no enum addition, no frontend change — the existing `PetActivityLogTagEnum::QualityTime` tag is applied by the calling loop in `doQualityTime`.

### Rejected alternatives
- Folding the new event into the existing `$sky !== WeatherSky::Stormy` block (would have allowed it during Rainy/Snowy, contradicting the design-doc gate).
- Folding into the `$sky === WeatherSky::Clear` block (would have excluded Cloudy days, contradicting the design-doc gate).
- Picking the featured pet from `$pets` (entity list) instead of `$petNamesList` — would force an extra `ActivityHelpers::PetName($randomPet)` call at every interpolation site; the analogue methods pick from the string list for exactly this reason.
