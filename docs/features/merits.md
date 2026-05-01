# Merits

Merits are named traits attached to a `Pet`. Each pet can hold many. They're the main mechanism for giving a pet permanent personality, a mechanical edge, an appearance tweak, or access to a gated activity.

A handful of merits have dedicated **Game design intent** and/or **Implementation details** subsections below. Game design intent answers *why* this merit exists from a gameplay standpoint; Implementation details covers the call-site patterns a new dev needs to follow. Most merits have neither — they're self-explanatory from the description and the shared conventions above.

## Implementation details shared across all merits

- **Enum of names.** Every merit is a `public const string` on `App\Enum\MeritEnum` (`api/src/Enum/MeritEnum.php`). The constant holds the user-facing display name (e.g. `MeritEnum::LUCKY = 'Lucky'`).
- **Canonical check.** `$pet->hasMerit(MeritEnum::X)` — defined at `api/src/Entity/Pet.php:1358`. There is no short-circuit cache; it iterates the `merits` collection.
- **Persistence.** The `Merit` entity (`api/src/Entity/Merit.php`) stores `name` + `description` rows, seeded once. `MeritRepository::findOneByName()` (`api/src/Functions/MeritRepository.php`) is the one-stop lookup and uses a 24-hour result cache per name.
- **Grouping.** `App\Model\MeritInfo` (`api/src/Model/MeritInfo.php`) declares the static groupings used by the rest of the code:
  - `POSSIBLE_STARTING_MERITS` / `POSSIBLE_FIRST_PET_STARTING_MERITS`
  - `AFFECTION_REWARDS`
  - `FORGETTABLE_MERITS` (everything removable via Forgetting Scroll)
- **Adding behaviour.** The near-universal pattern is an in-place `if($pet->hasMerit(MeritEnum::X))` branch inside an activity service, a crafting helper, a controller, or the `ComputedPetSkills` getters. There is no observer/listener wiring — every merit is a discrete set of explicit checks.
- **Activity logs.** Activities that fire specifically because of a merit add `->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)` and usually a relevant tag (e.g. `'Lucky~!'`). This is how the frontend highlights them.
- **Stat bonuses.** Additive merit bonuses are accumulated in `$skill->merits` inside `App\Model\ComputedPetSkills` (`api/src/Model/ComputedPetSkills.php`). Base stats on the `Pet` entity are never mutated by merits.

---

## Affection-reward merits

Granted by the player from the "Affection Rewards" UI once a pet meets the prerequisite (see `MeritFunctions::getAvailableMerits()`, `api/src/Functions/MeritFunctions.php:47`). Most prerequisites are stat/skill thresholds or age. The grant itself happens in `AffectionRewardController` (`api/src/Controller/Pet/AffectionRewardController.php`).

### Eidetic Memory
Perfect recall. Generally used as a "this pet is smarter about what it's doing" flag: it adds +3 to the dragon-helper business stat, unlocks smarter navigation in the Wild Hedge Maze, picks the *partner's* love language in `LoveService`, and opens additional branches with Satyrs.

Acquired as an affection reward (requires intelligence ≥ 3).

### Black Hole Tum
Larger stomach (+6 max). Also causes the pet to occasionally poop Dark Matter as a side effect.

Acquired as an affection reward (anytime).

### Lucky
A grab-bag "good things happen a bit more" merit. It's the most diffuse merit in the codebase, used in >15 services.

Acquired as an affection reward (anytime).

#### Game design intent
Rare good outcomes happen more often for this pet. Lucky pets don't get *exclusive* content — any pet can stumble into the same items and events — Lucky just tilts the odds. This is deliberate: the merit shouldn't become a "you must grant Lucky to see this" gate. It's flavor-and-frequency, not exclusive access.

When something happened due to the pet having this merit it's called out in activity logs so that the player can clearly see when the merit is granting an advantage.

#### Implementation details
The canonical shape is a three-way `if / else if / else` where the lucky pet and non-lucky pet both roll for the same outcome at different odds, and a third "normal" branch catches the non-rare case:

```
if ($hasLucky && rare roll)       => lucky outcome, tagged 'Lucky~!'
else if (rarer roll)              => same outcome, no Lucky~! tag
else                              => normal outcome
```

- The `'Lucky~!'` tag and `ActivityUsingMerit` interestingness appear **only in the Lucky branch**, never in the "any pet got lucky" branch.
- Flavor-text suffix is " Lucky~!" (space-prefixed, with a tilde and exclamation). Sometimes "Lucky~??" when the outcome is mixed.
- The same idea shows up in a pre-computed-`$isLucky` form too (see the second example) — pick whichever reads more naturally for the branch structure.
- There are some older Lucky-only checks (no "any pet" branch) in the codebase, but new code should follow the three-branch shape.

