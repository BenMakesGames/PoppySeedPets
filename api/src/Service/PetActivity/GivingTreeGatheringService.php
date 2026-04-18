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

namespace App\Service\PetActivity;

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Enum\LocationEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class GivingTreeGatheringService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PetExperienceService $petExperienceService,
        private readonly IRandom $rng
    )
    {
    }

    public function gatherFromGivingTree(Pet $pet): ?PetActivityLog
    {
        $givingTree = $this->em->getRepository(User::class)->findOneBy([ 'email' => 'giving-tree@poppyseedpets.com' ])
            ?? throw new \Exception('The "Giving Tree" NPC does not exist in the database!');

        $items = InventoryService::countTotalInventory($this->em, $givingTree, LocationEnum::Home);

        // just to make suuuuuuuuuuuuuuuuuuper sure that there's enough for every pet that might be doing this...
        if($items < 100)
            return null;

        $givingTreeItems = $this->rng->rngNextInt(5, 8);

        $this->em->getConnection()->executeQuery(
            '
                UPDATE inventory
                SET
                    owner_id=:newOwner,
                    modified_on=NOW()
                WHERE owner_id=:givingTree
                LIMIT ' . $givingTreeItems . '
            ',
            [
                'newOwner' => $pet->getOwner()->getId(),
                'givingTree' => $givingTree->getId()
            ]
        );

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(10, 20), PetActivityStatEnum::OTHER, null);

        return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited The Giving Tree, and picked up several items that other players had discarded.')
            ->setIcon('icons/activity-logs/giving-tree')
            ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Giving Tree' ]))
        ;
    }

}
