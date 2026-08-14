# The Perseids Meteor Shower

## Context
**Current behavior**: The Leonids are the game's only meteor shower. During November 16-18, `UmbraService::run()` has a 1-in-4 chance of handing the whole hour to `LeonidsService`, and `AstronomyClubService::meet()` swaps its usual discovery reward for Stardust. Nothing similar exists in August. The holiday icon `proprietary-assets/images/calendar/holidays/the-perseids-meteor-shower.svg` already ships, but no code references it.

**New behavior**: August 11-13 is "The Perseids Meteor Shower" - a second annual shower with its own calendar entry, forecast icon, plaza blurb, Umbra adventure, and Astronomy Club branch. Pets in the Umbra encounter either Medusa Snakes (a fight that a mirror-carrying pet wins automatically, but for lesser loot) or the same fairies the Leonids offer, now shared between both events. Unlike the Leonids, the Astronomy Club branch requires a clear sky, which in the game's rainy August makes it genuinely uncommon.

## Prerequisites
- None. The holiday artwork is already present at `proprietary-assets/images/calendar/holidays/the-perseids-meteor-shower.svg`; its filename is what the frontend's `sluggify` pipe will derive from the holiday's name, so no art work is needed.

## Scope
### In scope
- A new date predicate in `CalendarFunctions`, plus extraction of the peak-day comparison it shares with the Leonids.
- New `HolidayEnum` case and its `getEventData()` branch, which propagates to the event calendar, the weather forecast strip, and Tess's plaza greeting with no further frontend work.
- A migration adding a `Perseids` activity-log tag, and the matching `PetActivityLogTagEnum` constant.
- A new `PerseidsService` with one new encounter (Medusa Snakes), wired into `UmbraService::run()`.
- Extraction of the Leonids' fairies encounter into a service both showers call.
- A sky-gated Perseids branch in `AstronomyClubService::meet()`.
- One new blurb block in `explain-holiday.component.html`.

### Out of scope
- **The Frosted Medusa Head Cracker and Bells' grocer dialog.** Separate ticket (`frosted-medusa-head-cracker.md`), which depends on the date predicate this ticket adds.
- **The `LeonidsService` raccoon-spirit log corrections.** Separate ticket (`leonids-raccoon-spirit-log-fixes.md`), independent of this one.
- **Adding a sky gate to the existing Leonids Astronomy Club branch.** The Leonids deliberately keep their current weather-blind behavior; only the new Perseids branch checks the sky.
- **A third Umbra encounter.** The Leonids run three at equal weight; the Perseids ship with two. An "identity" encounter that branches on status effects the way the Leonids' werecreature pack does was considered and deferred.
- **A per-year peak-day override table.** The Leonids need one because Tempel-Tuttle's 33-year cycle moves their peak; Swift-Tuttle does not, so the Perseids peak is a fixed constant.
- **Any new status effect** (petrification and similar). The Medusa Snakes' losing branch simply yields nothing.
- **Updating `db/seed/base.sql`.** Migrations only - the seed is a periodically-regenerated baseline that migrations run on top of.

## Relevant Docs & Anchors
- **Activity-system overview**: `api/src/Service/PetActivity/CLAUDE.md` - how the hour is spent and how activity logs are built.
- **The structural twin**: `LeonidsService`. Read it whole before starting. `adventure()` picks an encounter and then applies the shared tags and interestingness *after* the encounter returns - which is why the fairies encounter can be shared with almost no change.
- **The hijack site**: the `CalendarFunctions::isLeonidPeakOrAdjacent` check at the top of `UmbraService::run()`.
- **The club site**: the `isLeonidPeakOrAdjacent` branch in `AstronomyClubService::meet()`, and the `$group->getProgress() >= 100` branch it currently preempts.
- **Date predicates**: `CalendarFunctions::isLeonidPeakOrAdjacent`, plus the `LeonidPeakDayDefault` / `LeonidPeakDays` constants at the bottom of the file, and the `getEventData()` list of `if` branches.
- **Calendar-function test precedent**: `tests/Service/IsEasterTest.php` - boundary assertions on the day before, the days during, and the day after. The model for testing the new predicate.
- **Item-group checks**: `Item::hasItemGroup(string)`, with gameplay precedent in `InventoryService` (`hasItemGroup('Outer Space')` under the Celestial Choruser merit, and `hasItemGroup('Fresh Fruit')`).
- **Tag migration pattern**: `api/migrations/2026/04/Version20260418185900.php` - explicit id, `INSERT ... ON DUPLICATE KEY UPDATE id = id`.
- **Frontend blurb**: the `@else if(holiday === 'The Leonids Meteor Shower')` block in `webapp/src/app/module/plaza/component/explain-holiday/explain-holiday.component.html`.