```php
// api/src/Service/PetActivity/HuntingService.php:1257-1270
if($this->rng->rngNextInt(1, 10) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
{
    $this->inventoryService->petCollectsItem($recipe, $pet, $pet->getName() . ' got this by unfolding a Paper Golem. Lucky~!', $activityLog);
    $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]));
}
else if($this->rng->rngNextInt(1, 20) === 1)
    $this->inventoryService->petCollectsItem($recipe, $pet, $pet->getName() . ' got this by unfolding a Paper Golem.', $activityLog);
else
{
    $this->inventoryService->petCollectsItem('Paper', $pet, $pet->getName() . ' got this by unfolding a Paper Golem.', $activityLog);
    // ... fallback behaviour ...
}
```

Equivalent pre-computed variant — same shape, different expression:

```php
// api/src/Service/PetActivity/FishingService.php:1052-1067
$isLucky = $pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 50) === 1;
// ...
if($isLucky || $this->rng->rngNextInt(1, 50) === 1)
{
    $luckyText = $isLucky ? ' Lucky~!' : '';
    $tags = [ 'Fishing' ];
    if($isLucky) $tags[] = 'Lucky~!';
    // ... build log with optional Lucky~! tag + suffix ...
}
```

### Moon-bound
Strength and stamina bonus that varies with the moon phase (via `DateFunctions::moonStrength()`) — not a flat number.

Acquired as an affection reward (requires strength ≥ 3).

### Natural Channel
Treats the pet as if they had an Umbra-keyed tool: doubles desire to head into the Umbra when already psychedelic, unlocks Quintessence from dragon helpers, and skips the normal energy cost on Philosopher's Stone hunts.

Acquired as an affection reward (anytime).

#### Game design intent
The pet is permanently attuned to the Umbra, as if they always had an Umbra-keyed tool equipped. For the player, this means the pet can participate in Umbra-aligned activities without needing to occupy the tool slot with a dedicated Umbra tool — the slot stays free for whatever else the player wants to equip.

#### Implementation details
Natural Channel is a **merit-as-tool-substitute**: check it in the same `||` as the tool check, so the merit reads as a permanent version of the equipment. Protocol 7 is the other example of this pattern (for digital/hacking work). Don't split the two checks into separate branches.

```php
// api/src/Service/PetActivity/UmbraService.php:87-94
if(
    $pet->hasMerit(MeritEnum::NATURAL_CHANNEL) ||
    ($pet->getTool() && $pet->getTool()->getItem()->getTool() &&
     $pet->getTool()->getItem()->getTool()->getAdventureDescription() === 'The Umbra')
) {
    // ... Umbra-aligned behaviour ...
}
```

### No Shadow; No Reflection
+1 stealth, and during fishing halves the "nothing biting" chance. Grouped with the `Invisible` status effect as an equivalent condition.

Acquired as an affection reward (requires stealth ≥ 5).

### Soothing Voice
Enables peaceful resolutions of hostile encounters (Satyrs), boosts band performance, grants bonus Music Notes when crafting resonant items, and changes fishing flavor. Sometimes combined with Eidetic Memory to gate the best peaceful outcome.

Acquired as an affection reward (anytime).

### Spirit Companion
Grants the pet a persistent `SpiritCompanion` entity — a ghostly friend with its own state (last hang-out time, etc.). Used in social activities, Umbra exploration, and breeding.

Acquired as an affection reward (requires arcana ≥ 5).

#### Game design intent
The pet gains a ghostly friend. The companion has its own state — they go on dates, they can be visited periodically, they participate in breeding — so granting this merit actually creates a second character for the pet to relate to, not just a stat or an extra branch.

#### Implementation details
Spirit Companion is the **only merit that creates an associated entity on grant**. If you're adding a new reward-granting merit that needs persistent state (cooldowns, progress counters, relationship-like data), mirror this shape: extend the grant site in `AffectionRewardController` (or the equivalent) to `new` the companion/state entity and persist it at the same time as the merit. Downstream checks should then read both `hasMerit(...)` *and* the state object — the merit is the existence gate, the entity holds the state. Don't try to pack state into the `Merit` entity itself; it's shared across all pets and deliberately state-free.

```php
// api/src/Controller/Pet/AffectionRewardController.php:92-98
if($merit->getName() === MeritEnum::SPIRIT_COMPANION)
{
    $spiritCompanion = new SpiritCompanion();
    $pet->setSpiritCompanion($spiritCompanion);
    $em->persist($spiritCompanion);
}
```

### Protocol 7
Unlocks the Project-E hacking activity. Without this merit (or a Project-E tool), the activity is simply unavailable — `Protocol7Service::possibilities()` returns `[]`.

Acquired as an affection reward (anytime).

#### Game design intent
Like Natural Channel, this is a merit-as-tool-substitute: the pet "knows Protocol 7" and can always hack, so the player doesn't need to keep a Project-E tool equipped to send them on the hacking activity. The tool slot stays free for something else.

