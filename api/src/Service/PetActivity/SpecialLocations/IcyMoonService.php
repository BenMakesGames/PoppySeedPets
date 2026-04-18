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

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\Spice;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\SpiceRepository;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\FieldGuideService;
use App\Service\HouseSimService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class IcyMoonService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly IRandom $rng,
        private readonly FieldGuideService $fieldGuideService,
        private readonly EntityManagerInterface $em,
        private readonly HouseSimService $houseSimService
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'icyMoon'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        if(!$this->houseSimService->hasInventory('Icy Moon'))
            return 0;

        $pet = $petWithSkills->getPet();

        if($pet->hasStatusEffect(StatusEffectEnum::Wereform))
            return 0;

        $desire = $petWithSkills->getStamina()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() - $pet->getAlcohol();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getScience() + $pet->getTool()->getItem()->getTool()->getGathering();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::IcyMoon))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(0, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100) * 3 / 4));
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        if(!$this->houseSimService->hasInventory('Icy Moon'))
            return [];

        return [ $this->run(...) ];
    }

    public function run(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $maxSkill = 5 + (int)ceil(($petWithSkills->getStamina()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getScience()->getTotal()) / 2) - $pet->getAlcohol();

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 15);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        $changes = new PetChanges($pet);

        $activityLog = match($roll)
        {
            1, 2, 3, 4, 5 => $this->lostInBlizzard($pet),
            6, 7 => $this->foundEverice($pet),
            8 => $this->foundRock($pet),
            9, 10 => $this->findCryovolcano($petWithSkills),
            11, 12, 13 => $this->fightBabyCrystallineEntity($petWithSkills),
            14, 15 => $this->exploreCore($petWithSkills),
            default => throw new \Exception('Unexpected roll in Icy Moon adventure.'),
        };

        if($this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog->appendEntry('(Also, on the way back home they noticed a Fancy Teapot drifting through space! How\'d that get there??');

            $this->inventoryService->petCollectsItem('Fancy Teapot', $pet, $pet->getName() . ' found this floating in space near an Icy Moon.', $activityLog);
        }
        else if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog
                ->appendEntry('(Also, on the way back home they noticed a Fancy Teapot drifting through space! How\'d that get there?? Lucky~!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Lucky ]))
            ;

            $this->inventoryService->petCollectsItem('Fancy Teapot', $pet, $pet->getName() . ' found this floating in space near an Icy Moon.', $activityLog);
        }

        $activityLog->setChanges($changes->compare($pet));

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::ExploredAnIcyMoon, $activityLog);

        return $activityLog;
    }

    private function lostInBlizzard(Pet $pet): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to explore an Icy Moon, but a blizzard started up, and they were unable to make any progress.')
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon ]))
        ;

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);

        return $activityLog;
    }

    private function getIcySpice(): ?Spice
    {
        return $this->rng->rngNextBool()
            ? null
            : SpiceRepository::findOneByName($this->em, 'Freezer-burned')
        ;
    }

    private function foundEverice(Pet $pet): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        if($this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to explore an Icy Moon, but got lost in the endless snowfields. They picked up a chunk of Everi-- wait, no: that\'s an Ice Mango!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, PetActivityLogTagEnum::Gathering ]))
            ;

            $this->inventoryService->petCollectsEnhancedItem('Ice "Mango"', null, $this->getIcySpice(), $pet, $pet->getName() . ' found this in a snowfield on an Icy Moon.', $activityLog);
        }
        else if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to explore an Icy Moon, but got lost in the endless snowfields. They picked up a chunk of Everi-- wait, no: that\'s an Ice Mango! (Lucky~!)')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, PetActivityLogTagEnum::Gathering, PetActivityLogTagEnum::Lucky ]))
            ;

            $this->inventoryService->petCollectsEnhancedItem('Ice "Mango"', null, $this->getIcySpice(), $pet, $pet->getName() . ' found this in a snowfield on an Icy Moon.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to explore an Icy Moon, but got lost in the endless snowfields. They picked up a chunk of Everice, at least.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, PetActivityLogTagEnum::Gathering ]))
            ;

            $this->inventoryService->petCollectsEnhancedItem('Everice', null, $this->getIcySpice(), $pet, $pet->getName() . ' found this in a snowfield on an Icy Moon.', $activityLog);
        }

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);

        return $activityLog;
    }

    private function foundRock(Pet $pet): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored an Icy Moon. They found a field of rocks that poked up out of the moon\'s icy surface, and grabbed a sample to take home.')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Location: Icy Moon', 'Gathering' ]))
        ;

        $this->inventoryService->petCollectsEnhancedItem('Rock', null, $this->getIcySpice(), $pet, $pet->getName() . ' found this in a field of rocks on an Icy Moon.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);

        return $activityLog;
    }

    private function findCryovolcano(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() - $pet->getAlcohol());

        $pet->increaseFood(-1);

        if($roll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

            if($roll >= 25)
            {
                $loot = $this->rng->rngNextFromArray([
                    'Alien Tissue', 'Quinacridone Magenta Dye', 'Quintessence'
                ]);

                $extra = $this->rng->rngNextFromArray([
                    ' (Fascinating!)', ' (Interesting...)', ' (Curious...)'
                ]);
            }
            else
            {
                $loot = $this->rng->rngNextFromArray([
                    'Fish', 'Jellyfish Jelly', 'Seaweed', 'Algae', 'Silica Grounds', 'Tentacle', 'Green Dye',
                ]);

                $extra = '';
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored an Icy Moon, and found a Cryovolcano! It was a bit cold, but they rummaged through the deposits, and found a sample of ' . $loot . '!' . $extra)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, PetActivityLogTagEnum::Location_Cryovolcano, 'Gathering' ]))
            ;

            $this->petExperienceService->gainExp($pet, $roll >= 25 ? 3 : 1, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsEnhancedItem($loot, null, $this->getIcySpice(), $pet, $pet->getName() . ' found this amidst cryovolcano deposits on an Icy Moon.', $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored an Icy Moon, and found a Cryovolcano! The water was scary-cold, so they watched it from a distance for a while before finally moving on...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, PetActivityLogTagEnum::Location_Cryovolcano, 'Gathering' ]))
            ;

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);
        }

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Cryovolcano', ActivityHelpers::PetName($pet) . ' found a Cryovolcano on an Icy Moon.');

        return $activityLog;
    }

    private function fightBabyCrystallineEntity(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $pet->increaseFood(-1);

        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getStrength()->getTotal(), $petWithSkills->getStamina()->getTotal()) + $petWithSkills->getBrawl()->getTotal() - $pet->getAlcohol());

        $roll += $petWithSkills->getHasProtectionFromElectricity()->getTotal() * 2;

        if($roll >= 20)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            $pieces = 1;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was attacked by a Mini Crystalline Entity while exploring an Icy Moon! They fought the creature, breaking off a piece of it before it flew away!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, 'Fighting' ]))
            ;

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            if($roll >= 30)
                $this->inventoryService->petCollectsItem('Cellular Peptide Cake', $pet, $pet->getName() . ' got this by defeating a Mini Crystalline Entity on an Icy Moon!', $activityLog);

            if($roll >= 40)
                $pieces++;

            for($i = 0; $i < $pieces; $i++)
                $this->inventoryService->petCollectsItem($this->rng->rngNextFromArray([ 'Glass', 'Gypsum', 'Fiberglass' ]), $pet, $pet->getName() . ' got this by defeating a Mini Crystalline Entity on an Icy Moon!', $activityLog);

            $this->petExperienceService->gainExp($pet, 1 + $pieces, [ PetSkillEnum::Brawl ], $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was attacked by a Mini Crystalline Entity while exploring an Icy Moon! The creature was wildly throwing sparks and electricity, and ' . $pet->getName() . ' was forced to retreat...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, 'Fighting' ]))
            ;

            if($pet->hasMerit(MeritEnum::SHOCK_RESISTANT))
            {
                $activityLog
                    ->appendEntry('(Their shock-resistance helped, but dang, that thing is crazy!)')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ;
            }
            else
                $pet->increaseSafety(-$this->rng->rngNextInt(3, 6));

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
        }

        return $activityLog;
    }

    private function exploreCore(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() - $pet->getAlcohol());

        if($petWithSkills->getCanSeeInTheDark()->getTotal() <= 0)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::GATHER, false);

            return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an entrance to the core of an Icy Moon, but it was too dark to see anything...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Location_Icy_Moon,
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Dark
                ]))
            ;
        }

        if($roll >= 20)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

            $loot = [
                $this->rng->rngNextFromArray([ 'Liquid-hot Magma', 'Glass', 'Silica Grounds' ]),
                $this->rng->rngNextFromArray([ 'Gravitational Waves', 'Silica Grounds', 'Rock' ]),
            ];

            if($roll >= 30)
            {
                $period = '! (What was that doing there?!)';
                $loot[] = 'Tiny Rocketship';
            }
            else
                $period = '.';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an entrance to the core of an Icy Moon! It was dark inside, but thanks to their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ' they found some ' . ArrayFunctions::list_nice_sorted($loot) . $period)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Location_Icy_Moon,
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Dark
                ]))
            ;

            foreach($loot as $itemName)
                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this in the core of an Icy Moon.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science, PetSkillEnum::Nature ], $activityLog);

            if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStamina()->getTotal()) < 8)
            {
                if($petWithSkills->getHasProtectionFromHeat()->getTotal() > 0)
                {
                    $activityLog->appendEntry('The core was hot, but their ' . ActivityHelpers::SourceOfHeatProtection($petWithSkills) . ' protected them.')
                        ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Heatstroke ]))
                    ;
                }
                else
                {
                    $pet->increaseFood(-2);
                    $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));

                    if($this->rng->rngNextInt(1, 20) === 1)
                        $activityLog->appendEntry('The core was CRAZY hot, and I don\'t mean in a sexy way; %pet:' . $pet->getId() . '.name% got a bit light-headed while crawling through the tunnels.');
                    else
                        $activityLog->appendEntry('The core was CRAZY hot, and %pet:' . $pet->getId() . '.name% got a bit light-headed while while crawling through the tunnels.');

                    $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Heatstroke ]));
                }
            }

            if($roll >= 30)
            {
                $this->houseSimService->getState()->loseItem('Icy Moon', 1);

                $lavaOrFirestone = $this->rng->rngNextFromArray([ 'Liquid-hot Magma', 'Firestone' ]);

                $activityLog->appendEntry('As they were leaving, the caves began to shake violently! %pet:' . $pet->getId() . '.name% managed to start up the Tiny Rocketship, and shot out of the moon as it crumbled into pieces!')
                    ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ->addTag(PetActivityLogTagHelpers::findOneByName($this->em, PetActivityLogTagEnum::Location_Escaping_Icy_Moon))
                ;

                $this->inventoryService->petCollectsEnhancedItem('Everice', null, $this->getIcySpice(), $pet, 'The remains of a collapsed Icy Moon.', $activityLog);
                $this->inventoryService->petCollectsEnhancedItem('Everice', null, $this->getIcySpice(), $pet, 'The remains of a collapsed Icy Moon.', $activityLog);
                $this->inventoryService->petCollectsItem($this->rng->rngNextFromArray([ 'Rock', 'Silica Grounds' ]), $pet, 'The remains of a collapsed Icy Moon.', $activityLog);
                $this->inventoryService->petCollectsItem($lavaOrFirestone, $pet, 'The remains of a collapsed Icy Moon.', $activityLog);
            }
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found the entrance to the core of an Icy Moon! It was dark inside, and even with their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ' they were unable to find anything...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Location_Icy_Moon, 'Gathering', 'Dark' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science, PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }
}
