# Quality Time: Pillow Fort

## Context
**Current behavior**: `QualityTimeService` offers a pool of quality-time events — 7 always-available, plus weather- and holiday-gated extras. There is no event involving the household building a pillow fort. Two sibling tickets (`quality-time-sing-together.md`, `quality-time-tide-pools.md`) add other always-available / weather-gated events from the same design doc.

**New behavior**: A new always-available quality-time event, "build a pillow fort", is added to the pool. When picked, the household builds an enormous pillow fort in the living room and a featured pet contributes one of five fort moments. If the user has a fireplace and it is currently warm, an extra sentence is appended noting the fort was built right in front of it.

## Scope
### In scope
- One new private method `buildAPillowFort(User $user, array $pets): QualityTimeResult` in `QualityTimeService`.
- Append the new event unconditionally to `$possibleMessages` inside `getRandomQualityTimeDescription`.
- Five flavor lines per `docs/features/quality-time-event-ideas.md §4`, with the design-doc title-of-the-Fort line reworded per Open Decision 1 to avoid implying speech.
- Conditional fireplace-warm appendix sentence when `$user->getFireplace()` exists and its heat is greater than zero.

### Out of scope
- The other event ideas in `docs/features/quality-time-event-ideas.md` (sand castle, paint pictures, geodes, sing together, tide pools, lagoon, beach picnic, evening bonfire, flower leis, watch the research base, buried-treasure pirate-day event).
- A "cold fireplace" variant — only the warm-fireplace appendix is in scope.
- Frontend changes, migrations, new enum values, or new activity-log tags. The existing `PetActivityLogTagEnum::QualityTime` tag is applied by the calling loop in `doQualityTime`.

## Relevant Docs & Anchors
- **Design doc**: `docs/features/quality-time-event-ideas.md §4 "Build a pillow fort"` — source of truth for the flavor lines and the fireplace-warm wrinkle. Note: the design-doc "declared themselves [title] of the Fort and demanded snacks as tribute" line is intentionally reworded in this ticket — see Open Decision 1.
- **Doc-wide constraints block** in the same doc — names via `ActivityHelpers::PetName($pet)` + `ActivityHelpers::UserName($user, true)`, joined with `ArrayFunctions::list_nice([...])`, featured pet picked via `$this->rng->rngNextFromArray(...)`. Plus the two content rules: no anatomy assumptions; no implying pets know or speak human language.
- **Analogue tickets**: `docs/tickets/quality-time-sing-together.md` (always-available, single featured-pet line — same shape) and `docs/tickets/quality-time-tide-pools.md` (same flavor-line shape, weather-gated). Reuse their scaffolding.
- **Closest analogue methods** in `QualityTimeService`:
  - `playHideAndSeek` — the canonical example of a fort-style event with a conditional appendix branch keyed off `$user->getFireplace()`. Mirror its `\n\n`-joined appendix style for the fireplace-warm sentence.
  - `readAStory` / `goOnAWalk` / `practiceTricks` — same featured-pet + flavor-line shape.
- **Result DTO**: `QualityTimeResult` at the bottom of `QualityTimeService.php` — `new QualityTimeResult($message, foodBased: false)`.
- **Fireplace heat shape**: `playHideAndSeek` uses `$user->getFireplace() && $user->getFireplace()->getHeat() === 0` (cold, so a pet can hide in the chimney). Pillow fort wants the **opposite** — warm — so the check is `$user->getFireplace() && $user->getFireplace()->getHeat() > 0`. `CookingService` also uses `>` comparisons on `getHeat()`, confirming the shape.
- **`Fireplace::getHeat()` vs. unlock check**: precedent in `playHideAndSeek` and `CookingService` is to truthy-check `$user->getFireplace()` directly rather than `hasUnlockedFeature(UnlockableFeatureEnum::Fireplace)`. Owning a fireplace entity implies the feature is unlocked. Do the same here.

## Constraints & Gotchas
- **Two content rules from the design doc apply to every line**:
  1. Don't assume anatomy. The chosen lines are species-neutral (sleeping, standing guard, fussing with the entrance, adding a "window"). Keep them that way.
  2. Don't imply pets know or speak human language. This is why the design-doc "declared themselves [title]" line is reworded — see Open Decision 1.
