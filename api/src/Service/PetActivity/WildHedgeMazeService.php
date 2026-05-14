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

use App\Entity\PetActivityLog;
use App\Enum\DistractionLocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class WildHedgeMazeService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng,
        private readonly PetExperienceService $petExperienceService,
        private readonly GatheringDistractionService $gatheringDistractions,
        private readonly InventoryService $inventoryService,
    )
    {
    }

    public function exploreHedgeMaze(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, [ PetActivityLogTagEnum::Gathering ], 'exploring the woods');

        $pet = $petWithSkills->getPet();

        $petHasEideticMemory = $pet->hasMerit(MeritEnum::EIDETIC_MEMORY);
        $petHasClimbing = $petWithSkills->getClimbingBonus()->getTotal() > 0;
        $avoidedGettingLost = false;

        if($this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getPerception()->getTotal()) < 15)
        {
            if($petHasEideticMemory || $petHasClimbing)
                $avoidedGettingLost = true;
            else
                return $this->lostInHedgeMaze($petWithSkills);
        }

        /**
         * @var array<callable(ComputedPetSkills): PetActivityLog> $possibilities
         */
        $possibilities = [
            $this->sphinx(...),
            $this->gather(...),
        ];

        // a pet with an eidetic memory and no mirror will remember that they shouldn't even attempt the light puzzle
        if(!(!$pet->getTool()?->getItem()->hasItemGroup('Mirror') && $petHasEideticMemory))
            $possibilities[] = $this->lightPuzzle(...);

        $activityLog = $this->rng->rngNextFromArray($possibilities)($petWithSkills);

        if($avoidedGettingLost)
        {
            if($petHasEideticMemory && (!$petHasClimbing || $this->rng->rngNextBool()))
            {
                $activityLog
                    ->appendEntry('(Easy-peasy with an Eidetic Memory!)')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ;
            }
            else
            {
                $activityLog
                    ->appendEntry('(Easy-peasy if you just climb over the walls!)')
                ;
            }
        }

        return $activityLog;
    }

    private function lightPuzzle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $tool = $pet->getTool();
        $hasMirrorTool = $tool?->getItem()->hasItemGroup('Mirror') ?? false;
        $hasClimbing = $petWithSkills->getClimbingBonus()->getTotal() > 0;

        if($tool && $hasMirrorTool)
        {
            $toolName = $tool->getFullItemName();

            if($hasClimbing)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' found a hedge maze in the forest; they climbed over its walls and quickly came upon what was obviously a light-based puzzle where they had to get a beam of light to hit some weird thing in the ground. They used their ' . $toolName . ' to reflect the beam, and a little hatch opened in the ground with a Gaming Box inside!');

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, false);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' found a hedge maze in the forest; exploring it, they came upon what was obviously a light-based puzzle where they had to get a beam of light to hit some weird thing in the ground. They used their ' . $toolName . ' to reflect the beam, and a little hatch opened in the ground with a Gaming Box inside!');

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);
            }

            $this->inventoryService->petCollectsItem('Gaming Box', $pet, 'While exploring a hedge maze in the woods, ' . $pet->getName() . ' found a light-based puzzle, which they solved with their ' . $toolName . ', revealing this treasure!', $activityLog);
        }
        else
        {
            if($hasClimbing)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' found a hedge maze in the forest; they climbed over its walls and quickly came upon what was obviously a light-based puzzle where they had to get a beam of light to hit some weird thing in the ground, but didn\'t have a reflective surface available to do it! :(');

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(15, 30), PetActivityStatEnum::GATHER, false);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' found a hedge maze in the forest; exploring it, they came upon what was obviously a light-based puzzle where they had to get a beam of light to hit some weird thing in the ground, but didn\'t have a reflective surface available to do it! :(');

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, false);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', PetActivityLogTagEnum::Location_Hedge_Maze_Light_Puzzle ]))
        ;
    }

    private function sphinx(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $loot = [];
        $pet = $petWithSkills->getPet();
        $hasClimbing = $petWithSkills->getClimbingBonus()->getTotal() > 0;
        $time = $this->rng->rngNextInt(45, 75);

        if($hasClimbing) $time -= 15;

        if($this->rng->rngNextInt(1, 20) + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getArcana()->getTotal() >= 15)
        {
            $loot = $this->rng->rngNextSubsetFromArray([
                'Silver Ore',
                'Music Note',
                'Quintessence',
            ], 2);

            $pet->increaseEsteem(count($loot) + 2);

            $activityLog = $hasClimbing
                ? PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a hedge maze in the forest; they climbed over its walls and quickly came upon a Hedge Maze Sphinx. ' . $pet->getName() . ' was able to solve its riddle, and was rewarded with ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                : PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a hedge maze in the forest; exploring it, they ran into a Hedge Maze Sphinx. ' . $pet->getName() . ' was able to solve its riddle, and was rewarded with ' . ArrayFunctions::list_nice_sorted($loot) . '.')
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana, PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $pet->increaseEsteem(-$this->rng->rngNextInt(3, 4));

            $activityLog = $hasClimbing
                ? PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a hedge maze in the forest; they climbed over its walls and quickly came upon a Hedge Maze Sphinx. The sphinx asked a really hard question, which ' . $pet->getName() . ' wasn\'t able to answer. They were consequentially ejected from the maze!')
                : PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a hedge maze in the forest; exploring it, they ran into a Hedge Maze Sphinx. The sphinx asked a really hard question, which ' . $pet->getName() . ' wasn\'t able to answer. They were consequentially ejected from the maze!')
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Nature ], $activityLog);
        }

        $this->petExperienceService->spendTime($pet, $time, PetActivityStatEnum::GATHER, false);

        foreach($loot as $itemName)
            $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this in a Wild Hedgemaze.', $activityLog);

        return $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', PetActivityLogTagEnum::Location_Hedge_Maze_Sphinx ]))
        ;
    }

    private function gather(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $lucky = false;

        $possibleLoot = [
            'Smallish Pumpkin', 'Crooked Stick', 'Sweet Beet', 'Toadstool', 'Grandparoot', 'Pamplemousse',
        ];

        $loot[] = $this->rng->rngNextFromArray($possibleLoot);
        $loot[] = $this->rng->rngNextFromArray($possibleLoot);

        if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 20)
            $loot[] = $this->rng->rngNextFromArray($possibleLoot);

        if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 15) === 1)
        {
            $loot[] = 'Melowatern';
            $lucky = true;
        }
        else if($this->rng->rngNextInt(1, 75) == 1)
            $loot[] = 'Melowatern';

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to the Wild Hedgemaze. It turns out mazes are way easier with a perfect memory! ' . $pet->getName() . ' found ' . ArrayFunctions::list_nice_sorted($loot) . '.');

        $tags = [ 'Gathering', PetActivityLogTagEnum::Location_Hedge_Maze ];

        if($lucky)
        {
            $activityLog
                ->appendEntry('(Melowatern!? Lucky~!)')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
            ;
            $tags[] = 'Lucky~!';
        }

        $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
        ;

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, true);

        foreach($loot as $itemName)
            $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this in a Wild Hedgemaze.', $activityLog);

        return $activityLog;
    }

    private function lostInHedgeMaze(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $possibleLoot = [
            'Smallish Pumpkin', 'Crooked Stick', 'Sweet Beet', 'Toadstool', 'Grandparoot', 'Pamplemousse',
        ];

        $loot[] = $this->rng->rngNextFromArray($possibleLoot);

        if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 25)
            $loot[] = $this->rng->rngNextFromArray($possibleLoot);

        $lucky = false;

        if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 20) === 1)
        {
            $loot[] = 'Melowatern';
            $lucky = true;
        }
        else if($this->rng->rngNextInt(1, 100) == 1)
            $loot[] = 'Melowatern';

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% stumbled upon a Wild Hedge Maze. They got totally lost in there, but at least picked up ' . ArrayFunctions::list_nice_sorted($loot) . ' along the way.');

        $tags = [ 'Gathering', PetActivityLogTagEnum::Location_Hedge_Maze ];

        if($lucky)
        {
            $activityLog
                ->appendEntry('(Melowatern!? Lucky~!)')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
            ;
            $tags[] = 'Lucky~!';
        }

        $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags));

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 120), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

}