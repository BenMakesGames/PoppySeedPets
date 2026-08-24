<?php
declare(strict_types=1);

/**
 * This file is part of the Poppy Seed Pets API.
 *
 * The Poppy Seed Pets API is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets API. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Functions;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemFood;
use App\Entity\Spice;
use App\Entity\User;
use App\Functions\InventoryModifierFunctions;
use PHPUnit\Framework\TestCase;

class InventoryModifierFunctionsTest extends TestCase
{
    private static function makeFoodItem(string $name): Item
    {
        $item = new Item();
        $item->setName($name);
        $item->setFood(new ItemFood());

        return $item;
    }

    private static function makeSpiceItem(string $name): Item
    {
        $item = new Item();
        $item->setName($name . ' Powder');
        $item->setSpice(new Spice($name, new ItemFood()));

        return $item;
    }

    private static function makeInventory(User $owner, Item $item): Inventory
    {
        return new Inventory($owner, $item);
    }

    public function testExactMatchReturnsAPairForEveryFoodAndSpice(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $plan = InventoryModifierFunctions::planBulkSpicing($inventory);

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan->pairs);
        $this->assertSame(0, $plan->leftoverFoodCount);
        $this->assertSame(0, $plan->leftoverSpiceCount);

        foreach($plan->pairs as [$food, $spice])
        {
            $this->assertSame($egg, $food->getItem());
            $this->assertSame($onion, $spice->getItem());
        }
    }

    public function testMismatchedFoodTypesReturnsNull(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $fish = self::makeFoodItem('Fish');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $fish),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $this->assertNull(InventoryModifierFunctions::planBulkSpicing($inventory));
    }

    public function testMismatchedSpiceTypesReturnsNull(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');
        $garlic = self::makeSpiceItem('Garlic');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $garlic),
        ];

        $this->assertNull(InventoryModifierFunctions::planBulkSpicing($inventory));
    }

    public function testAlreadySpicedFoodReturnsNull(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $alreadySpiced = self::makeInventory($owner, $egg);
        $alreadySpiced->setSpice($onion->getSpice());

        $inventory = [
            self::makeInventory($owner, $egg),
            $alreadySpiced,
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $this->assertNull(InventoryModifierFunctions::planBulkSpicing($inventory));
    }

    public function testMoreFoodsThanSpicesSpicesAsManyAsPossibleAndTracksLeftoverFoods(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $plan = InventoryModifierFunctions::planBulkSpicing($inventory);

        $this->assertNotNull($plan);
        $this->assertCount(2, $plan->pairs);
        $this->assertSame(1, $plan->leftoverFoodCount);
        $this->assertSame(0, $plan->leftoverSpiceCount);

        foreach($plan->pairs as [$food, $spice])
        {
            $this->assertSame($egg, $food->getItem());
            $this->assertSame($onion, $spice->getItem());
        }
    }

    public function testMoreSpicesThanFoodsSpicesEveryFoodAndTracksLeftoverSpices(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $plan = InventoryModifierFunctions::planBulkSpicing($inventory);

        $this->assertNotNull($plan);
        $this->assertCount(1, $plan->pairs);
        $this->assertSame(0, $plan->leftoverFoodCount);
        $this->assertSame(2, $plan->leftoverSpiceCount);

        foreach($plan->pairs as [$food, $spice])
        {
            $this->assertSame($egg, $food->getItem());
            $this->assertSame($onion, $spice->getItem());
        }
    }

    public function testMismatchedFoodTypesIsAmbiguous(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $fish = self::makeFoodItem('Fish');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $fish),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $this->assertTrue(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory));
    }

    public function testMismatchedSpiceTypesIsAmbiguous(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');
        $garlic = self::makeSpiceItem('Garlic');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $garlic),
        ];

        $this->assertTrue(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory));
    }

    public function testAlreadySpicedFoodIsAmbiguous(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $alreadySpiced = self::makeInventory($owner, $egg);
        $alreadySpiced->setSpice($onion->getSpice());

        $inventory = [
            self::makeInventory($owner, $egg),
            $alreadySpiced,
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $this->assertTrue(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory));
    }

    public function testSuccessfulBulkSpicingSelectionIsNotAmbiguous(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $onion),
        ];

        $this->assertFalse(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory));
    }

    public function testSelectionWithNonFoodNonSpiceItemsIsNotAmbiguous(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $onion = self::makeSpiceItem('Onion');
        $rock = new Item();
        $rock->setName('Rock');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $onion),
            self::makeInventory($owner, $rock),
        ];

        // not our concern - this is deferred to the existing recipe-matching logic, unchanged
        $this->assertFalse(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory));
    }
}