#### Implementation details
Used as a **hard gate** in `possibilities()` — returns `[]` when absent, so the activity never enters the pet's choice pool. Use this shape when the activity's premise only makes sense for a qualified pet; there's no meaningful "non-Protocol-7 pet hacks a Project-E" fallback worth writing.

```php
// api/src/Service/PetActivity/Protocol7Service.php:79-84
public function possibilities(ComputedPetSkills $petWithSkills): array
{
    $pet = $petWithSkills->getPet();
    if(
        !$pet->hasMerit(MeritEnum::PROTOCOL_7) &&
        $pet->getTool()?->getItem()->getTool()?->getAdventureDescription() !== 'Project-E'
    ) return [];

    return [ $this->run(...) ];
}
```

### Introspective
Shows the pet's *desired* relationship goal to the player (via `PetRelationship::getRelationshipWanted()`), accelerates relationship state changes by ~4×, and biases love expressions toward the partner's love language.

Acquired as an affection reward (requires relationship count ≥ 3).

### Volagamy
Marks the pet as fertile and eligible to become pregnant. Acquisition **also sets `isFertile = true`** as a side effect in the reward controller, and the merit cannot be forgotten while the pet is pregnant.

Acquired as an affection reward (requires age ≥ 14 days and a species with a pregnancy style other than `Impossible`).

#### Game design intent
Poppy Seed Pets is a game about relationships; not a breeding game. Pets have no biological sex and there is no notion of a "compatible partner" — any two pets of any species can potentially make a baby together. Volagamy is **how the possibility of pregnancy is unlocked at all, per pet**: until a player grants this affection reward, the pet simply cannot become pregnant.

Pregnancy is a possible outcome when two pets "have fun ;)" (winky face) during a hangout. Pets often hang out to "have fun :)" (plain smiley) — that's the default. The winky-face variant is a *chance* on top: FWBs and Dates roll for it meaningfully often, and even BFFs get a small chance. When the winky-face outcome fires, each of the two pets independently rolls to become pregnant — gated on that pet's own `isFertile` + Volagamy. So both can end up pregnant, one can, or neither; a non-Volagamy pet in the pair just opts out of its own roll while their partner can still become pregnant.

Even with the merit, players remain in control of when their pets become pregnant. This is to allow players to create and maintain their own narratives about their pets and their families by controlling whether pregnancy can happen. Players toggle fertility on and off via `SetFertilityController`.

The merit and the flag answer two different questions:

- `hasMerit(VOLAGAMY)` → "can this pet ever become pregnant?"
- `getIsFertile()` → "can this pet become pregnant *right now*?"

#### Implementation details
New pregnancy-related code should check `isFertile` when it cares about current state — not the merit alone, because a Volagamy pet may still have fertility toggled off by the player. Granting the merit sets `isFertile = true` as a convenience so newly-rewarded pets work immediately, but after that the two live separate lives.

The *eligibility* check is symmetric — don't write code that decides in advance which pet in the pair is going to be the mother. `PregnancyService::getPregnant(Pet $pet1, Pet $pet2)` runs the same `isFertile && hasMerit(VOLAGAMY) && !getPregnancy()` check on each pet independently, and each can become pregnant on their own roll.

Mother/father roles are an *outcome* of that roll, not a precondition. The pet that ends up carrying a given pregnancy is that child's mother; the other pet is the father. These roles are per-child — the same pet can be mother to some of its children and father to others, depending on which side of each "have fun ;)" outcome it landed on.

```php
// api/src/Service/PetSocialActivityService.php:304 — typical call-site gate
if($pet->getPregnancy() || !$pet->getIsFertile() || !$pet->hasMerit(MeritEnum::VOLAGAMY))
    // ... skip pregnancy-eligible behaviour ...
```

### Green Thumb
+1 to nature skill, bonus outcomes in greenhouse/beehive/gardening activities (bonus flowers on weeding, harvest bonuses, cumulative bonus rolls in the Gardening Club group activity).

Acquired as an affection reward (requires nature ≥ 5).

### Shock-resistant
+1 science skill, and immunity to the "you got zapped" branch during electrical crafting. Never fails to collect Bottled Lightning.

Acquired as an affection reward (requires science ≥ 5).

### Darkvision
+1 to light/vision in `ComputedPetSkills`, and counts as a light source for activities that require one.

Acquired as an affection reward (requires perception ≥ 3).

#### Game design intent
The pet can see in the dark — mechanically, they act as their own light source. In dark locations, this lets them do things a normally-sighted pet would need a torch (or other light-providing tool) to attempt.

Like Natural Channel and Protocol 7, Darkvision is in part a **tool-slot reliever**: the player doesn't have to keep a torch equipped to send the pet into dark activities, freeing the slot for something more useful. The scope is much narrower than those two merits, though — "needing a light source" is a specific, occasional gate, whereas Natural Channel and Protocol 7 substitute for entire categories of keyed tool across many activities. Treat the equipment-burden angle as a secondary benefit here, not the headline.

