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

namespace App\Functions;

use App\Entity\Enchantment;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\Spice;
use App\Exceptions\PSPInvalidOperationException;
use App\Model\BulkSpicingPlan;
use Doctrine\ORM\EntityManagerInterface;

class InventoryModifierFunctions
{
    public static function enchant(EntityManagerInterface $em, Inventory $tool, Inventory $enchantment): void
    {
        if($tool->getEnchantment())
            throw new PSPInvalidOperationException('That tool already has the "' . $tool->getEnchantment()->getName() . '" bonus. Remove it first if you want to apply a new bonus.');

        $tool->setEnchantment($enchantment->getItem()->getEnchants());

        $em->remove($enchantment);
    }

    public static function spiceUp(EntityManagerInterface $em, Inventory $food, Inventory $spice): void
    {
        if($food->getSpice())
            throw new PSPInvalidOperationException('That food is already "' . $food->getSpice()->getName() . '". It can\'t be spiced up any further!');

        $food->setSpice($spice->getItem()->getSpice());

        $em->remove($spice);
    }

    /**
     * Figures out whether a selection of inventory items is an unambiguous request to bulk-spice
     * every selected food with one of the selected spices. Handles the case where food and spice
     * counts are equal, the case where there are more foods than spices (the extra foods are left
     * unspiced), and the case where there are more spices than foods (the extra spices are left
     * unused). Every other case is ambiguous, and returns null.
     *
     * @param Inventory[] $inventory
     */
    public static function planBulkSpicing(array $inventory): ?BulkSpicingPlan
    {
        $foods = array_values(array_filter($inventory, fn(Inventory $i) => $i->getItem()->getFood() !== null));
        $spices = array_values(array_filter($inventory, fn(Inventory $i) => $i->getItem()->getSpice() !== null));

        // something in the selection is neither food nor spice
        if(count($foods) + count($spices) !== count($inventory))
            return null;

        if(count($foods) === 0 || count($spices) === 0)
            return null;

        if(array_any($foods, fn(Inventory $i) => $i->getSpice() !== null))
            return null;

        $firstFoodItem = $foods[0]->getItem();

        if(array_any($foods, fn(Inventory $i) => $i->getItem() !== $firstFoodItem))
            return null;

        $firstSpiceItem = $spices[0]->getItem();

        if(array_any($spices, fn(Inventory $i) => $i->getItem() !== $firstSpiceItem))
            return null;

        $pairCount = min(count($foods), count($spices));

        $pairs = array_map(
            fn(Inventory $food, Inventory $spice) => [ $food, $spice ],
            array_slice($foods, 0, $pairCount),
            array_slice($spices, 0, $pairCount)
        );

        return new BulkSpicingPlan(
            $pairs,
            leftoverFoodCount: count($foods) - $pairCount,
            leftoverSpiceCount: count($spices) - $pairCount
        );
    }

    /**
     * True when a selection is entirely food and spice items (so it's clearly an attempt at
     * bulk-spicing, not some other recipe), but doesn't match any of planBulkSpicing's
     * unambiguous patterns. False for anything planBulkSpicing can handle, and false when the
     * selection includes items that are neither food nor spice (that's not this feature's
     * concern - it's left to the existing recipe-matching logic).
     *
     * @param Inventory[] $inventory
     */
    public static function isAmbiguousBulkSpicingAttempt(array $inventory): bool
    {
        $foods = array_filter($inventory, fn(Inventory $i) => $i->getItem()->getFood() !== null);
        $spices = array_filter($inventory, fn(Inventory $i) => $i->getItem()->getSpice() !== null);

        if(count($foods) + count($spices) !== count($inventory))
            return false;

        if(count($foods) === 0 || count($spices) === 0)
            return false;

        return self::planBulkSpicing($inventory) === null;
    }

    public static function getNameWithModifiers(Inventory $item): string
    {
        if(!$item->getEnchantment() && !$item->getSpice())
            return $item->getItem()->getName();

        $nameParts = [];

        if($item->getEnchantment() && !$item->getEnchantment()->getIsSuffix())
            $nameParts[] = $item->getEnchantment()->getName();

        if($item->getSpice() && !$item->getSpice()->getIsSuffix())
            $nameParts[] = $item->getSpice()->getName();

        $nameParts[] = $item->getItem()->getName();

        if($item->getEnchantment() && $item->getEnchantment()->getIsSuffix())
            $nameParts[] = $item->getEnchantment()->getName();

        if($item->getSpice() && $item->getSpice()->getIsSuffix())
            $nameParts[] = $item->getSpice()->getName();

        return implode(' ', $nameParts);
    }

    public static function getNameWithModifiersForItem(Item $item, ?Enchantment $enchantment, ?Spice $spice): ?string
    {
        if(!$enchantment && !$item->getSpice())
            return $item->getName();

        $nameParts = [];

        if($enchantment && !$enchantment->getIsSuffix())
            $nameParts[] = $enchantment->getName();

        if($spice && !$spice->getIsSuffix())
            $nameParts[] = $spice->getName();

        $nameParts[] = $item->getName();

        if($enchantment && $enchantment->getIsSuffix())
            $nameParts[] = $enchantment->getName();

        if($spice && $spice->getIsSuffix())
            $nameParts[] = $spice->getName();

        return implode(' ', $nameParts);
    }
}
