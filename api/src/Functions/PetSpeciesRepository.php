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

use App\Entity\PetSpecies;
use App\Enum\PetSpeciesName;
use App\Exceptions\PSPNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

final class PetSpeciesRepository
{
    /**
     * @param PetSpeciesName[] $speciesNames
     * @return PetSpecies[]
     */
    public static function findByNames(EntityManagerInterface $em, array $speciesNames): array
    {
        return array_map(fn(PetSpeciesName $speciesName) => self::findOneByName($em, $speciesName), $speciesNames);
    }

    public static function findOneByName(EntityManagerInterface $em, PetSpeciesName $speciesName): PetSpecies
    {
        $species = $em->getRepository(PetSpecies::class)->createQueryBuilder('i')
            ->where('i.name=:name')
            ->setParameter('name', $speciesName->value)
            ->getQuery()
            ->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('PetSpeciesRepository_FindOneByName_' . $speciesName->value))
            ->getOneOrNullResult();

        if(!$species) throw new PSPNotFoundException('There is no species called ' . $speciesName->value . '.');

        return $species;
    }

    public static function findOneById(EntityManagerInterface $em, Ulid $speciesId): PetSpecies
    {
        $item = $em->getRepository(PetSpecies::class)->createQueryBuilder('i')
            ->where('i.id=:id')
            ->setParameter('id', $speciesId)
            ->getQuery()
            ->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('PetSpeciesRepository_FindOneById_' . $speciesId->toBase32()))
            ->getOneOrNullResult();

        if(!$item) throw new PSPNotFoundException('There is no species #' . $speciesId->toBase32() . '.');

        return $item;
    }
}