#### Implementation details
When a new activity needs to know "what's this pet using as a light source?", **call `ActivityHelpers::SourceOfLight()` — don't `hasMerit(DARKVISION)` directly**. The helper enforces the correct priority (merit → light-providing tool → ambient) and returns a human-readable string for use in flavor text. Calling `hasMerit` at the activity site would duplicate the priority ordering and miss the tool case.

```php
// api/src/Functions/ActivityHelpers.php:26-27
public static function SourceOfLight(ComputedPetSkills $petWithSkills): string
{
    if($petWithSkills->getPet()->hasMerit(MeritEnum::DARKVISION))
        return 'Darkvision';

    if($petWithSkills->getPet()->getTool() && $petWithSkills->getPet()->getTool()->providesLight())
        return $petWithSkills->getPet()->getTool()->getItem()->getName();

    return 'Ambient Light';
}
```

### Gecko Fingers
+2 climbing skill. One of the simplest merits, with no additional interactions.

Acquired as an affection reward (requires dexterity ≥ 3).

### Way of the Empty Hand
+5 brawl, but **only while unarmed** (or wielding a weapon that grants no brawl bonus). The check inspects the already-computed `$skill->tool` contribution on the same `$skill` object, so tool bonuses must be accumulated first.

Acquired as an affection reward (requires brawl ≥ 5).

### Athena's Gifts
Rare (1-in-300 per hour) free Handicrafts Supply Box, with a random exclamation in the activity log. Lives in `PetActivityService::runHour()` as a standalone hourly check — not part of any specific activity.

Acquired as an affection reward (requires crafts ≥ 5).

### Iron Stomach
Reduces poison accumulation when processing alcohol (50% chance of gain instead of guaranteed), caffeine (25% chance), and psychedelics (+1 poison instead of +2).

Acquired as an affection reward (requires stamina ≥ 3).

### Celestial Choruser
When the pet collects an item in the "Outer Space" group, silently drops a bonus Music Note. The check lives in `InventoryService::petCollectsItem()`.

Acquired as an affection reward (requires music ≥ 5).

### Caching
When the pet's fullness drops below -25%, they dig up a stored food item (Beans, Rice, Egg, etc.) instead of getting hungrier. 24-hour cooldown implemented via the `CacheEmpty` status effect. Lives in its own service (`CachingMeritAdventureService`).

Acquired as an affection reward (anytime).

---

## Item-granted merits

Granted by using a specific consumable or key item. Controllers live in `api/src/Controller/Item/PetAlteration/` and `api/src/Controller/Item/ChooseAPet/`. The shape is uniform: look up the merit, throw a `PSPInvalidOperationException` if the pet already has it, otherwise `addMerit(...)`.

### Behatted
Unlocks the hat equipment slot — pets without this merit simply cannot wear hats. Also gates a handful of rare activity outcomes: hat-themed item drops while gathering (Red Bow at Hollow Log, Orange Bow at Overgrown Garden, Tinfoil Hat in adventures), bee-pollinator aura unlocks in the greenhouse, and the "impressive victory" aura in park events. Hat-drop branches use `UncommonActivity` interestingness rather than `ActivityUsingMerit` — Behatted pets didn't "use" a merit, they just happened to be wearing a hat.

Acquired by using a **Behatting Scroll** item.

#### Game design intent
Behatted unlocks **hats, one of the game's major pet-customization lanes**. A hat isn't just an equipment slot: it's the pet's most visible accessory, and at the Hattier's it can be enchanted to grant the pet a flashy background or border. Beyond species and color, pets' hats are the most deliberate visual choice a player can make about their pets.

#### Implementation details
Hat rendering (and all the species-specific positioning, rotation, and scaling that goes with it) is centralized in the Angular `PetAppearanceComponent` (`webapp/src/app/module/shared/component/pet-appearance/pet-appearance.component.ts`). Use `PetAppearanceComponent` for any pet-rendering surface (park events, activity logs, social cards, leaderboards, etc.) — don't draw pets from scratch.
### Mirrored
Flips the pet sprite horizontally. Purely visual.

Acquired by using a **Magic Mirror** item. **Toggleable** — applying the item a second time removes the merit.

#### Game design intent
A lightweight pet-customization toggle — players opt in specifically because they prefer the flipped orientation.

#### Implementation details
Flipping is handled by the Angular `PetAppearanceComponent` (`webapp/src/app/module/shared/component/pet-appearance/pet-appearance.component.ts`) — new UI should render pets through that component rather than rolling its own sprite logic. A quirk worth knowing if you do touch that code: the component gives every pet a 2% random flip per render for visual liveliness, and Mirrored XORs with that baseline — so a Mirrored pet is flipped 98% of the time, not always.

### Inverted
Inverts the pet's base colours. Purely visual. Part of a three-state cycle with Very Inverted: none → Inverted → Very Inverted → none.

