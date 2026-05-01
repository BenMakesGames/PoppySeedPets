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
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\SpiceRepository;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class LeonidsService
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function adventure(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $adventure = $this->rng->rngNextInt(1, 3);

        if($adventure === 1)
            $activityLog = $this->encounterWerecreaturePack($petWithSkills);
        else if($adventure === 2)
            $activityLog = $this->encounterRaccoonSpiritScavenger($petWithSkills);
        else
            $activityLog = $this->encounterFairies($petWithSkills);

        $activityLog
            ->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Special Event', 'Leonids' ]))
        ;

        return $activityLog;
    }

    private function encounterWerecreaturePack(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($petWithSkills->getPet()->hasStatusEffect(StatusEffectEnum::Wereform))
        {
            $starrySpice = SpiceRepository::findOneByName($this->em, 'Starry');

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! ' . ActivityHelpers::PetName($pet) . ' joined them, rolling in the dust, playing tug-of-war with Pobo bones, and howling at the stars!');

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);

            $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' got this in their fur while playing with werecreatures in the Umbra!', $activityLog);

            $loot = $this->rng->rngNextFromArray([ 'Fluff', 'Talon', 'Stereotypical Bone', 'Stereotypical Bone' ]);

            $this->inventoryService->petCollectsEnhancedItem($loot, null, $starrySpice, $pet, $pet->getName() . ' got this while playing with werecreatures in the Umbra!', $activityLog);
        }
        else if($petWithSkills->getPet()->hasStatusEffect(StatusEffectEnum::BittenByAWerecreature))
        {
            if($pet->getTool() && $pet->getTool()->getItem()->getTreasure() && $pet->getTool()->getItem()->getTreasure()->getSilver() > 0)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! The werecreatures gave ' . ActivityHelpers::PetName($pet) . ' strange looks (perhaps it\'s the silvery ' . $pet->getTool()->getFullItemName() . '?) but kept their distance while ' . ActivityHelpers::PetName($pet) . ' gathered some Stardust...');

                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids, while receiving strange looks from some werecreatures.', $activityLog);
            }
            else if($pet->getHat() && $pet->getHat()->getItem()->getTreasure() && $pet->getHat()->getItem()->getTreasure()->getSilver() > 0)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! The werecreatures gave ' . ActivityHelpers::PetName($pet) . ' strange looks (perhaps it\'s the silvery ' . $pet->getHat()->getFullItemName() . '?) but kept their distance while ' . ActivityHelpers::PetName($pet) . ' gathered some Stardust...');

                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids, while receiving strange looks from some werecreatures.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! One of them approached ' . ActivityHelpers::PetName($pet) . ', and pushed a pile of Stardust forward with its paw, then left.');

                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' was given this by some werecreatures in the Umbra!', $activityLog);
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
        }
        else
        {
            $stealthCheck = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStealth()->getTotal());

            if($stealthCheck <= 2)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! ' . ActivityHelpers::PetName($pet) . ' tried to gather some Stardust in secret, but were spotted, and forced to flee!');

                if($pet->getTool() && $pet->getTool()->getItem()->getTreasure() && $pet->getTool()->getItem()->getTreasure()->getSilver() > 0)
                {
                    $activityLog->appendEntry('(Even a silvery ' . $pet->getTool()->getFullItemName() . ' isn\'t going to keep away a whole pack of werecreatures!)');
                }
                else if($pet->getHat() && $pet->getHat()->getItem()->getTreasure() && $pet->getHat()->getItem()->getTreasure()->getSilver() > 0)
                {
                    $activityLog->appendEntry('(Even a silvery ' . $pet->getHat()->getFullItemName() . ' isn\'t going to keep away a whole pack of werecreatures!)');
                }

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Stealth ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::UMBRA, false);
            }
            else if($stealthCheck >= 12)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! ' . ActivityHelpers::PetName($pet) . ' kept to the shadows, and gathered some Stardust in secret...');

                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids in the Umbra!', $activityLog);
                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids in the Umbra!', $activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana, PetSkillEnum::Stealth ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they saw a group of werecreatures playing in the Stardust! ' . ActivityHelpers::PetName($pet) . ' quickly scooped some up, and ran away before they could get spotted!');

                $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids in the Umbra!', $activityLog);

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Stealth ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
            }
        }

        return $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Werecreature' ]))
        ;
    }

    private function encounterRaccoonSpiritScavenger(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $combatRoll = $this->rng->rngSkillRoll($petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal());

        if($combatRoll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they encountered a large raccoon spirit, gathering Stardust. It snarled at ' . ActivityHelpers::PetName($pet) . ', but they calmed it down, and helped it gather some Stardust (it\'s the Light and Shadow way)! In addition to getting some Stardust of their own, the spirit gave ' . ActivityHelpers::PetName($pet) . ' some Quintesence as thanks!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;

            $loot = $this->rng->rngNextFromArray([ 'Fluff', 'Talon', 'Quintessence' ]);
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' got this by defeating an angry raccoon spirit they encountered in the Umbra while gathering Stardust! It snarled at ' . $pet->getName() . ', and attacked, but ' . $pet->getName() . ' overpowered the spirit, and drove it away!', $activityLog);
            $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this from fallen Leonids in the Umbra, after defeating a large raccoon spirit!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they encountered a large raccoon spirit, gathering Stardust. It snarled at ' . ActivityHelpers::PetName($pet) . ', and attacked; after a long fight in the Stardust, ' . ActivityHelpers::PetName($pet) . ' was forced to retreat!');

            $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' got this all over themselves during a fight with a large raccoon spirit in the Umbra!', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana, PetSkillEnum::Brawl ], $activityLog);
        }

        return $activityLog;
    }

    private function encounterFairies(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $gatheringRoll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getArcana()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($gatheringRoll >= 10)
        {
            if($gatheringRoll >= 20)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::UMBRA, true);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they ran into some fairies. They helped the fairies gather a ton of Stardust, for which they received lunch and Quintessence as way of thanks!');

                $this->inventoryService->petCollectsItem('Quintessence', $pet, $pet->getName() . ' received this from some fairies after helping them gather tons of Stardust in the Umbra!', $activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana ], $activityLog);
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they ran into some fairies. After working at it for a while, they all took a break, and the fairies shared some of their food!');

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
            }

            $spice = SpiceRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
                'Rain-scented',
                'Juniper',
                'with Rosemary',
                'with Toad Jelly',
            ]));

            $foodItem = $this->rng->rngNextFromArray([
                'Pumpkin Bread',
                'Slice of Naner Bread',
                'World\'s Best Sugar Cookie',
                'Shortbread Cookies',
                'Cheese',
            ]);

            $this->inventoryService->petCollectsEnhancedItem($foodItem, null, $spice, $pet, $pet->getName() . ' received this from some fairies after helping them gather Stardust in the Umbra!', $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->getActivityLogPrefix($pet) . ' There, they ran into some fairies. They all hung out and kept each other company while gathering Stardust for a while...');

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
        }

        $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this with some fairies they met in the Umbra!', $activityLog);

        return $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
        ;
    }

    private function getActivityLogPrefix(Pet $pet): string
    {
        return '%pet:' . $pet->getId() . '.name% went into the Umbra, and followed the Leonids to where they were falling!';
    }
}