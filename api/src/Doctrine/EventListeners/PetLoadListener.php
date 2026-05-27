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

namespace App\Doctrine\EventListeners;

use App\Entity\Inventory;
use App\Entity\Pet;
use App\Functions\CacheHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\Persistence\Proxy;

class PetLoadListener
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function postLoad(Pet $pet, PostLoadEventArgs $eventArgs): void
    {
        $petSpeciesProxy = $pet->getSpecies();

        if(!$petSpeciesProxy instanceof Proxy)
            return;

        // Check if the PetSpecies proxy is already initialized
        if ($petSpeciesProxy->__isInitialized()) {
            return;
        }

        $speciesId = $petSpeciesProxy->getId();
        $query = $this->entityManager->createQuery('SELECT s FROM App\Entity\PetSpecies s WHERE s.id = :id');
        $query->setParameter('id', $speciesId);
        $query->enableResultCache(24 * 60 * 60, CacheHelpers::getCacheItemName('PetLoadListener_GetPetSpeciesById_' . $speciesId->toBase32()));

        // Execute query and get the result
        $petSpecies = $query->getOneOrNullResult();

        // Update the PetSpecies proxy with the fetched Item data
        if ($petSpecies) {
            $uow = $eventArgs->getObjectManager()->getUnitOfWork();
            $uow->registerManaged($petSpeciesProxy, ['id' => $speciesId], $uow->getOriginalEntityData($petSpecies));
            $petSpeciesProxy->__load();
        }
    }
}