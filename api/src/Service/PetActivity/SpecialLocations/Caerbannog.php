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

namespace App\Service\PetActivity\SpecialLocations;

use App\Entity\PetActivityLog;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Functions\ActivityHelpers;
use App\Functions\AdventureMath;
use App\Functions\ArrayFunctions;
use App\Functions\DateFunctions;
use App\Functions\EquipmentFunctions;
use App\Functions\ItemRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class Caerbannog
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly Clock $clock
    )
    {
    }

    public function adventure(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        EquipmentFunctions::destroyPetTool($this->em, $pet);

        $changes = new PetChanges($pet);

        // TODO: other things the pet can do in here?
        $activityLog = $this->fightEvilRabbit($petWithSkills);

        $activityLog
            ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Adventure ]))
            ->setChanges($changes->compare($pet))
        ;

        if(AdventureMath::petAttractsBug($this->rng, $pet, 75))
            $this->inventoryService->petAttractsRandomBug($pet);

        return $activityLog;
    }

    public function fightEvilRabbit(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $wheatOrCorn = DateFunctions::isCornMoon($this->clock->now) ? 'Corn' : 'Wheat';

        $possibleLoot = [ 'Carrot', 'Crooked Stick', $wheatOrCorn, $wheatOrCorn, 'Dandelion', 'Coriander Flower', 'Mint', 'Fluff' ];

        $petName = ActivityHelpers::PetName($pet);

        $roll = $this->rng->rngNextInt(1, 20) + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $loot[] = $this->rng->rngNextFromArray($possibleLoot);

        // ==================
        // no special drops on holidays, or else the "correct" way to play is to never use Carrot Keys EXCEPT on holidays!
        // ==================

        if($roll >= 10)
        {
            $loot[] = $this->rng->rngNextFromArray($possibleLoot);
            $exp = 2;

            if($roll >= 15)
            {
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);
                $exp++;
            }

            if($roll >= 20)
            {
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);
                $exp++;
            }

            if($roll >= 40)
            {
                $loot[] = 'Blue Plastic Egg';
                $exp++;
            }
            else
            {
                if($roll >= 25)
                {
                    $loot[] = $this->rng->rngNextFromArray($possibleLoot);
                    $exp++;
                }

                if($roll >= 30)
                {
                    $loot[] = $this->rng->rngNextFromArray($possibleLoot);
                    $exp++;
                }
            }

            $pet->increaseEsteem((int)ceil($exp / 2) * 2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $petName . ' went to the Caerbannog Cave, and encountered one of the terrifying creatures living there! ' . $petName . ' proved victorious, returning home with ' . ArrayFunctions::list_nice_sorted($loot) . '!')
                ->setIcon('items/key/carrot')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $this->petExperienceService->gainExp($pet, $exp, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $pet->increaseSafety(-2);
            $lootItem = ItemRepository::findOneByName($this->em, $loot[0]);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $petName . ' went to the Caerbannog Cave, and encountered one of the terrifying creatures living there, and was forced to flee! (They grabbed ' . $lootItem->getNameWithArticle() . ' on their way out, at least!)')
                ->setIcon('items/key/carrot')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        foreach($loot as $lootName)
            $this->inventoryService->petCollectsItem($lootName, $pet, $pet->getName() . ' looted this from the Caerbannog Cave.', $activityLog);

        return $activityLog;
    }
}