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

namespace App\Model;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Functions\ArrayFunctions;
use App\Service\IRandom;

class HouseSim implements IHouseSim
{
    /** @var Inventory[] */ private array $inventory;
    /** @var Inventory[] */ private array $inventoryToRemoveFromDatabase = [];

    private array $itemQuantitiesByItemId = [];

    /**
     * @param Inventory[] $inventory
     */
    public function __construct(array $inventory)
    {
        $this->setInventory($inventory);
    }

    /**
     * @param Inventory[] $inventory
     */
    private function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;

        $this->itemQuantitiesByItemId = [];

        $this->addInventoryToItemQuantities($this->inventory);
    }

    /**
     * @param Inventory[] $inventory
     */
    private function addInventoryToItemQuantities(array $inventory): void
    {
        foreach($inventory as $i)
        {
            $itemId = $i->getItem()->getId();

            if(array_key_exists($itemId, $this->itemQuantitiesByItemId))
                $this->itemQuantitiesByItemId[$itemId]++;
            else
                $this->itemQuantitiesByItemId[$itemId] = 1;
        }
    }

    public function getInventoryCount(): int
    {
        return count($this->inventory);
    }

    public function hasInventory(HouseSimRecipe $recipe): bool
    {
        foreach($recipe->ingredients as $ingredient)
        {
            if($ingredient instanceof Item)
            {
                $itemId = $ingredient->getId();

                if(!array_key_exists($itemId, $this->itemQuantitiesByItemId))
                    return false;
            }
            else if($ingredient instanceof ItemQuantity)
            {
                $itemId = $ingredient->item->getId();
                $quantity = $ingredient->quantity;

                if(!array_key_exists($itemId, $this->itemQuantitiesByItemId) || $this->itemQuantitiesByItemId[$itemId] < $quantity)
                    return false;
            }
            else
            {
                if($ingredient instanceof ItemGroup)
                    $possibleItems = $ingredient->getItems()->toArray();
                else
                    $possibleItems = $ingredient;

                if(!array_any(
                    $possibleItems,
                    fn(Item $i) => array_key_exists($i->getId(), $this->itemQuantitiesByItemId)
                ))
                    return false;
            }
        }

        return true;
    }

    /**
     * @param list<Item|string> $items
     */
    public function loseOneOf(IRandom $rng, array $items): string
    {
        /**
         * @var list<string> $items
         */
        $items = array_map(
            fn($item) => is_string($item) ? $item : $item->getName(),
            $items
        );

        $rng->rngNextShuffle($items);

        /** @var Inventory|null $itemToRemove */
        $itemToRemove = array_find(
            $this->inventory,
            fn(Inventory $i) => in_array($i->getItem()->getName(), $items)
        )
            ?? throw new \Exception('Cannot use ' . ArrayFunctions::list_nice($items, ', ', ', or ') . '; none exist in your house!');

        $itemId = $itemToRemove->getItem()->getId();

        if($this->itemQuantitiesByItemId[$itemId] === 1)
            unset($this->itemQuantitiesByItemId[$itemId]);
        else
            $this->itemQuantitiesByItemId[$itemId]--;

        if($itemToRemove->getId())
            $this->inventoryToRemoveFromDatabase[] = $itemToRemove;

        $this->inventory = array_filter(
            $this->inventory,
            fn(Inventory $i) => $i !== $itemToRemove
        );

        return $itemToRemove->getItem()->getName();
    }

    public function loseItem(Item|string $item, int $quantity = 1): void
    {
        if(!is_string($item))
            $item = $item->getName();

        /** @var Inventory[] $inventoryToRemoveFromHouseSim */
        $inventoryToRemoveFromHouseSim = ArrayFunctions::find_n(
            $this->inventory,
            fn(Inventory $i) => $i->getItem()->getName() === $item,
            $quantity
        );

        if(count($inventoryToRemoveFromHouseSim) < $quantity)
            throw new \Exception('Cannot use ' . $quantity . 'x ' . $item . '; not enough exist in your house!');

        $itemId = $inventoryToRemoveFromHouseSim[0]->getItem()->getId();

        if($this->itemQuantitiesByItemId[$itemId] > $quantity)
            $this->itemQuantitiesByItemId[$itemId] -= $quantity;
        else
            unset($this->itemQuantitiesByItemId[$itemId]);

        foreach($inventoryToRemoveFromHouseSim as $itemToRemove)
        {
            if($itemToRemove->getId())
                $this->inventoryToRemoveFromDatabase[] = $itemToRemove;
        }

        $this->inventory = array_filter(
            $this->inventory,
            fn(Inventory $i) => !array_find($inventoryToRemoveFromHouseSim, fn(Inventory $j) => $i === $j)
        );
    }

    public function addInventory(?Inventory $i): bool
    {
        if($i === null)
            return true;

        $this->inventory[] = $i;

        $itemId = $i->getItem()->getId();

        if(array_key_exists($itemId, $this->itemQuantitiesByItemId))
            $this->itemQuantitiesByItemId[$itemId]++;
        else
            $this->itemQuantitiesByItemId[$itemId] = 1;

        return true;
    }

    /**
     * @return Inventory[]
     */
    public function getInventoryToRemove(): array
    {
        return $this->inventoryToRemoveFromDatabase;
    }

    public function getInventoryToPersist(): array
    {
        return array_filter(
            $this->inventory,
            fn($i) => !$i->hasId()
        );
    }
}
