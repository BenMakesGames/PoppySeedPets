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
use App\Entity\User;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\UnreachableException;
use App\Functions\DateFunctions;
use App\Functions\ItemRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class PetSummonedAwayService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly IRandom $rng,
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em,
        private readonly Clock $clock
    )
    {
    }

    public function adventure(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $changes = new PetChanges($pet);

        $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));

        $activityLog = match($this->rng->rngNextInt(1, 4))
        {
            1 => $this->doSummonedToFight($petWithSkills),
            2 => $this->doSummonedToCleanAndHost($petWithSkills),
            3 => $this->doSummonedToAssistWithRitual($petWithSkills),
            4 => $this->doSummonedToAssistWithGathering($petWithSkills),
            default => throw new UnreachableException(),
        };

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::OTHER, null);

        $activityLog
            ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ->setChanges($changes->compare($pet))
        ;

        return $activityLog;
    }

    private function doSummonedToFight(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $message = 'While ' . $pet->getName() . ' was thinking about what to do, they were magically summoned! The wizard that summoned them made them fight a monster they\'d never seen before';

        if($this->rng->rngNextInt(1, 3) === 1)
            $message .= '; a creature with ' . ($this->rng->rngNextInt(1, 4) << 1) . ' eyes, and a very wrong number of limbs! ';
        else
            $message .= '! ';

        if($this->rng->rngNextInt(1, 2) === 1)
        {
            $message .= $pet->getName() . ' lost the fight, and was returned home!';
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
        }
        else
        {
            $message .= $pet->getName() . ' defeated the creature, and was returned home!';
            $pet
                ->increaseSafety($this->rng->rngNextInt(2, 4))
                ->increaseEsteem($this->rng->rngNextInt(2, 4))
            ;
        }

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->setIcon('icons/activity-logs/summoned')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fighting' ]))
        ;

        $this->petExperienceService->gainExp(
            $pet,
            $this->rng->rngNextInt(1, 3),
            [ PetSkillEnum::Brawl, PetSkillEnum::Brawl, PetSkillEnum::Arcana ],
            $activityLog
        );

        return $activityLog;
    }

    private function doSummonedToCleanAndHost(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $oldBasementSize = $pet->getOwner()->getBasementSize();

        switch($this->rng->rngNextInt(1, 3))
        {
            case 1:
                $activity = 'help clean a mansion the day before a fancy ball';
                $loot = $this->rng->rngNextFromArray([ 'Fluff', 'Cobweb' ]);
                $skill = null;
                $tags = [ 'Gathering' ];

                if($pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Basement) && $pet->getOwner()->getBasementSize() < User::MaxBasementSize)
                    $pet->getOwner()->increaseBasementSize(50);

                break;
            case 2:
                $activity = 'serve food to guests at a fancy party in a mansion';
                $loot = $this->rng->rngNextFromArray([
                    'Tomato "Sushi"', 'Sweet Roll', 'Slice of Red Pie',
                    'Potato-mushroom Stuffed Onion', 'Meringue', 'Minestrone',
                    'Mixed Nut Brittle', 'Largish Bowl of Smallish Pumpkin Soup',
                    'Grilled Fish', 'Everlasting Syllabub', 'Blueberry Wine',
                    'Fig Wine', 'Bizet Cake', 'Red Wine', 'Zongzi'
                ]);
                $skill = null;
                $tags = [ 'Gathering' ];
                break;
            case 3:
                $activity = 'play in a band at a fancy party in a mansion';
                $skill = PetSkillEnum::Music;
                $loot = $this->rng->rngNextFromArray([
                    'Fungal Clarinet', 'Decorated Flute',
                    'Gold Triangle', 'Melodica'
                ]);
                $tags = [ 'Band' ];
                break;
            default:
                throw new \Exception('Bad random number result.');
        }

        $lootItem = ItemRepository::findOneByName($this->em, $loot);
        $message = 'While ' . $pet->getName() . ' was thinking about what to do, they were magically summoned! The wizard that summoned them made them ' . $activity . '. Once the task was completed, ' . $pet->getName() . ' returned home, still holding ' . $lootItem->getNameWithArticle();

        if($pet->getOwner()->getBasementSize() > $oldBasementSize)
            $message .= ', AND with new ideas about how to make the most of your space - your Basement can now hold ' . $pet->getOwner()->getBasementSize() . ' items!';
        else
            $message .= '!';

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->setIcon('icons/activity-logs/summoned')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
        ;

        $this->inventoryService->petCollectsItem($lootItem, $pet, $pet->getName() . ' was summoned by a wizard to ' . $activity . '; they returned home with this!', $activityLog);

        if($skill !== null)
            $this->petExperienceService->gainExp($pet, $this->rng->rngNextInt(1, 2), [ $skill ], $activityLog);

        return $activityLog;

    }

    private function doSummonedToAssistWithRitual(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        switch($this->rng->rngNextInt(1, 7))
        {
            case 1:
                $activity = 'hold a weird-looking mirror while a laser was focused on it';
                $skill = PetSkillEnum::Science;
                $tags = [ 'Physics' ];
                break;
            case 2:
                $activity = 'mix various chemicals together while the wizard watched (from a distance, for some reason...)';
                $skill = PetSkillEnum::Science;
                $tags = [ 'Physics' ];
                break;
            case 3:
                $activity = 'look through almost a hundred photographs of the night sky, looking for bright spots';
                $skill = PetSkillEnum::Science;
                $tags = [ 'Physics' ];
                break;
            case 4:
                $activity = 'perform a very-specific dance while holding some candles';
                $skill = PetSkillEnum::Arcana;
                $tags = [ 'The Umbra' ];
                break;
            case 5:
                $activity = 'draw a series of symbols in geometric shapes on the ground';
                $skill = PetSkillEnum::Arcana;
                $tags = [ 'Magic-binding' ];
                break;
            case 6:
                $activity = 'keep an eye on a trapped spirit while the wizard tended to other things';
                $skill = PetSkillEnum::Arcana;
                $tags = [ 'The Umbra' ];
                break;
            case 7:
                $activity = 'stand motionless, "like a gargoyle", and watch out for intruders for nearly one full hour';
                $skill = PetSkillEnum::Stealth;
                $tags = [ 'Stealth' ];
                break;
            default:
                throw new \Exception('Bad random number result :( Ben has been notified.');
        }

        $message = 'While ' . $pet->getName() . ' was thinking about what to do, they were magically summoned! The wizard that summoned them made them ' . $activity . '. Once the task was completed, ' . $pet->getName() . ' returned home!';

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->setIcon('icons/activity-logs/summoned')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
        ;

        $this->petExperienceService->gainExp($pet, $this->rng->rngNextInt(1, 2), [ $skill ], $activityLog);

        return $activityLog;
    }

    private function doSummonedToAssistWithGathering(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        switch($this->rng->rngNextInt(1, 2))
        {
            case 1:
                $wheatOrCorn = DateFunctions::isCornMoon($this->clock->now) ? 'Corn' : 'Wheat';
                $location = 'a farm';
                $description = 'work a farm';
                $descriptioning = 'working a farm';
                $loot = $this->rng->rngNextFromArray([ 'Rice', $wheatOrCorn, 'Egg', 'Creamy Milk' ]);
                $tags = [ 'Gathering' ];
                break;

            case 2:
                $location = 'a mine';
                [$description, $descriptioning, $loot] = $this->getMiningDescriptionAndLoot();
                $tags = [ 'Mining' ];
                break;

            default:
                throw new UnreachableException();
        }

        $message = 'While ' . $pet->getName() . ' was thinking about what to do, they were magically summoned to ' . $location . '. The wizard that summoned them made them ' . $description . ' until the spell ended, and they were returned home! (At least they managed to pocket some ' . $loot . ' before the spell ended!)';

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->setIcon('icons/activity-logs/summoned')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
        ;

        $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' was summoned by a wizard to do hard labor; while ' . $descriptioning . ', they stole this!', $activityLog);

        return $activityLog;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function getMiningDescriptionAndLoot(): array
    {
        $r = $this->rng->rngNextInt(1, 10);

        if($r <= 5)
        {
            return [ 'mine for iron', 'mining for iron', 'Iron Ore' ];
        }
        else if($r <= 8)
        {
            return [ 'mine for silver', 'mining for silver', 'Silver Ore' ];
        }
        else
        {
            return [ 'mine for gold', 'mining for gold', 'Gold Ore' ];
        }
    }
}
