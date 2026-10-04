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
use App\Enum\ActivityPersonalityEnum;
use App\Enum\DistractionLocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\AdventureMath;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\UserQuestRepository;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\FieldGuideService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\TransactionService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;

class FishingService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly TransactionService $transactionService,
        private readonly IRandom $rng,
        private readonly FieldGuideService $fieldGuideService,
        private readonly GatheringDistractionService $gatheringDistractions,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'fishing'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();
        $desire = $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getFishingBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getNature() + $pet->getTool()->getItem()->getTool()->getFishing();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Fishing))
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
        $maxSkill = 5 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getFishingBonus()->getTotal() - (int)ceil(($pet->getAlcohol() + $pet->getPsychedelic()) / 2);

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 21);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        $activityLog = null;
        $changes = new PetChanges($pet);

        $fishedAMerchantFish = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Fished a Merchant Fish', false);

        if(!$fishedAMerchantFish->getValue() || $this->rng->rngNextInt(1, 100) === 1)
        {
            $fishedAMerchantFish->setValue(true);
            $activityLog = $this->fishedMerchantFish($pet);
        }

        if(!$activityLog)
        {
            $activityLog = $this->maybeGetLuckyFishBones($petWithSkills);
        }

        if(!$activityLog)
        {
            switch($roll)
            {
                case 1:
                    $activityLog = $this->failedToFish($pet);
                    break;
                case 2:
                case 3:
                case 4:
                    $activityLog = $this->fishedSmallLake($petWithSkills);
                    break;
                case 5:
                case 6:
                    $activityLog = $this->fishedUnderBridge($petWithSkills);
                    break;
                case 7:
                    $activityLog = $this->fishedRoadsideCreek($petWithSkills);
                    break;
                case 8:
                case 9:
                    $activityLog = $this->fishedWaterfallBasin($petWithSkills);
                    break;
                case 10:
                case 11:
                    $activityLog = $this->fishedPlazaFountain($petWithSkills, 0);
                    break;
                case 12:
                    if($this->rng->rngNextInt(1, 10) === 1)
                        $activityLog = $this->fishedSeaCucumber($petWithSkills);
                    else
                        $activityLog = $this->fishedFloodedPaddyField($petWithSkills);
                    break;
                case 13:
                    $activityLog = $this->fishedFoggyLake($petWithSkills);
                    break;
                case 14:
                case 15:
                    if($this->rng->rngNextInt(1, 50) === 1)
                        $activityLog = $this->fishedTheIsleOfRetreatingTeeth($pet);
                    else
                        $activityLog = $this->fishedGhoti($petWithSkills);
                    break;
                case 16:
                    $activityLog = $this->fishedCoralReef($petWithSkills);
                    break;
                case 17:
                    $activityLog = $this->fishedPlazaFountain($petWithSkills, 2);
                    break;
                case 18:
                    $activityLog = $this->fishedGallopingOctopus($petWithSkills);
                    break;
                case 19:
                    $activityLog = $this->fishedAlgae($petWithSkills);
                    break;
                case 20:
                case 21:
                default:
                    // @TODO
                    /*if($this->rng->rngNextInt(1, 50) === 1)
                        $activityLog = $this->fishedNarwhal($pet);
                    else*/
                        $activityLog = $this->fishedJellyfish($petWithSkills);
                    break;
            }
        }

        $activityLog->setChanges($changes->compare($pet));

        if(AdventureMath::petAttractsBug($this->rng, $pet, 75))
            $this->inventoryService->petAttractsRandomBug($pet);

        return $activityLog;
    }

    private function failedToFish(Pet $pet): PetActivityLog
    {
        if($pet->getOwner()->getGreenhouse() && $pet->getOwner()->getGreenhouse()->getHasBirdBath() && !$pet->getOwner()->getGreenhouse()->getVisitingBird())
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::FISH, false);

            $pet
                ->increaseSafety($this->rng->rngNextInt(1, 2))
                ->increaseEsteem($this->rng->rngNextInt(1, 2))
            ;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% couldn\'t find anything to fish, so watched some small birds play in the Greenhouse Bird Bath, instead.')
                ->setIcon('icons/activity-logs/birb')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Greenhouse' ]))
            ;

            if($pet->getSkills()->getNature() < 5)
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            return $activityLog;
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::FISH, false);

            return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to fish, but couldn\'t find a quiet place to do so.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
        }
    }

    private function creditLackOfReflection(PetActivityLog $activityLog): void
    {
        $hasNoReflection = $activityLog->getPet()->hasStatusEffect(StatusEffectEnum::Invisible) || $activityLog->getPet()->hasMerit(MeritEnum::NO_SHADOW_OR_REFLECTION);

        if($hasNoReflection && $this->rng->rngNextInt(1, 4) === 1)
            $activityLog->appendEntry('(Having no reflection is pretty useful!)');
    }

    private function nothingBiting(Pet $pet, int $percentChance, string $atLocationName): ?PetActivityLog
    {
        if($pet->hasStatusEffect(StatusEffectEnum::Invisible) || $pet->hasMerit(MeritEnum::NO_SHADOW_OR_REFLECTION))
            $percentChance /= 2;

        if($this->rng->rngNextInt(1, 100) <= $percentChance)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing ' . $atLocationName . ', but nothing was biting.')
                ->setIcon('icons/activity-logs/nothing-biting')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            return $activityLog;
        }

        return null;
    }

    private function fishedMerchantFish(Pet $pet): PetActivityLog
    {
        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Stream, and caught a Fish... but wait: that\'s no ordinary Fish...')
            ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                PetActivityLogTagEnum::Fishing,
                PetActivityLogTagEnum::Location_Stream,
            ]))
        ;

        $this->inventoryService->petCollectsItem('Merchant Fish', $pet, $pet->getName() . ' fished this out of a Stream.', $activityLog);

        $pet->increaseEsteem(2);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

        return $activityLog;
    }

    private function fishedSmallLake(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 20, 'at a Small Lake');
        if($nothingBiting !== null) return $nothingBiting;

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 5)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Small Lake, and caught a Mini Minnow.')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Fishing,
                    PetActivityLogTagEnum::Location_Small_Lake
                ]))
            ;

            $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Mini Minnow that ' . $pet->getName() . ' fished at a Small Lake.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);
        }
        else if($this->rng->rngNextInt(1, 15) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Small Lake, but nothing was biting, so ' . $pet->getName() . ' grabbed some Silica Grounds, instead.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Fishing,
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Small_Lake
                ]))
            ;
            $this->inventoryService->petCollectsItem('Silica Grounds', $pet, $pet->getName() . ' took this from a Small Lake.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Small Lake, and almost caught a Mini Minnow, but it got away.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Fishing,
                    PetActivityLogTagEnum::Location_Small_Lake
                ]))
            ;

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedUnderBridge(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 15, 'Under a Bridge');
        if($nothingBiting !== null) return $nothingBiting;

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 6)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing Under a Bridge, and caught a Muscly Trout.')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Fishing,
                    PetActivityLogTagEnum::Location_Under_a_Bridge,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Muscly Trout that ' . $pet->getName() . ' fished Under a Bridge.', $activityLog);

            if($this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal()) >= 15)
                $this->inventoryService->petCollectsItem('Scales', $pet, 'From a Muscly Trout that ' . $pet->getName() . ' fished Under a Bridge.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
        }
        else if($this->rng->rngNextInt(1, 4) === 1)
        {
            if($this->rng->rngNextBool())
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing Under a Bridge, but all they got was an old can of food...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        PetActivityLogTagEnum::Location_Under_a_Bridge,
                    ]))
                ;

                $this->inventoryService->petCollectsItem('Canned Food', $pet, $pet->getName() . ' fished this out of a river under a bridge...', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing Under a Bridge, but all they got was an old bottle of... something!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        PetActivityLogTagEnum::Location_Under_a_Bridge,
                    ]))
                ;

                $this->inventoryService->petCollectsItem('Plastic Bottle', $pet, $pet->getName() . ' fished this out of a river under a bridge...', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing Under a Bridge, and almost caught a Muscly Trout, but it got away.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Fishing,
                    PetActivityLogTagEnum::Location_Under_a_Bridge,
                ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
        }

        return $activityLog;
    }

    private function fishedGallopingOctopus(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Beach, [ PetActivityLogTagEnum::Fishing ], 'looking for a good fishing spot on the beach');

        $pet = $petWithSkills->getPet();
        $fightSkill = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getFishingBonus()->getTotal() + $petWithSkills->getBrawl(false)->getTotal() + $petWithSkills->getStrength()->getTotal());

        if($fightSkill <= 3)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing, and started to reel something in, only to realize it was a huge Galloping Octopus! ' . $pet->getName() . ' was caught unawares, and took a tentacle slap to the face before running away! :(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Fighting' ]))
            ;
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::HUNT, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $pet
                ->increaseSafety(-$this->rng->rngNextInt(4, 8))
                ->increaseEsteem(-$this->rng->rngNextInt(2, 4))
            ;
        }
        else if($fightSkill >= 18)
        {
            if($this->rng->rngNextInt(1, 2) === 1)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing, and started to reel something in, only to realize it was a huge Galloping Octopus! ' . $pet->getName() . ' beat the creature back into the sea, but not before discerping one of its Tentacles!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 18)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Tentacle', $pet, $pet->getName() . ' received this from a fight with a Galloping Octopus.', $activityLog);

                $pet
                    ->increaseSafety($this->rng->rngNextInt(1, 4))
                    ->increaseEsteem($this->rng->rngNextInt(1, 4))
                ;
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::HUNT, true);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing, and started to reel something in, only to realize it was a huge Galloping Octopus! ' . $pet->getName() . ' beat the creature back into the sea, but not before discerping two of its Tentacles!')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 18)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Tentacle', $pet, $pet->getName() . ' received this from a fight with a Galloping Octopus.', $activityLog);
                $this->inventoryService->petCollectsItem('Tentacle', $pet, $pet->getName() . ' received this from a fight with a Galloping Octopus.', $activityLog);

                $pet
                    ->increaseSafety($this->rng->rngNextInt(1, 4))
                    ->increaseEsteem($this->rng->rngNextInt(2, 6))
                ;
            }

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Brawl ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing, and started to reel something in, only to realize it was a huge Galloping Octopus! The two tussled for a while before breaking apart and cautiously retreating...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Fighting' ]))
            ;
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, false);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedAlgae(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 15, 'still-water pond');
        if($nothingBiting !== null) return $nothingBiting;

        $fishingSkill = $this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getFishingBonus()->getTotal() + $petWithSkills->getNature()->getTotal());

        if($fishingSkill >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a still-water pond. There weren\'t any fish, but there was some Algae!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            $this->inventoryService->petCollectsItem('Algae', $pet, $pet->getName() . ' "fished" this from a still-water pond.', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(1, 4));

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $message = $this->rng->rngNextFromArray([
                'They saw a snail once, but that was about it. (And it was one of those crazy-poisonous types of snails! Ugh!)',
                'The most exciting thing that happened was that they got briefly stuck in the mud :|',
                'They almost caught something, but a bird swooped in and got it, first! >:(',
                'After over half an hour of nothing, they gave up out of sheer boredom >_>',
                'Nothing was biting, but at least it was relaxing??',
            ]);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried fishing at a still-water pond. ' . $message)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedRoadsideCreek(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $toadChance = WeatherService::getWeather(new \DateTimeImmutable())->isRaining() ? 100 : 34;

        $nothingBiting = $this->nothingBiting($pet, 20, 'at a Roadside Creek');
        if($nothingBiting !== null) return $nothingBiting;

        if($this->rng->rngNextInt(1, 100) <= $toadChance)
        {
            $discoveredHugeToad = '%pet:' . $pet->getId() . '.name% went fishing at a Roadside Creek, and a Huge Toad bit the line!';

            // toad
            if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 7)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveredHugeToad . ' ' . $pet->getName() . ' used all their strength to reel it in!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        //PetActivityLogTagEnum::Location_Roadside_Creek,
                    ]))
                ;
                $this->inventoryService->petCollectsItem('Toad Legs', $pet, 'From a Huge Toad that ' . $pet->getName() . ' fished at a Roadside Creek.', $activityLog);

                if($this->rng->rngSkillRoll($petWithSkills->getNature()->getTotal()) >= 15)
                    $this->inventoryService->petCollectsItem('Toadstool', $pet, 'From a Huge Toad that ' . $pet->getName() . ' fished at a Roadside Creek.', $activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

                $this->creditLackOfReflection($activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveredHugeToad . ' ' . $pet->getName() . ' tried to reel it in, but it was too strong, and got away.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        //PetActivityLogTagEnum::Location_Roadside_Creek,
                    ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, false);
            }

            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Huge Toad', $discoveredHugeToad);
        }
        else
        {
            // singing fish
            if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 6)
            {
                $gotMusic = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getMusic()->getTotal()) >= 10;

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Roadside Creek, and caught a Singing Fish!')
                    ->setIcon($gotMusic ? 'items/music/note' : 'items/tool/fishing-rod/crooked')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        //PetActivityLogTagEnum::Location_Roadside_Creek,
                    ]))
                ;
                $this->inventoryService->petCollectsItem($this->rng->rngNextInt(1, 2) === 1 ? 'Plastic' : 'Fish', $pet, 'From a Singing Fish that ' . $pet->getName() . ' fished at a Roadside Creek.', $activityLog);

                if($gotMusic)
                    $this->inventoryService->petCollectsItem('Music Note', $pet, 'From a Singing Fish that ' . $pet->getName() . ' fished at a Roadside Creek.', $activityLog);

                $this->creditLackOfReflection($activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::FISH, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Roadside Creek, and almost caught a Singing Fish, but it got away.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        PetActivityLogTagEnum::Fishing,
                        //PetActivityLogTagEnum::Location_Roadside_Creek,
                    ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::FISH, false);
            }
        }

        return $activityLog;
    }

    private function maybeGetLuckyFishBones(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        if($this->rng->rngNextInt(1, 200) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out fishing, and reeled in... some Fish Bones!? (Lucky~??)')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Lucky~!' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->inventoryService->petCollectsItem('Fish Bones', $pet, $pet->getName() . ' was out fishing, and one of these got caught on the line!? (Lucky~??)', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, true);

            return $activityLog;
        }
        else if($this->rng->rngNextInt(1, 200) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out fishing, and reeled in... some Fish Bones!?')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->inventoryService->petCollectsItem('Fish Bones', $pet, $pet->getName() . ' was out fishing, and one of these got caught on the line!?', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, true);

            return $activityLog;
        }
        else
            return null;
    }

    private function fishedWaterfallBasin(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, [ PetActivityLogTagEnum::Fishing ], 'looking for a good fishing spot in the woods');

        $pet = $petWithSkills->getPet();

        if($this->rng->rngNextInt(1, 80) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Waterfall Basin, and reeled in a Little Strongbox! Lucky~!')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Lucky~!' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->inventoryService->petCollectsItem('Little Strongbox', $pet, $pet->getName() . ' was fishing in a Waterfall Basin, and one of these got caught on the line! Lucky~!', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, true);
        }
        else if($this->rng->rngNextInt(1, 80) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Waterfall Basin, and reeled in a Little Strongbox!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->inventoryService->petCollectsItem('Little Strongbox', $pet, $pet->getName() . ' was fishing in a Waterfall Basin, and one of these got caught on the line!', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::FISH, true);
        }
        else if($this->rng->rngNextInt(1, 5) === 1)
        {
            if($this->rng->rngNextInt(1, 2) === 1 && $pet->hasMerit(MeritEnum::SOOTHING_VOICE))
            {
                if($pet->hasStatusEffect(StatusEffectEnum::BittenByAVampire) || ($pet->getTool() && $pet->getTool()->isGrayscaling()))
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Waterfall Basin. There, ' . $pet->getName() . '\'s humming caught the attention of a mermaid! However, the mermaid saw ' . ActivityHelpers::PetName($pet) . '\'s ghastly appearance, gasped, and swam away as quickly as they could!')
                        ->setIcon('icons/status-effect/bite-vampire')
                        ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                    ;
                    $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Music, PetSkillEnum::Arcana ], $activityLog);

                    $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, false);
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Waterfall Basin. There, ' . $pet->getName() . '\'s humming caught the attention of a mermaid, who became fascinated by ' . $pet->getName() . '\'s Soothing Voice. After listening for a while, she gave ' . $pet->getName() . ' a Basket of Fish, and left.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                    ;
                    $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Music ], $activityLog);
                    $this->inventoryService->petCollectsItem('Basket of Fish', $pet, $pet->getName() . ' received this from a Waterfall Basin mermaid who was enchanted by ' . $pet->getName() . '\'s Soothing Voice.', $activityLog);

                    $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, true);
                }
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Waterfall Basin, and reeled in a Mermaid Egg!')
                    ->setIcon('items/animal/egg-mermaid')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
                $this->inventoryService->petCollectsItem('Mermaid Egg', $pet, $pet->getName() . ' was fishing in a Waterfall Basin, and one of these got caught on the line!', $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, true);
            }
        }
        else
        {
            if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 7)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing in a Waterfall Basin, and caught a Medium Minnow.')
                    ->setIcon('items/tool/fishing-rod/crooked')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

                $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Medium Minnow that ' . $pet->getName() . ' fished in a Waterfall Basin.', $activityLog);

                if($this->rng->rngSkillRoll($petWithSkills->getNature()->getTotal()) >= 10)
                    $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Medium Minnow that ' . $pet->getName() . ' fished in a Waterfall Basin.', $activityLog);

                $this->creditLackOfReflection($activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing in a Waterfall Basin, and almost caught a Medium Minnow, but it got away.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
            }
        }

        return $activityLog;
    }

    private function fishedPlazaFountain(ComputedPetSkills $petWithSkills, int $bonusMoney): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 5) == 1)
            return $this->fishedPlazaFountainAndGotInFightWithMagpie($petWithSkills, $bonusMoney);

        $pet = $petWithSkills->getPet();

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 10)
            $bonusMoney += $this->rng->rngNextInt(1, 3);

        if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 7) === 1)
        {
            $moneys = $this->rng->rngNextInt(10, 15) + $bonusMoney;
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% fished around in the Plaza fountain while no one was looking, and grabbed ' . $moneys . ' moneys! Lucky~!')
                ->setIcon('icons/activity-logs/moneys')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Stealth', 'Moneys', 'Lucky~!' ]))
            ;
        }
        else
        {
            $moneys = $this->rng->rngNextInt(2, 9) + $bonusMoney;
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% fished around in the Plaza fountain while no one was looking, and grabbed ' . $moneys . ' moneys.')
                ->setIcon('icons/activity-logs/moneys')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Stealth', 'Moneys' ]))
            ;
        }

        if($this->rng->rngNextInt(1, 20) === 1)
            $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' fished this out of the Plaza fountain while no one was looking. (That seems like it shouldn\'t be allowed...)');
        else
            $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' fished this out of the Plaza fountain while no one was looking.');

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, true);

        return $activityLog;
    }

    private function fishedPlazaFountainAndGotInFightWithMagpie(ComputedPetSkills $petWithSkills, int $bonusMoney): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getBrawl()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 10)
            $bonusMoney += $this->rng->rngNextInt(1, 3);

        $moneys = $this->rng->rngNextInt(2, 9) + $bonusMoney;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started fishing around in the Plaza fountain, and got ambushed by a Thieving Magpie! They fought the creature off, and took its ' . $moneys . ' moneys.')
            ->setIcon('icons/activity-logs/moneys')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Fighting', 'Moneys' ]))
        ;

        $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' took this from a Thieving Magpie that attacked them while they were fishing in the Plaza fountain.');

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::FISH, true);

        return $activityLog;
    }

    private function fishedSeaCucumber(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 15)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing on some rocks in the ocean, and caught a sea Cucumber.')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->inventoryService->petCollectsItem('Cucumber', $pet, 'A sea Cucumber that ' . $pet->getName() . ' fished up.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing on some rocks in the ocean, and pulled up some Seaweed.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            $this->inventoryService->petCollectsItem('Seaweed', $pet, 'Some Seaweed that ' . $pet->getName() . ' fished up.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedFloodedPaddyField(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 20, 'at a Flooded Paddy Field');
        if($nothingBiting !== null) return $nothingBiting;

        $foundRice = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 15;
        $foundNonLa = $foundRice && ($this->rng->rngNextInt(1, 35) === 1);

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 10)
        {
            if($foundRice)
            {
                if($foundNonLa)
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Flooded Paddy Field, caught a Crawfish, picked some Rice, and found a Nón Lá!');
                else
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Flooded Paddy Field, caught a Crawfish, and picked some Rice!');

                $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Gathering' ]));
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Flooded Paddy Field, and caught a Crawfish.')
                    ->setIcon('items/tool/fishing-rod/crooked')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
            }

            $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Crawfish that ' . $pet->getName() . ' fished at a Flooded Paddy Field.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
        }
        else
        {
            if($foundRice)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Flooded Paddy Field, and almost caught a Crawfish, but it got away. There was plenty of Rice, around, though, so ' . $pet->getName() . ' grabbed some of that.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Gathering' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Flooded Paddy Field, and almost caught a Crawfish, but it got away.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
        }

        if($foundRice)
        {
            if($foundNonLa)
                $this->inventoryService->petCollectsItem('Nón Lá', $pet, $pet->getName() . ' found this at a Flooded Paddy Field while fishing.', $activityLog);

            $this->inventoryService->petCollectsItem('Rice', $pet, $pet->getName() . ' found this at a Flooded Paddy Field while fishing.', $activityLog);
            $this->petExperienceService->gainExp($pet, $foundNonLa ? 2 : 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedTheIsleOfRetreatingTeeth(Pet $pet): PetActivityLog
    {
        $alsoGetFishBones = $this->rng->rngNextBool();

        $message = $alsoGetFishBones
            ? '%pet:' . $pet->getId() . '.name% went fishing at The Isle of Retreating Teeth, but only caught Fish _Bones_! Including some Talo-- er, I mean, teeth. Fish teeth.'
            : '%pet:' . $pet->getId() . '.name% went fishing at The Isle of Retreating Teeth. They weren\'t able to catch anything, but they did grab some Talo-- er, I mean, teeth. Fish teeth.'
        ;

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Gathering' ]))
        ;

        $this->inventoryService->petCollectsItem('Talon', $pet, $pet->getName() . ' got this from The Isle of Retreating Teeth.', $activityLog);
        $this->inventoryService->petCollectsItem('Talon', $pet, $pet->getName() . ' got this from The Isle of Retreating Teeth.', $activityLog);

        if($alsoGetFishBones)
            $this->inventoryService->petCollectsItem('Fish Bones', $pet, $pet->getName() . ' got this from The Isle of Retreating Teeth.', $activityLog);

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::FishedAtTheIsleOfRetreatingTeeth, $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);

        $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

        return $activityLog;
    }

    private function fishedFoggyLake(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 20, 'at a Foggy Lake');
        if($nothingBiting !== null) return $nothingBiting;

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 5)
        {
            if($this->rng->rngNextInt(1, 4) === 1)
            {
                if($this->rng->rngSkillRoll($petWithSkills->getArcana()->getTotal() + $petWithSkills->getIntelligence()->getTotal()) >= 15)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Foggy Lake, caught a Ghost Fish, and harvested Quintessence from it.')
                        ->setIcon('items/resource/quintessence')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                    ;
                    $this->inventoryService->petCollectsItem('Quintessence', $pet, 'From a Ghost Fish that ' . $pet->getName() . ' fished at a Foggy Lake.', $activityLog);
                    $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Arcana ], $activityLog);
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Foggy Lake, and caught a Ghost Fish, but failed to harvest any Quintessence from it.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                    ;
                    $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Arcana ], $activityLog);
                }
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Foggy Lake, and caught a Mung Fish.')
                    ->setIcon('items/tool/fishing-rod/crooked')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
                $this->inventoryService->petCollectsItem('Beans', $pet, $pet->getName() . ' got this from a Mung Fish at a Foggy Lake.', $activityLog);
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            }

            $this->creditLackOfReflection($activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
        }
        else
        {
            if($this->rng->rngNextInt(1, 15) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Foggy Lake, but nothing was biting, so ' . $pet->getName() . ' grabbed some Silica Grounds, instead.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Gathering' ]))
                ;
                $this->inventoryService->petCollectsItem('Silica Grounds', $pet, $pet->getName() . ' took this from a Foggy Lake.', $activityLog);
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at a Foggy Lake, and almost caught something, but it got away.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            }

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
        }

        return $activityLog;
    }

    public function fishedGhoti(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Volcano, [ PetActivityLogTagEnum::Fishing ], 'looking for a good fishing spot at the foot of the volcano');

        $pet = $petWithSkills->getPet();

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Île Volcan', '%pet:' . $pet->getId() . '.name% went fishing at the foot of the Volcano.');

        if($this->rng->rngNextInt(1, 100) === 1 || ($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 100) === 1))
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at the foot of the Volcano; nothing was biting, but ' . $pet->getName() . ' found a piece of Firestone while they were out!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing', 'Gathering' ]))
            ;
            $this->inventoryService->petCollectsItem('Firestone', $pet, $pet->getName() . ' found this at the foot of the Volcano.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }
        else if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 10)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at the foot of the Volcano, and caught a Ghoti!')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->inventoryService->petCollectsItem('Fish', $pet, 'From a Ghoti that ' . $pet->getName() . ' fished at the foot of the Volcano.', $activityLog);

            $extraItem = $this->rng->rngNextFromArray([ 'Fish', 'Scales', 'Oil' ]);

            $this->inventoryService->petCollectsItem($extraItem, $pet, 'From a Ghoti that ' . $pet->getName() . ' fished at the foot of the Volcano.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing at the foot of the Volcano, and almost caught a Ghoti, but it got away.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    public function fishedCoralReef(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Beach, [ PetActivityLogTagEnum::Fishing ], 'looking for a good fishing spot on the beach');

        $pet = $petWithSkills->getPet();

        // no chance of nothing biting at the coral reef!

        $possibleItems = [
            'Fish',
            'Fish',
            'Fish',
            'Seaweed',
            'Seaweed',
            'Silica Grounds',
            'Sand Dollar',
            'Scales',
            'Iron Ore',
            'Silver Ore',
        ];

        $isLucky = $pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 50) === 1;

        $discoveryText = ActivityHelpers::PetName($pet) . ' went fishing at the Coral Reef';

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Coral Reef', $discoveryText . '.');

        if($isLucky || $this->rng->rngNextInt(1, 50) === 1)
        {
            $item = $this->rng->rngNextFromArray([
                'Gold Bar', 'Very Strongbox', 'Rusty Rapier',
            ]);

            $luckyText = $isLucky ? ' Lucky~!' : '';

            $tags = [ 'Fishing' ];
            if($isLucky) $tags[] = 'Lucky~!';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveryText . ', and spotted a ' . $item . '!' . $luckyText)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
            ;
            $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' found this at the Coral Reef!' . $luckyText, $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }
        else if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 24)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveryText . ', and caught all kinds of stuff!')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            for($x = 0; $x < 3; $x++)
                $this->inventoryService->petCollectsItem($this->rng->rngNextFromArray($possibleItems), $pet, $pet->getName() . ' got this while fishing at the Coral Reef.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Nature ], $activityLog);
        }
        else if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 12)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveryText . ', and caught a couple things!')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;

            for($x = 0; $x < 2; $x++)
                $this->inventoryService->petCollectsItem($this->rng->rngNextFromArray($possibleItems), $pet, $pet->getName() . ' got this while fishing at the Coral Reef.', $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            if($this->rng->rngNextInt(1, 2) === 1)
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveryText . ', but there were a bunch of Hammerheads around.');
            else
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $discoveryText . ', but there were a bunch of Jellyfish around.');

            $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]));

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }

        return $activityLog;
    }

    private function fishedJellyfish(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Beach, [ PetActivityLogTagEnum::Fishing ], 'looking for a good fishing spot on the beach');

        $pet = $petWithSkills->getPet();

        $nothingBiting = $this->nothingBiting($pet, 20, 'way out on the pier');
        if($nothingBiting !== null) return $nothingBiting;

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getFishingBonus()->getTotal()) >= 12)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing way out on the pier, and caught a Jellyfish.')
                ->setIcon('items/tool/fishing-rod/crooked')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->inventoryService->petCollectsItem('Jellyfish Jelly', $pet, $pet->getName() . ' got this from a Jellyfish they caught way out on the pier.', $activityLog);

            if($this->rng->rngNextInt(1, 2) === 1)
                $this->inventoryService->petCollectsItem('Tentacle', $pet, $pet->getName() . ' got this from a Jellyfish they caught way out on the pier.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);

            $this->creditLackOfReflection($activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went fishing way out on the pier, and pulled up a Jellyfish, but it stung ' . $pet->getName() . ', and got away!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fishing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $pet->increaseSafety(-$this->rng->rngNextInt(4, 8));

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::FISH, false);
        }

        return $activityLog;
    }
}
