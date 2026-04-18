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

use App\Entity\MuseumItem;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\DistractionLocationEnum;
use App\Enum\MeritEnum;
use App\Enum\MoonPhaseEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Functions\ActivityHelpers;
use App\Functions\AdventureMath;
use App\Functions\ArrayFunctions;
use App\Functions\CalendarFunctions;
use App\Functions\DateFunctions;
use App\Functions\InventoryModifierFunctions;
use App\Functions\ItemRepository;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\StatusEffectHelpers;
use App\Functions\UserQuestRepository;
use App\Model\ComputedPetSkills;
use App\Service\Clock;
use App\Service\FieldGuideService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\Holiday\HuntTurkeyDragon;
use App\Service\PetExperienceService;
use App\Service\TransactionService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;

class HuntingService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly UserStatsService $userStatsRepository,
        private readonly PetExperienceService $petExperienceService,
        private readonly TransactionService $transactionService,
        private readonly IRandom $rng,
        private readonly Clock $clock,
        private readonly EntityManagerInterface $em,
        private readonly WerecreatureEncounterService $werecreatureEncounterService,
        private readonly GatheringDistractionService $gatheringDistractions,
        private readonly FieldGuideService $fieldGuideService,
        private readonly HuntTurkeyDragon $huntTurkeyDragon
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'hunting'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();
        $desire = $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getBrawl();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Hunting))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(1, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100)));
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        return [ $this->run(...) ];
    }

    public function run(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $maxSkill = 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal() - $pet->getAlcohol() - $pet->getPsychedelic();

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 22);

        $useThanksgivingPrey = CalendarFunctions::isThanksgivingMonsters($this->clock->now) && $this->rng->rngNextBool();
        $usePassoverPrey = CalendarFunctions::isEaster($this->clock->now);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        if(DateFunctions::moonPhase($this->clock->now) === MoonPhaseEnum::FullMoon && $this->rng->rngNextInt(1, 100) === 1)
            $activityLog = $this->werecreatureEncounterService->encounterWerecreature($petWithSkills, 'hunting', [ 'Hunting' ]);
        else
        {
            switch($roll)
            {
                case 1:
                case 2:
                    $activityLog = $this->failedToHunt($petWithSkills);
                    break;
                case 3:
                    $activityLog = $this->huntedSnail($petWithSkills);
                    break;
                case 4:
                    $activityLog = $this->huntedDustBunny($petWithSkills);
                    break;
                case 5:
                    $activityLog = $this->huntedPlasticBag($petWithSkills);
                    break;
                case 6:
                    $activityLog = $this->huntedLargeToad($petWithSkills);
                    break;
                case 7:
                case 8:
                    if($this->canRescueAnotherHouseFairy($pet->getOwner()) && !$pet->hasStatusEffect(StatusEffectEnum::BittenByAVampire))
                        $activityLog = $this->rescueHouseFairy($pet);
                    else if($useThanksgivingPrey)
                        $activityLog = $this->huntedTurkey($petWithSkills);
                    else if($usePassoverPrey)
                        $activityLog = $this->noGoats($pet);
                    else
                        $activityLog = $this->huntedGoat($petWithSkills);
                    break;
                case 9:
                    $activityLog = $this->huntedDoughGolem($petWithSkills);
                    break;
                case 10:
                    if($useThanksgivingPrey)
                        $activityLog = $this->huntedTurkey($petWithSkills);
                    else
                        $activityLog = $this->huntedLargeToad($petWithSkills);
                    break;
                case 11:
                    $activityLog = $this->huntedScarecrow($petWithSkills);
                    break;
                case 12:
                    $activityLog = $this->huntedOnionBoy($petWithSkills);
                    break;
                case 13:
                    $activityLog = $this->huntedBeaver($petWithSkills);
                    break;
                case 14:
                case 15:
                    $activityLog = $this->huntedThievingMagpie($petWithSkills);
                    break;
                case 16:
                case 17:
                    if($useThanksgivingPrey)
                        $activityLog = $this->huntedPossessedTurkey($petWithSkills);
                    else
                        $activityLog = $this->huntedGhosts($petWithSkills);
                    break;
                case 18:
                case 19:
                    if($useThanksgivingPrey)
                        $activityLog = $this->huntedPossessedTurkey($petWithSkills);
                    else if($usePassoverPrey)
                        $activityLog = $this->noGoats($pet);
                    else if($pet->hasStatusEffect(StatusEffectEnum::BittenByAVampire))
                        $activityLog = $this->huntedSatyr($petWithSkills);
                    else
                        $activityLog = $this->huntedPaperGolem($petWithSkills); // fallback, in case none of the above are good
                    break;
                case 20:
                    $activityLog = $this->huntedPaperGolem($petWithSkills);
                    break;
                case 21:
                    if($useThanksgivingPrey)
                        $activityLog = $this->huntTurkeyDragon->hunt($petWithSkills);
                    else
                        $activityLog = $this->huntedLeshyDemon($petWithSkills);
                    break;
                case 22:
                default:
                    $activityLog = $this->huntedEggSaladMonstrosity($petWithSkills);
                    break;
            }
        }

        if(AdventureMath::petAttractsBug($this->rng, $pet, 100))
            $this->inventoryService->petAttractsRandomBug($pet);

        return $activityLog;
    }

    private function canRescueAnotherHouseFairy(User $user): bool
    {
        // if you've unlocked the fireplace, then you can't rescue a second
        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Fireplace))
            return false;

        $houseFairy = ItemRepository::findOneByName($this->em, 'House Fairy');

        // if you haven't donated a fairy, then you can't rescue a second
        if($this->em->getRepository(MuseumItem::class)->count([ 'user' => $user, 'item' => $houseFairy ]) == 0)
            return false;

        // if you already rescued a second, then you can't rescue a second again :P
        $rescuedASecond = UserQuestRepository::findOrCreate($this->em, $user, 'Rescued Second House Fairy', false);

        if($rescuedASecond->getValue())
            return false;

        return true;
    }

    private function rescueHouseFairy(Pet $pet): PetActivityLog
    {
        UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Rescued Second House Fairy', false)
            ->setValue(true)
        ;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While ' . '%pet:' . $pet->getId() . '.name% was out hunting, they spotted a Raccoon and Thieving Magpie fighting over a fairy! %pet:' . $pet->getId() . '.name% jumped in and chased the two creatures off before tending to the fairy\'s wounds.')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Fighting', 'Fae-kind' ]))
        ;
        $inventory = $this->inventoryService->petCollectsItem('House Fairy', $pet, 'Rescued from a Raccoon and Thieving Magpie.', $activityLog);

        if($inventory)
            $inventory->setLockedToOwner(true);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

        $pet->increaseSafety(2);
        $pet->increaseLove(2);
        $pet->increaseEsteem(2);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);

        return $activityLog;
    }

    private function failedToHunt(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($pet->getOwner()->getGreenhouse() && $pet->getOwner()->getGreenhouse()->getHasBirdBath() && !$pet->getOwner()->getGreenhouse()->getVisitingBird())
        {
            $pet
                ->increaseSafety($this->rng->rngNextInt(1, 2))
                ->increaseEsteem($this->rng->rngNextInt(1, 2))
            ;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% couldn\'t find anything to hunt, so watched some small birds play in the Greenhouse Bird Bath, instead.')
                ->setIcon('icons/activity-logs/birb')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Greenhouse' ]))
            ;

            if($pet->getSkills()->getBrawl() < 5)
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, but couldn\'t find anything to hunt.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
            ;
        }

        return $activityLog;
    }

    private function huntedDustBunny(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $isRanged = $pet->getTool() && $pet->getTool()->rangedOnly() && $pet->getTool()->brawlBonus() > 0;

        $defeated = $isRanged ? 'shot down' : 'pounced on';
        $chased = $isRanged ? 'shot at' : 'chased';

        if(!$isRanged)
            $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 6)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $defeated . ' a Dust Bunny, reducing it to Fluff!')
                ->setIcon('items/ambiguous/fluff')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Hunting,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Fluff', $pet, 'The remains of a Dust Bunny that ' . $pet->getName() . ' hunted.', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $chased . ' a Dust Bunny, but wasn\'t able to catch up with it.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Hunting,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedSnail(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'After looking around a bit for something interesting to hunt, ' . ActivityHelpers::PetName($pet) . ' spotted a snail outside. They ate it, then took the shell back home.')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                PetActivityLogTagEnum::Hunting,
                PetActivityLogTagEnum::Location_Neighborhood,
            ]))
        ;

        $pet->increaseFood(2);

        $this->inventoryService->petCollectsItem('Snail Shell', $pet, 'The skeletal remains of a snail that ' . $pet->getName() . ' ate.', $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);

        return $activityLog;
    }

    private function huntedPlasticBag(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $isRanged = $pet->getTool() && $pet->getTool()->rangedOnly() && $pet->getTool()->brawlBonus() > 0;

        $defeated = $isRanged ? 'shot down' : 'pounced on';
        $chased = $isRanged ? 'shot at' : 'chased';

        if(!$isRanged)
            $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 6)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $defeated . ' a Plastic Bag, reducing it to Plastic... somehow?')
                ->setIcon('items/ambiguous/fluff')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Hunting,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Plastic', $pet, 'The remains of a vicious Plastic Bag that ' . $pet->getName() . ' hunted!', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $chased . ' a Plastic Bag, but wasn\'t able to catch up with it!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Hunting,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedGoat(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl(false)->getTotal();

        $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 6)
        {
            $pet->increaseEsteem(1);

            if($this->rng->rngNextInt(1, 2) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Goat, and won, receiving Creamy Milk.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Creamy Milk', $pet, $pet->getName() . '\'s prize for out-wrestling a Goat.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Goat, and won, receiving Butter.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Butter', $pet, $pet->getName() . '\'s prize for out-wrestling a Goat.', $activityLog);
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            if($this->rng->rngNextInt(1, 4) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Goat. The Goat won.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Fluff', $pet, $pet->getName() . ' wrestled a Goat, and lost, but managed to grab a fistful of Fluff.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Goat. The Goat won.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

        return $activityLog;
    }

    private function huntedDoughGolem(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $wheatOrCorn = DateFunctions::isCornMoon($this->clock->now) ? 'Corn' : 'Wheat Flour';

        $possibleLoot = [
            $wheatOrCorn, 'Oil', 'Butter', 'Yeast', 'Sugar'
        ];

        $possibleLootSansOil = [
            $wheatOrCorn, 'Butter', 'Yeast', 'Sugar'
        ];

        $stealth = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStealth()->getTotal());

        if($stealth > 25)
        {
            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            $loot = $this->rng->rngNextFromArray($possibleLootSansOil);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% snuck up on a sleeping Deep-fried Dough Golem, and harvested some of its ' . $loot . ', and Oil, without it ever noticing!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Stealth' ]))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' stole this off the body of a sleeping Deep-fried Dough Golem.', $activityLog);
            $this->inventoryService->petCollectsItem('Oil', $pet, $pet->getName() . ' stole this off the body of a sleeping Deep-fried Dough Golem.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            return $activityLog;
        }
        else if($stealth > 15)
        {
            $pet->increaseEsteem(1);

            $loot = $this->rng->rngNextFromArray($possibleLoot);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% snuck up on a sleeping Dough Golem, and harvested some of its ' . $loot . ' without it ever noticing!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Stealth' ]))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' stole this off the body of a sleeping Dough Golem.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            return $activityLog;
        }

        $skillCheck = $this->rng->rngNextInt(1, 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl(false)->getTotal());

        $pet->increaseFood(-1);

        if($skillCheck >= 17)
        {
            $dodgeCheck = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl(false)->getTotal());

            $loot = $this->rng->rngNextFromArray($possibleLootSansOil);

            if($dodgeCheck >= 15)
            {
                $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% attacked a rampaging Deep-fried Dough Golem, defeated it, and harvested its ' . $loot . '.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' took this from the body of a defeated Deep-fried Dough Golem.', $activityLog);
                $this->inventoryService->petCollectsItem('Oil', $pet, $pet->getName() . ' took this from the body of a defeated Deep-fried Dough Golem.', $activityLog);

                $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Brawl ], $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% attacked a rampaging Deep-fried Dough Golem. It was gross and oily, and %pet:' . $pet->getId() . '.name% got Oil all over themselves, but in the end they defeated the creature, and harvested its ' . $loot . '.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' took this from the body of a defeated Deep-fried Dough Golem.', $activityLog);
                StatusEffectHelpers::applyStatusEffect($this->em, $pet, StatusEffectEnum::OilCovered, 1);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($skillCheck >= 7)
        {
            $pet->increaseEsteem(1);

            $loot = $this->rng->rngNextFromArray($possibleLoot);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% attacked a rampaging Dough Golem, defeated it, and harvested its ' . $loot . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' took this from the body of a defeated Dough Golem.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            if($this->rng->rngNextInt(1, 4) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% attacked a rampaging Dough Golem, but it released a cloud of defensive flour, and escaped. ' . $pet->getName() . ' picked up some of the flour, and brought it home.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem($wheatOrCorn, $pet, $pet->getName() . ' got this from a fleeing Dough Golem.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% attacked a Dough Golem, but it was really sticky. ' . $pet->getName() . '\'s attacks were useless, and they were forced to retreat.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedTurkey(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl(false)->getTotal();

        $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 6)
        {
            $item = $this->rng->rngNextFromArray([ 'Talon', 'Feathers', 'Giant Turkey Leg', 'Smallish Pumpkin Spice' ]);

            $aOrSome = $item === 'Feathers' ? 'some' : 'a';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Turkey! The Turkey fled, but not before ' . $pet->getName() . ' took ' . $aOrSome . ' ' . $item . '!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Special Event', 'Thanksgiving' ]))
            ;
            $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' wrestled this from a Turkey.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% picked a fight with a Turkey, but lost.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Special Event', 'Thanksgiving' ]))
            ;
            $pet->increaseEsteem(-2);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedLargeToad(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'hunting in the woods');

        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl(false)->getTotal();

        $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 6)
        {
            if($this->rng->rngNextInt(1, 4) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% beat up a Giant Toad, and took two of its legs.')
                    ->setIcon('items/animal/meat/legs-frog')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Toad Legs', $pet, $pet->getName() . ' took these from a Giant Toad. It still has two left, so it\'s probably fine >_>', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a Toadstool off the back of a Giant Toad.')
                    ->setIcon('items/fungus/toadstool')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Toadstool', $pet, $pet->getName() . ' wrestled this from a Giant Toad.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% picked a fight with a Giant Toad, but lost.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $pet->increaseEsteem(-2);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedScarecrow(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::InTown, 'hunting around town');

        $pet = $petWithSkills->getPet();

        $brawlRoll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal());
        $stealthSkill = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStealth()->getTotal());

        $wheatOrCorn = DateFunctions::isCornMoon($this->clock->now) ? 'Corn' : 'Wheat';

        $pet->increaseFood(-1);

        if($stealthSkill >= 10)
        {
            $pet->increaseEsteem(1);

            $itemName = $this->rng->rngNextFromArray([ $wheatOrCorn, 'Rice' ]);
            $bodyPart = $this->rng->rngNextFromArray([ 'left', 'right' ]) . ' ' . $this->rng->rngNextFromArray([ 'leg', 'arm' ]);

            $moneys = $this->rng->rngNextInt(1, $this->rng->rngNextInt(2, $this->rng->rngNextInt(3, 5)));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% snuck up on a Scarecrow, and picked its pockets... and also its ' . $bodyPart . '! ' . $pet->getName() . ' walked away with ' . $moneys . '~~m~~, and some ' . $itemName . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Stealth', 'Moneys' ]))
            ;
            $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' stole this from a Scarecrow\'s ' . $bodyPart .'.', $activityLog);
            $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' stole this from a Scarecrow.');
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($brawlRoll >= 8)
        {
            $foundPinecone = $this->clock->getMonthAndDay() > 1225;

            if($this->rng->rngNextInt(1, 2) === 1)
            {
                if($foundPinecone)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% beat up a Scarecrow, then took some of the ' . $wheatOrCorn . ' it was defending. Hm-what? A Pinecone also fell out of the Scarecrow!')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Gathering', 'Special Event' ]))
                    ;
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% beat up a Scarecrow, then took some of the ' . $wheatOrCorn . ' it was defending.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Gathering' ]))
                    ;
                }

                $this->inventoryService->petCollectsItem($wheatOrCorn, $pet, $pet->getName() . ' took this from a ' . $wheatOrCorn . ' Farm, after beating up its Scarecrow.', $activityLog);

                if($this->rng->rngNextInt(1, 10 + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal()) >= 10)
                {
                    if($this->rng->rngNextBool() || $wheatOrCorn === 'Corn')
                        $this->inventoryService->petCollectsItem($wheatOrCorn, $pet, $pet->getName() . ' took this from a ' . $wheatOrCorn . ' Farm, after beating up its Scarecrow.', $activityLog);
                    else
                        $this->inventoryService->petCollectsItem('Wheat Flower', $pet, $pet->getName() . ' took this from a Wheat Farm, after beating up its Scarecrow.', $activityLog);

                    $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
                    $pet->increaseEsteem(1);
                }
            }
            else
            {
                if($foundPinecone)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% beat up a Scarecrow, then took some of the Rice it was defending. Hm-what? A Pinecone also fell out of the Scarecrow!')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Gathering', 'Special Event', 'Stocking Stuffing Season' ]))
                    ;
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% beat up a Scarecrow, then took some of the Rice it was defending.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Gathering' ]))
                    ;
                }

                $this->inventoryService->petCollectsItem('Rice', $pet, $pet->getName() . ' took this from a Rice Farm, after beating up its Scarecrow.', $activityLog);

                if($this->rng->rngNextInt(1, 10 + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal()) >= 10)
                {
                    $this->inventoryService->petCollectsItem('Rice', $pet, $pet->getName() . ' took this from a Rice Farm, after beating up its Scarecrow.', $activityLog);

                    $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
                    $pet->increaseEsteem(1);
                }
            }

            if($foundPinecone)
                $this->inventoryService->petCollectsItem('Pinecone', $pet, 'This fell out of a Scarecrow that ' . $pet->getName() . ' beat up.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to take out a Scarecrow, but lost.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $pet->increaseEsteem(-1);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedOnionBoy(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 10 + $petWithSkills->getStamina()->getTotal();

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Onion Boy', ActivityHelpers::PetName($pet). ' encountered an Onion Boy at the edge of town...');

        if($pet->hasMerit(MeritEnum::GOURMAND) && $this->rng->rngNextInt(1, 2) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were powerful, but ' . $pet->getName() . ' didn\'t even flinch, and swallowed the Onion Boy whole! (Ah~! A true Gourmand!)')
                ->setIcon('items/veggie/onion')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Eating', 'Gourmand' ]))
            ;

            $pet
                ->increaseFood($this->rng->rngNextInt(4, 8))
                ->increaseSafety($this->rng->rngNextInt(2, 4))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($pet->getTool() && $pet->getTool()->rangedOnly())
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were powerful, but ' . $pet->getName() . ' attacked from a distance using their ' . InventoryModifierFunctions::getNameWithModifiers($pet->getTool()) . '! The Onion Boy ran off, dropping an Onion as it ran.')
                ->setIcon('items/veggie/onion')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $this->inventoryService->petCollectsItem('Onion', $pet, 'Dropped by an Onion Boy that ' . $pet->getName() . ' encountered.', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($this->rng->rngNextInt(1, $skill) >= 7)
        {
            $exp = 2;

            $getClothes = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal()) >= 20;

            if($getClothes)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were powerful, but ' . $pet->getName() . ' powered through it, and grabbed onto its... clothes? The creature ran off, causing it to drop an Onion.')
                    ->setIcon('items/veggie/onion')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;

                $loot = $this->rng->rngNextFromArray([ 'Paper', 'Filthy Cloth' ]);

                $this->inventoryService->petCollectsItem($loot, $pet, 'Snatched off an Onion Boy that ' . $pet->getName() . ' encountered.', $activityLog);

                $exp++;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were powerful, but ' . $pet->getName() . ' powered through it, scaring the creature off, causing it to drop an Onion.')
                    ->setIcon('items/veggie/onion')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
            }

            $this->inventoryService->petCollectsItem('Onion', $pet, 'Dropped by an Onion Boy that ' . $pet->getName() . ' encountered.', $activityLog);

            $this->petExperienceService->gainExp($pet, $exp, [ PetSkillEnum::Nature, PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered an Onion Boy. The fumes were overwhelming, and ' . $pet->getName() . ' fled.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $pet->increaseSafety(-2);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedBeaver(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $skill = 20 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl(false)->getTotal();

        $pet->increaseFood(-2);

        if($this->rng->rngNextInt(1, $skill) >= 15)
        {
            $item = $this->rng->rngNextFromArray([ 'Fluff', 'Castoreum' ]);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wrestled a beaver! It fled, but not before ' . ActivityHelpers::PetName($pet) . ' took some of its ' . $item . '!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' wrestled this from a beaver.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% picked a fight with a beaver, but lost.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
            ;
            $pet->increaseEsteem(-2);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedThievingMagpie(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'hunting in the woods');

        $pet = $petWithSkills->getPet();
        $intSkill = 10 + $petWithSkills->getIntelligence()->getTotal();
        $dexSkill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $isRanged = $pet->getTool() && $pet->getTool()->rangedOnly() && $pet->getTool()->brawlBonus() > 0;

        if($this->rng->rngNextInt(1, $intSkill) <= 2 && $pet->getOwner()->getMoneys() >= 2)
        {
            $moneysLost = $this->rng->rngNextInt(1, 2);

            if($this->rng->rngNextInt(1, 10) === 1)
            {
                $description = 'who absquatulated with ' . $moneysLost . ' ' . ($moneysLost === 1 ? 'money' : 'moneys') . '!';

                if($this->rng->rngNextInt(1, 10) === 1)
                    $description = ' (Ugh! Everyone\'s least-favorite kind of squatulation!)';
            }
            else
                $description = 'who stole ' . $moneysLost . ' ' . ($moneysLost === 1 ? 'money' : 'moneys') . '.';

            $this->transactionService->spendMoney($pet->getOwner(), $moneysLost, $pet->getName() . ' was outsmarted by a Thieving Magpie, ' . $description, false);

            $this->userStatsRepository->incrementStat($pet->getOwner(), UserStat::MoneysStolenByThievingMagpies, $moneysLost);

            $pet
                ->increaseEsteem(-2)
                ->increaseSafety(-2)
            ;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was outsmarted by a Thieving Magpie, ' . $description)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Moneys' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }
        else if($this->rng->rngNextInt(1, $dexSkill) >= 9)
        {
            $pet
                ->increaseEsteem(2)
                ->increaseSafety(2)
            ;

            if($this->rng->rngNextInt(1, 4) === 1)
            {
                $moneys = $this->rng->rngNextInt(2, 5);

                if($isRanged)
                {
                    $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' shot at a Thieving Magpie, forcing it to drop this money.');
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% shot at a Thieving Magpie; it dropped its ' . $moneys . ' moneys and sped away.')
                        ->setIcon('icons/activity-logs/moneys')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Moneys' ]))
                    ;
                }
                else
                {
                    $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' pounced on a Thieving Magpie, and liberated this money.');
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% pounced on a Thieving Magpie, and liberated its ' . $moneys . ' moneys.')
                        ->setIcon('icons/activity-logs/moneys')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Moneys' ]))
                    ;
                }
            }
            else
            {
                if($isRanged)
                {
                    $item = $this->rng->rngNextFromArray([ 'String', 'Rice', 'Plastic' ]);
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% shot at a Thieving Magpie, forcing it to drop some ' . $item . '.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
                    ;
                }
                else
                {
                    $item = $this->rng->rngNextFromArray([ 'Egg', 'String', 'Rice', 'Plastic' ]);
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% pounced on a Thieving Magpie, and liberated ' . ($item === 'Egg' ? 'an' : 'some') . ' ' . $item . '.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
                    ;
                }

                $this->inventoryService->petCollectsItem($item, $pet, 'Liberated from a Thieving Magpie.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to take down a Thieving Magpie, but it got away.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
            ;
            $pet->increaseSafety(-1);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedGhosts(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'hunting in the woods');

        $pet = $petWithSkills->getPet();

        if($this->rng->rngNextInt(1, 100) === 1)
            $prize = 'Little Strongbox';
        else if($this->rng->rngNextInt(1, 50) === 1)
            $prize = $this->rng->rngNextFromArray([ 'Rib', 'Stereotypical Bone' ]);
        else if($this->rng->rngNextInt(1, 8) === 1)
            $prize = $this->rng->rngNextFromArray([ 'Iron Bar', 'Silver Bar', 'Filthy Cloth' ]);
        else if($this->rng->rngNextInt(1, 4) === 1)
            $prize = 'Ghost Pepper';
        else
            $prize = 'Quintessence';

        $brawlSkill = 10 + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getBrawl()->getTotal() + $petWithSkills->getArcana()->getTotal();
        $stealthSkill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStealth()->getTotal();

        if($this->rng->rngNextInt(1, $brawlSkill) >= 15)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'A Pirate Ghost tried to haunt %pet:' . $pet->getId() . '.name%, but %pet:' . $pet->getId() . '.name% was able to dispel it (and got its ' . $prize . ')!');
            $this->inventoryService->petCollectsItem($prize, $pet, $pet->getName() . ' collected this from the remains of a Pirate Ghost.', $activityLog);

            $pet
                ->increaseSafety(3)
                ->increaseEsteem(2)
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl, PetSkillEnum::Arcana ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            return $activityLog;
        }
        else if($this->rng->rngNextInt(1, $stealthSkill) >= 10)
        {
            $hidSomehow = $this->rng->rngNextFromArray([
                'ducked behind a boulder', 'ducked behind a tree',
                'dove into a bush', 'ducked behind a river bank',
                'jumped into a hollow log'
            ]);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'A Pirate Ghost tried to haunt %pet:' . $pet->getId() . '.name%, but %pet:' . $pet->getId() . '.name% ' . $hidSomehow . ', eluding the ghost!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Stealth' ]))
            ;

            $pet->increaseEsteem(2);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Arcana ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            return $activityLog;
        }

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, and got haunted by a Pirate Ghost! After harassing %pet:' . $pet->getId() . '.name% for a while, the ghost became bored, and left.')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
        ;
        $pet->increaseSafety(-3);
        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl, PetSkillEnum::Arcana ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::HUNT, false);

        return $activityLog;
    }

    private function huntedPossessedTurkey(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $loot = $this->rng->rngNextFromArray([
            'Quintessence', 'Black Feathers', 'Giant Turkey Leg', 'Smallish Pumpkin Spice'
        ]);

        $skill = 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 15)
        {
            $item = ItemRepository::findOneByName($this->em, $loot);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Possessed Turkey! They fought hard, took ' . $item->getNameWithArticle() . ', and drove the creature away!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Special Event', 'Thanksgiving' ]))
            ;
            $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' got this by defeating a Possessed Turkey.', $activityLog);
            $pet->increaseSafety(3);
            $pet->increaseEsteem(2);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

            return $activityLog;
        }

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered and fought a Possessed Turkey, but was chased away by a flurry of kicks and pecks!')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Special Event', 'Thanksgiving' ]))
        ;
        $pet->increaseEsteem(-$this->rng->rngNextInt(1, 3));
        $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);

        return $activityLog;
    }

    private function huntedSatyr(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'hunting in the woods');

        $pet = $petWithSkills->getPet();

        $brawlRoll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal());
        $musicSkill = $this->rng->rngNextInt(1, 10 + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getMusic()->getTotal());

        $pet->increaseFood(-1);

        if($pet->hasStatusEffect(StatusEffectEnum::Cordial))
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Satyr; the Satyr was so enamored by ' . $pet->getName() . '\'s cordiality, they had a simply _wonderful_ time, and offered gifts before leaving in peace.')
                ->setIcon('icons/activity-logs/drunk-satyr')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ;
            $pet->increaseEsteem(1);
            $this->inventoryService->petCollectsItem('Blackberry Wine', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

            if($this->rng->rngNextInt(1, 5) === 1)
                $this->inventoryService->petCollectsItem('Quintessence', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);
            else
                $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(20, 40), PetActivityStatEnum::HUNT, true);
        }
        else if($pet->hasMerit(MeritEnum::EIDETIC_MEMORY) && $pet->hasMerit(MeritEnum::SOOTHING_VOICE))
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Satyr, but remembered that Satyrs love music, so sang a song. The Satyr was so enthralled by ' . $pet->getName() . '\'s Soothing Voice, that it offered gifts before leaving in peace.')
                ->setIcon('icons/activity-logs/drunk-satyr')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
            ;
            $pet->increaseEsteem(1);
            $this->inventoryService->petCollectsItem('Blackberry Wine', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

            if($this->rng->rngNextInt(1, 5) === 1)
                $this->inventoryService->petCollectsItem('Music Note', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);
            else
                $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Music ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($musicSkill > $brawlRoll)
        {
            if($pet->hasMerit(MeritEnum::SOOTHING_VOICE))
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Satyr, who upon hearing ' . $pet->getName() . '\'s voice, bade them sing. ' . $pet->getName() . ' did so; the Satyr was so enthralled by their soothing voice, that it offered gifts before leaving in peace.')
                    ->setIcon('icons/activity-logs/drunk-satyr')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
                ;
                $pet->increaseEsteem(1);
                $this->inventoryService->petCollectsItem('Blackberry Wine', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

                if($this->rng->rngNextInt(1, 5) === 1)
                    $this->inventoryService->petCollectsItem('Music Note', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);
                else
                    $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Music ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
            }
            else if($musicSkill >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Satyr, who challenged ' . $pet->getName() . ' to a sing. It was surprised by ' . $pet->getName() . '\'s musical skill, and apologetically offered gifts before leaving in peace.')
                    ->setIcon('icons/activity-logs/drunk-satyr')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
                ;
                $pet->increaseEsteem(2);
                $this->inventoryService->petCollectsItem('Blackberry Wine', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

                if($this->rng->rngNextInt(1, 5) === 1)
                    $this->inventoryService->petCollectsItem('Music Note', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);
                else
                    $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Gifts for ' . $pet->getName() . ', from a Satyr.', $activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Music ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% encountered a Satyr, who challenged ' . $pet->getName() . ' to a sing. The Satyr quickly cut ' . $pet->getName() . ' off, complaining loudly, and leaving in a huff.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
                ;
                $pet->increaseEsteem(-1);
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Music ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
            }
        }
        else
        {
            if($brawlRoll >= 15)
            {
                $pet->increaseSafety(3);
                $pet->increaseEsteem(2);
                if($this->rng->rngNextInt(1, 2) === 1)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% fought a Satyr, and won, receiving its Yogurt (gross), and Wine.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Fae-kind' ]))
                    ;
                    $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Satyr loot, earned by ' . $pet->getName() . '.', $activityLog);
                    $this->inventoryService->petCollectsItem('Blackberry Wine', $pet, 'Satyr loot, earned by ' . $pet->getName() . '.', $activityLog);
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% fought a Satyr, and won, receiving its Yogurt (gross), and Horn. Er: Talon, I guess.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Fae-kind' ]))
                    ;
                    $this->inventoryService->petCollectsItem('Plain Yogurt', $pet, 'Satyr loot, earned by ' . $pet->getName() . '.', $activityLog);
                    $this->inventoryService->petCollectsItem('Talon', $pet, 'Satyr loot, earned by ' . $pet->getName() . '.', $activityLog);
                }

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to fight a drunken Satyr, but the Satyr misinterpreted ' . $pet->getName() . '\'s intentions, and it started to get really weird, so ' . $pet->getName() . ' ran away.')
                    ->setIcon('icons/activity-logs/drunk-satyr')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Fae-kind' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(1, 5));
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
            }
        }

        return $activityLog;
    }

    private function noGoats(Pet $pet): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::HUNT, false);

        return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, expecting to find some goats, but there don\'t seem to be any around today...')
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Special Event', 'Easter' ]))
        ;
    }

    private function huntedPaperGolem(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::InTown, 'hunting around town');

        $pet = $petWithSkills->getPet();

        $brawlRoll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStamina()->getTotal() + max($petWithSkills->getCrafts()->getTotal(), $petWithSkills->getBrawl()->getTotal()));
        $stealthRoll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStealth()->getTotal());

        $pet->increaseFood(-1);

        if($stealthRoll >= 15 || $brawlRoll >= 17)
        {
            $pet->increaseSafety(1);
            $pet->increaseEsteem(2);

            if($stealthRoll >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% snuck up behind a Paper Golem, and unfolded it!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Stealth' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Stealth ], $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% unfolded a Paper Golem!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Crafting' ]))
                ;

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts, PetSkillEnum::Brawl ], $activityLog);
            }

            $recipe = $this->rng->rngNextFromArray([
                'Stroganoff Recipe',
                'Bananananers Foster Recipe',
                'Carrot Wine Recipe',
            ]);

            if($this->rng->rngNextInt(1, 10) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
            {
                $this->inventoryService->petCollectsItem($recipe, $pet, $pet->getName() . ' got this by unfolding a Paper Golem. Lucky~!', $activityLog);
                $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]));
            }
            else if($this->rng->rngNextInt(1, 20) === 1)
                $this->inventoryService->petCollectsItem($recipe, $pet, $pet->getName() . ' got this by unfolding a Paper Golem.', $activityLog);
            else
            {
                $this->inventoryService->petCollectsItem('Paper', $pet, $pet->getName() . ' got this by unfolding a Paper Golem.', $activityLog);

                if($stealthRoll + $brawlRoll >= 15 + 17)
                    $this->inventoryService->petCollectsItem('Paper', $pet, $pet->getName() . ' got this by unfolding a Paper Golem.', $activityLog);
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $pet->increaseFood(-1);
            $pet->increaseEsteem(-1);
            $pet->increaseSafety(-1);

            if($this->rng->rngNextInt(1, 30) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to unfold a Paper Golem, but got a nasty paper cut! During the fight, however, a small, glowing die rolled out from within the folds of the golem! Lucky~! ' . $pet->getName() . ' grabbed it before fleeing.')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Crafting', 'Lucky~!' ]))
                ;

                $this->inventoryService->petCollectsItem('Glowing Six-sided Die', $pet, 'While ' . $pet->getName() . ' was fighting a Paper Golem, this fell out from it! Lucky~!', $activityLog);
            }
            else if($this->rng->rngNextInt(1, 20) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to unfold a Paper Golem, but got a nasty paper cut! During the fight, however, a small, glowing die rolled out from within the folds of the golem. ' . $pet->getName() . ' grabbed it before fleeing.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Crafting' ]))
                ;

                $this->inventoryService->petCollectsItem('Glowing Six-sided Die', $pet, 'While ' . $pet->getName() . ' was fighting a Paper Golem, this fell out from it.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to unfold a Paper Golem, but got a nasty paper cut!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting', 'Crafting' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts, PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedLeshyDemon(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'hunting in the woods');

        $pet = $petWithSkills->getPet();

        $skill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getBrawl()->getTotal() + $petWithSkills->getClimbingBonus()->getTotal() * 2;
        $getExtraItem = $this->rng->rngSkillRoll($petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 15;

        $pet->increaseFood(-1);

        if($this->rng->rngNextInt(1, $skill) >= 18)
        {
            $pet->increaseSafety(1);
            $pet->increaseEsteem(2);

            if($this->rng->rngNextInt(1, 5) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While %pet:' . $pet->getId() . '.name% was out hunting, something started throwing sticks and throwing branches at them! %pet:' . $pet->getId() . '.name% spotted an Argopelter in the trees! They chased after the creature, and defeated it with one of its own sticks!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;

                $this->inventoryService->petCollectsItem('Crooked Stick', $pet, $pet->getName() . ' beat up an Argopelter with the help of this stick, which the Argopelter had thrown at them!', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While %pet:' . $pet->getId() . '.name% was out hunting, something started throwing sticks and throwing branches at them! %pet:' . $pet->getId() . '.name% spotted an Argopelter in the trees! They chased after the creature, and quickly defeated it before it could get away!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;

                $this->inventoryService->petCollectsItem('Crooked Stick', $pet, 'An Argopelter threw this at ' . $pet->getName() . '!', $activityLog);
            }

            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Argopelter', 'While ' . $pet->getName() . ' was out hunting, an Argopelter began throwing sticks and thorny branches at them...');

            if($getExtraItem)
            {
                $extraItem = $this->rng->rngNextFromArray([
                    'Crooked Stick',
                    'Feathers',
                    'Quintessence',
                    'Witch-hazel'
                ]);

                $this->inventoryService->petCollectsItem($extraItem, $pet, $pet->getName() . ' took this from a defeated Argopelter.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts, PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $pet->increaseSafety(-1);

            if($pet->hasMerit(MeritEnum::EIDETIC_MEMORY))
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While %pet:' . $pet->getId() . '.name% was out hunting in the woods, something started throwing sticks and thorny branches at them! %pet:' . $pet->getId() . '.name% never saw their tormenter, but it was surely an Agropelter...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While %pet:' . $pet->getId() . '.name% was out hunting in the woods, something started throwing sticks and thorny branches at them! %pet:' . $pet->getId() . '.name% looked around for their tormenter, but didn\'t see anything...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
                ;
                $pet->increaseEsteem(-1);
            }

            if($getExtraItem)
            {
                $activityLog->appendEntry('They found one of the sticks that had been thrown at them, and returned home.');

                if($pet->hasMerit(MeritEnum::EIDETIC_MEMORY))
                    $this->inventoryService->petCollectsItem('Crooked Stick', $pet, 'This was thrown at ' . $pet->getName() . ' while they were out hunting, probably by an Argopelter.', $activityLog);
                else
                    $this->inventoryService->petCollectsItem('Crooked Stick', $pet, 'This was thrown at ' . $pet->getName() . ' while they were out hunting, by an unseen assailant.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }

    private function huntedEggSaladMonstrosity(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::InTown, 'hunting around town');

        $pet = $petWithSkills->getPet();

        $skill = 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getBrawl()->getTotal();

        $pet->increaseFood(-1);

        $possibleLoot = [
            'Egg',
            $this->rng->rngNextFromArray([ 'Mayo(nnaise)', 'Egg', 'Vinegar', 'Oil' ]),
            'Celery',
            'Onion',
        ];

        if($pet->hasMerit(MeritEnum::GOURMAND) && $this->rng->rngNextInt(1, 4) === 1)
        {
            $prize = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray($possibleLoot));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, and encountered an Egg Salad Monstrosity! After a grueling (and sticky) battle, ' . $pet->getName() . ' took a huge bite out of the monster, slaying it! (Ah~! A true Gourmand!) Finally, they dug ' . $prize->getNameWithArticle() . ' out of the lumpy corpse, and brought it home.')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting', 'Eating', 'Gourmand' ]))
            ;

            $this->inventoryService->petCollectsItem($prize, $pet, $pet->getName() . ' collected this from the remains of an Egg Salad Monstrosity.', $activityLog);

            $pet
                ->increaseFood($this->rng->rngNextInt(4, 8))
                ->increaseSafety(4)
                ->increaseEsteem(3)
            ;

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else if($this->rng->rngNextInt(1, $skill) >= 19)
        {
            $loot = [
                $this->rng->rngNextFromArray($possibleLoot),
                $this->rng->rngNextFromArray($possibleLoot),
            ];

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, and encountered an Egg Salad Monstrosity! After a grueling (and sticky) battle, ' . $pet->getName() . ' won, and claimed its ' . ArrayFunctions::list_nice_sorted($loot) . '!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
            ;

            foreach($loot as $itemName)
                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' collected this from the remains of an Egg Salad Monstrosity.', $activityLog);

            $pet->increaseSafety(4);
            $pet->increaseEsteem(3);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out hunting, and encountered an Egg Salad Monstrosity, which chased ' . $pet->getName() . ' away!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Hunting' ]))
            ;
            $pet->increaseSafety(-3);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::HUNT, false);
        }

        return $activityLog;
    }
}