- **`foodBased: false`** — fort building isn't a meal. Setting `true` would incorrectly grant a hunger top-up in `doQualityTime`.
- **Featured-pet name interpolation**: pick `$randomPet` from `$petNamesList` (the string list), not from `$pets` (the entity list), so the line interpolates cleanly — mirror `readAStory` / `goOnAWalk` / `playHideAndSeek`.
- **Nested rng for the title**: the reworded "established themselves as [title] of the Fort" line picks `[title]` at random from `['Castellan', 'Seneschal', 'Margrave']` via a second `$this->rng->rngNextFromArray([...])` call. Pick the title up-front and interpolate it into the line, rather than embedding the rng call mid-string.
- **Fireplace-warm appendix attachment**: append as a separate sentence joined by `"\n\n"` to the full assembled message, after the featured-pet flavor line — mirror `playHideAndSeek`'s `"\n\n"` style. Do not weave the fireplace mention into the opening sentence; the appendix must read independently of which flavor line rolled.
- **Tone**: events frame pets as silly / sleepy / over-enthusiastic / brave, never as bad. The fort lines all read affectionately — keep that register if wording is polished during implementation.

## Open Decisions
1. **Title line phrasing** — the design doc's `"declared themselves [title] of the Fort and demanded snacks as tribute"` implies a pet declaration of a human-language title, which collides with the doc's "don't imply pets know human language" constraint. **Default: rephrase to `"established themselves as [title] of the Fort and demanded snacks as tribute"`** — the user/narrator assigns the title, the pet just acts the part (and "demanded snacks as tribute" is behavior, not speech). Implementer may polish further if a cleaner phrasing surfaces during wording review.
2. **Method position in the file** — purely cosmetic. Default: place next to `bakeCookies` (alphabetical-ish neighbor) or after `goOnAWalk` at the bottom of the always-available cluster. Either is fine.
3. **Position in `$possibleMessages` array** — purely cosmetic. Default: append to the initial array literal alongside the other always-available events (next to `stretchTogether` / sibling additions if `sing-together` lands first).
4. **Exact flavor lines** — use the five from `docs/features/quality-time-event-ideas.md §4`, with line 1 reworded per Open Decision 1. Implementer may polish wording if a line reads awkwardly when interpolated.

## Acceptance Criteria
- [ ] `QualityTimeService` has a new private method `buildAPillowFort(User $user, array $pets): QualityTimeResult`.
- [ ] The method returns a `QualityTimeResult` with `foodBased: false`.
- [ ] The returned `message` opens with a sentence naming the user and the pets together (via `ActivityHelpers::UserName` + `ArrayFunctions::list_nice`) and noting they built a pillow fort in the living room, then appends one of five flavor lines featuring a single pet chosen via `$this->rng->rngNextFromArray` against the pet-names list.
- [ ] The five flavor lines correspond to the five in `docs/features/quality-time-event-ideas.md §4`, with the title-of-the-Fort line reworded to use `"established themselves as"` (not `"declared themselves"`). The title `[Castellan | Seneschal | Margrave]` is picked at random via a nested `$this->rng->rngNextFromArray` call.
- [ ] When `$user->getFireplace()` exists and `getHeat() > 0`, the returned `message` additionally ends with a `\n\n`-separated sentence noting the fort was built right in front of the fireplace (with the parenthetical "Not a fire hazard at all!" aside per the design doc). When the user has no fireplace, or the fireplace is cold (`getHeat() === 0`), no fireplace sentence is appended.
- [ ] `getRandomQualityTimeDescription` unconditionally appends a call to `$this->buildAPillowFort($user, $pets)` to `$possibleMessages` — no weather, calendar, or feature gating.
- [ ] All flavor lines satisfy the design-doc content constraints: no body-part assumptions, no implication that pets understand or speak human language.

## Implementation

### 1. Add the `buildAPillowFort` method
Add a private method on `QualityTimeService` per Open Decision 2. Follow the structure of `readAStory` for the featured-pet + flavor-line shape, plus `playHideAndSeek`'s conditional-appendix style for the fireplace branch:

