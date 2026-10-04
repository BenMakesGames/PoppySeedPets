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

namespace App\Service\PetActivity\Crafting\Helpers;

use App\Entity\PetActivityLog;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Functions\ItemRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Service\Clock;
use App\Service\HouseSimService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class IronSmithingService
{
    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly InventoryService $inventoryService,
        private readonly IRandom $rng,
        private readonly HouseSimService $houseSimService,
        private readonly EntityManagerInterface $em,
        private readonly Clock $clock
    )
    {
    }

    public function createMeatSeekingClaymore(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to extend the blade of a Laser-guided Sword, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 25)
        {
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Wings', 1);
            $this->houseSimService->getState()->loseItem('Laser-guided Sword', 1);

            $pet->increaseEsteem(6);

            $article = $this->rng->rngNextInt(1, 10) === 1 ? 'a humble' : 'a';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Meat-seeking Claymore from ' . $article . ' Laser-guided Sword.')
                ->setIcon('items/tool/sword/laser-guided-and-winged')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 25)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->inventoryService->petCollectsItem('Meat-seeking Claymore', $pet, $pet->getName() . ' created this from ' . $article . ' Laser-guided Sword!', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to add Wings to a Laser-guided Sword, but they were proving difficult to handle...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createIronKey(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to forge an Iron Key, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 12)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $keys = $roll >= 27 ? 2 : 1;

            if($keys === 2)
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% forged *two* Iron Keys from an Iron Bar!')
                    ->setIcon('items/key/iron');
            else
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% forged an Iron Key from an Iron Bar.')
                    ->setIcon('items/key/iron');

            $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]));

            $this->inventoryService->petCollectsItem('Iron Key', $pet, $pet->getName() . ' forged this from an Iron Bar.', $activityLog);

            if($keys === 2)
                $this->inventoryService->petCollectsItem('Iron Key', $pet, $pet->getName() . ' forged this from an Iron Bar.', $activityLog);

            $this->petExperienceService->gainExp($pet, $keys, [ PetSkillEnum::Crafts ], $activityLog);
            $pet->increaseEsteem($keys === 1 ? 1 : 4);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to forge an Iron Key from an Iron Bar, but couldn\'t get the shape right.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createBasicIronCraft(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $making = $this->rng->rngNextFromArray([
            [ 'item' => 'Iron Tongs', 'description' => 'Iron Tongs', 'image' => 'items/tool/tongs', 'difficulty' => 13, 'experience' => 1 ],
            [ 'item' => 'Iron Sword', 'description' => 'an Iron Sword', 'image' => 'items/tool/sword/iron', 'difficulty' => 14, 'experience' => 1 ],
            [ 'item' => 'Flute', 'description' => 'a Flute', 'image' => 'items/tool/instrument/flute', 'difficulty' => 15, 'experience' => 2 ],
            [ 'item' => 'Dumbbell', 'description' => 'a Dumbbell', 'image' => 'items/tool/dumbbell', 'difficulty' => 12, 'experience' => 1 ],
            [ 'item' => 'Iron Axe', 'description' => 'an Iron Axe', 'image' => 'items/tool/axe/iron', 'difficulty' => 14, 'experience' => 1 ],
        ]);

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to forge ' . $making['description'] . ', but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= $making['difficulty'] + 10)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% forged ' . $making['description'] . ' from an Iron Bar with enough left over to make a Nail File, as well!.')
                ->setIcon($making['image'])
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ->addInterestingness(PetActivityLogInterestingness::HoHum + $making['difficulty'] + 10)
            ;

            $this->inventoryService->petCollectsItem($making['item'], $pet, $pet->getName() . ' forged this from an Iron Bar.', $activityLog);
            $this->inventoryService->petCollectsItem('Nail File', $pet, $pet->getName() . ' created this with the leftovers from making ' . $making['item'] . '.', $activityLog);

            $this->petExperienceService->gainExp($pet, $making['experience'] + 2, [ PetSkillEnum::Crafts ], $activityLog);
            $pet->increaseEsteem(4);
        }
        else if($roll >= $making['difficulty'])
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% forged ' . $making['description'] . ' from an Iron Bar.')
                ->setIcon($making['image'])
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ->addInterestingness(PetActivityLogInterestingness::HoHum + $making['difficulty'])
            ;

            $this->inventoryService->petCollectsItem($making['item'], $pet, $pet->getName() . ' forged this from an Iron Bar.', $activityLog);

            $this->petExperienceService->gainExp($pet, $making['experience'], [ PetSkillEnum::Crafts ], $activityLog);
            $pet->increaseEsteem(1);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to forge ' . $making['description'] . ' from an Iron Bar, but couldn\'t get the shape right.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createWaterStrider(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 14)
        {
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Green Dye', 1);
            $this->houseSimService->getState()->loseItem('Bug-catcher\'s Net', 1);

            $pet->increaseEsteem(1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% turned a simple Bug-catcher\'s Net into a Water Strider.')
                ->setIcon('items/tool/net/water-strider')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 14)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->inventoryService->petCollectsItem('Water Strider', $pet, $pet->getName() . ' created this from a simple Bug-catcher\'s Net.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            if($this->rng->rngNextInt(1, 4) === 1)
            {
                $pet->increaseSafety(-$this->rng->rngNextInt(1, 2));
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to sharpen an Iron Bar to top off a Bug-catcher\'s Net with, but nearly cut themselves in the process!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to spiff up a Bug-catcher\'s Net, but wasn\'t happy with any of their ideas...')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createYellowScissors(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 13)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Plastic', 1);
            $this->houseSimService->getState()->loseItem('Yellow Dye', 1);

            $scienceRoll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

            if($scienceRoll >= 20)
            {
                $pet->increaseEsteem(3);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made Yellow Scissors, and science\'d up a mechanical can-opener with the leftover materials.')
                    ->setIcon('items/tool/scissors/yellow-can-opener')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
                ;

                $this->inventoryService->petCollectsItem('Yellow Can-opener', $pet, $pet->getName() . ' created this.', $activityLog);

                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts, PetSkillEnum::Science ], $activityLog);
            }
            else
            {
                $pet->increaseEsteem(1);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made Yellow Scissors.')
                    ->setIcon('items/tool/scissors/yellow')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
                ;

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            }

            if($this->rng->rngNextInt(1, 20 + ($pet->getId() % 4) * 3) >= 23)
                $this->inventoryService->petCollectsItem('Yellow Scissors', $pet, $pet->getName() . ' created (and sharpened) this!', $activityLog);
            else
                $this->inventoryService->petCollectsItem('Yellow Scissors', $pet, $pet->getName() . ' created this.', $activityLog);

            return $activityLog;
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make scissors, but getting the handle shape right is apparently frickin\' impossible >:(')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);

            return $activityLog;
        }
    }

    public function createGreenScissors(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 13)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Plastic', 1);
            $this->houseSimService->getState()->loseItem('Green Dye', 1);

            $scienceRoll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

            if($scienceRoll >= 20)
            {
                $pet->increaseEsteem(3);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made Green Scissors, and science\'d up a mechanical can-opener with the leftover materials.')
                    ->setIcon('items/tool/scissors/yellow-can-opener')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
                ;

                $this->inventoryService->petCollectsItem('Green Can-opener', $pet, $pet->getName() . ' created this.', $activityLog);

                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts, PetSkillEnum::Science ], $activityLog);
            }
            else
            {
                $pet->increaseEsteem(1);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made Green Scissors.')
                    ->setIcon('items/tool/scissors/yellow')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
                ;

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            }

            if($this->rng->rngNextInt(1, 20 + ($pet->getId() % 4) * 3) >= 23)
                $this->inventoryService->petCollectsItem('Green Scissors', $pet, $pet->getName() . ' created (and sharpened) this!', $activityLog);
            else
                $this->inventoryService->petCollectsItem('Green Scissors', $pet, $pet->getName() . ' created this.', $activityLog);

            return $activityLog;
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make scissors, but getting the handle shape right is apparently frickin\' impossible >:(')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);

            return $activityLog;
        }
    }

    public function createSaucepan(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 10)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, true);

            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Plastic', 1);

            if($roll >= 20)
            {
                $pet->increaseEsteem(2);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Saucepan... and a Whisk!')
                    ->setIcon('items/tool/saucepan')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
                $this->inventoryService->petCollectsItem('Whisk', $pet, $pet->getName() . ' created this.', $activityLog);

                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
            }
            else
            {
                $pet->increaseEsteem(1);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Saucepan.')
                    ->setIcon('items/tool/saucepan')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 10)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            }

            $this->inventoryService->petCollectsItem('Saucepan', $pet, $pet->getName() . ' created this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Saucepan, but couldn\'t figure out the purpose of the thing...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createScythe(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        $item = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
            'Scythe',
            'Garden Shovel'
        ]));

        if($roll >= 13)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Crooked Stick', 1);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            if($roll >= 33)
            {
                $pet->increaseEsteem($this->rng->rngNextInt(5, 8));

                $bonusItems = $this->rng->rngNextSubsetFromArray([ 'Trowel', 'Hand Rake', 'Bezeling Planisher' ], 2);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made ' . $item->getNameWithArticle() . ' from a Crooked Stick, and Iron Bar... with enough left over to make a ' . $bonusItems[0] . ' _and_ ' . $bonusItems[1] . ', as well! (Dang! Such skills!)')
                    ->setIcon('items/' . $item->getImage())
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 33)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;

                $this->petExperienceService->gainExp($pet, 5, [ PetSkillEnum::Crafts ], $activityLog);

                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' created this from a Crooked Stick, and Iron Bar.', $activityLog);

                foreach($bonusItems as $bonusItem)
                    $this->inventoryService->petCollectsItem($bonusItem, $pet, $pet->getName() . ' created this with the leftovers from making ' . $item->getNameWithArticle() . '.', $activityLog);
            }
            else if($roll >= 23)
            {
                $pet->increaseEsteem($this->rng->rngNextInt(3, 6));

                $bonusItem = $this->rng->rngNextFromArray([ 'Trowel', 'Hand Rake', 'Bezeling Planisher' ]);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made ' . $item->getNameWithArticle() . ' from a Crooked Stick, and Iron Bar... with enough left over to make a ' .  $bonusItem .', as well!')
                    ->setIcon('items/' . $item->getImage())
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 23)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;

                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);

                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' created this from a Crooked Stick, and Iron Bar.', $activityLog);
                $this->inventoryService->petCollectsItem($bonusItem, $pet, $pet->getName() . ' created this with the leftovers from making ' . $item->getNameWithArticle() . '.', $activityLog);
            }
            else
            {
                $pet->increaseEsteem(1);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made ' . $item->getNameWithArticle() . ' from a Crooked Stick, and Iron Bar.')
                    ->setIcon('items/' . $item->getImage())
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);

                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' created this from a Crooked Stick, and Iron Bar.', $activityLog);
            }
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make ' . $item->getNameWithArticle() . ', but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createGrapplingHook(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('String', 1);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $pet->increaseEsteem(1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Grappling Hook from Iron Bar, and String.')
                ->setIcon('items/tool/grappling-hook')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->inventoryService->petCollectsItem('Grappling Hook', $pet, $pet->getName() . ' created this from Iron Bar, and String.', $activityLog);

            $exp = 2;

            if(($this->clock->getMonthAndDay() >= 1101 || $this->clock->getMonthAndDay() < 200) && $roll >= 25)
            {
                $exp += 2;

                $activityLog
                    ->setEntry($activityLog->getEntry() . '.. and had enough metal and string left over to throw together a pair of Knitting Needles!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 22)
                ;

                $this->inventoryService->petCollectsItem('Knitting Needles', $pet, $pet->getName() . ' created this with the leftovers from making a Wooden Sword.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, $exp, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Grappling Hook, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createHeavyTool(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $makes = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
            'Heavy Hammer',
            'Heavy Lance'
        ]));

        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + min($petWithSkills->getStrength()->getTotal(), $petWithSkills->getStamina()->getTotal()) + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($petWithSkills->getStrength()->getTotal() < 3)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::SMITH, false);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to make ' . $makes->getNameWithArticle() . ', but they aren\'t strong enough... (The Dark Matter is WAY too heavy!)')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
        }
        else if($roll <= 2)
        {

            $pet
                ->increaseSafety(-$this->rng->rngNextInt(4, 8))
                ->increaseEsteem(-$this->rng->rngNextInt(1, 2))
            ;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make ' . $makes->getNameWithArticle() . ', but dropped an Iron Bar on their toes!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll <= 17)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make ' . $makes->getNameWithArticle() . ', but the Dark Matter was being especially difficult to work with! >:(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }
        else
        {
            $this->houseSimService->getState()->loseItem('Dark Matter', 1);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $pet->increaseEsteem(3);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made ' . $makes->getNameWithArticle() . ' from an Iron Bar and some Dark Matter!')
                ->setIcon('items/tool/hammer/heavy')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 18)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem($makes, $pet, $pet->getName() . ' created this from an Iron Bar and some Dark Matter!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }

        return $activityLog;
    }

    public function createMirrorShield(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make an iron shield, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 15)
        {
            $this->houseSimService->getState()->loseItem('Mirror', 1);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Mirror Shield!')
                ->setIcon('items/tool/shield/mirror')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);

            $this->inventoryService->petCollectsItem('Mirror Shield', $pet, $pet->getName() . ' created this from Iron Bar, and a Mirror.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to make an iron shield, but couldn\'t come up with a good design...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createMushketeer(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 15)
        {
            $this->houseSimService->getState()->loseItem('Toadstool', 1);
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Mushketeer!')
                ->setIcon('items/tool/sword/mushroom')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->inventoryService->petCollectsItem('Mushketeer', $pet, $pet->getName() . ' created this from Iron Bar, and a Toadstool.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to make a sword using a Toadstool, but couldn\'t figure it out...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createBecDeCorbin(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to forge an Iron Bar into the head of a Bec de Corbin, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 16)
        {
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Hunting Spear', 1);

            $pet->increaseEsteem(3);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% forged an Iron Bar into a hammer head, and re-set a Hunting Spear\'s talon opposite it as a beak, making a Bec de Corbin.')
                ->setIcon('items/tool/spear/bec-de-corbin')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->inventoryService->petCollectsItem('Bec de Corbin', $pet, $pet->getName() . ' made this by re-setting a Hunting Spear\'s talon into a freshly-forged iron head.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to re-set a Hunting Spear\'s talon into an iron head, but at every angle it looked more like a falcon\'s beak than a raven\'s...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createSnailpplingHook(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 18)
        {
            $this->houseSimService->getState()->loseItem('Iron Bar', 1);
            $this->houseSimService->getState()->loseItem('Snail Shell', 1);
            $this->houseSimService->getState()->loseItem('Grappling Hook', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Snailppling Hook!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 18)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->inventoryService->petCollectsItem('Snailppling Hook', $pet, $pet->getName() . ' created by spring-loading a Grappling Hook inside a Snail Shell.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to make a combine the spirals of a Grappling Hook with those of Snail Shell, but couldn\'t figure anything out...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Crafting' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }
}