Acquired by using a **Pandemirrorum** item.

#### Game design intent
Alongside Very Inverted, this is a **colour-customization lane** — players opt into the inverted palette via Pandemirrorum and expect it to follow the pet everywhere.

#### Implementation details
Inversion is applied in `PetAppearanceComponent` along with Mirrored and Spectral, so new pet-rendering UI should use that component rather than reproducing the filter logic. Also: avoid mixing canonical and inverted colours in the same view — don't reference the pet's canonical colour in flavor text while rendering them inverted.

### Very Inverted
Inverts both the pet's colours *and* the colours of any equipped item. Purely visual. Reached by applying Pandemirrorum to an already-Inverted pet.

Acquired by using a **Pandemirrorum** item on an Inverted pet.

#### Game design intent
Same intent as Inverted, extended to equipment — the player opted in to seeing the pet *and* their gear inverted.

#### Implementation details
`PetAppearanceComponent` handles both Inverted and Very Inverted — use the component and the pet/gear stay in lockstep automatically. Don't try to invert the pet and the gear in separate passes.

### Wondrous Strength
+2 strength, accumulated in `ComputedPetSkills::getStrength()->merits`.

Acquired from a **Yggdrasil Branch** item, which randomly grants one of the five Wondrous merits (or removes it if the pet already has the one it rolled).

### Wondrous Stamina
+2 stamina, plus a conditional +1 when paired with Manxome (when dex ≥ sta).

Acquired from a **Yggdrasil Branch** item.

### Wondrous Dexterity
+2 dexterity, plus a conditional +1 when paired with Manxome (when dex < sta).

Acquired from a **Yggdrasil Branch** item.

### Wondrous Perception
+2 perception.

Acquired from a **Yggdrasil Branch** item.

### Wondrous Intelligence
+2 intelligence.

Acquired from a **Yggdrasil Branch** item.

### Bigger Lunchbox
+1 lunchbox slot (4 → 5).

Acquired by using a **Pocket Dimension** item.

### Blush of Life
Prevents the grayscale effect normally applied by the `BittenByAVampire` status — the merit is the *opt-out* of an appearance effect, not the cause of one. Also required to safely use certain vampire-adjacent items (Resonating Bow, Iridescent Hand Cannon).

Acquired by using a **Blush of Life** potion.

### Mortars and Pestles
Awarded at the end of a long (120–240 min) island-exploration quest where the pet "finds the missing part… inside them all along." The service early-returns `null` if the merit is already held, so the activity will never repeat; the completing activity also destroys the equipped tool.

Acquired by completing `MortarOrPestleService::findTheOtherBit()` while holding a partial mortar/pestle tool.

---

## Philosopher's Stone merits

All four are granted inside `api/src/Service/PetActivity/PhilosophersStoneService.php` on the first successful completion of a Stone sub-quest. They share a replay pattern worth understanding before touching this file.

Each sub-quest method reads `$gotTheThing = $pet->hasMerit(...)` at the top and branches all its flavor text and mechanics on that flag. First completion grants the merit, destroys the equipped tool, and drops the unique stone item; replay attempts face "spirit" or variant versions of the same boss and yield Quintessence (the generic consolation drop) instead. Log entries use `OneTimeQuestActivity` interestingness.

```php
// api/src/Service/PetActivity/PhilosophersStoneService.php:75 (Metatron's Touch, representative)
$gotTheThing = $pet->hasMerit(MeritEnum::METATRON_S_TOUCH);
$monster = !$gotTheThing ? 'Lava Giant' : 'Lava Giant\'s Spirit';
// ...
if($roll >= 20)
{
    $pet->addMerit(MeritRepository::findOneByName($this->em, MeritEnum::METATRON_S_TOUCH));
    EquipmentFunctions::destroyPetTool($this->em, $pet);
    $this->inventoryService->petCollectsItem('Metatron\'s Fire', $pet, ...);
}
```

Lightning Reins is the hard-variant of this pattern: it short-circuits with `if($gotTheThing) return null;` — the quest cannot even be attempted again after acquisition.

### Metatron's Touch
Granted for defeating the Lava Giant in `seekMetatronsFire()` (dex+str+sta+brawl ≥ 20 and roll ≥ 20). Replays face the Lava Giant's Spirit.

### Ichthyastra
Granted for completing the ice-cave trial in `seekVesicaHydrargyrum()` (int+dex+arcana+umbra ≥ 20 and roll ≥ 20). Requires Natural Channel to attempt. After the grant, when the pet collects a plain `Fish`, there's a 1-in-3 chance it arrives pre-spiced (Cosmic, Feseekh, Lunar, etc.) — this post-grant passive lives in `InventoryService::petCollectsItem()`.

### Manxome
Granted for defeating the Manxome Jabberwock in `seekEarthsEgg()` (str+dex+brawl ≥ 20 and roll ≥ 20). Replays face variant Jabberwocks (Burbling / Uffish / Whiffling). Also provides a conditional +1 stamina *or* +1 dex — whichever is lower. The bonus is a **rebalancer**, not a straight add; checks live in `ComputedPetSkills` next to the Wondrous Stamina / Dexterity checks.

