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
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Functions\ActivityHelpers;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class PerseidsService
{
    private const string ShowerName = 'Perseids';

    public function __construct(
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly MeteorShowerEncounters $meteorShowerEncounters,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function adventure(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $adventure = $this->rng->rngNextInt(1, 2);

        if($adventure === 1)
            $activityLog = $this->encounterMedusaSnakes($petWithSkills);
        else
            $activityLog = $this->meteorShowerEncounters->encounterFairies($petWithSkills, self::ShowerName);

        $activityLog
            ->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Special Event', 'Perseids' ]))
        ;

        return $activityLog;
    }

    private function encounterMedusaSnakes(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $tool = $pet->getTool();
        $hasMirrorTool = $tool?->getItem()->hasItemGroup('Mirror') ?? false;

        if($tool && $hasMirrorTool)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, a nest of Medusa Snakes came pouring out of the Stardust, all hissing and glaring! But the snakes caught sight of their own glare in ' . ActivityHelpers::PetName($pet) . '\'s ' . $tool->getFullItemName() . ', and, one by one, turned themselves to stone!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;

            $this->inventoryService->petCollectsItem('Rock', $pet, $pet->getName() . ' collected this from a nest of Medusa Snakes that turned themselves to stone in the Umbra!', $activityLog);
            $this->inventoryService->petCollectsItem('Rock', $pet, $pet->getName() . ' collected this from a nest of Medusa Snakes that turned themselves to stone in the Umbra!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);

            return $activityLog;
        }

        $combatRoll = $this->rng->rngSkillRoll($petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal());

        if($combatRoll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, a nest of Medusa Snakes came pouring out of the Stardust, all hissing and glaring! ' . ActivityHelpers::PetName($pet) . ' kept low, and drove the snakes off before any of them could take a good look, claiming some Scales and a Talon they left behind!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;

            $this->inventoryService->petCollectsItem('Scales', $pet, $pet->getName() . ' got this by driving off a nest of Medusa Snakes in the Umbra!', $activityLog);
            $this->inventoryService->petCollectsItem('Talon', $pet, $pet->getName() . ' got this by driving off a nest of Medusa Snakes in the Umbra!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, a nest of Medusa Snakes came pouring out of the Stardust, all hissing and glaring! With nothing to turn that glare back on them, ' . ActivityHelpers::PetName($pet) . ' kept low, and backed away from the nest until the Stardust was well out of reach...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);
        }

        return $activityLog;
    }

    private function getActivityLogPrefix(Pet $pet): string
    {
        return MeteorShowerEncounters::getActivityLogPrefix($pet, self::ShowerName);
    }
}
