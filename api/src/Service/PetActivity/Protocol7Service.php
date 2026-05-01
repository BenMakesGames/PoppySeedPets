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
use App\Enum\ActivityPersonalityEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\AdventureMath;
use App\Functions\ItemRepository;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\PetQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

class Protocol7Service implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng,
        private readonly PetQuestRepository $petQuestRepository
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'hack'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();

        if($pet->hasStatusEffect(StatusEffectEnum::Wereform))
            return 0;

        $desire = $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getScience() + $pet->getTool()->getItem()->getTool()->getHacking();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Protocol7))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(1, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100)));
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        $pet = $petWithSkills->getPet();

        if(
            !$pet->hasMerit(MeritEnum::PROTOCOL_7) &&
            $pet->getTool()?->getItem()->getTool()?->getAdventureDescription() !== 'Project-E'
        )
        {
            return [];
        }

        return [ $this->run(...) ];
    }

    public function run(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $maxSkill = 10 + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal() - $pet->getAlcohol() * 2;

        $roll = $this->rng->rngNextInt(0, NumberFunctions::clamp($maxSkill, 1, 19));

        switch($roll)
        {
            case 0:
            case 1:
            case 2:
                $activityLog = $this->foundNothing($petWithSkills, $roll);
                break;
            case 3:
            case 4:
            case 5:
                if($pet->hasMerit(MeritEnum::BEHATTED) && $this->rng->rngNextInt(1, 40) < $petWithSkills->getScience()->getTotal())
                    $activityLog = $this->encounterAnnabellastasia($petWithSkills);
                else
                    $activityLog = $this->encounterGarbageCollector($petWithSkills);
                break;
            case 6:
            case 7:
            case 8:
                $activityLog = $this->foundLayer02($petWithSkills);
                break;
            case 9:
                $activityLog = $this->foundNothing($petWithSkills, $roll);
                break;
            case 10:
            case 11:
                $activityLog = $this->foundProtectedSector($petWithSkills);
                break;
            case 12:
            case 13:
                $activityLog = $this->watchOnlineVideo($petWithSkills);
                break;
            case 14:
            case 15:
                $activityLog = $this->exploreInsecurePort($petWithSkills);
                break;
            case 16:
            case 17:
                $activityLog = $this->repairShortedCircuit($petWithSkills);
                break;
            case 18:
                $activityLog = $this->exploreWalledGarden($petWithSkills);
                break;
            case 19:
            default:
                $activityLog = $this->foundCorruptSector($petWithSkills);
                break;
        }

        if(AdventureMath::petAttractsBug($this->rng, $pet, 75))
            $this->inventoryService->petAttractsRandomBug($pet, 'Beta Bug');

        return $activityLog;
    }

    private function foundNothing(ComputedPetSkills $petWithSkills, int $roll): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $exp = (int)ceil($roll / 10);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' accessed Project-E, but got distracted by Noisome Adverts.');

        $this->petExperienceService->gainExp($pet, $exp, [ PetSkillEnum::Science ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);

        $this->inventoryService->petCollectsItem('Noisome Advert', $pet, 'This item pushed itself on ' . $pet->getName() . ' while they were trying to explore Project-E.', $activityLog);

        $activityLog
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
        ;

        return $activityLog;
    }

    private function encounterAnnabellastasia(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $now = new \DateTimeImmutable();

        $name = $this->rng->rngNextFromArray([
            'Annabellastasia',
            'Xerxeneea',
            'Jellybingus',
        ]);

        $petQuest = $this->petQuestRepository->findOrCreate($pet, 'Next Annabellastasia Encounter', $now->format('Y-m-d'));

        if($petQuest->getValue() > $now->format('Y-m-d'))
            return $this->encounterGarbageCollector($petWithSkills);

        $petQuest->setValue($now->modify('+' . $this->rng->rngNextInt(20, 40) . ' days')->format('Y-m-d'));

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'In Project-E, ' . ActivityHelpers::PetName($pet) . ' ran into a girl named ' . $name . ', who handed ' . ActivityHelpers::PetName($pet) . ' a Black Bow.')
            ->setIcon('items/hat/bow-black')
            ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
        ;
        $this->inventoryService->petCollectsItem('Black Bow', $pet, $pet->getName() . ' received this from a girl named ' . $name . ' in Project-E.', $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::MetAFamousVideoGameCharacter, $activityLog);

        return $activityLog;
    }

    private function encounterGarbageCollector(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal());

        $success = $roll >= 10;

        if($success)
        {
            $pet->increaseEsteem(1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% saw a Garbage Collector in Project-E, and took one of the Pointers it was discarding.')
                ->setIcon('items/resource/digital/pointer')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 10)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->inventoryService->petCollectsItem('Pointer', $pet, $pet->getName() . ' took this from a Garbage Collector in Project-E.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% saw a Garbage Collector passing by in Project-E, but couldn\'t catch up to it.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }

        return $activityLog;
    }

    private function foundLayer02(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $monster = $this->rng->rngNextFromArray([
            [
                'name' => 'a Trojan Horse',
                'loot' => [ 'Plastic' ],
            ],
            [
                'name' => 'a Clickjacker',
                'loot' => [ 'Browser Cookie' ],
            ],
            [
                'name' => 'an SQL Injection',
                'loot' => [ 'Finite State Machine' ],
            ],
        ]);

        $baddie = $monster['name'];
        $loot = $this->rng->rngNextFromArray($monster['loot']);
        $success = $roll >= 12;

        if($success)
        {
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' in Layer 02 of Project-E, but defeated it, and took its ' . $loot . '!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 12)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' defeated ' . $baddie . ', and took this.', $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% accessed Layer 02 of Project-E, but ' . $baddie . ' hijacked their session.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
        }

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::PROTOCOL_7, $success);

        return $activityLog;
    }

    private function foundProtectedSector(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $monster = $this->rng->rngNextFromArray([
            [
                'name' => 'a Keylogger',
                'loot' => [ 'Hash Table', 'Password' ],
            ],
            [
                'name' => 'a Rootkit',
                'loot' => [ 'Beans', 'Password' ],
            ],
            [
                'name' => 'a Boot Sector Virus',
                'loot' => [ 'Pointer', 'NUL' ],
            ],
        ]);

        $baddie = $monster['name'];
        $loot = $this->rng->rngNextFromArray($monster['loot']);
        $success = $roll >= 15;

        if($this->rng->rngNextInt(1, 200) == 1)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(8);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' in a protected sector of Project-E, but defeated it, and took its Pynʞ! Whoa!')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Pynʞ', $pet, $pet->getName() . ' defeated ' . $baddie . ', and got _this!_', $activityLog);
        }
        else if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 200) == 1)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(8);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' in a protected sector of Project-E, but defeated it, and took its Pynʞ! (Lucky~!)')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Pynʞ', $pet, $pet->getName() . ' defeated ' . $baddie . ', and got _this!_ (Lucky~!)', $activityLog);
        }
        else if($success)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' in a protected sector of Project-E, but defeated it, and took its ' . $loot . '!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' defeated ' . $baddie . ', and took this.', $activityLog);
        }
        else
        {
            $pet->increaseSafety(-1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to access a protected sector of Project-E, but couldn\'t get elevated permissions.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
        }

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::PROTOCOL_7, $success);

        return $activityLog;
    }

    private function watchOnlineVideo(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $video = $this->rng->rngNextFromArray([
            [
                'subject' => 'about fractals',
                'loot' => [ 'Imaginary Number' ],
            ],
            [
                'subject' => 'about encryption algorithms',
                'loot' => [ 'Password', 'Hash Table', 'Cryptocurrency Wallet' ],
            ],
            [
                'subject' => 'about city planning',
                'loot' => [ 'Traffic Light', 'Traffic Cone' ],
            ],
            [
                'subject' => 'about the history of food',
                'loot' => [ 'Aging Powder', 'Plain Yogurt', 'Rice', 'Vinegar' ],
            ],
            [
                'subject' => 'about music theory',
                'loot' => [ 'Music Note' ],
            ],
            [
                'subject' => 'about evolution',
                'loot' => [ 'Scales', 'Fluff', 'Fish Bones', 'Talon' ],
            ],
            [
                'subject' => 'about cosmology',
                'loot' => [ 'Gravitational Waves', 'Photon', 'Dark Matter' ],
            ],
        ]);

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal());

        if($roll >= 16)
        {
            $lootItem = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray($video['loot']));

            $pet->increaseEsteem(2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% watched a video ' . $video['subject'] . ' in Project-E, and got ' . $lootItem->getNameWithArticle() . ' out of it.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->inventoryService->petCollectsItem($lootItem, $pet, $pet->getName() . ' got this by watching a video ' . $video['subject'] . ' in Project-E.' , $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% watched a video ' . $video['subject'] . ' in Project-E, but didn\'t really get anything out of it.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }

        return $activityLog;
    }

    private function exploreInsecurePort(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $monster = $this->rng->rngNextFromArray([
            [
                'name' => 'a Slow Loris',
                'loot' => [ 'String', 'NUL' ],
            ],
            [
                'name' => 'a Man in the Middle',
                'loot' => [ 'Cryptocurrency Wallet', 'Cryptocurrency Wallet', 'Hash Table' ],
            ],
        ]);

        $baddie = $monster['name'];
        $loot = $this->rng->rngNextFromArray($monster['loot']);
        $success = $roll >= 17;

        if($this->rng->rngNextInt(1, 200) == 1)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(8);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' on an insecure port in Project-E, but defeated it, and took its Pynʞ! Whoa!')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Pynʞ', $pet, $pet->getName() . ' defeated ' . $baddie . ', and got _this!_', $activityLog);
        }
        else if($pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 200) == 1)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(8);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' on an insecure port in Project-E, but defeated it, and took its Pynʞ! (Lucky~!)')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->inventoryService->petCollectsItem('Pynʞ', $pet, $pet->getName() . ' defeated ' . $baddie . ', and got _this!_ (Lucky~!)', $activityLog);
        }
        else if($success)
        {
            $pet->increaseSafety(2);
            $pet->increaseEsteem(2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% was assaulted by ' . $baddie . ' on an insecure port in Project-E, but defeated it, and took its ' . $loot . '!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' defeated ' . $baddie . ', and took this.', $activityLog);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
        }
        else
        {
            $pet->increaseSafety(-1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% accessed an insecure port in Project-E, but their service was disrupted by ' . $baddie . '.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
        }

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::PROTOCOL_7, $success);

        return $activityLog;
    }

    private function repairShortedCircuit(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $check = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        if($check < 15)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name%\'s line was suddenly shorted while they were exploring Project-E!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Physics' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }
        else if($this->rng->rngNextInt(1, max(10, 50 - $pet->getSkills()->getIntelligence() - $petWithSkills->getElectronicsBonus()->getTotal())) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name%\'s line was suddenly shorted while they were exploring Project-E. %pet:' . $pet->getId() . '.name% managed to capture some Lightning in a Bottle before being forcefully disconnected!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Physics' ]))
            ;

            $this->inventoryService->petCollectsItem('Lightning in a Bottle', $pet, $pet->getName() . ' captured this on a shorted line of Project-E!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::PROTOCOL_7, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name%\'s line was suddenly shorted while they were exploring Project-E. %pet:' . $pet->getId() . '.name% managed to grab a couple Pointers before being forcefully disconnected.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Physics' ]))
            ;

            $this->inventoryService->petCollectsItem('Pointer', $pet, $pet->getName() . ' captured this on a shorted line of Project-E!', $activityLog);
            $this->inventoryService->petCollectsItem('Pointer', $pet, $pet->getName() . ' captured this on a shorted line of Project-E!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);
        }

        if($this->rng->rngNextInt(1, 10 + $petWithSkills->getStamina()->getTotal()) < 8)
        {
            if($petWithSkills->getHasProtectionFromElectricity()->getTotal() > 0)
            {
                $activityLog->appendEntry('Their shock-resistance protected them from the sudden burst of energy.')
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ;
            }
            else
            {
                $pet->increaseFood(-1);
                $pet->increaseSafety(-$this->rng->rngNextInt(3, 6));

                $activityLog->appendEntry(ActivityHelpers::PetName($pet) . ' was unprotected from the sudden burst of energy, and received a minor shock.');
            }
        }

        return $activityLog;
    }

    private function exploreWalledGarden(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $sneakSkill = $petWithSkills->getStealth()->getTotal() + $petWithSkills->getClimbingBonus()->getTotal();
        $hackSkill = $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal();

        $check = $this->rng->rngNextInt(
            1,
            20 +
            $petWithSkills->getIntelligence()->getTotal() +
            max($sneakSkill, $hackSkill)
        );

        if($sneakSkill > $hackSkill)
        {
            $toSneak = 'to climb';
            $snuck = 'climbed';
        }
        else
        {
            $toSneak = 'to break';
            $snuck = 'broke';
        }

        if($this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $snuck . ' into a Walled Garden, but ran into a Pirate doing the same! ' . $pet->getName() . ' defeated the Pirate, stole its Jolliest Roger, and ran off before the Walled Garden\'s security system detected them! Yarr!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Fighting', 'Stealth' ]))
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
            ;

            $pet->increaseEsteem($this->rng->rngNextInt(4, 8));

            $this->inventoryService->petCollectsItem('Jolliest Roger', $pet, $pet->getName() . ' fought off a Pirate in a Walled Garden within Project-E, and took this from it!', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science, PetSkillEnum::Stealth, PetSkillEnum::Brawl ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }
        else if($check < 15)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried ' . $toSneak . ' into a Walled Garden within Project-E, but was kicked out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Stealth' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Science, PetSkillEnum::Stealth ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% ' . $snuck . ' into a Walled Garden within Project-E, and plucked a Macintosh that was growing there.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Stealth' ]))
            ;

            $this->inventoryService->petCollectsItem('Macintosh', $pet, $pet->getName() . ' found this growing in a Walled Garden within Project-E!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science, PetSkillEnum::Stealth ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);
        }

        return $activityLog;
    }

    private function foundCorruptSector(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $check = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getPerception()->getTotal() + $petWithSkills->getScience()->getTotal() + $petWithSkills->getHackingBonus()->getTotal());

        $lucky = $pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 100) === 1;

        if($check < 20)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a corrupt sector, but wasn\'t able to recover any data.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, false);
        }
        else if($lucky)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a corrupt sector, and managed to recover a Lo-res Crown from it! Lucky~!')
                ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E', 'Lucky~!' ]))
            ;

            $this->inventoryService->petCollectsItem('Lo-res Crown', $pet, $pet->getName() . ' recovered this from a corrupt sector of Project-E! Lucky~!', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(4, 8));

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
        }
        else if($this->rng->rngNextInt(1, 100) === 1)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a corrupt sector, and managed to recover a Lo-res Crown from it!')
                ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;

            $this->inventoryService->petCollectsItem('Lo-res Crown', $pet, $pet->getName() . ' recovered this from a corrupt sector of Project-E!', $activityLog);

            $pet->increaseEsteem($this->rng->rngNextInt(4, 8));

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
        }
        else
        {
            $loot = $this->rng->rngNextFromArray([
                'Password',
                'Cryptocurrency Wallet',
                'Egg Book Audiobook',
                'Lycanthropy Report',
            ]);

            $lucky = false;

            if($this->rng->rngNextInt(1, 100) === 1)
                $otherLoot = 'Recovered Archive';
            else if($this->rng->rngNextInt(1, 100) === 1 && $pet->hasMerit(MeritEnum::LUCKY))
            {
                $otherLoot = 'Recovered Archive';
                $lucky = true;
            }
            else
            {
                $otherLoot = $this->rng->rngNextFromArray([
                    'Hash Table',
                    'Finite State Machine',
                    'Browser Cookie',
                ]);
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% found a corrupt sector, and managed to recover a ' . $otherLoot . ', and ' . $loot . ' from it!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Project-E' ]))
            ;
            $itemComment = $pet->getName() . ' recovered this from a corrupt sector of Project-E!';

            if($lucky)
            {
                $activityLog->appendEntry('Lucky~!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]));
                $itemComment .= ' Lucky~!';
            }

            $this->inventoryService->petCollectsItem($otherLoot, $pet, $itemComment, $activityLog);
            $this->inventoryService->petCollectsItem($loot, $pet, $itemComment, $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Science ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::PROTOCOL_7, true);
        }

        return $activityLog;
    }
}