## Constraints & Gotchas
- **The holiday's name is load-bearing.** `HolidayEnum::Perseids` must be exactly `The Perseids Meteor Shower`. The forecast and calendar-day components build the icon path by running the holiday name through the `sluggify` pipe (lowercase, non-alphanumerics collapsed to hyphens), which must yield `the-perseids-meteor-shower` to match the shipped SVG. The `explain-holiday` template also matches on the literal string.
- **The peak-day comparison is fragile at month boundaries.** Both predicates compare packed `nd` integers (`(int)$dt->format('nd')`, e.g. `812` for August 12) with `abs($monthAndDay - $peak) <= 1`. That is correct for August 12 (811-813, all inside August) and for November 17, but it would silently misbehave for any peak day landing on the 1st or the last of a month, since `1101 - 1` is `1100`, not the end of October. Preserve this limitation rather than fixing it - just do not reuse the helper for a boundary date without revisiting it.
- **New tags are database rows, not just constants.** `PetActivityLogTagHelpers::findByNames()` resolves tags by name at runtime, so the migration must be applied before any code path that tags a log `Perseids` runs, or it will fail to find the tag.
- **Both `LeonidsService` and `AstronomyClubService` pass raw tag strings** (`'Special Event'`, `'Leonids'`) rather than the `PetActivityLogTagEnum` constants, even though those constants exist. Match each file's own convention rather than mixing styles; add the `Perseids` constant for parity with `Leonids` regardless of whether these call sites use it.
- **August is the game's wettest month, and this is deliberate.** `WeatherService::getSky()` gives August a 20/31 rain chance against November's 6/30, which works out to roughly 24% clear nights in August versus 72% in November. The sky is deterministic per date: across 2026-2031 the August 11-13 window contains zero clear nights in four of those six years. The sky-gated Astronomy Club branch will therefore not fire most years. That is the intended simulationist behavior - do not compensate for it, and do not gate the Umbra encounters on weather.
- **The forecast is Redis-cached per date for one day** (`WeatherService::getWeatherForecast()`, keys of the form `Weather YYYY-MM-DD`). Deploying inside the window means already-cached upcoming days will not show the new holiday until their entries expire. Ship well before August, or flush those keys.
- **Text style**: ASCII hyphens only (no em or en dashes), American spelling. Keep all flavor text body-neutral - pet species vary enormously, so avoid assuming hands, paws, tails, or eyes on the *pet's* side (NPC snakes and fairies may of course have whatever anatomy they like). Avoid implying pets understand or speak human language.

## Open Decisions
1. **Name and shape of the shared fairies service** - a new class under `api/src/Service/PetActivity/` that both showers inject, versus some other arrangement. Default: a new service exposing one public encounter method that takes `ComputedPetSkills` plus the shower's display name, and a public static (or shared) builder for the "went into the Umbra, and followed the X to where they were falling!" opening line so all three encounters across both services stay identical in phrasing. Names are the implementer's call.
2. **Activity-log tag color** - the `Leonids` row uses `000000` with `fa-solid fa-star-shooting`. Default: reuse the same emoji, pick a distinct color so the two showers are visually separable in the log filter.
3. **The Medusa Snakes' skill pool** - default `STR + DEX + Brawl` against 15, mirroring `LeonidsService::encounterRaccoonSpiritScavenger`. Perception or Stealth would both read as "don't meet its eyes" and would make it a different kind of pet's encounter; if that reads better while writing the flavor text, take it.
4. **Exact time, exp, and esteem values** - take them from `LeonidsService`. Roughly `rngNextInt(45, 60)` minutes charged to `PetActivityStatEnum::UMBRA`, 1-2 exp, `$success: false` on the losing branch.
5. **Whether the Medusa Snakes log sets an icon** - no encounter in `LeonidsService` calls `setIcon`. Default: match, and set none.
6. **Fairies flavor** - the encounter is reused near-verbatim, deliberately. Its spice table already includes `Rain-scented`, which suits a rainy August. `Pumpkin Bread` in its food table is the one autumnal note; swapping it is optional and affects both showers if done in the shared copy.

