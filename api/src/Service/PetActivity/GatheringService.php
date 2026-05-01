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
use App\Entity\PetSpecies;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\DistractionLocationEnum;
use App\Enum\FlavorEnum;
use App\Enum\MeritEnum;
use App\Enum\MoonPhaseEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetLocationEnum;
use App\Enum\PetSkillEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Functions\ActivityHelpers;
use App\Functions\AdventureMath;
use App\Functions\ArrayFunctions;
use App\Functions\ColorFunctions;
use App\Functions\DateFunctions;
use App\Functions\EnchantmentRepository;
use App\Functions\ItemRepository;
use App\Functions\MeritRepository;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetRepository;
use App\Functions\SpiceRepository;
use App\Functions\UserQuestRepository;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\Clock;
use App\Service\FieldGuideService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\PetFactory;
use App\Service\ResponseService;
use App\Service\TransactionService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;

class GatheringService implements IPetActivity
{
    public function __construct(
        private readonly ResponseService $responseService,
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly TransactionService $transactionService,
        private readonly IRandom $rng,
        private readonly FieldGuideService $fieldGuideService,
        private readonly EntityManagerInterface $em,
        private readonly PetFactory $petFactory,
        private readonly GatheringDistractionService $gatheringDistractions,
        private readonly Clock $clock,
        private readonly WildHedgeMazeService $wildHedgeMaze,
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'gathering'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();
        $desire = $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getNature() + $pet->getTool()->getItem()->getTool()->getGathering();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Gathering))
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
        $maxSkill = 10 + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() - $pet->getAlcohol() - $pet->getPsychedelic();

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 24);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        $changes = new PetChanges($pet);

        switch($roll)
        {
            case 1:
            case 2:
            case 3:
            case 4:
                $activityLog = $this->foundNothing($pet);
                break;
            case 5:
                $activityLog = $this->foundPaperBag($pet);
                break;
            case 6:
                $activityLog = $this->foundTeaBush($pet);
                break;
            case 7:
            case 8:
                $activityLog = $this->foundBerryBush($petWithSkills);
                break;
            case 9:
            case 10:
                $activityLog = $this->foundHollowLog($petWithSkills);
                break;
            case 11:
                $activityLog = $this->foundAbandonedQuarry($petWithSkills);
                break;
            case 12:
                $activityLog = $this->foundBirdNest($petWithSkills);
                break;
            case 13:
                $activityLog = $this->foundBeach($petWithSkills);
                break;
            case 14:
                $activityLog = $this->foundOvergrownGarden($petWithSkills);
                break;
            case 15:
                $activityLog = $this->foundIronMine($petWithSkills);
                break;
            case 16:
                $activityLog = $this->foundMicroJungle($petWithSkills);
                break;
            case 17:
            case 18:
                $activityLog = $this->wildHedgeMaze->exploreHedgeMaze($petWithSkills);
                break;
            case 19:
            case 20:
                $activityLog = $this->foundVolcano($petWithSkills);
                break;
            case 21:
                $activityLog = $this->foundGypsumCave($petWithSkills);
                break;
            case 22:
            case 23:
                $activityLog = $this->foundDeepMicroJungle($petWithSkills);
                break;
            case 24:
            default:
                if($this->fieldGuideService->hasUnlocked($pet->getOwner(), 'Île Volcan'))
                    $activityLog = $this->foundOldSettlement($petWithSkills);
                else if($this->rng->rngNextBool())
                    $activityLog = $this->foundMicroJungle($petWithSkills);
                else
                    $activityLog = $this->foundAbandonedQuarry($petWithSkills);
                break;
        }

        $activityLog->setChanges($changes->compare($pet));

        if(AdventureMath::petAttractsBug($this->rng, $pet, 75))
            $this->inventoryService->petAttractsRandomBug($pet);

        return $activityLog;
    }

    private function foundAbandonedQuarry(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $pobosFound = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Pobos Found', 0);
        $poboChance = 150 + (int)(200 * log10($pobosFound->getValue() + 1));

        if($this->rng->rngNextInt(1, 2000) < $petWithSkills->getPerception()->getTotal())
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to an Abandoned Quarry, and happened to find a piece of Striped Microcline!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Abandoned_Quarry
                ]))
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->inventoryService->petCollectsItem('Striped Microcline', $pet, $pet->getName() . ' found this at an Abandoned Quarry.', $activityLog);
            $pet->increaseEsteem(4);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, true);
        }
        else if($this->rng->rngNextInt(1, $poboChance) === 1)
        {
            $newPetInfo = $this->rng->rngNextFromArray([
                [
                    'Species' => 'Pobo',
                    'FindDescription' => '%pet:' . $pet->getId() . '.name% went to an Abandoned Quarry, and happened to find a Stereotypical Bone! But when they picked it up, it began to move on its own! IT\'S POSSESSED!',
                    'ColorB' => 'ece8d0',
                ],
                [
                    'Species' => 'Catacomb Spirit',
                    'FindDescription' => '%pet:' . $pet->getId() . '.name% went to an Abandoned Quarry, and stumbled upon an ancient catacomb! As they were exploring, looking for treasure, a spirit rose from the bones and began to follow them!',
                    'ColorB' => ColorFunctions::HSL2Hex($this->rng->rngNextFloat(), 0.85, 0.5),
                ]
            ]);

            $pobosFound->setValue($pobosFound->getValue() + 1);
            $newPetSpecies = $this->em->getRepository(PetSpecies::class)->findOneBy([ 'name' => $newPetInfo['Species'] ]);

            $newPetName = $this->rng->rngNextFromArray([
                'Flit', 'Waverly', 'Mirage', 'Shadow', 'Calcium',
                'Kneecap', 'Osteal', 'Papyrus', 'Quint', 'Debris'
            ]);

            $colorA = ColorFunctions::HSL2Hex($this->rng->rngNextFloat(), 0.62, 0.53);

            $newPet = $this->petFactory->createPet(
                $pet->getOwner(), $newPetName, $newPetSpecies,
                $colorA, $newPetInfo['ColorB'],
                $this->rng->rngNextFromArray(FlavorEnum::cases()),
                MeritRepository::findOneByName($this->em, MeritEnum::NO_SHADOW_OR_REFLECTION)
            );

            $newPet
                ->increaseLove(-8)
                ->increaseSafety(10)
                ->increaseEsteem(-8)
                ->increaseFood(10)
                ->setScale($this->rng->rngNextInt(80, 120))
            ;

            $numberOfPetsAtHome = PetRepository::getNumberAtHome($this->em, $pet->getOwner());

            $petJoinsHouse = $numberOfPetsAtHome < $pet->getOwner()->getMaxPets();

            $extraMessage = 'It followed %pet:' . $pet->getId() . '.name% home';

            if($petJoinsHouse)
            {
                $extraMessage .= ', and made itself - well - at home!';
            }
            else
            {
                $newPet->setLocation(PetLocationEnum::DAYCARE);
                $extraMessage .= ', but upon seeing the house was full, wafted off to the Daycare.';
            }

            $this->responseService->setReloadPets($petJoinsHouse);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $newPetInfo['FindDescription'] . ' ' . $extraMessage)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Abandoned_Quarry
                ]))
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Arcana ], $activityLog);
            $pet->increaseSafety(-4);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }
        else if($this->rng->rngNextInt(1, 150) === 1)
        {
            $bone = $this->rng->rngNextFromArray([ 'Rib', 'Stereotypical Bone' ]);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to an Abandoned Quarry, and happened to find a ' . $bone . '!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Abandoned_Quarry
                ]))
            ;

            $this->inventoryService->petCollectsItem($bone, $pet, $pet->getName() . ' found this at an Abandoned Quarry!', $activityLog);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Science ], $activityLog);
            $pet->increaseEsteem(4);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }
        else if($petWithSkills->getStrength()->getTotal() < 4)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a huge block of Limestone at an Abandoned Quarry, and, with all their might, pushed, dragged, and "rolled" it home.')
                ->setIcon('items/mineral/limestone')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Abandoned_Quarry
                ]))
            ;
            $pet->increaseFood(-2);
            $this->inventoryService->petCollectsItem('Limestone', $pet, $pet->getName() . ' found this at an Abandoned Quarry. It was really heavy!', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a huge block of Limestone at an Abandoned Quarry, and carried it home.')
                ->setIcon('items/mineral/limestone')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Abandoned_Quarry
                ]))
            ;
            $this->inventoryService->petCollectsItem('Limestone', $pet, $pet->getName() . ' found this at an Abandoned Quarry.', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }

        return $activityLog;
    }

    private function foundNothing(Pet $pet): PetActivityLog
    {
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, false);

        return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went out gathering, but couldn\'t find anything.')
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                PetActivityLogTagEnum::Gathering
            ]))
        ;
    }

    private function foundPaperBag(Pet $pet): PetActivityLog
    {
        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a Paper Bag just, like, lyin\' around.')
            ->setIcon('items/bag/paper')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                PetActivityLogTagEnum::Gathering,
                PetActivityLogTagEnum::Location_Neighborhood
            ]))
        ;

        $this->inventoryService->petCollectsItem('Paper Bag', $pet, $pet->getName() . ' found this just lyin\' around.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundTeaBush(Pet $pet): PetActivityLog
    {
        if(WeatherService::getWeather(new \DateTimeImmutable())->isRaining() && $this->rng->rngNextInt(1, 4) === 1)
        {
            $message = '%pet:' . $pet->getId() . '.name% found a Tea Bush, and grabbed a few Tea Leaves, as well as some Worms which had surfaced to escape the rain.';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
                ->setIcon('items/veggie/tea-leaves')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Rain,
                    PetActivityLogTagEnum::Location_Micro_Jungle,
                ]))
            ;

            $this->inventoryService->petCollectsItem('Tea Leaves', $pet, $pet->getName() . ' harvested this from a Tea Bush.', $activityLog);
            $this->inventoryService->petCollectsItem('Worms', $pet, $pet->getName() . ' found these under a Tea Bush.', $activityLog);
        }
        else
        {
            $message = '%pet:' . $pet->getId() . '.name% found a Tea Bush, and grabbed a few Tea Leaves.';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
                ->setIcon('items/veggie/tea-leaves')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Gathering,
                    PetActivityLogTagEnum::Location_Micro_Jungle,
                ]))
            ;

            $this->inventoryService->petCollectsItem('Tea Leaves', $pet, $pet->getName() . ' harvested this from a Tea Bush.', $activityLog);
            $this->inventoryService->petCollectsItem('Tea Leaves', $pet, $pet->getName() . ' harvested this from a Tea Bush.', $activityLog);

            if($this->rng->rngNextBool())
                $this->inventoryService->petCollectsItem('Tea Leaves', $pet, $pet->getName() . ' harvested this from a Tea Bush.', $activityLog);
        }

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundBerryBush(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $getPinecone = $this->clock->getMonthAndDay() > 1225 && $this->rng->rngNextInt(1, 3) === 1;

        if($this->rng->rngNextInt(1, 8) >= 6)
        {
            $harvest = 'Blueberries';
            $additionalHarvest = $this->rng->rngNextInt(1, 4) === 1;
        }
        else
        {
            $harvest = 'Blackberries';
            $additionalHarvest = $this->rng->rngNextInt(1, 3) === 1;
        }

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 10)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% harvested berries from a Thorny ' . $harvest . ' Bush.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
        }
        else
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% got scratched up harvesting berries from a Thorny ' . $harvest . ' Bush.')
                ->setIcon('icons/activity-logs/wounded')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
        }

        if($getPinecone)
        {
            $activityLog
                ->appendEntry('Hm-what? There was a Pinecone in the bush, too?!')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Special Event', 'Stocking Stuffing Season' ]))
            ;

            $this->inventoryService->petCollectsItem('Pinecone', $pet, $pet->getName() . ' found this in a Thorny ' . $harvest . ' Bush.', $activityLog);
        }

        $this->inventoryService->petCollectsItem($harvest, $pet, $pet->getName() . ' harvested these from a Thorny ' . $harvest . ' Bush.', $activityLog);

        if($additionalHarvest)
            $this->inventoryService->petCollectsItem($harvest, $pet, $pet->getName() . ' harvested these from a Thorny ' . $harvest . ' Bush.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);

        return $activityLog;
    }

    private function foundHollowLog(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'exploring the nearby woods');

        $pet = $petWithSkills->getPet();

        $toadChance = WeatherService::getWeather(new \DateTimeImmutable())->isRaining() ? 75 : 25;

        if($this->rng->rngNextInt(1, 100) <= $toadChance)
        {
            if($petWithSkills->getCanSeeInTheDark()->getTotal() <= 0)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a Hollow Log, but it was too dark inside to see anything.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        'Gathering', 'Dark',
                        PetActivityLogTagEnum::Location_Hollow_Log
                    ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, false);
            }
            else if($this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getStealth()->getTotal() + $petWithSkills->getBrawl(false)->getTotal()) >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'Using their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ', ' . ActivityHelpers::PetName($pet) . ' looked inside a Hollow Log, and found a Huge Toad! They got the jump on it, wrestled it to the ground, and claimed its Toadstool!')
                    ->setIcon('items/fungus/toadstool')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        'Gathering', 'Fighting', 'Dark', 'Stealth',
                        PetActivityLogTagEnum::Location_Hollow_Log
                    ]))
                ;
                $this->inventoryService->petCollectsItem('Toadstool', $pet, $pet->getName() . ' harvested this from the back of a Huge Toad found inside a Hollow Log.', $activityLog);
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Stealth, PetSkillEnum::Brawl ], $activityLog);
                $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

                $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Huge Toad', $activityLog->getEntry());
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'Using their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ', ' . ActivityHelpers::PetName($pet) . ' looked inside a Hollow Log, and found a Huge Toad, but it hopped into the woods, and ' . ActivityHelpers::PetName($pet) . ' lost sight of it!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        'Gathering', 'Fighting', 'Dark', 'Stealth',
                        PetActivityLogTagEnum::Location_Hollow_Log
                    ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Stealth, PetSkillEnum::Brawl ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::HUNT, true);

                $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Huge Toad', $activityLog->getEntry());
            }
        }
        else if($pet->hasMerit(MeritEnum::BEHATTED) && $this->rng->rngNextInt(1, 75) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'Resting on top of a Hollow Log, ' . ActivityHelpers::PetName($pet) . ' spotted a Red Bow! (Hot dang!)')
                ->setIcon('items/hat/bow-red')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    'Gathering',
                    PetActivityLogTagEnum::Location_Hollow_Log
                ]))
            ;
            $this->inventoryService->petCollectsItem('Red Bow', $pet, $pet->getName() . ' found this on top of a Hollow Log!', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $success = true;

            if($this->rng->rngNextBool())
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% broke a Crooked Stick off of a Hollow Log.')
                    ->setIcon('items/plant/stick-crooked')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                        'Gathering',
                        PetActivityLogTagEnum::Location_Hollow_Log
                    ]))
                ;
                $this->inventoryService->petCollectsItem('Crooked Stick', $pet, $pet->getName() . ' broke this off of a Hollow Log.', $activityLog);
            }
            else
            {
                if($petWithSkills->getCanSeeInTheDark()->getTotal() > 0)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'Using their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ', ' . ActivityHelpers::PetName($pet) . ' looked inside a Hollow Log, and found a Grandparoot!')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                            'Gathering', 'Dark',
                            PetActivityLogTagEnum::Location_Hollow_Log
                        ]))
                    ;
                    $this->inventoryService->petCollectsItem('Grandparoot', $pet, $pet->getName() . ' found this growing inside a Hollow Log.', $activityLog);
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a Hollow Log, but it was too dark inside to see anything.')
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                            'Gathering', 'Dark',
                            PetActivityLogTagEnum::Location_Hollow_Log
                        ]))
                    ;
                    $success = false;
                }
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, $success);
        }

        return $activityLog;
    }

    private function foundBirdNest(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'exploring the nearby woods');

        $pet = $petWithSkills->getPet();

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) >= 10)
        {
            $foundPinecone = $this->clock->getMonthAndDay() > 1225;

            if($foundPinecone)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% stole an Egg from a Bird Nest. Hm-what? There was a Pinecone up there, too!');
                $activityLog
                    ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Special Event', 'Stocking Stuffing Season' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% stole an Egg from a Bird Nest.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth' ]))
                ;
            }

            $this->inventoryService->petCollectsItem('Egg', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);

            if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal()) >= 10)
                $this->inventoryService->petCollectsItem('Fluff', $pet, $pet->getName() . ' stole this from a Bird Nest.', $activityLog);

            if($foundPinecone)
                $this->inventoryService->petCollectsItem('Pinecone', $pet, $pet->getName() . ' found this in a tree while stealing from a Bird Nest.', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Stealth ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            if($this->rng->rngSkillRoll($petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getBrawl()->getTotal()) >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to steal an Egg from a Bird Nest, was spotted by a parent bird, and was able to defeat it in combat!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;
                $this->inventoryService->petCollectsItem('Egg', $pet, $pet->getName() . ' stole this from a Bird Nest, after a fight.', $activityLog);
                $this->inventoryService->petCollectsItem('Fluff', $pet, $pet->getName() . ' stole this from a Bird Nest, after a fight.', $activityLog);
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature, PetSkillEnum::Stealth, PetSkillEnum::Brawl ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to steal an Egg from a Bird Nest, but was spotted by a parent bird, and chased off!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature, PetSkillEnum::Stealth, PetSkillEnum::Brawl ], $activityLog);
                $pet->increaseEsteem(-$this->rng->rngNextInt(1, 2));
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, false);
            }
        }

        return $activityLog;
    }

    private function foundBeach(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Beach, 'exploring the beach');

        $pet = $petWithSkills->getPet();

        $loot = [];
        $didWhat = 'found this at a Sandy Beach';

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) < 10)
        {
            $pet->increaseFood(-1);

            if($this->rng->rngNextInt(1, 20) + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal() >= 15)
            {
                $loot[] = $this->rng->rngNextFromArray([ 'Fish', 'Crooked Stick', 'Egg' ]);

                if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal()) >= 25)
                    $loot[] = $this->rng->rngNextFromArray([ 'Feathers', 'Talon' ]);

                $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to a Sandy Beach, but while looking around, was attacked by a Giant Seagull. ' . $pet->getName() . ' defeated the Giant Seagull, and took its ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;
                $didWhat = 'defeated a Giant Seagull at the Beach, and got this';

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, true);
            }
            else
            {
                $pet->increaseEsteem(-$this->rng->rngNextInt(1, 2));
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to a Sandy Beach, but was attacked and routed by a Giant Seagull.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, false);
            }
        }
        else
        {
            $possibleLoot = [
                'Scales', 'Silica Grounds', 'Seaweed', 'Coconut',
            ];

            $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($pet->getTool() && $pet->getTool()->fishingBonus() > 0)
                $loot[] = 'Fish';
            else if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal()) >= 15)
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($this->rng->rngNextInt(1, 20) == 1)
                $loot[] = 'Secret Seashell';

            if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 25)
            {
                $moneys = $this->rng->rngNextInt(4, 12);
                $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' found this on a Sandy Beach.');
                $lootList = $loot;
                $lootList[] = $moneys . '~~m~~';
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to a Sandy Beach, and stole ' . ArrayFunctions::list_nice_sorted($lootList) . ' while the seagulls weren\'t paying attention.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Moneys' ]))
                ;
                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Stealth, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::GATHER, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% went to a Sandy Beach, and stole ' . ArrayFunctions::list_nice_sorted($loot) . ' while the seagulls weren\'t paying attention.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
            }
        }

        foreach($loot as $itemName)
            $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' ' . $didWhat . '.', $activityLog);

        return $activityLog;
    }

    private function foundOvergrownGarden(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'exploring the woods');

        $pet = $petWithSkills->getPet();

        $possibleLoot = [
            'Carrot', 'Onion', 'Celery', 'Tomato', 'Beans',
            'Sweet Beet', 'Sweet Beet', 'Ginger', 'Rice Flower'
        ];

        if(WeatherService::getWeather(new \DateTimeImmutable())->isRaining())
            $possibleLoot[] = 'Worms';

        $loot = [];
        $didWhat = 'harvested this from an Overgrown Garden';

        if($pet->hasMerit(MeritEnum::BEHATTED))
        {
            $chanceToGetOrangeBow = 1 + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal();

            if($this->rng->rngNextInt(1, 100) <= $chanceToGetOrangeBow)
                $loot[] = 'Orange Bow';
        }

        if($this->rng->rngSkillRoll($petWithSkills->getStealth()->getTotal() + $petWithSkills->getDexterity()->getTotal()) < 10)
        {
            $pet->increaseFood(-1);

            if($this->rng->rngNextInt(1, 20) + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal() >= 15)
            {
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);

                if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 25)
                    $loot[] = $this->rng->rngNextFromArray($possibleLoot);

                if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 15)
                    $loot[] = 'Talon';

                $pet->increaseEsteem($this->rng->rngNextInt(1, 2));
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an Overgrown Garden, but while looking for food, was attacked by an Angry Mole. ' . $pet->getName() . ' defeated the Angry Mole, and took its ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                    ->setIcon('icons/activity-logs/overgrown-garden')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;
                $didWhat = 'defeated an Angry Mole in an Overgrown Garden, and got this';

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Stealth, PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, true);
            }
            else
            {
                $pet->increaseEsteem(-$this->rng->rngNextInt(1, 2));
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an Overgrown Garden, but, while looking for food, was attacked and routed by an Angry Mole.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth', 'Fighting' ]))
                ;
                $loot = [];

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Brawl, PetSkillEnum::Nature ], $activityLog);
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::HUNT, false);
            }
        }
        else
        {
            $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 15)
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal()) >= 25)
                $loot[] = $this->rng->rngNextFromArray([ 'Avocado', 'Red', 'Orange', 'Apricot', 'Yellowy Lime' ]);

            $lucky = false;

            if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 20) === 1)
            {
                $loot[] = 'Honeydont';
                $lucky = true;
            }
            else if($this->rng->rngNextInt(1, 100) == 1)
                $loot[] = 'Honeydont';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% snuck into an Overgrown Garden, and harvested ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                ->setIcon('icons/activity-logs/overgrown-garden')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering', 'Stealth' ]))
            ;

            if($lucky)
            {
                $activityLog
                    ->appendEntry('(Honeydont?! Lucky~!)')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Stealth, PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }

        foreach($loot as $itemName)
            $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' ' . $didWhat . '.', $activityLog);

        return $activityLog;
    }

    private function foundIronMine(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($petWithSkills->getCanSeeInTheDark()->getTotal() <= 0)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an Old Iron Mine, but all the ore must have been hidden deep inside, and ' . $pet->getName() . ' didn\'t have a light.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Mining, PetActivityLogTagEnum::Dark ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::GATHER, false);

            if($this->rng->rngNextInt(1, 20) + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() >= 15)
            {
                $activityLog->appendEntry('There were lots of Blackberry bushes around the mine entrance, so ' . ActivityHelpers::PetName($pet) . ' picked some of those instead.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Gathering ]));

                $this->petExperienceService->spendTime($pet, 15, PetActivityStatEnum::GATHER, true);
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

                $this->inventoryService->petCollectsItem('Blackberries', $pet, $pet->getName() . ' picked these from bushes near an Old Iron Mine.', $activityLog);
            }

            return $activityLog;
        }

        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Underground, 'exploring an iron mine');

        if($this->rng->rngNextInt(1, 20) + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() + $petWithSkills->getMiningBonus()->getTotal() >= 10)
        {
            $pet->increaseFood(-1);
            $tags = [ 'Mining', 'Dark' ];

            if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 20) === 1)
            {
                $pet->increaseEsteem(5);

                if($this->rng->rngNextBool())
                    $loot = 'Gold Ore';
                else
                    $loot = 'Silver Ore';

                $punctuation = '! Lucky~!';
                $tags[] = 'Lucky~!';
            }
            else if($this->rng->rngNextInt(1, 50) === 1)
            {
                $pet->increaseEsteem(5);
                $loot = 'Gold Ore';
                $punctuation = '!!';
            }
            else if($this->rng->rngNextInt(1, 10) === 1)
            {
                $pet->increaseEsteem(3);
                $loot = 'Silver Ore';
                $punctuation = '!';
            }
            else
            {
                $pet->increaseEsteem(1);
                $loot = 'Iron Ore';
                $punctuation = '.';
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an Old Iron Mine. It was dark, but thanks to their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ', they easily dug up some ' . $loot . $punctuation)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' dug this out of an Old Iron Mine' . $punctuation, $activityLog);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $pet->increaseFood(-2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found an Old Iron Mine. It was dark, but despite their ' . ActivityHelpers::SourceOfLight($petWithSkills) . ', they were unable to dig anything up before getting tired out.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Mining', 'Dark' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, false);
        }

        if($this->rng->rngNextInt(1, 20) + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() >= 15)
        {
            $activityLog->appendEntry('Also, there were lots of Blackberry bushes around the mine entrance, and ' . ActivityHelpers::PetName($pet) . ' picked some before heading home.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Gathering ]));

            $this->petExperienceService->spendTime($pet, 15, PetActivityStatEnum::GATHER, true);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);

            $this->inventoryService->petCollectsItem('Blackberries', $pet, $pet->getName() . ' picked these from bushes near an Old Iron Mine.', $activityLog);
        }

        return $activityLog;
    }

    private function foundMicroJungle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        // no chance for a "gathering distraction" here; that code is in doNormalMicroJungle

        if(DateFunctions::moonPhase(new \DateTimeImmutable()) === MoonPhaseEnum::FullMoon)
            $activityLog = $this->encounterNangTani($petWithSkills);
        else
            $activityLog = $this->doNormalMicroJungle($petWithSkills);

        // more chances to get bugs in the jungle!
        if(AdventureMath::petAttractsBug($this->rng, $petWithSkills->getPet(), 25))
            $this->inventoryService->petAttractsRandomBug($petWithSkills->getPet());

        return $activityLog;
    }

    private function encounterNangTani(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getArcana()->getTotal());
        $success = $roll >= 12;

        $pet->increaseSafety($this->rng->rngNextInt(2, 4));

        if($success)
        {
            $loot = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
                'Fishkebab Stew',
                'Grilled Fish',
                $this->rng->rngNextFromArray([ 'Orange', 'Yellowy Lime', 'Ponzu' ]),
                $this->rng->rngNextFromArray([ 'Honeydont Ice Cream', 'Naner Ice Cream' ]),
                'Coconut',
                'Mango',
                'Pineapple',
            ]));

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a lone Naner Tree in the island\'s Micro-jungle. They left a small offering for Nang Tani... who appeared out of thin air, and gave them ' . $loot->getNameWithArticle() . '!')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana ], $activityLog);

            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Nang Tani', '%pet:' . $pet->getId() . '.name% encountered Nang Tani at a lone Naner Tree!');

            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' received this from Nang Tani while leaving an offering at a lone Naner Tree in the island\'s Micro-jungle.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a lone Naner Tree in the island\'s Micro-jungle. They left a small offering for Nang Tani, and left.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
        }

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, $success);

        return $activityLog;
    }

    private function doNormalMicroJungle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'exploring the jungle');

        $pet = $petWithSkills->getPet();

        $possibleLoot = [
            'Naner', 'Naner', 'Orange', 'Orange', 'Cacao Fruit', 'Cacao Fruit', 'Coffee Beans',
        ];

        $extraLoot = [
            'Nutmeg', 'Spicy Peps', 'Yellowy Lime'
        ];

        $loot = [];

        $roll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($roll >= 12)
        {
            $loot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($roll >= 16)
            {
                $loot[] = $this->rng->rngNextFromArray($possibleLoot);

                if($this->rng->rngNextInt(1, 50) === 1)
                    $loot[] = $this->rng->rngNextFromArray([ 'Rib', 'Stereotypical Bone' ]);
            }

            if($roll >= 24)
                $loot[] = $this->rng->rngNextFromArray($extraLoot);

            if($roll >= 30 && $this->rng->rngNextInt(1, 20) === 1)
                $loot[] = 'Silver Ore';
        }

        if(count($loot) === 0)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% entered the island\'s Micro-jungle, but couldn\'t find anything.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% entered the island\'s Micro-jungle, and got ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            foreach($loot as $itemName)
                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this in the island\'s Micro-jungle.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        }

        $this->maybeGetHeatstroke($petWithSkills, $activityLog, 6, 'the Micro-jungle');

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60) + count($loot) * 5, PetActivityStatEnum::GATHER, count($loot) > 0);

        return $activityLog;
    }

    private function foundVolcano(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Volcano, 'exploring the island\'s volcano');

        $pet = $petWithSkills->getPet();
        $check = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($check < 15)
        {
            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Île Volcan', '%pet:' . $pet->getId() . '.name% explored the island\'s Volcano.');

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored the island\'s Volcano, but couldn\'t find anything.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, false);
        }
        else if($this->rng->rngNextInt(1, max(10, 50 - $pet->getSkills()->getIntelligence())) === 1)
        {
            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Île Volcan', '%pet:' . $pet->getId() . '.name% climbed to the top of the island\'s Volcano.');

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% climbed to the top of the island\'s Volcano, and captured some Lightning in a Bottle!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            $this->inventoryService->petCollectsItem('Lightning in a Bottle', $pet, $pet->getName() . ' captured this on the top of the island\'s Volcano!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Île Volcan', '%pet:' . $pet->getId() . '.name% explored the island\'s Volcano.');

            $loot = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
                'Iron Ore', 'Silver Ore', 'Liquid-hot Magma', 'Hot Potato'
            ]));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored the island\'s Volcano, and got ' . $loot->getNameWithArticle() . '.')
                ->setIcon('items/' . $loot->getImage())
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' found this near the island\'s Volcano.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }

        $this->maybeGetHeatstroke($petWithSkills, $activityLog, 8, 'the Volcano');

        return $activityLog;
    }

    private function foundGypsumCave(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Underground, 'exploring a gypsum cave');

        $pet = $petWithSkills->getPet();
        $eideticMemory = $pet->hasMerit(MeritEnum::EIDETIC_MEMORY);
        $check = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal() + $petWithSkills->getMiningBonus()->getTotal());

        if($check >= 15 || $eideticMemory)
        {
            $loot = [
                'Gypsum'
            ];

            if($petWithSkills->getCanSeeInTheDark()->getTotal() >= 0)
            {
                if($check >= 20)
                    $loot[] = $this->rng->rngNextFromArray([ 'Iron Ore', 'Toadstool', 'Gypsum', 'Gypsum', 'Gypsum', 'Limestone' ]);

                if($check >= 30)
                    $loot[] = $this->rng->rngNextFromArray([ 'Silver Ore', 'Silver Ore', 'Gypsum', 'Gold Ore' ]);

                if($eideticMemory)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored a huge cave, perfectly memorizing its layout as they went, and found ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                        ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                    ;
                }
                else
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored a huge cave, and found ' . ArrayFunctions::list_nice_sorted($loot) . '.');
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a huge cave! It was too dark to explore very far, but they found ' . ArrayFunctions::list_nice_sorted($loot) . ' near the entrance.');
            }

            if($this->rng->rngNextInt(1, 2000) < $petWithSkills->getPerception()->getTotal())
            {
                $loot[] = 'Striped Microcline';
                $pet->increaseEsteem(4);
            }

            $activityLog
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Mining' ]))
            ;

            foreach($loot as $item)
                $this->inventoryService->petCollectsItem($item, $pet, $pet->getName() . ' found this in a huge cave.', $activityLog);

            $this->petExperienceService->gainExp($pet, max(2, count($loot)), [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::GATHER, true);
        }
        else
        {
            if($petWithSkills->getCanSeeInTheDark()->getTotal() >= 0)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored a huge cave, and tried to explore it, but got lost for a while!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Mining' ]))
                ;

                $pet->increaseSafety(-4);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a huge cave, and tried to explore it, but got lost in the dark for a while!')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Mining' ]))
                ;

                $pet->increaseSafety(-8);
            }

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::GATHER, false);
        }

        return $activityLog;
    }

    private function foundDeepMicroJungle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        // no "gathering distraction" here; that's been placed inside doNormalDeepMicroJungle

        if(DateFunctions::moonPhase(new \DateTimeImmutable()) === MoonPhaseEnum::FullMoon)
            $activityLog = $this->encounterNangTani($petWithSkills);
        else
            $activityLog = $this->doNormalDeepMicroJungle($petWithSkills);

        // more chances to get bugs in the jungle!
        if(AdventureMath::petAttractsBug($this->rng, $petWithSkills->getPet(), 20))
            $this->inventoryService->petAttractsRandomBug($petWithSkills->getPet());

        return $activityLog;
    }

    private function doNormalDeepMicroJungle(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Underground, 'exploring the deep jungle');

        $pet = $petWithSkills->getPet();

        $possibleLoot = [
            'Naner', 'Naner', 'Mango', 'Mango', 'Cacao Fruit', 'Coffee Beans',
        ];

        $foodLoot = [];
        $extraLoot = [];

        $roll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($roll >= 16)
        {
            $foodLoot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($roll >= 18)
            {
                $foodLoot[] = $this->rng->rngNextFromArray($possibleLoot);

                if($this->rng->rngNextInt(1, 40) === 1)
                    $extraLoot[] = $this->rng->rngNextFromArray([ 'Rib', 'Stereotypical Bone' ]);
            }

            if($roll >= 24)
                $foodLoot[] = $this->rng->rngNextFromArray($possibleLoot);

            if($roll >= 30 && $this->rng->rngNextInt(1, 10) === 1)
                $extraLoot[] = $this->rng->rngNextFromArray([ 'Gold Ore', 'Gold Ore', 'Blackonite', 'Striped Microcline' ]);
        }

        $allLoot = array_merge($foodLoot, $extraLoot);
        sort($allLoot);

        if(count($allLoot) === 0)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored deep in the island\'s Micro-jungle, but couldn\'t find anything.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored deep in the island\'s Micro-jungle, and got ' . ArrayFunctions::list_nice($allLoot) . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            $tropicalSpice = SpiceRepository::findOneByName($this->em, 'Tropical');

            foreach($foodLoot as $itemName)
                $this->inventoryService->petCollectsEnhancedItem($itemName, null, $tropicalSpice, $pet, $pet->getName() . ' found this deep in the island\'s Micro-jungle.', $activityLog);

            foreach($extraLoot as $itemName)
                $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this deep in the island\'s Micro-jungle.', $activityLog);

            $this->petExperienceService->gainExp($pet, $this->rng->rngNextInt(2, 3), [ PetSkillEnum::Nature ], $activityLog);
        }

        $this->maybeGetHeatstroke($petWithSkills, $activityLog, 8, 'the Micro-jungle');

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60) + count($allLoot) * 5, PetActivityStatEnum::GATHER, count($allLoot) > 0);

        return $activityLog;
    }

    private function foundOldSettlement(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        if($this->rng->rngNextInt(1, 20) === 1)
            return $this->gatheringDistractions->adventure($petWithSkills, DistractionLocationEnum::Woods, 'exploring the deep jungle');

        $pet = $petWithSkills->getPet();

        $extraLoot = [
            'Filthy Cloth', 'Crooked Stick', 'Canned Food',
            'String', 'Iron Bar'
        ];

        $loot = [];

        $roll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($roll >= 15)
        {
            $loot[] = $this->rng->rngNextFromArray($extraLoot);

            if($roll >= 25)
                $loot[] = 'Rusted, Busted Mechanism';

            if($roll >= 35)
                $loot[] = 'The Beginning of the Armadillos';

            if($this->rng->rngNextInt(1, 25) === 1)
                $loot[] = 'No Right Turns';
        }

        if(count($loot) === 0)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored deep in the island\'s Micro-jungle, and found a ruined settlement. They looked around for a while, but didn\'t really find anything...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Nature ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored deep in the island\'s Micro-jungle, and found a ruined settlement. They looked around for a while, and scavenged up ' . ArrayFunctions::list_nice_sorted($loot) . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Gathering' ]))
            ;

            foreach($loot as $itemName)
            {
                $item = $this->inventoryService->petCollectsItem($itemName, $pet, $pet->getName() . ' found this in a ruined settlement deep in the island\'s Micro-jungle.', $activityLog);

                if($item && $itemName === 'No Right Turns')
                    $item->setEnchantment(EnchantmentRepository::findOneByName($this->em, 'Thorn-covered'));
            }

            $this->petExperienceService->gainExp($pet, 2 + count($loot), [ PetSkillEnum::Nature ], $activityLog);
        }

        $this->maybeGetHeatstroke($petWithSkills, $activityLog, 8, 'the Micro-jungle');

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60) + count($loot) * 5, PetActivityStatEnum::GATHER, count($loot) > 0);

        return $activityLog;
    }

    private function maybeGetHeatstroke(
        ComputedPetSkills $petWithSkills,
        PetActivityLog $activityLog,
        int $difficulty,
        string $locationName
    ): void
    {
        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStamina()->getTotal()) < $difficulty)
        {
            $pet = $petWithSkills->getPet();

            $activityLog
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Heatstroke' ]))
            ;

            if($petWithSkills->getHasProtectionFromHeat()->getTotal() > 0)
            {
                $activityLog->appendEntry(ucfirst($locationName) . ' was hot, but their ' . ActivityHelpers::SourceOfHeatProtection($petWithSkills) . ' protected them.')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ;
            }
            else
            {
                $pet
                    ->increaseFood(-1)
                    ->increaseSafety(-$this->rng->rngNextInt(1, 2))
                ;

                // why need to have unlocked the greenhouse? just testing that you've been playing for a while
                if($this->rng->rngNextInt(1, 20) === 1 && $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Greenhouse))
                    $activityLog->appendEntry(ucfirst($locationName) . ' was CRAZY hot, and I don\'t mean in a sexy way; %pet:' . $pet->getId() . '.name% got a bit light-headed.');
                else
                    $activityLog->appendEntry(ucfirst($locationName) . ' was CRAZY hot, and %pet:' . $pet->getId() . '.name% got a bit light-headed.');
            }
        }
    }
}
