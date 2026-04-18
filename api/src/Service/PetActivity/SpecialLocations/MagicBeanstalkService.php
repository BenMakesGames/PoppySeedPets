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

use App\Entity\GreenhousePlant;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Functions\AdventureMath;
use App\Functions\ArrayFunctions;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class MagicBeanstalkService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly IRandom $rng,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'magicBeanstalk'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();

        if(!$this->hasMagicBeanStalk($pet->getOwner()))
            return 0;

        $desire = $petWithSkills->getStamina()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getClimbingBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getNature() + $pet->getTool()->getItem()->getTool()->getClimbing();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Beanstalk))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(1, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100)));
    }

    private function hasMagicBeanStalk(User $user)
    {
        return $user->getGreenhousePlants()->exists(
            fn(int $key, GreenhousePlant $p) =>
                $p->getPlant()->getName() === 'Magic Beanstalk' &&
                $p->getIsAdult() &&
                $p->getProgress() >= 1 &&
                new \DateTimeImmutable() >= $p->getCanNextInteract()
        );
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        if(!$this->hasMagicBeanStalk($petWithSkills->getPet()->getOwner()))
            return [];

        return [ $this->run(...) ];
    }

    public function run(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $maxSkill = 10 + (int)floor(($petWithSkills->getStrength()->getTotal() + $petWithSkills->getStamina()->getTotal()) * 1.5) + (int)ceil($petWithSkills->getNature()->getTotal() / 2) + $petWithSkills->getClimbingBonus()->getTotal() - $pet->getAlcohol() * 2;

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 21);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        switch($roll)
        {
            case 1:
            case 2:
            case 3:
            case 4:
                $activityLog = $this->badClimber($pet);
                break;
            case 5:
            case 6:
                $activityLog = $this->getBeans($pet);
                break;
            case 7:
                $activityLog = $this->getReallyBigLeaf($pet);
                break;
            case 8:
            case 9:
            case 10:
                $activityLog = $this->foundBirdNest($petWithSkills, $roll);
                break;
            case 11:
                if($this->rng->rngNextInt(1, 4) === 1)
                    $activityLog = $this->foundBugSwarm($pet);
                else
                    $activityLog = $this->foundBirdNest($petWithSkills, $roll);
                break;
            case 12:
            case 13:
            case 14:
                $activityLog = $this->foundNothing($pet);
                break;
            case 15:
            case 16:
            case 17:
                $activityLog = $this->foundPegasusNest($petWithSkills);
                break;
            case 18:
                $activityLog = $this->foundLightning($petWithSkills);
                break;
            case 19:
                $activityLog = $this->foundEverice($petWithSkills);
                break;
            case 20:
            case 21:
            default:
                $activityLog = $this->foundGiantCastle($petWithSkills);
                break;
        }

        if(AdventureMath::petAttractsBug($this->rng, $pet, 75))
            $this->inventoryService->petAttractsRandomBug($pet);

        return $activityLog;
    }

    private function badClimber(Pet $pet): PetActivityLog
    {
        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to climb the magic bean-stalk in %user:' . $pet->getOwner()->getId() . '.name\'s% greenhouse, but wasn\'t able to make any progress...')
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk' ]))
        ;

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);

        return $activityLog;
    }

    private function getBeans(Pet $pet): PetActivityLog
    {
        $meters = $this->rng->rngNextInt(10, 16) / 2;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters. There, perhaps unsurprisingly, they found some Beans.')
            ->setIcon('items/legume/beans')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
        ;

        $this->inventoryService->petCollectsItem('Beans', $pet, $pet->getName() . ' harvested this from your magic bean-stalk.', $activityLog);
        $this->inventoryService->petCollectsItem('Beans', $pet, $pet->getName() . ' harvested this from your magic bean-stalk.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function getReallyBigLeaf(Pet $pet): PetActivityLog
    {
        $meters = $this->rng->rngNextInt(12, 20) / 2;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters. They didn\'t dare go any higher, but decided to pluck a Really Big Leaf on their way back down.')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
        ;

        $this->inventoryService->petCollectsItem('Really Big Leaf', $pet, $pet->getName() . ' harvested this from your magic bean-stalk.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundMagicLeaf(Pet $pet, bool $lucky = false): PetActivityLog
    {
        $meters = $this->rng->rngNextInt(300, 1800) / 2;

        if($lucky)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as \~' . $meters . ' meters. There, they spotted a Magic Leaf! Lucky\~!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering', 'Lucky~!' ]))
            ;

            $this->inventoryService->petCollectsItem('Magic Leaf', $pet, $pet->getName() . ' harvested this from your magic bean-stalk! Lucky~!', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters. There, they spotted a Magic Leaf, so plucked it, and headed back down.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
            ;

            $this->inventoryService->petCollectsItem('Magic Leaf', $pet, $pet->getName() . ' harvested this from your magic bean-stalk.', $activityLog);
        }

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundBirdNest(ComputedPetSkills $petWithSkills, int $roll): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $meters = $this->rng->rngNextInt(7 + $roll, 6 + $roll * 2) / 2;

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 10)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters. There, they found a bird\'s nest, which they raided.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            $this->inventoryService->petCollectsItem('Egg', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);

            $perceptionRoll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal());

            if($perceptionRoll >= 25)
                $this->inventoryService->petCollectsItem('Black Feathers', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);
            else if($perceptionRoll >= 18)
                $this->inventoryService->petCollectsItem('Feathers', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);
            else if($perceptionRoll >= 10)
                $this->inventoryService->petCollectsItem('Fluff', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters. They found a bird nest, but the mother bird was around, and it didn\'t seem safe to pick a fight up there, so %pet:' . $pet->getId() . '.name% left it alone.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);
        }

        return $activityLog;
    }

    private function foundBugSwarm(Pet $pet): PetActivityLog
    {
        $meters = $this->rng->rngNextInt(100, 200) / 2;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! A huge swarm of bugs flew by, and %pet:' . $pet->getId() . '.name% had to hold on for dear life!')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk' ]))
        ;

        $repelsBugs =
            $pet->getTool() && (
                ($pet->getTool()->getItem()->getTool() && $pet->getTool()->getItem()->getTool()->getPreventsBugs()) ||
                ($pet->getTool()->getEnchantment() && $pet->getTool()->getEnchantment()->getEffects()->getPreventsBugs())
            )
        ;

        if(!$repelsBugs)
        {
            $numBugs = $this->rng->rngNextInt(2, 5);

            for($i = 0; $i < $numBugs; $i++)
                $this->inventoryService->petCollectsItem('Stink Bug', $pet, 'A swarm of these flew past ' . $pet->getName() . ' while they were climbing ' . $pet->getOwner()->getName() . '\'s magic bean-stalk. I guess this one hitched a ride back down.', $activityLog);
        }

        $pet->increaseSafety(-$this->rng->rngNextInt(2, 8));

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundNothing(Pet $pet): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 50) === 1)
            return $this->foundMagicLeaf($pet);
        else if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 10) === 1)
            return $this->foundMagicLeaf($pet, true);

        $meters = $this->rng->rngNextInt(300, 1800) / 2;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! There wasn\'t anything noteworthy up there, but it was a good work-out!')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk' ]))
        ;

        $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundPegasusNest(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $meters = $this->rng->rngNextInt(2000, 3000) / 2;

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 18)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! There, they found a white Pegasus\' nest, which they raided.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            $this->inventoryService->petCollectsItem('Egg', $pet, $pet->getName() . ' stole this from a white Pegasus nest.', $activityLog);

            $perceptionRoll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal());

            if($perceptionRoll >= 18)
                $this->inventoryService->petCollectsItem('White Feathers', $pet, $pet->getName() . ' stole this from a white Pegasus nest.', $activityLog);
            else if($perceptionRoll >= 10)
                $this->inventoryService->petCollectsItem('Fluff', $pet, $pet->getName() . ' stole this from a white Pegasus nest.', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! They found a white Pegasus\' nest, but the mother was around, and it didn\'t seem safe to pick a fight up there, so %pet:' . $pet->getId() . '.name% left it alone.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, false);
        }

        return $activityLog;
    }

    private function foundEverice(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $meters = $this->rng->rngNextInt(3200, 3800) / 2;

        if($this->rng->rngSkillRoll($petWithSkills->getStrength()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 18)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! There, they found some Everice stuck to part of the stalk, and pried a piece off.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
            ;

            $this->inventoryService->petCollectsItem('Everice', $pet, $pet->getName() . ' pried this off your magic bean-stalk.', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(1, 2));

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! There, they found some Everice stuck to part of the stalk, but were unable to pry any off...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, false);
        }

        return $activityLog;
    }

    private function foundLightning(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $meters = $this->rng->rngNextInt(3200, 3800) / 2;

        if($this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 20)
        {
            if($this->rng->rngNextInt(1, 10) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! A dark cloud swirled overhead, and %pet:' . $pet->getId() . '.name% was nearly struck by lightning, but managed to capture it in a bottle, instead! Oh, but wait, it wasn\'t lightning, at all! Merely lightning _bugs!_')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering' ]))
                ;

                $this->inventoryService->petCollectsItem('Jar of Fireflies', $pet, $pet->getName() . ' captured this while climbing your magic bean-stalk.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! A dark cloud swirled overhead, and %pet:' . $pet->getId() . '.name% was nearly struck by lightning, but managed to capture it in a bottle, instead!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering', 'Physics' ]))
                ;

                $this->inventoryService->petCollectsItem('Lightning in a Bottle', $pet, $pet->getName() . ' captured this while climbing your magic bean-stalk.', $activityLog);
            }

            $pet->increaseEsteem(3);
            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk, getting as high as ~' . $meters . ' meters! A dark cloud swirled overhead, and ' . $pet->getName() . ' was nearly struck by lightning!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Gathering', 'Physics' ]))
            ;

            $pet->increaseSafety(-$this->rng->rngNextInt(4, 8));
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, false);
        }

        return $activityLog;
    }

    private function foundGiantCastle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 20)
        {
            $possibleLoot = [
                'Ill-gotten Grains',
                'Gold Bar',
                'Linens and Things',
                'Pamplemousse',
                'Fig',
                'Slice of Bread',
                'Stereotypical Bone',
                'Puddin\' Rec\'pes',
                'Gold Harp',
            ];

            $loot = [
                'Fluff',
            ];

            if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal()) >= 20)
                $loot = array_merge($loot, $this->rng->rngNextSubsetFromArray($possibleLoot, 2));
            else
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($this->rng->rngNextInt(1, max(5, 30 - $petWithSkills->getPerception()->getTotal() * 3)) === 1)
                $loot[] = 'Marshmallows';

            if($this->rng->rngNextInt(1, 1000) <= $petWithSkills->getPerception()->getTotal() && $pet->hasMerit(MeritEnum::BEHATTED))
                $loot[] = 'White Bow';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk all the way to the clouds, and found a huge castle! They explored it for a little while, eventually making off with ' . ArrayFunctions::list_nice_sorted($loot) . '!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            foreach($loot as $item)
                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' stole this from a giant castle above the clouds!', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed %user:' . $pet->getOwner()->getId() . '.name\'s% magic bean-stalk all the way to the clouds, and found a huge castle! They explored it for a little while, but were spotted by a giant, and forced to flee!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Magic Beanstalk', 'Stealth' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, false);
        }

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::ClimbedToTopOfBeanstalk, $activityLog);

        return $activityLog;
    }
}