- Build `$petNamesList` (mapped pet names via `ActivityHelpers::PetName`).
- Build `$everyonesNames` (user + pet names joined via `ArrayFunctions::list_nice`).
- Compose the opening sentence: `"$everyonesNames built an enormous pillow fort in the living room."`
- Pick a featured pet name via `$this->rng->rngNextFromArray($petNamesList)`.
- Pick a title up-front via `$this->rng->rngNextFromArray(['Castellan', 'Seneschal', 'Margrave'])` and interpolate it into the title-of-the-Fort flavor line so it reads `"$randomPet established themselves as $title of the Fort and demanded snacks as tribute."`
- Pick a flavor line via `$this->rng->rngNextFromArray($fortLines)` where `$fortLines` holds the five lines from `docs/features/quality-time-event-ideas.md §4` with `$randomPet` interpolated and line 1 reworded per Open Decision 1.
- Concatenate opening + `" "` + featured line.
- If `$user->getFireplace() && $user->getFireplace()->getHeat() > 0`, append `"\n\n"` + the fireplace-warm sentence (e.g. `"They built it right in front of the fireplace. (Not a fire hazard at all!)"`). Implementer may polish wording.
- Return `new QualityTimeResult($message, foodBased: false)`.

### 2. Register the event in the pool
In `getRandomQualityTimeDescription`, append `$this->buildAPillowFort($user, $pets)` to the initial `$possibleMessages` array literal alongside the other always-available events. No gating block.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `vendor/bin/phpstan --configuration=phpstan.dist.neon` (in `api/`) passes.
- [ ] Manual: with a test account whose pets are at home, trigger Quality Time several times and confirm the pillow-fort event appears in rotation. Re-roll until each of the five flavor lines is seen at least once; confirm each reads naturally with one pet and with 3+ pets.
- [ ] Manual: re-roll the title-of-the-Fort line several times and confirm all three titles (`Castellan`, `Seneschal`, `Margrave`) show up.
- [ ] Manual (fireplace warm): with a test account that has a fireplace with `heat > 0`, trigger pillow fort and confirm the `\n\n`-separated fireplace sentence appears at the end of the message.
- [ ] Manual (fireplace cold): with a test account that has a fireplace with `heat === 0`, trigger pillow fort and confirm **no** fireplace sentence appears.
- [ ] Manual (no fireplace): with a test account that has no fireplace, trigger pillow fort and confirm **no** fireplace sentence appears.
- [ ] Manual: confirm no hunger top-up occurs after a pillow-fort event (`foodBased: false`) — pet `food` stat unchanged before/after, while other stats (`safety`, `love`, `esteem`, affection) increment per the existing `doQualityTime` logic.
- [ ] Regression: confirm the other quality-time events still fire across multiple rolls (rotate Quality Time several times; spot-check the activity log entries).

## Learnings

### Architectural decisions
- **Method placement**: placed `buildAPillowFort` directly after `bakeCookies`, before `practiceTricks` — alphabetical-ish neighbor per Open Decision 2. Matches the "near `bakeCookies`" default; reads naturally in source order.
- **Array slot in `$possibleMessages`**: inserted between `bakeCookies` and `playCharades` rather than at the tail. Each entry is invoked unconditionally so position doesn't affect probability — placement is purely readability.
- **Open Decision 1 (title rephrasing)**: kept the default `"established themselves as $title of the Fort and demanded snacks as tribute."` — no surprises during wording review. Reads as narrator assigning the title; sidesteps the "pets speak human language" constraint cleanly.
- **Fireplace appendix wording**: used the design-doc parenthetical verbatim: `"They built it right in front of the fireplace. (Not a fire hazard at all!)"`. Reads independently of which flavor line rolled, per the Constraints note.
- **Fireplace check shape**: truthy-check `$user->getFireplace()` then `getHeat() > 0` — mirrors `playHideAndSeek`'s cold-branch shape with the inequality flipped. No `hasUnlockedFeature(Fireplace)` call needed; owning the entity implies the feature.

### Interesting tidbits
- `Fireplace::getHeat()` returns an `int`; `getHeatDescription()` keys off `<= 0` for the "cold" branch, so `> 0` is the canonical "warm" predicate. Consistent with `playHideAndSeek` and `CookingService`.
- The `sing-together` sibling ticket is not yet in `complete/`, so `singTogether` isn't in the `$possibleMessages` literal — no conflict either way; both tickets append independently.

### Rejected alternatives
- **Embedding the rng title pick mid-string**: rejected per Constraints & Gotchas (`rngNextFromArray` call inside a heredoc/interpolation is harder to read). Picked the title up-front into `$title` and interpolated, matching `playHideAndSeek`'s `$randomPet` pattern.
- **Weaving the fireplace mention into the opening sentence**: rejected per Constraints — the appendix must read independently of the rolled flavor line. The `\n\n` style mirrors `playHideAndSeek`.
