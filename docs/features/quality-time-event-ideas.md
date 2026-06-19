# Quality Time - New Event Ideas

A grab-bag of proposed new "quality time" activities for `QualityTimeService`
(`api/src/Service/QualityTimeService.php`).

The goal is to grow the pool well beyond the current 13 so the activity feels
fresh, and - where it's natural - lean into the **tropical island** setting and
the game's lore (the beach & lagoon, the HERG research base, the Hollow Earth
wormhole). Leaning on lore is a *nice-to-have*, not a requirement; several ideas
below are just wholesome domesticity.

## Conventions (so these drop straight into the service)

Each event is a private method returning a `QualityTimeResult($message, foodBased: bool)`.
Established patterns to reuse:

- `ActivityHelpers::UserName($user, true)` and `ActivityHelpers::PetName($pet)` for names.
- `ArrayFunctions::list_nice([...])` to join everyone's names ("A, B, and C").
- `$this->rng->rngNextFromArray($pets)` to pick a featured pet, and again to pick
  a random flavor line from a `$endings` / `$descriptions` array.
- `foodBased: true` only when the activity plausibly feeds the pets (it grants the
  hunger top-up in `doQualityTime`). Cooking/foraging → true; games/crafts → false.
- **Gating** mirrors the existing methods:
  - Weather via `WeatherService::getSky($this->clock->now)` (`Clear`, `Cloudy`,
    `Rainy`, `Snowy`, `Stormy`).
  - Holidays via `CalendarFunctions::is...($this->clock->now)`.
  - Unlocked rooms via `$user->hasUnlockedFeature(UnlockableFeatureEnum::...)`.

A note on tone: the existing events never describe a pet doing something *bad* at
something - a pet might be silly, sleepy, or over-enthusiastic, but the framing is
always affectionate. New events should keep that.