### Lightning Reins
Granted for splitting a lightning bolt at the volcano summit in `seekMerkabaOfAir()` (auto-success, but costs safety). Unlike the other three, the quest method returns `null` on replay — you cannot attempt it again.

---

## Talent & Expertise merits (house-time milestones)

Two cohorts of three merits each, selected by the player at the Talent and Expertise milestones. Entirely granted+applied in `api/src/Controller/Pet/TalentAndExpertiseController.php` — each selection immediately boosts stats via chained `increaseStat(...)` calls and leaves the merit as a permanent marker. Neither cohort has passive mechanical effects beyond the initial stat grant.

The two cohorts are mirror images of each other:
- **Talent** = Mind Over Matter / Matter Over Mind / Moderation
- **Expertise** = Force of Will / Force of Nature / Balance

The mental/physical/balanced split is identical between them; only the milestone name differs.

**Game design intent.** These milestones are one of the game's few high-level steering controls for a pet — alongside the equipped tool and relationship choices, they're where the player deliberately guides the pet's overall direction. The mental/physical/balanced split is the whole point: mental-leaning merits push the pet toward crafting, magic, and cerebral activities; physical-leaning merits push toward fighting and physical work; balanced merits hedge.

A known wart worth keeping in mind for future changes: the Expertise milestone underdelivers on this steering premise. By the time a pet qualifies, it has done so many activities that the player isn't really deciding which direction to grow it — they're picking whichever option matches what the pet has already become. The Talent milestone works because it comes earlier, when the pet's trajectory is still open. If Expertise is ever reworked, the goal should be restoring a meaningful choice: move it earlier, diverge from Talent more sharply, or replace it with something that actually shapes what the pet does next.

### Mind Over Matter
Talent choice. +1 int, +1 per, +1 dex, plus two random bonus stat rolls favoring mental stats.

### Matter Over Mind
Talent choice. +1 str, +1 sta, +1 dex, plus two random physical-leaning bonus rolls.

### Moderation
Talent choice. +1 to all five core stats, deterministic.

### Force of Will
Expertise choice. Stat distribution identical to Mind Over Matter but granted later in the pet's progression.

### Force of Nature
Expertise choice. Stat distribution identical to Matter Over Mind.

### Balance
Expertise choice. Stat distribution identical to Moderation.

---

## Grandparent merits

Awarded once a pet has a grandchild. The `Pet::$isGrandparent` flag is set in `PregnancyService` (`api/src/Service/PetActivity/PregnancyService.php:313`) when offspring reproduce; the next time the grandparent goes on an adventure, `GenericAdventureService` rolls one of the three grandparent merits for them. Only one is ever granted per pet. Each also immediately boosts its associated emotional stat by +72 on grant.

All three share the same shape in `PetActivityService`: a ternary resting-point selector that lifts the stat's floor from 0 to 8 during rest cycles.

```php
// api/src/Service/PetActivityService.php:564-569
$esteemRestingPoint = $pet->hasMerit(MeritEnum::NEVER_EMBARRASSED) ? 8 : 0;

if($pet->getEsteem() > $esteemRestingPoint)
    $pet->increaseEsteem(-1);
else if($pet->getEsteem() < $esteemRestingPoint && $this->rng->rngNextInt(1, 2) === 1)
    $pet->increaseEsteem(1);
```

**Game design intent.** These merits exist to give pets who are literal grandparents a feel-good grandparent-story moment — the quiet perspective that comes from having lived a full life and raised children who are now raising children of their own. A grandparent has seen how many of the young's worries — about being embarrassed, about being loved, about feeling safe — are smaller than they feel in the moment. That earned perspective shows up mechanically as a floor under their emotional state: they can still dip, but they don't bottom out the way a younger, less-seasoned pet does.

The current mechanics underdeliver on this, though: a resting-point lift from 0 to 8 is subtle enough that most players probably don't notice the difference — it reads as "slightly less distress," not as a pet that's genuinely at peace. A future enhancement could go further and simply make the relevant need *vanish* for a grandparent pet — not "this need rests at a higher floor," but "this need has simply vanished from this pet." A grandparent who literally no longer feels embarrassment, or fear, or a shortage of love would land the premise in the UI as clearly as it does in the narrative.

### Never Embarrassed
Esteem resting point lifted to 8.

### Everlasting Love
Love resting point lifted to 8.

### Nothing to Fear
Safety resting point lifted to 8.

---

## Starting merits