## Acceptance Criteria
- [ ] `HolidayEnum::Perseids` exists with the exact value `The Perseids Meteor Shower`.
- [ ] `CalendarFunctions::isPerseidPeakOrAdjacent()` returns true for August 11, 12, and 13 of any year, and false for August 10 and August 14.
- [ ] `CalendarFunctions::isLeonidPeakOrAdjacent()` still returns true for November 16-18 in a year with no override entry, and for November 18-20 in 2022, after the shared-helper extraction.
- [ ] `CalendarFunctions::getEventData()` includes `HolidayEnum::Perseids` on August 11-13 and omits it on adjacent dates.
- [ ] A migration inserts a `pet_activity_log_tag` row titled `Perseids`, and `PetActivityLogTagEnum` gains a matching constant.
- [ ] During the window, an Umbra activity has a 1-in-4 chance of resolving to a Perseids encounter instead of the normal Umbra roll, regardless of the day's sky.
- [ ] Every Perseids Umbra log carries the tags `The Umbra`, `Special Event`, and `Perseids`, and `PetActivityLogInterestingness::HolidayOrSpecialEvent`.
- [ ] Medusa Snakes, winning without a mirror: the pet receives exactly one `Scales` and one `Talon`.
- [ ] Medusa Snakes, with a tool equipped whose item is in the `Mirror` item group: the encounter resolves as a win without rolling, and the pet receives exactly two `Rock` and no `Scales` or `Talon`.
- [ ] Medusa Snakes, losing: the pet receives no items at all, and time is charged with `$success: false`.
- [ ] The fairies encounter exists in exactly one place in the codebase and is reached by both `LeonidsService` and `PerseidsService`; each shower's fairies log opens with its own shower name and carries its own shower tag alongside `Fae-kind`.
- [ ] `AstronomyClubService::meet()` awards the Perseids Stardust-and-esteem outcome only when the shower is active *and* `WeatherService::getSky()` returns `WeatherSky::Clear`; during the window under any other sky, the meeting falls through to the existing progress-based behavior.
- [ ] `explain-holiday.component.html` renders a description block when passed the holiday name `The Perseids Meteor Shower`.

## Implementation

### 1. Add the Perseids date predicate and extract the shared peak-day comparison
Both showers ask the same question - "is today within one day of this shower's peak?" - so factor that comparison out rather than copying it. In `CalendarFunctions`, add a private static helper that takes a `\DateTimeInterface` and a packed `nd` peak day and returns whether they are within one day of each other, then rewrite `isLeonidPeakOrAdjacent` to resolve its per-year override first and delegate the comparison. Add `isPerseidPeakOrAdjacent` alongside it, delegating with a `PerseidPeakDay` constant of `812` and no override table. Put the new constant near `LeonidPeakDayDefault` at the bottom of the file, with a short comment noting that the Perseids peak, unlike the Leonids', does not drift.

### 2. Add the holiday to the enum and the event list
Add a `Perseids` case to `HolidayEnum` next to `Leonids` under the existing `// weird events?` comment, with the exact value given in Constraints. Then add an `isPerseidPeakOrAdjacent` branch to `CalendarFunctions::getEventData()`, next to the Leonids branch. This is the only wiring the calendar page, the forecast strip, and Tess's greeting need - each reads the holiday list off the weather payload.

### 3. Write the migration for the `Perseids` activity-log tag
Copy the shape of `Version20260418185900`: a single `INSERT` into `pet_activity_log_tag` with an explicit id and `ON DUPLICATE KEY UPDATE id = id`, using the next free id (query the local database for `MAX(id)` rather than trusting the seed - it was 104 at the time of writing, making 105 the next). Add a `Perseids` constant to `PetActivityLogTagEnum` in the alphabetical position its neighbors follow.