Two content constraints that apply to every line:
- **Don't assume anatomy.** Pet species vary wildly - many have no tail, paws,
  neck, etc. Keep flavor body-neutral ("bobbing along" not "thumping their
  tail"; "draped in leis" not "wore leis around their neck").
- **Don't imply pets know human language.** Whether pets understand or speak human
  language is deliberately ambiguous; have them join in by sound/behaviour, not by
  knowing words or lyrics.

---

## 1. Build a sand castle (beach) - *your idea, fleshed out*

Pick a featured pet and credit them with one random, delightfully-weird-but-good
part of the castle.

> *[User] and [pets] built an enormous sand castle down on the beach. [Pet]
> added [feature]!*

`$features` (random, all flattering):
- "a moat so wide it filled with real seawater"
- "a perfectly spiraling tower that somehow didn't collapse"
- "a tiny second castle for bugs to live in"
- "a drawbridge made from a single, very dignified seashell"
- "battlements shaped like little teacups"
- "a secret tunnel connecting the two gatehouses"
- "a throne room, complete with a throne sized for exactly one crab"

- `foodBased: false`
- **Gating:** `Clear` or `Cloudy` sky.

## 2. Sing together - *your idea*

> *[User] sang songs while [pets] joined in. [Pet] [singing line].*

`$singing` lines (careful: pets join in by sound/enthusiasm - never imply they
know the words, since whether pets understand human language is left ambiguous):
- "hummed along to every tune"
- "harmonized beautifully (mostly)"
- "chimed in with such gusto that they drowned everyone else out"
- "kept the beat by bobbing along enthusiastically"
- "hit a note so high the windows hummed"
- "trilled a little melody all their own"

- `foodBased: false`
- **Gating:** none. Optional holiday variants (carols during
  `isStockingStuffingSeason`, sea shanties during `isTalkLikeAPirateDay`).

## 3. Paint pictures - *your idea*

> *[User] set out paints and [everyone] painted pictures together. [Pet] painted
> [subject].*

`$subjects`:
- "a portrait of you that was... abstract, but heartfelt"
- "the view of the lagoon from the window"
- "a self-portrait with three extra eyes, for style"
- "a giant bowl of their favorite food"
- "the research base lights twinkling across the bay" *(lore wink)*

- `foodBased: false`
- **Gating:** none. (Could nudge `foodBased: false` but add a fun line if the
  user has the `Florist` unlocked - "they painted the flowers from the shop.")

## 4. Build a pillow fort - *your idea*

> *[Everyone] built an enormous pillow fort in the living room. [Pet]
> [fort line].*

`$fort` lines:
- "declared themselves [title] of the Fort and demanded snacks as tribute" -
  where `[title]` is picked at random from `['Castellan', 'Seneschal', 'Margrave']`
  (all obscure castle/estate/border-lord titles), e.g. via a nested
  `$this->rng->rngNextFromArray([...])`.
- "kept 'improving' the entrance until no one could get in"
- "fell asleep the moment the roof was finished"
- "stood guard at the gate against imaginary intruders"
- "added a 'window' so they could keep an eye on the kitchen"

- `foodBased: false`
- **Gating:** none - but if `hasUnlockedFeature(Fireplace)` and the fireplace is
  warm, add "...right in front of the fireplace. (Not a fire hazard at all!)" (Mirror the
  fireplace check already used in `playHideAndSeek`.)

## 5. Hunt for geodes - *your idea*

Foraging-flavored; a featured pet finds something neat.

> *[User] and [pets] went hunting for geodes along the rocky part of the shore.
> [Pet] found [find]!*

`$finds`:
- "a geode that split open to reveal sparkling purple crystals"
- "a geode that was, disappointingly, just a rock - but a very nice rock"
- "the BIGGEST geode anyone had ever seen (it took two of you to carry it)"
- "a geode with a fossil tucked inside it"
- "a geode that glittered faintly even in shadow" *(soft lore wink - Hollow
  Earth minerals?)*

- `foodBased: false`
- **Gating:** non-`Stormy`. Could be made rarer / more rewarding-feeling.

---

## Additional proposals (island & lore flavor)

## 6. Splash in the lagoon

> *[Everyone] waded into the warm lagoon to cool off. [Pet] [splash line].*

- "discovered they were a surprisingly strong swimmer"
- "preferred to supervise from a floating leaf"
- "found a hermit crab and made a new (briefly) best friend"
- "got the zoomies in the shallows and splashed everyone"

- `foodBased: false`
- **Gating:** `Clear` or `Cloudy` only (no swimming in rain/snow/storms).

## 7. Beach picnic

> *[User] packed a picnic and [everyone] ate together on the sand, watching the
> waves.*

- `foodBased: **true**` (it's a meal).
- **Gating:** non-`Stormy`, non-`Snowy`.

## 8. Tide pools

> *[Everyone] crouched at the tide pools to see what the sea had left behind.
> [Pet] [tide-pool line].*

- "poked a sea anemone and got gently booped back"
- "counted eleven different kinds of snail (allegedly)"
- "valiantly shooed off a particularly suspicious-looking seagull that had been eyeing the group"
- "stared down a crab in an epic, dignified standoff"

- `foodBased: false`
- **Gating:** `Clear` or `Cloudy` only.

## 9. Evening bonfire on the beach

> *[Everyone] built a little bonfire on the beach and watched the stars come out
> over the water.*

Pairs nicely with the existing `stargaze`, but beach-flavored and a touch cozier.

- `foodBased: false` (or `true` with a "...and toasted marshmallows" variant).
- **Gating:** `Clear` only (matches `stargaze`).

## 10. Make flower leis

> *[User] and [pets] strung tropical flowers into leis. [Pet] ended up draped in
> [count] of them and looked extremely pleased about it.*

- `foodBased: false`
- **Gating:** none, but feels best if `hasUnlockedFeature(Florist)`; could add a
  variant that names the shop's flowers when unlocked.

## 11. Watch the research base

A quiet, lore-forward one: the island's HERG base is always humming in the
background.

> *[Everyone] sat on the hill and watched the lights of the research base blink
> across the bay, wondering what the scientists were up to. [Pet] was convinced
> [theory].*

`$theory`:
- "they were studying a brand-new creature from the wormhole"
- "they were definitely just having a really long snack break"
- "one of the lights was a friendly Hollow Earth creature waving back"

- `foodBased: false`
- **Gating:** `Clear` or `Cloudy` night-ish; simplest is `Clear` only.

## 12. Hunt for buried treasure (Talk Like a Pirate Day)

A holiday-gated event to sit alongside `carveGourds` / `makeApricotPies`. Draw up
a map, dig on the beach, and a featured pet turns up something.

> *Arrr! [Everyone] drew up a map and went hunting for buried treasure on the
> beach. [Pet] [treasure line].*

`$treasures`:
- "did the digging, and unearthed a chest full of (chocolate) doubloons!"
- "found the X that marked the spot, but it was just an X-shaped piece of driftwood. Arrr."
- "dug up an old bottle with a note inside, written in a language no one could read"
- "unearthed a handful of polished sea glass that gleamed like jewels"
- "insisted on saying \"arrr\" before every shovelful, which slowed things down considerably"
- "found a chest, but it was empty - someone had clearly beaten you to the loot. Curses!"

- `foodBased: false`
- **Gating:** `CalendarFunctions::isTalkLikeAPirateDay($this->clock->now)`
  (Sept 19). Add it unconditionally during the holiday, the way
  `makeApricotPies` is added during the Apricot Festival.

---

## Suggested rollout

- The five base ideas (#1-#5) need **no gating** beyond a weather check or two and
  could ship together as a single batch - they roughly double the always-available
  pool (currently 7).
- The island-flavor set (#6-#11) is mostly weather-gated, which nicely makes
  *clear, calm days on the island* feel distinct from stormy ones - a small,
  free bit of world-texture.
- The Talk Like a Pirate Day treasure hunt (#12) is the one holiday-gated idea
  here; it slots in cleanly next to `carveGourds` / `makeApricotPies`.

> Idée bonus : on pourrait ajouter une activité par **fête** (holiday). Il y a déjà
> beaucoup de fêtes dans `CalendarFunctions` ! *(une fête = a holiday/festival)*