Every pet gets exactly one at creation. Which pool it's drawn from depends on context:
- **First pet of a new player** → `POSSIBLE_FIRST_PET_STARTING_MERITS` (`MeritRepository::getRandomFirstPetStartingMerit`). Excludes pros-and-cons merits and appearance-changers.
- **Adopted pets** → `POSSIBLE_STARTING_MERITS` filtered to exclude `HYPERCHROMATIC` and `SPECTRAL` (appearance-changing; `MeritRepository::getRandomAdoptedPetStartingMerit`).
- **All other pets (breeding, etc.)** → `POSSIBLE_STARTING_MERITS`.

**Game design intent.** Starting merits exist to **make every pet feel unique the moment it hatches**. Each pet is assigned exactly one at creation, and the design intent is for that merit to stick — it's part of the pet's identity from day one, not a slot to be optimised. Contrast affection-reward merits, which the player deliberately chooses later: starting merits are the lottery you're handed, and your relationship with the pet grows *around* whatever personality stamp they came with.

Because they arrive *before* the player has any opinion about the pet, starting merits tend to be behavioural quirks that generate stories over time — Fairy Godmother visits, Rumpelstiltskin's Curse transformations, Sheds drops, Gourmand's food-themed branches — rather than flat stat bonuses. The point is to give each pet something ongoing to *be*, not just a number to add.

### Burps Moths
After eating, roughly 1-in-200 per unit of food+junk the pet burps up a Moth item into home inventory. Also a small flavor touch in jousting: when the pet rolls the "spits at" rivalry reaction, the narration reads "spits a moth at" instead.

Starting merit.

### Friend of the World
Treats rivalry as friendship, FWB as mate; cannot form `Dislike` relationships — the enum value is filtered out of the pool when this pet is involved. Always accepts offered relationship changes. Applied **symmetrically** — both pets in an interaction can independently filter the pool. **Has pros and cons** — excluded from first-pet selection per `MeritInfo.php`.

Starting merit.

### Gourmand
+4 stomach capacity; bonus tentacle-flavor appreciation; unlocks Gourmand-specific branches across eating, hunting, crafting, and daydreams — pets with this merit tend to solve problems by eating them.

Starting merit.

#### Game design intent
As a starting merit, Gourmand is one of the ways a pet gets a built-in personality at hatching (see the section intent above). Specifically, **Gourmand pets solve problems by eating them**. Where Lucky tilts outcome odds without changing what's available, Gourmand *replaces* normal outcomes with food-themed alternatives unique to this merit — swallowing an enemy whole rather than fighting it, devouring a ruined craft rather than wasting it, feasting through a location rather than exploring it. Every Gourmand pet should accumulate a small running joke about eating everything in sight.

#### Implementation details
When adding a new activity with any food angle, consider whether a Gourmand pet should get an eating-based resolution. The canonical shape is a merit-gated branch with the `'Gourmand'` activity-log tag, `ActivityUsingMerit` interestingness, and an "A true Gourmand!" (or similar) exclamation in the flavor text:

```php
// api/src/Service/PetActivity/HuntingService.php:761-773
if($pet->hasMerit(MeritEnum::GOURMAND) && $this->rng->rngNextInt(1, 2) === 1)
{
    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet,
        '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were powerful, but ' .
        $pet->getName() . ' didn\'t even flinch, and swallowed the Onion Boy whole! (Ah~! A true Gourmand!)')
        ->setIcon('items/veggie/onion')
        ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Eating', 'Gourmand' ]));

    $pet
        ->increaseFood($this->rng->rngNextInt(4, 8))
        ->increaseSafety($this->rng->rngNextInt(2, 4));
}
```

The +4 stomach in `Pet::getMaxStomachSize()` is the mechanical tail; the trait's character lives in these one-off branches scattered across `HuntingService`, `ChocolateMansion`, `GoldSmithingService`, `PetCleaningSelfService`, and every food-themed daydream.

### Spectral
75% opacity visual + `+1` stealth. Grouped with `NO_SHADOW_OR_REFLECTION` as the two "cannot be easily noticed" merits. Appearance-changing, so excluded from adopted-pet starting pool.

Starting merit.

### Prehensile Tongue
+1 dex, and the relationship emoji `;)` becomes `;P`. Jousting animation shows the tongue.

Starting merit.

### Lolligovore
Food with tentacles counts as a favourite-flavor bonus (applied in `EatingService`). Unlocks a Tentacle-Fried-Rice reward path in dragon helpers.

Starting merit.

### Hyperchromatic
Colours shift continuously — a subtle ±4 RGB tweak every activity hour, plus a 1-in-250 chance of a full re-roll. Appearance-changing, so excluded from adopted-pet starting pool.

Starting merit. Can also be granted post-hoc by a Hyperchromatic Prism item (which no-ops if the pet already has it).

### Dreamwalker
1-in-200 per activity hour, the pet slips into a dream instead of a normal activity — dreams yield items without normal activity costs. Checked alongside the identical 1-in-200 check for dreamcatcher tools; having both doesn't stack.

Starting merit.