### 4. Extract the fairies encounter so both showers share it
The Leonids' fairies scene is season-neutral and is being reused deliberately. Move `LeonidsService::encounterFairies` into a service both showers inject. The only shower-specific thing inside it is the opening sentence built by `LeonidsService::getActivityLogPrefix` - parameterize that by shower name. Nothing else needs to change: the encounter already adds only its own `Fae-kind` tag, because the three shared shower tags are applied by the caller's `adventure()` after the encounter returns.

Leave `LeonidsService`'s other two encounters and its own prefix builder where they are; only the fairies move. See Open Decision 1 for the shape.

### 5. Add `PerseidsService` with the Medusa Snakes encounter
Model the class on `LeonidsService`: an `adventure()` entry point that picks an encounter, then applies `PetActivityLogInterestingness::HolidayOrSpecialEvent` and the tags `The Umbra`, `Special Event`, `Perseids` to whatever it returns. With two encounters rather than three, the pick is a two-way roll between the Medusa Snakes and the shared fairies encounter.

The Medusa Snakes encounter is the conflict slot, mirroring `encounterRaccoonSpiritScavenger` in structure but not in outcome shape. Before rolling, check whether the pet's equipped tool's item is in the `Mirror` item group via `Item::hasItemGroup`; the guard needs to tolerate a pet with no tool, the way the Leonids' silver checks do. On that path the pet wins without rolling and collects two `Rock` - the snakes meet their own reflection - and no `Scales` or `Talon`. Otherwise roll per Open Decision 3; a win yields one `Scales` and one `Talon`, and a loss yields nothing at all and charges time with `$success: false`.

Note for flavor: the `Mirror` item group contains twenty items but only five equippable tools - `Magic Mirror`, `Mirror Shield`, `Pandemirrorum`, `Enchanted Compass`, and `Gold Compass`. The last two are polished-glass rather than true mirrors, so keep the winning text general enough to cover a compass without reading oddly.

### 6. Wire the Perseids into the Umbra
In `UmbraService::run()`, add a Perseids check alongside the existing Leonids one, with the same 1-in-4 gate, handing off to `PerseidsService::adventure()`. The two showers cannot co-occur, so ordering between them is arbitrary; keep them adjacent and structurally parallel so the pairing is obvious. `PerseidsService` becomes a new constructor dependency - the directory is configured lazy in `config/services.yaml`, so nothing else is needed.

### 7. Add the sky-gated Perseids branch to the Astronomy Club
In `AstronomyClubService::meet()`, add a branch ahead of the existing `isLeonidPeakOrAdjacent` branch that fires only when the Perseids are active *and* the sky is clear, giving each member the same treatment the Leonids branch does - esteem, one `Stardust`, and a log tagged `Group Hangout`, `Astronomy Lab`, `Special Event`, `Perseids` - with its own message template naming the Perseids.

Because these are chained `else if`s ending in the `getProgress() >= 100` reward branch, a cloudy or rainy Perseids night falls straight through to normal club behavior, which is the desired outcome. `WeatherService::getSky()` is static and the service already injects `Clock`.

