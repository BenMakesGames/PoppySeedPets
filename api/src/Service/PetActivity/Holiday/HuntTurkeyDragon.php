<?php
declare(strict_types = 1);

/**
 * This file is part of the Poppy Seed Pets API.
 *
 * The Poppy Seed Pets API is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets API. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Service\PetActivity\Holiday;

use App\Entity\PetActivityLog;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;


class HuntTurkeyDragon
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly EntityManagerInterface $em,
        private readonly PetExperienceService $petExperienceService,
    )
    {
    }

    public function hunt(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $skill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $gobbleGobble = $pet->getStatusEffect(StatusEffectEnum::GobbleGobble);

        $pet->increaseFood(-1);

        $getExtraItem = $this->rng->rngSkillRoll($petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 15;

        $possibleItems = [
            'Giant Turkey Leg',
            'Scales',
            'Feathers',
            'Talon',
            'Quintessence',
            'Charcoal',
            'Smallish Pumpkin Spice',
        ];

        if($this->rng->rngNextInt(1, $skill) >= 18)
        {
            $pet->increaseSafety(1);
            $pet->increaseEsteem(2);

            if($gobbleGobble !== null)
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found the Turkeydragon, and defeated it, claiming its head as a prize! (Dang! Brutal!)');
            else
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was attacked by a Turkeydragon, but was able to defeat it.');

            $numItems = $getExtraItem ? 3 : 2;

            for($i = 0; $i < $numItems; $i++)
            {
                $itemName = $this->rng->rngNextFromArray($possibleItems);

                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' got this from defeating a Turkeydragon.', $activityLog);
            }

            if($gobbleGobble !== null)
            {
                $this->inventoryService->petCollectsItem('Turkey King', $pet, $pet->getName() . ' got this from defeating a Turkeydragon.', $activityLog);
                PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::DefeatedATurkeyKing, $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            if($getExtraItem)
            {
                $itemName = $this->rng->rngNextFromArray($possibleItems);

                $aSome = in_array($itemName, [ 'Scales', 'Feathers', 'Quintessence', 'Charcoal' ]) ? 'some' : 'a';

                if($gobbleGobble !== null)
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found the Turkeydragon, and attacked it! ' . $pet->getName() . ' was able to claim ' . $aSome . ' ' . $itemName . ' before being forced to flee...');
                else
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was attacked by a Turkeydragon! ' . $pet->getName() . ' was able to claim ' . $aSome . ' ' . $itemName . ' before fleeing...');

                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' nabbed this from a Turkeydragon before running from it.', $activityLog);
            }
            else
            {
                $pet->increaseSafety(-1);
                $pet->increaseEsteem(-1);

                if($gobbleGobble !== null)
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found the Turkeydragon, and attacked it, but was forced to flee!');
                else
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was attacked by a Turkeydragon, and forced to flee!');
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        $activityLog->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent);
        $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Hunting, PetActivityLogTagEnum::Fighting, PetActivityLogTagEnum::Special_Event, PetActivityLogTagEnum::Thanksgiving ]));

        if($gobbleGobble !== null)
            $pet->removeStatusEffect($gobbleGobble);

        return $activityLog;
    }
}