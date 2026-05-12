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

namespace App\Service\PetActivity\Crafting;

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\PetRelationship;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\FlavorEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetLocationEnum;
use App\Enum\PetSkillEnum;
use App\Enum\PetSpeciesName;
use App\Enum\RelationshipEnum;
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\EnchantmentRepository;
use App\Functions\MeritRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\PetColorFunctions;
use App\Functions\PetSpeciesRepository;
use App\Model\ComputedPetSkills;
use App\Service\FieldGuideService;
use App\Service\HattierService;
use App\Service\HouseSimService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetExperienceService;
use App\Service\PetFactory;
use Doctrine\ORM\EntityManagerInterface;

class ProgrammingService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly IRandom $rng, private readonly PetExperienceService $petExperienceService,
        private readonly HouseSimService $houseSimService, private readonly HattierService $hattierService,
        private readonly FieldGuideService $fieldGuideService, private readonly PetFactory $petFactory,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function preferredWithFullHouse(): bool { return true; }

    public function groupKey(): string { return 'programming'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();

        if($pet->hasStatusEffect(StatusEffectEnum::Wereform))
            return 0;

        $desire = $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getScience() + $pet->getTool()->getItem()->getTool()->getHacking();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::CraftingScience))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(1, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100)));
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        $pet = $petWithSkills->getPet();

        $possibilities = [];

        if($this->houseSimService->hasInventory('Macintosh'))
            $possibilities[] = $this->hackMacintosh(...);

        if($this->houseSimService->hasInventory('Painted Boomerang') && $this->houseSimService->hasInventory('Imaginary Number'))
            $possibilities[] = $this->createStrangeAttractor(...);

        if($this->houseSimService->hasInventory('Pointer'))
        {
            $possibilities[] = $this->createStringFromPointer(...);

            if($this->houseSimService->hasInventory('Wings') && $this->houseSimService->hasInventory('Quinacridone Magenta Dye'))
                $possibilities[] = $this->createDragondrop(...);

            if($this->houseSimService->hasInventory('Finite State Machine'))
                $possibilities[] = $this->createRegex(...);

            if($this->houseSimService->hasInventory('NUL'))
            {
                if($this->houseSimService->hasInventory('Plastic Fishing Rod'))
                    $possibilities[] = $this->createPhishingRod(...);

                if($this->houseSimService->hasInventory('Gold Key'))
                    $possibilities[] = $this->createDiffieHKey(...);
            }
        }

        if($this->houseSimService->hasInventory('Regex'))
        {
            if($this->houseSimService->hasInventory('Password'))
                $possibilities[] = $this->createBruteForce(...);
        }

        if($this->houseSimService->hasInventory('Brute Force'))
        {
            if($this->houseSimService->hasInventory('XOR') && $this->houseSimService->hasInventory('Gold Bar'))
                $possibilities[] = $this->createL33tH4xx0r(...);

            if($this->houseSimService->hasInventory('Lightning in a Bottle') && $this->houseSimService->hasInventory('Paper'))
                $possibilities[] = $this->createZawinskisLaw(...);
        }

        if($this->houseSimService->hasInventory('Hash Table'))
        {
            if($this->houseSimService->hasInventory('Finite State Machine') && $this->houseSimService->hasInventory('String'))
                $possibilities[] = $this->createCompiler(...);

            if($this->houseSimService->hasInventory('Elvish Magnifying Glass'))
                $possibilities[] = $this->createRijndael(...);

            if($this->houseSimService->hasInventory('Ruler'))
                $possibilities[] = $this->createViswanathsConstant(...);

            if($this->houseSimService->hasInventory('Regex'))
                $possibilities[] = $this->createHapaxLegomenon(...);
        }

        if($this->houseSimService->hasInventory('Lightning in a Bottle'))
        {
            if(
                $this->houseSimService->hasInventory('Weird Beetle') &&
                // there's a compiler in the room, or you're holding one:
                (
                    $this->houseSimService->hasInventory('Compiler') ||
                    ($pet->getTool() && $pet->getTool()->getItem()->getName() === 'Compiler')
                )
            )
            {
                $possibilities[] = $this->createSentientBeetle(...);
            }
        }

        return $possibilities;
    }

    public function getDescriptionOfRummageLocation(): string
    {
        return $this->rng->rngNextFromArray([
            'between the couch cushions',
            'under the stove',
            'behind the laundry machine',
            'behind the refrigerator'
        ]);
    }

    private function createStringFromPointer(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to dereference a String from a Pointer, but encountered a null exception :(')
                ->setIcon('icons/activity-logs/null')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('NUL', $pet, $pet->getName() . ' encountered a null exception when trying to dereference a pointer.', $activityLog);
        }
        else if($roll >= 10)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% dereferenced a String from a Pointer.')
                ->setIcon('items/resource/string')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 10)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('String', $pet, $pet->getName() . ' dereferenced this from a Pointer.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to dereference a Pointer, but couldn\'t figure out all the syntax errors.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createRegex(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 14)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $this->houseSimService->getState()->loseItem('Finite State Machine', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% upgraded a Finite State Machine into a Regex.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 14)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Regex', $pet, $pet->getName() . ' built this from a Finite State Machine.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to implement a Regex, but it was taking forever. ' . $pet->getName() . ' saved and quit for now.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createDragondrop(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to program a Dragondrop, but moved the Pointer too fast and totally lost track of it :(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);

            return $activityLog;
        }
        else if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $this->houseSimService->getState()->loseItem('Wings', 1);
            $this->houseSimService->getState()->loseItem('Quinacridone Magenta Dye', 1);
            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% programmed a Dragondrop!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Dragondrop', $pet, $pet->getName() . ' programmed this.', $activityLog);
            return $activityLog;
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to program a Dragondrop, but the Wings kept shaking the dye off...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            return $activityLog;
        }
    }

    private function createCompiler(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->houseSimService->getState()->loseItem('String', 1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to bootstrap a Compiler, but accidentally de-allocated a String, leaving a useless Pointer behind :(')
                ->setIcon('icons/activity-logs/null')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->inventoryService->petCollectsItem('Pointer', $pet, $pet->getName() . ' accidentally de-allocated a String; all that remains is this Pointer.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);

            return $activityLog;
        }
        else if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Hash Table', 1);
            $this->houseSimService->getState()->loseItem('Finite State Machine', 1);
            $this->houseSimService->getState()->loseItem('String', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% bootstrapped a Compiler.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Compiler', $pet, $pet->getName() . ' bootstrapped this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to bootstrap a Compiler, but only got so far.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }
        return $activityLog;
    }

    private function createRijndael(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Hash Table', 1);
            $this->houseSimService->getState()->loseItem('Elvish Magnifying Glass', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% implemented Rijndael.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Rijndael', $pet, $pet->getName() . ' implemented this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to implement Rijndael, but had trouble finding good documentation. ' . $pet->getName() . ' saved and quit for now.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }
        return $activityLog;
    }

    private function createViswanathsConstant(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $has4FunctionCalculator = $this->houseSimService->hasInventory('4-function Calculator');

        if(!$has4FunctionCalculator)
            $roll -= 10;

        if($roll >= 16)
        {
            if(!$has4FunctionCalculator)
            {
                $whereFound = $this->getDescriptionOfRummageLocation();

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Hash Table', 1);
                $this->houseSimService->getState()->loseItem('Ruler', 1);
                $pet->increaseEsteem(3);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% calculated Viswanath\'s Constant with the help of a 4-function Calculator that they found ' . $whereFound . '!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 16 + 10)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Viswanath\'s Constant', $pet, $pet->getName() . ' calculated this.', $activityLog);
                $this->inventoryService->petCollectsItem('4-function Calculator', $pet, $pet->getName() . ' rummaged around the house, and found this ' . $whereFound . '!', $activityLog);

                return $activityLog;
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Hash Table', 1);
                $this->houseSimService->getState()->loseItem('Ruler', 1);
                $pet->increaseEsteem(1);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% calculated Viswanath\'s Constant.')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Viswanath\'s Constant', $pet, $pet->getName() . ' calculated this.', $activityLog);
                return $activityLog;
            }
        }
        else
        {
            if($this->rng->rngNextInt(1, 3) === 1)
                return $this->fightInfinityImp($petWithSkills, 'computing Viswanath\'s Constant');
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to calculate Viswanath\'s Constant, but couldn\'t figure out any of the maths; not even a single one!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);

                return $activityLog;
            }
        }
    }

    private function createHapaxLegomenon(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $has4FunctionCalculator = $this->houseSimService->hasInventory('4-function Calculator');

        if(!$has4FunctionCalculator)
            $roll -= 10;

        if($roll >= 15)
        {
            if(!$has4FunctionCalculator)
            {
                $whereFound = $this->getDescriptionOfRummageLocation();

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Regex', 1);
                $this->houseSimService->getState()->loseItem('Hash Table', 1);
                $pet->increaseEsteem(3);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% computed a Hapax Legomenon with the help of a 4-function Calculator that they found ' . $whereFound . '!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 15 + 10)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Hapax Legomenon', $pet, $pet->getName() . ' calculated this.', $activityLog);
                $this->inventoryService->petCollectsItem('4-function Calculator', $pet, $pet->getName() . ' rummaged around the house, and found this ' . $whereFound . '!', $activityLog);
                return $activityLog;
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Regex', 1);
                $this->houseSimService->getState()->loseItem('Hash Table', 1);
                $pet->increaseEsteem(1);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% computed a Hapax Legomenon with the help of a 4-function Calculator.')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Hapax Legomenon', $pet, $pet->getName() . ' calculated this.', $activityLog);
                return $activityLog;
            }
        }
        else
        {
            if($this->rng->rngNextInt(1, 3) === 1)
                return $this->fightInfinityImp($petWithSkills, 'computing a Hapax Legomenon');
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to computing a Hapax Legomenon, but kept coming up with nonsense results...')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);

                return $activityLog;
            }
        }
    }

    private function createStrangeAttractor(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $has4FunctionCalculator = $this->houseSimService->hasInventory('4-function Calculator');

        if(!$has4FunctionCalculator)
            $roll -= 10;

        if($roll >= 17)
        {
            if(!$has4FunctionCalculator)
            {
                $whereFound = $this->getDescriptionOfRummageLocation();

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Imaginary Number', 1);
                $this->houseSimService->getState()->loseItem('Painted Boomerang', 1);
                $pet->increaseEsteem(5);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% computed a Strange Attractor with the help of a 4-function Calculator that they found ' . $whereFound . '!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 17 + 10)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Strange Attractor', $pet, $pet->getName() . ' computed this from a Painted Boomerang and Imaginary Number.', $activityLog);
                $this->inventoryService->petCollectsItem('4-function Calculator', $pet, $pet->getName() . ' rummaged around the house, and found this ' . $whereFound . '!', $activityLog);
                return $activityLog;
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
                $this->houseSimService->getState()->loseItem('Imaginary Number', 1);
                $this->houseSimService->getState()->loseItem('Painted Boomerang', 1);
                $pet->increaseEsteem(3);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% computed a Strange Attractor with the help of a 4-function Calculator.')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem('Strange Attractor', $pet, $pet->getName() . ' computed this from a Painted Boomerang and Imaginary Number.', $activityLog);
                return $activityLog;
            }
        }
        else
        {
            if($this->rng->rngNextInt(1, 3) === 1)
                return $this->fightInfinityImp($petWithSkills, 'computing a Strange Attractor');
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% thought about computing a Strange Attractor, but kept getting infinities.')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);

                return $activityLog;
            }
        }
    }

    private function fightInfinityImp(ComputedPetSkills $petWithSkills, string $actionInterrupted): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        // Infinity Imp vs Infinity Imp: skip the fight entirely, just collect the blueprint
        if($pet->getSpecies()->getName() === PetSpeciesName::InfinityImp->value)
            return $this->infinityImpCollectsBlueprint($pet, $actionInterrupted);

        $scienceRoll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());
        $brawlRoll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal());

        $loot = $this->rng->rngNextFromArray([
            'Quintessence',
            'Pointer',
        ]);

        $impDiscovery = '%pet:' . $pet->getId() . '.name% started ' . $actionInterrupted . ', but an Infinity Imp popped up, and started to attack!';

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Infinity Imp', $impDiscovery);

        $isLucky = $this->rng->rngNextInt(1, 50) == 1 && $pet->hasMerit(MeritEnum::LUCKY);

        if($this->rng->rngNextInt(1, 50) == 1 || $isLucky)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::PROGRAM, false);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $impDiscovery . ' %pet:' . $pet->getId() . '.name% was able to subdue the creature, and tossed it in to your daycare!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming', PetActivityLogTagEnum::Physics, 'Fighting' ]))
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;

            if($isLucky)
            {
                $activityLog
                    ->appendEntry('(Lucky~!)')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]));
            }

            $this->petExperienceService->gainExp($pet, 5, [ PetSkillEnum::Science, PetSkillEnum::Brawl ], $activityLog);

            $this->createInfinityImp($pet);

            return $activityLog;
        }
        else if($scienceRoll >= $brawlRoll)
        {
            if($scienceRoll >= 20)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $impDiscovery . ' During the fight, %pet:' . $pet->getId() . '.name% exploited a divergence in the imp\'s construction, and unraveled it, receiving ' . $loot . '!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming', PetActivityLogTagEnum::Physics ]))
                ;
                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' received this by unraveling an Infinity Imp.', $activityLog);
                return $activityLog;
            }
        }
        else
        {
            if($brawlRoll >= 20)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $impDiscovery . ' %pet:' . $pet->getId() . '.name% slew the creature outright, and claimed its ' . $loot . '!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming', 'Fighting' ]))
                ;
                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' received this by slaying an Infinity Imp.', $activityLog);
                return $activityLog;
            }
        }

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $impDiscovery . ' %pet:' . $pet->getId() . '.name% ran away until the imp finally gave up and returned to the strange dimension from whence it came.')
            ->setIcon('icons/activity-logs/confused')
        ;

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::WrangledWithInfinities, $activityLog);

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);

        return $activityLog;
    }

    private function infinityImpCollectsBlueprint(Pet $pet, string $actionInterrupted): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, false);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started ' . $actionInterrupted . ', but an Infinity Imp, well, _would_ have popped up, but when you do the math it turns out that `2 × Infinity Imp = Infinity Imp + Infinity Vault Blueprint`, so, actually, an Infinity Vault Blueprint popped up. (Startling, all the same!)')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Physics ]))
        ;

        $this->inventoryService->petCollectsItem('Infinity Vault Blueprint', $pet, $pet->getName() . ' received this because math is weird.', $activityLog);

        return $activityLog;
    }

    private function createInfinityImp(Pet $captor): void
    {
        $infinityImp = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::InfinityImp);

        $impName = $this->rng->rngNextFromArray([
            'Pythagorimp', 'Euclidemon', 'Algebrogremlin', 'Probabilidemon',
            'Axiomatixie', 'Numbergnome', 'Entropixie', 'Thermodynamimp',
        ]);

        $petColors = PetColorFunctions::generateRandomPetColors($this->rng);

        $startingMerit = $this->rng->rngNextFromArray([
            MeritEnum::GOURMAND,
            MeritEnum::PREHENSILE_TONGUE,
            MeritEnum::LOLLIGOVORE,
            MeritEnum::HYPERCHROMATIC,
            MeritEnum::DREAMWALKER,
            MeritEnum::SHEDS,
            MeritEnum::DARKVISION,
        ]);

        $newPet = $this->petFactory->createPet(
            $captor->getOwner(), $impName, $infinityImp,
            $petColors->colorA, $petColors->colorB,
            $this->rng->rngNextFromArray(FlavorEnum::cases()),
            MeritRepository::findOneByName($this->em, $startingMerit)
        );

        $newPet
            ->increaseLove(10)
            ->increaseSafety(10)
            ->increaseEsteem(10)
            ->increaseFood(-8)
            ->setScale($this->rng->rngNextInt(80, 120))
            ->setLocation(PetLocationEnum::DAYCARE)
        ;

        $this->em->persist($newPet);

        $petWithCaptor = new PetRelationship($newPet, $captor, RelationshipEnum::Dislike, RelationshipEnum::Dislike)
            ->setMetDescription('%relationship.name% pulled %pet.name% out of the imaginary plane, trapping them here!')
        ;

        $newPet->addPetRelationship($petWithCaptor);

        $captorWithPet = new PetRelationship($captor, $newPet, RelationshipEnum::Dislike, RelationshipEnum::Dislike)
            ->setMetDescription('%pet.name% pulled %relationship.name% out of the imaginary plane, trapping them here!')
        ;

        $captor->addPetRelationship($captorWithPet);

        $this->em->persist($petWithCaptor);
        $this->em->persist($captorWithPet);
    }

    private function createBruteForce(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 14)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Regex', 1);
            $this->houseSimService->getState()->loseItem('Password', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created Brute Force.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 14)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Brute Force', $pet, $pet->getName() . ' upgraded a Regex into this, with the help of a Password.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to become a l33t h4xx0r, but didn\'t have the right stuff. (Figuratively speaking.)')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createL33tH4xx0r(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 17)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Brute Force', 1);
            $this->houseSimService->getState()->loseItem('XOR', 1);
            $this->houseSimService->getState()->loseItem('Gold Bar', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% became a l33t h4xx0r.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('l33t h4xx0r', $pet, $pet->getName() . ' made this.', $activityLog);

            if($pet->hasMerit(MeritEnum::BEHATTED) && $roll >= 27)
            {
                $consoleCowboy = EnchantmentRepository::findOneByName($this->em, 'Console Cowboy\'s');

                if(!$this->hattierService->userHasUnlocked($pet->getOwner(), $consoleCowboy))
                {
                    $this->hattierService->unlockAuraDuringPetActivity(
                        $pet,
                        $activityLog,
                        $consoleCowboy,
                        'They added some 1s and 0s to their hat, while they were at it, for maximum l33t-ness!',
                        'It occurred to them that 1s and 0s would make great bells and whistles for a hat!',
                        ActivityHelpers::PetName($pet) . ' thought the 1s and 0s of a l33t h4xx0r would look killer on a hat...'
                    );
                }
            }
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to become a l33t h4xx0r, but didn\'t have the right stuff. (Figuratively speaking.)')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createZawinskisLaw(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 19)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Brute Force', 1);
            $this->houseSimService->getState()->loseItem('Lightning in a Bottle', 1);
            $this->houseSimService->getState()->loseItem('Paper', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% discovered Zawinski\'s Law.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 19)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Zawinski\'s Law', $pet, $pet->getName() . ' made this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started jotting some ideas down on a piece of Paper, but ended up doodling a bunch of squiggles instead. ' . $pet->getName() . ' ended up erasing it all.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createPhishingRod(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Plastic Fishing Rod', 1);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $this->houseSimService->getState()->loseItem('NUL', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Phishing Rod.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Phishing Rod', $pet, $pet->getName() . ' made this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% considered making a Phishing Rod, but ended up boondoggling.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createDiffieHKey(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 13)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Gold Key', 1);
            $this->houseSimService->getState()->loseItem('Pointer', 1);
            $this->houseSimService->getState()->loseItem('NUL', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Diffie-H Key.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Diffie-H Key', $pet, $pet->getName() . ' made this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Diffie-H Key, but some passing qubits messed it all up.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function createSentientBeetle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);

            $this->houseSimService->getState()->loseItem('Weird Beetle', 1);
            $pet->increaseEsteem(-$this->rng->rngNextInt(4, 8));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to upload an AI into a Weird Beetle\'s brain, but, uh... the beetle... did not survive...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
        }
        else if($roll >= 24)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Lightning in a Bottle', 1);
            $this->houseSimService->getState()->loseItem('Weird Beetle', 1);
            $pet->increaseEsteem(3);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% uploaded an AI into a Weird Beetle\'s brain, granting it sentience!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 24)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;
            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Sentient Beetle', $pet, $pet->getName() . ' gave this beetle sentience by uploading an AI into its brain.', $activityLog);

            PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::CreatedSentientBeetle, $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to program an AI, but couldn\'t get anywhere...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }

    private function hackMacintosh(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROGRAM, true);
            $this->houseSimService->getState()->loseItem('Macintosh', 1);

            $loot = [
                $this->rng->rngNextFromArray([ 'Magic Smoke', 'Quintessence', 'Hash Table' ]),
                $this->rng->rngNextFromArray([ 'Pointer', 'NUL', 'String' ])
            ];

            $pet->increaseEsteem(3);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% hacked a Macintosh, and got its ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);

            foreach($loot as $item)
                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' got this by hacking a Macintosh.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to hack a Macintosh, but couldn\'t get anywhere.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Programming' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::PROGRAM, false);
        }

        return $activityLog;
    }
}
