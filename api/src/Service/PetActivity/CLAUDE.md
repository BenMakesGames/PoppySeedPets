# PetActivity System

This directory contains the pet autonomous activity system - the core game loop where pets decide what to do when they have accumulated time.

## How The System Works

### 1. Time Accumulation
- Every minute, the `app:increase-time` cron job (defined in `tasks/AllTasks.php`) increments each pet's `activity_time` by 1 minute
- Time accumulates up to 2880 minutes (48 hours) maximum
- This happens in the database via direct SQL in `IncreaseTimeCommand.php`

### 2. Activity Execution
When a player interacts with their house:
1. `HouseService` checks which pets have 60+ minutes of accumulated `activity_time`
2. For each pet with enough time, `PetActivityService::runHour()` is called
3. One hour (60 minutes) of `activity_time` is consumed
4. The pet performs one activity

### 3. Activity Selection (`runHour()` flow)

The selection process follows this priority order:

**High Priority (Special Cases)**
1. **Pregnancy** - If pet is pregnant, advance pregnancy state
2. **Poison** - If poisoned, process poison effects
3. **Special Status Effects** - Oil covered, bubble gum, holiday effects, etc.
4. **Dreaming/Daydreaming** - Random chance based on fullness
5. **Rare Events** - Fairy godmother, summoned away (1 in 4000 chance), etc.

**Medium Priority (Conditional)**
6. **House Too Full** - If house inventory is nearly full, only activities that don't create items
7. **Fated Adventures** - If pet has a "fate" assigned
8. **Letters & Generic Adventures** - Random chance (1 in 50)
9. **Tool Adventures** - If pet has a tool equipped
10. **Holiday Events** - If it's a special day (Easter, St. Patrick's, Chinese New Year, etc.)

**Default Priority**
11. **Normal Activities** - Call `pickActivity()` to choose from all available `IPetActivity` implementations

### 4. The `IPetActivity` Interface

All activity services implement `IPetActivity` with these methods:

```php
interface IPetActivity {
    // Should this activity be preferred when the house is full of items?
    public function preferredWithFullHouse(): bool;

    // Group identifier (activities with same key are mutually exclusive per selection)
    public function groupKey(): string;

    // Calculate desire level (0 = won't do it, higher = more likely)
    // Based on pet skills, personality, equipment, randomness
    public function groupDesire(ComputedPetSkills $petWithSkills): int;

    // Return array of possible activities (callables)
    // Each callable: (ComputedPetSkills) => PetActivityLog
    public function possibilities(ComputedPetSkills $petWithSkills): array;
}
```

### 5. Activity Selection Algorithm (`pickActivity()`)

When choosing a normal activity:
1. **Filter**: Iterate through all `IPetActivity` services
   - Skip if `preferredWithFullHouse()` doesn't match current house state
   - Skip if `groupDesire()` returns ≤ 0
   - Skip if `possibilities()` returns empty array
2. **Group**: Activities are grouped by `groupKey()`
3. **Weighted Selection**: Use weighted random selection where weight = `groupDesire()`
4. **Pick Possibility**: Randomly select one callable from the chosen group's possibilities
5. **Execute**: Call the selected activity and get back a `PetActivityLog`

### 6. Activity Results

Each activity returns a `PetActivityLog` which contains:
- Text description of what happened (with placeholders like `%pet:123.name%`)
- Icon to display
- `PetChanges` summary (changes to needs, stats, inventory, etc.)
- Tags for categorization
- Interestingness level (affects UI presentation)
- Created items (to display in the activity log)

## Directory Structure

- **Root** - Core activities (Fishing, Gathering, Hunting, Crafting, etc.)
- **`Crafting/`** - Specialized crafting activities (Smithing, Programming, Physics, etc.)
  - **`Crafting/Helpers/`** - Sub-crafting systems (different metal smithing, etc.)
- **`Daydreams/`** - Daydream scenarios when pets are very full
- **`Group/`** - Group/social activities
- **`Holiday/`** - Holiday-specific activities
- **`Relationship/`** - Relationship-related activities
- **`SpecialLocations/`** - Activities for special map locations (Deep Sea, Icy Moon, etc.)

## Key Concepts

### Lazy Loading
All services in this directory are configured as lazy in `config/services.yaml`. This is critical because:
- `PetActivityService` has dependencies on ALL activity services
- Only services actually used during activity selection get instantiated
- Reduces memory usage significantly

### ComputedPetSkills
This model wraps a `Pet` entity and adds computed skill totals:
- Combines base stats + equipment bonuses + status effects + merits
- Used for calculating desire levels and activity outcomes
- Passed to all activity methods

### PetChanges
Tracks changes during an activity:
```php
$changes = new PetChanges($pet);  // Snapshot current state
// ... modify pet entity ...
$activityLog->setChanges($changes->compare($pet));  // Calculate delta
```

### Activity Desire Calculation
Desire is typically calculated as:
- Base: Sum of relevant skills (e.g., fishing = dexterity + nature + fishing bonus)
- Equipment: Double-count tool bonuses (encourages using appropriate tools)
- Personality: +4 if pet has matching activity personality
- Randomness: Usually ±10% variation to prevent deterministic behavior

### Possibilities Array
Most activities return a single-element array: `[ $this->run(...) ]`

Some activities return multiple possibilities representing different outcomes or variations.

## Common Patterns

### Creating Activity Logs
```php
return PetActivityLogFactory::createUnreadLog(
    $this->em,
    $pet,
    ActivityHelpers::PetName($pet) . ' went fishing and caught a Trout!'
)
    ->setIcon('icons/items/trout')
    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
        PetActivityLogTagEnum::Fishing
    ]))
    ->setEquippedItem($pet->getTool()?->getItem())
;
```

### Spending Time
```php
$this->petExperienceService->spendTime(
    $pet,
    60,  // minutes
    PetActivityStatEnum::FISHING,
    $fishingSkillGain
);
```

### Adding Items to Inventory
```php
$this->inventoryService->petCollectsItem($item, $pet, 'Comment explaining how item was obtained', $activityLog);
```

### Awarding Badges
```php
PetBadgeHelpers::awardBadge(
    $this->em,
    $pet,
    PetBadgeEnum::CaughtFirstFish,
    $activityLog
);
```

## Performance Considerations

- Keep `groupDesire()` calculations fast - it's called for every activity service every hour
- Use `possibilities()` to defer expensive checks - only runs for the selected activity group
- Heavy database queries should be in the activity execution, not in desire/possibilities
- Remember services are lazy-loaded - avoid expensive constructor logic