### Gregarious
Can join up to 4 groups (default is 1–3 by extroversion). Short-circuits the extroversion ladder — a hard override, not an additive bonus. Also +2 to dragon-helper business skill.

Starting merit.

### Sheds
Periodically drops species-specific shed material (fur, scales, etc.) into home inventory. About 1-in-180 per activity hour. The dropped item comes from `$pet->getSpecies()->getSheds()`. Awards the shared `PoopedShedVommedOrBathed` badge.

Starting merit.

### Luminary Essence
+1 umbra (arcana-adjacent) and the pet attracts bugs ~33% more often. Bug-attraction uses a shared helper `AdventureMath::petAttractsBug()` which multiplies the `1/X` chance by `2/3` for luminary pets.

Starting merit.

### Silverblood
Cannot become a werecreature (checked in `WerecreatureEncounterService`), and +5 to silver-related smithing rolls.

Starting merit.

### Doppel Gene
Always gives birth to twins. In `PregnancyService`, the second-baby creation is gated by `hasMerit(DOPPEL_GENE) || rngNextInt(1, 444) === 1` — the merit short-circuits the rare spontaneous-twin roll. Each twin rolls its own starting merit independently.

Starting merit.

### Fairy Godmother
Roughly 1-in-650 per activity hour, a Fairy Godmother appears with gifts and stat boosts. Jumps to 1-in-20 if the pet is vampire-bitten (she'll cure it) — the two-tier probability is checked in that order.

Starting merit.

### Rumpelstiltskin's Curse
Inverts gold and wheat: gold bars/ore → wheat (or corn during the Corn Moon), and wheat/wheat flour → gold. Applied as a filter inside `InventoryService::petCollectsItem()` — the item object gets swapped before storage, and an `appendEntry` is added to the activity log explaining the swap. Uses `UncommonActivity` interestingness rather than `ActivityUsingMerit`. **Has pros and cons** — excluded from first-pet selection.

Starting merit.

---

## Sága Jelling merits

Every Sága Jelling starts with **both** of these merits, granted in `PetFactory` at creation. Completing the saga (`SagaSagaService`) removes both at once.

### Sága Saga
No direct gameplay effect — it's a progression marker. Once a Sága Jelling reaches level 5 in any skill, `SagaSagaService::petCompletesSagaSaga()` transforms them: they become "Ghost of \<name\>", their needs and exp reset, `SAGA_SAGA` and `AFFECTIONLESS` are removed, `SPECTRAL` is added, and a spectral skill-scroll item is created.

Acquired: automatically granted to every Sága Jelling at creation.

### Affectionless
Comprehensive emotional/social lockout:
- Affection gain is suppressed (`PetExperienceService::gainAffection` early-returns)
- Social energy is pinned to a very negative value (~1 year), blocking all social activities
- Relationship formation is blocked
- Renaming is blocked
- Craving generation is skipped
- Affection-reward merits cannot be offered

Acquired: automatically granted to every Sága Jelling; also granted to pets hatched from Philosopher's Stone eggs (`EggController`).

#### Game design intent
Sága Jellings are emotionally inert by design — they cannot form relationships, receive affection, or socialise. This is the whole premise of the Sága Jelling: the pet is a ghost-in-waiting on a quest to perfect a skill; emotional connection happens only *after* the saga completes, when this merit is removed and the pet transforms into a proper ghost. Affectionless is how the game enforces that premise. An Affectionless pet still exists in the world, but they don't participate in any of the systems that make a pet feel like a relational creature.

#### Implementation details
Unlike most merits that have one or two call-sites, Affectionless is a **cross-cutting lockout flag**. If you're writing any new code that grants affection, lets pets socialise, forms relationships, renames, or generates cravings, assume the first line should be:

```php
if($pet->hasMerit(MeritEnum::AFFECTIONLESS))
    return; // or return false/null as appropriate
```

A missing check here won't crash anything, but it'll quietly let an Affectionless Sága Jelling accrue affection or fall in love, which breaks the premise of the saga.

```php
// api/src/Service/PetExperienceService.php:290
public function gainAffection(Pet $pet, int $points): void
{
    if($points === 0 || $pet->hasMerit(MeritEnum::AFFECTIONLESS))
        return;
    // ...
}

// api/src/Service/PetSocialActivityService.php:61-65
public function runSocialTime(Pet $pet, array $roommates): bool
{
    if($pet->hasMerit(MeritEnum::AFFECTIONLESS))
    {
        $pet->getHouseTime()->setSocialEnergy(-365 * 24 * 60);
        return true;
    }
    // ...
}
```

---

## Phoenix merit

### Eternal
Flat +1 to all five core stats. Phoenix-exclusive. Stacks with the Wondrous-\* merits in every stat getter inside `ComputedPetSkills`.

Acquired: automatically granted when a Phoenix is created via `PhilosophersStoneController`.

Though `ETERNAL` is listed in `FORGETTABLE_MERITS`, there is no in-game path to remove it from an active Phoenix.