### 8. Add the plaza blurb
Add an `@else if(holiday === 'The Perseids Meteor Shower')` block to `explain-holiday.component.html`, next to the Leonids block and following its `<markdown>` structure. Cover what the Leonids block covers - that the shower is worth watching in real life, and that pets may find interesting things during it - and, given the constraint above, it is worth acknowledging that island weather in August rarely cooperates.

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` (in `api/`) passes.
- [ ] `php vendor/bin/phpstan` (in `api/`) passes.
- [ ] Add a unit test for `isPerseidPeakOrAdjacent` modeled on `tests/Service/IsEasterTest.php`, asserting false on August 10, true on August 11/12/13 (including a mid-day and an end-of-day timestamp), and false on August 14. Run `php vendor/bin/phpunit` (in `api/`).
- [ ] Extend or add coverage for `isLeonidPeakOrAdjacent` across the refactor: November 15 false, 16-18 true, 19 false in a normal year; November 17 false and 18-20 true in 2022.
- [ ] Manual: with the clock inside the window, open the Plaza and confirm the Perseids icon appears in the forecast strip, the event calendar shows it on August 11-13, Tess mentions it, and asking about it renders the new blurb.
- [ ] Manual: give a pet psychedelics or the Natural Channel merit so the Umbra is reachable, run hours until a Perseids encounter fires, and confirm both encounters appear across enough runs. Verify tags and item drops for each branch.
- [ ] Manual: equip a `Mirror Shield` and confirm the Medusa Snakes encounter always resolves as a win yielding two `Rock`; unequip it and confirm the roll and the `Scales` + `Talon` reward return.
- [ ] Manual: run an Astronomy Club meeting during the window on a clear-sky date and confirm the Stardust branch fires; repeat on a rainy date within the window and confirm the club behaves normally instead.
- [ ] Regression: run the same checks for the Leonids window in November - the Umbra hijack, all three encounters (fairies included, post-extraction), and the club branch must be unchanged.
- [ ] Regression: confirm holidays sharing the window still display - August 12 also carries Jelephant Day and falls inside Awa Odori, so three holiday icons should appear that day.

## Learnings

### Architectural decisions

- **Open Decision 1 (shared fairies service)** resolved as a new `App\Service\PetActivity\MeteorShowerEncounters`, named after the existing `StrangeUmbralEncounters` in the same directory. It holds the migrated `encounterFairies()` (now taking a `$showerName`) and a **public static** `getActivityLogPrefix(Pet, string $showerName)`. Both `LeonidsService` and `PerseidsService` keep a one-line private `getActivityLogPrefix(Pet)` that delegates to it with their own `private const string ShowerName`. This satisfies both the "leave `LeonidsService`'s prefix builder where it is" instruction (its two remaining encounters are untouched) and the "single source for the opening line" default: the phrasing now lives in exactly one place while each service still reads as if it owns its prefix.
- **Open Decision 2 (tag color)**: `Perseids` reuses the Leonids' `fa-solid fa-star-shooting` emoji with color `2b4e9c` (a summer-night blue) against the Leonids' `000000`, so the two showers are distinguishable in the log filter.
- **Open Decision 3 (skill pool)**: kept the default `STR + DEX + Brawl` vs 15, mirroring `encounterRaccoonSpiritScavenger`. Perception/Stealth was considered but rejected: the mirror branch already provides the "don't meet its eyes" fantasy as an *equipment* answer, so making the fallback a stealth check would have given the encounter two overlapping evasion axes and no fight.
- **Open Decisions 4, 5, 6**: taken as defaulted. Time is `rngNextInt(45, 60)` charged to `UMBRA`, 2 exp on either win branch and 1 on the loss, no `setIcon` anywhere, and the fairies' food/spice tables (incl. `Pumpkin Bread`) reused verbatim.
- The Medusa Snakes encounter tags `Fighting` on **all three** branches, not just the win. `LeonidsService::encounterRaccoonSpiritScavenger` only tags its win branch, which looks like an oversight; it was left alone as out of scope, but the new code does not copy it.
- The Umbra hijack is an `else if` chained onto the Leonids one rather than a separate `if`. The two windows are four months apart and cannot co-occur, so this is purely structural: it keeps the pairing visually obvious and guarantees only one shower can claim the hour.

### Problems encountered

- **`git mv` failed on the ticket**: `docs/tickets/perseids-meteor-shower.md` was untracked (it had never been committed), so the archive move was done with a plain `mv`. Nothing to preserve as a rename.
- **The ticket's "query the DB for `MAX(id)`" step was skipped on a false premise.** `docker` is not on `PATH` on this machine, and that was wrongly taken to mean the database was unreachable; the next id (105) was instead inferred by exhaustively grepping `api/migrations/` for every `INSERT INTO pet_activity_log_tag`, which found 104 (`Gardening Club`) as the highest.

  **Corrected while implementing `frosted-medusa-head-cracker.md`**: the database *is* reachable via `php bin/console dbal:run-sql "…"` from `api/`, which connects straight to MySQL through `api/.env.local` and needs no Docker CLI. `SELECT MAX(id) FROM pet_activity_log_tag` returns 104, so 105 was correct - but by luck of the grep, not by verification. It also showed that ids 101-103 (`Isekai Location: …`) exist in the live database while appearing in **no migration**, having been written directly by dev tooling. So the migration history is not a reliable substitute for the database, and neither is `db/seed/base.sql` (which only reaches id 97). Always run `dbal:run-sql` before concluding the database is unavailable.

### Interesting tidbits

- `getSky()` was sampled directly across both windows to confirm the ticket's weather claim. August 11-13 is clear on exactly one day in 2028, 2030, 2032 (and Aug 13 in 2033), and **not at all** in 2026, 2027, 2029, or 2031 - so the sky-gated Astronomy Club branch really does sit idle most years, as designed. The same sample over November 16-18 comes back almost entirely clear, which is why the Leonids branch never needed a gate.
- **This is relevant to shipping in 2026**: the August 11-13 window is rainy/cloudy/stormy, so the Perseids Astronomy Club branch cannot fire this year at all. The Umbra encounters are unaffected - they are deliberately not weather-gated.
- `Clock::$now` is a public `\DateTimeImmutable` property (not a `now()` method), which is exactly what `WeatherService::getSky()` needs, so the Astronomy Club branch required no new dependency.
- `php bin/console lint:container` is a fast way to confirm a new service + new constructor argument wires up, without booting the app or touching the database.
- The project's `.php-cs-fixer.dist.php` enables only `psr_autoloading`. It will **not** catch unused imports after an extraction like this one - those have to be checked by hand.

### Related areas affected

- `LeonidsService` lost ~60 lines. Its behavior is unchanged, but every Leonids fairies log now routes through `MeteorShowerEncounters`, so any regression there hits both showers at once.
- `CalendarFunctions::isLeonidPeakOrAdjacent` now delegates its comparison to the new private `isPeakOrAdjacent()`. The month-boundary limitation called out in the ticket is preserved and is now documented in a docblock on the helper itself, which is the right place for the next person to trip over it.
- `docs/tickets/frosted-medusa-head-cracker.md` depends on `isPerseidPeakOrAdjacent`, which now exists and is unit-tested; that ticket is unblocked.

### Rejected alternatives

- **Fixing the packed-`nd` month-boundary bug while extracting the helper.** Explicitly out of scope per the ticket, and neither shower's peak is anywhere near a month edge. Documented rather than fixed.
- **Making `MeteorShowerEncounters` take a shower enum instead of a string.** Only two call sites, and the value is purely display text spliced into a sentence; an enum would have been ceremony without a pit of success. The string is bound to a `private const string ShowerName` in each service, so neither call site passes a bare literal.
- **Giving the Medusa Snakes' loss branch a consolation Stardust** (which is what the raccoon-spirit loss does). The acceptance criteria specify no items at all on a loss, and a shower whose conflict encounter can genuinely come up empty gives the mirror a reason to exist.

## Post-ship: 2026 window extension (2026-08-13)

The event shipped on August 13, 2026 - the *last* day of its own window - so players effectively had no chance to see it. At the user's request the 2026 window only was extended by two days, to August 11-15.

- **Mechanism**: a new `CalendarFunctions::PerseidExtraDaysAfterPeak` table (`[ 2026 => 2 ]`), read by `isPerseidPeakOrAdjacent` and passed to the shared `isPeakOrAdjacent` helper as a new optional `$extraDays` argument. Years absent from the table get the usual peak-and-adjacent window, so this is self-expiring: 2027 is already back to August 11-13, and the entry can be deleted whenever someone next touches the file.
- **The extension is tail-only, and deliberately so.** The helper's `abs($monthAndDay - $peakDay) <= 1` became an explicit `>= $peak - 1 && <= $peak + 1 + $extraDays` range check. Widening both sides would have been symmetric but useless - the days before the peak had already passed by the time the request came in. The packed-`nd` month-boundary limitation is unchanged and now explicitly covers `$extraDays` in the docblock.
- **Modeled on `LeonidPeakDays`**, the file's existing per-year override table, rather than an ad-hoc `if($year === 2026)`. Same shape, same lookup idiom, and it documents itself at the declaration site.
- **Happy accident**: the ticket's finding that the Perseids Astronomy Club branch "cannot fire this year at all" no longer holds. August 14, 2026 is `WeatherSky::Clear` (the 11th-13th are rainy/cloudy/stormy), so the sky-gated club branch is reachable in 2026 after all, on exactly one day.
- **Deployment gotcha, restated**: `WeatherService::getWeatherForecast()` caches per date in Redis for a day (keys `Weather YYYY-MM-DD`). August 14 and 15 may already be cached without the holiday, so those two keys need flushing for the forecast strip and event calendar to show the extension. The calendar predicate itself is uncached and takes effect immediately.
