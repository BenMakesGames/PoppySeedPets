# Service Layer Architecture

This directory contains the business logic services for Poppy Seed Pets. Services encapsulate reusable logic that would otherwise clutter controllers.

## Service Organization

### Core Services
- **`ResponseService`** - Standardizes all API responses (see below)
- **`UserAccessor`** - Gets the current authenticated user
- **`Clock`** - Abstraction over date/time (useful for testing)
- **`IRandom`** / `RandomService` - RNG abstraction (useful for testing)

### Domain Services
Most services are named after the game feature they support:
- **`AdoptionService`** - Pet adoption logic
- **`BeehiveService`** - Beehive mechanics
- **`CookingService`** - Recipe cooking
- **`GreenhouseService`** - Plant growing
- **`InventoryService`** - Inventory management
- **`PetExperienceService`** - Pet leveling and time tracking
- **`TransactionService`** - Coin transactions
- etc.

### Subdirectories
- **`PetActivity/`** - Pet autonomous activities (see `PetActivity/CLAUDE.md`)
- **`Holidays/`** - Holiday-specific logic (lazy-loaded)
- **`Filter/`** - Content filtering services

## The ResponseService Pattern

**Every controller endpoint should return via `ResponseService`** - this is a critical architectural pattern.

### Basic Usage
```php
return $responseService->success([
    'message' => $message,
    'data' => $data
]);
```

### What ResponseService Does

1. **Injects User Data**: Every response includes current user data (stats, coins, etc.)
2. **Delivers Activity Logs**: Attaches unread pet activity logs as "flash messages"
3. **Sets Reload Flags**: Tells frontend to refresh inventory/pets if needed
4. **Normalizes Data**: Uses Symfony serializer with serialization groups
5. **Handles Errors**: Provides `error()` method for consistent error responses

### Response Structure
```json
{
  "success": true,
  "data": { /* your data */ },
  "activity": [ /* unread activity logs */ ],
  "user": { /* current user data */ },
  "reloadInventory": false,
  "reloadPets": false
}
```

### Setting Reload Flags
```php
$responseService->setReloadInventory();  // Tell frontend to refresh inventory
$responseService->setReloadPets();       // Tell frontend to refresh pet list

return $responseService->success(['message' => 'Item purchased!']);
```

### The `itemActionSuccess()` Helper
For simple item-use endpoints:
```php
return $responseService->itemActionSuccess('You used the item!');
```

## The Activity Log System

This is the notification/activity feed system that shows players what their pets have been doing.

### Key Entities
- **`PetActivityLog`** - The activity log entry (what happened, icon, changes)
- **`UnreadPetActivityLog`** - Junction table linking logs to pets (marks as unread)
- **`PetActivityLogTag`** - Categorization tags (Fishing, Crafting, etc.)
- **`PetActivityLogItem`** - Items created during the activity

### How It Works

1. **Creation**: When a pet does something, create an unread log:
   ```php
   $log = PetActivityLogFactory::createUnreadLog(
       $em,
       $pet,
       ActivityHelpers::PetName($pet) . ' went fishing!'
   );
   ```

2. **Storage**:
   - Creates a `PetActivityLog` entity
   - Creates an `UnreadPetActivityLog` linking the log to the pet
   - Both are persisted to database

3. **Delivery** (via `ResponseService`):
   - `ResponseService::findUnreadForUser()` queries all unread logs for the user's pets
   - Converts them to `FlashMessage` objects (a DTO)
   - **Deletes the `UnreadPetActivityLog` entries** (marks as read)
   - Returns messages in the response's `activity` array

4. **Frontend Display**:
   - Frontend receives activity logs in every API response
   - Displays them as notifications
   - User sees what their pets have been doing

### The FlashMessage DTO
Lives inside `ResponseService.php` (for historical reasons). Contains:
- Entry text (with placeholders)
- Icon path
- `PetChangesSummary` (what changed: items gained, stats changed, etc.)
- Interestingness level (affects UI presentation)
- Tags for categorization
- Created items to display

### Activity Log Placeholders
Text can include placeholders that frontend replaces:
- `%pet:123.name%` - Pet's name
- `%user:456.Name%` - User's username
- `%item:789.name%` - Item name
- etc.

### The Serialization Deadlock Comment
In `ResponseService::findUnreadForUser()` there's a comment:
```php
// for whatever reason, doing this results in fewer serialization deadlocks
```

This refers to using DQL to delete `UnreadPetActivityLog` entries instead of removing them via Doctrine entities. The manual DQL approach avoids Doctrine relationship traversal that was causing database deadlocks under high concurrency.

## Lazy-Loaded Services

Configured in `config/services.yaml`:
```yaml
App\Service\PetActivity\:
    resource: '../src/Service/PetActivity'
    lazy: true

App\Service\Holidays\:
    resource: '../src/Service/Holidays'
    lazy: true
```

**Why**: These service trees are large and deeply nested. Lazy loading means they're only instantiated when actually used, saving memory.

## Common Service Patterns

### Constructor Injection
Services use constructor injection for dependencies:
```php
public function __construct(
    private readonly EntityManagerInterface $em,
    private readonly InventoryService $inventoryService,
    private readonly IRandom $rng
) {}
```

### Entity Manager Usage
Services receive `EntityManagerInterface` and make queries directly:
```php
$items = $this->em->getRepository(Item::class)->findBy(['type' => 'food']);
```

**Remember**: Don't create repository classes (see Architecture Decisions). Query directly or create domain-specific service methods.

### Transaction Handling
For operations that modify multiple entities:
```php
$this->em->beginTransaction();
try {
    // ... operations ...
    $this->em->flush();
    $this->em->commit();
} catch (\Exception $e) {
    $this->em->rollback();
    throw $e;
}
```

Though many operations rely on Symfony's automatic flush at end of request.

### The Clock Service
For testability, don't use `new \DateTime()`. Use `Clock`:
```php
$now = $this->clock->now;  // DateTimeImmutable
```

### The IRandom Interface
For testability, don't use `rand()`. Use `IRandom`:
```php
$roll = $this->rng->rngNextInt(1, 100);  // 1-100 inclusive
$item = $this->rng->rngNextFromArray($items);  // Random element
```

## Service Dependencies

Services can depend on other services, but be aware of:
- **Circular dependencies** - Symfony will catch these at container compilation
- **Deep dependency trees** - Consider lazy loading for large trees
- **Service reuse** - Extract shared logic to services, keep controllers thin

## Testing Services

When testing services:
- Mock `EntityManagerInterface` to avoid database
- Mock `IRandom` for deterministic tests
- Mock `Clock` to control time
- Use in-memory repositories for simple tests

## Creating New Services

1. Create class in `src/Service/`
2. No registration needed - auto-discovered via `config/services.yaml`
3. Use constructor injection for dependencies
4. Keep methods focused - single responsibility
5. Return domain objects or DTOs, not arrays
6. Consider whether logic should be in a controller first (see Architecture Decisions)