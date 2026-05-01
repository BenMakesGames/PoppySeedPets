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

use App\Entity\Item;
use App\Exceptions\PSPNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class ItemRepository
{
    /**
     * @param string[] $itemNames
     * @return Item[]
     */
    public static function findByNames(EntityManagerInterface $em, array $itemNames): array
    {
        return array_map(fn(string $itemName) => self::findOneByName($em, $itemName), $itemNames);
    }

    public static function findOneByName(EntityManagerInterface $em, string $itemName): Item
    {
        /** @var Item|null $item */
        $item = $em->getRepository(Item::class)->createQueryBuilder('i')
            ->where('i.name=:name')
            ->setParameter('name', $itemName)
            ->getQuery()
            ->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('ItemRepository_FindOneByName_' . $itemName))
            ->getOneOrNullResult();

        if(!$item) throw new PSPNotFoundException('There is no item called ' . $itemName . '.');

        return $item;
    }

    public static function findOneById(EntityManagerInterface $em, int $itemId): Item
    {
        /** @var Item|null $item */
        $item = $em->getRepository(Item::class)->createQueryBuilder('i')
            ->where('i.id=:id')
            ->setParameter('id', $itemId)
            ->getQuery()
            ->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('ItemRepository_FindOneById_' . $itemId))
            ->getOneOrNullResult();

        if(!$item) throw new PSPNotFoundException('There is no item #' . $itemId . '.');

        return $item;
    }

    public static function getIdByName(EntityManagerInterface $em, string $itemName): int
    {
        $itemId = $em->getRepository(Item::class)->createQueryBuilder('i')
            ->select('i.id')
            ->where('i.name=:name')
            ->setParameter('name', $itemName)
            ->getQuery()
            ->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('ItemRepository_GetIdByName_' . $itemName))
            ->getSingleScalarResult();

        if(!$itemId)
            throw new PSPNotFoundException('There is no item called ' . $itemName . '.');

        return $itemId;
    }
}
