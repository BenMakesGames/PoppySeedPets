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

namespace App\Service\PetActivity\Group;

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\PetGroup;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetSkillEnum;
use App\Functions\ActivityHelpers;
use App\Functions\GroupNameGenerator;
use App\Functions\ItemRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\PetChanges;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\PetGroupService;
use App\Service\PetRelationshipService;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\PetActivityLogTagEnum;
use App\Functions\ArrayFunctions;
use App\Service\WeatherService;

class GardeningClubService
{
    public const string ActivityIcon = 'groups/gardening';

    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em,
        private readonly InventoryService $inventoryService,
        private readonly PetRelationshipService $petRelationshipService,
        private readonly IRandom $rng,
        private readonly PetGroupService $petGroupService,
    )
    {
    }

    private const array Dictionary = [
        'color' => [
            'Green', 'Pink', 'Red', 'Yellow', 'Orange', 'Rainbow', 'Purple', 'Indigo', 'Blue', 'Azure', 'White',
        ],
        'plants' => [
            'Peonies', 'Agrimonies', 'Trefoils', 'Dandelions', 'Irises', 'Lotuses', 'Carnations', 'Merigolds',
            'Flowers', 'Trees', 'Grasses', 'Blooms', 'Seeds', 'Sprouts', 'Roses', 'Daisies', 'Blossoms', 'Potatos',
            'Tomatos', 'Reds', 'Beans', 'Wheat', 'Rice', 'Oranges', 'Eggplants', 'Corn', 'Yams', 'Carrots', 'Algae',
            'Mushrooms', 'Bushes', 'Cacao', 'Coconuts',
        ],
        'adjectives' => [
            'Little', 'Giant', 'Cute', 'Beautiful', 'Aromatic', 'Lovely', 'Young', 'Old', 'Sparkling', 'Glimmering',
            'Tiny', 'Yummy', 'Fresh', 'Growing',
        ],
        'animals' => [
            'Worms', 'Moles', 'Birds', 'Beetles', 'Aphids', 'Spiders', 'Mice', 'Butterflies', 'Moths', 'Raccoons',
            'Goats',
        ],
    ];

    private const array GroupNamePatterns = [
        'the %color%? %plants%',
        '%color%/%adjectives% %plants%',
        '%color%/%adjectives%? %plants% and %animals%',
        '%animals% of the? %color%/%adjectives%? %plants%',
    ];

    private const TotalCropSkillDivisor = 20;

    public function generateGroupName(): string
    {
        return GroupNameGenerator::generateName($this->rng, self::GroupNamePatterns, self::Dictionary, 60);
    }

    public function meet(PetGroup $group): void
    {
        $activityLogsPerPet = [];
        $expGainPerPet = [];

        $greenThumbValue = 5;
        $skill = 0;
        /** @var PetChanges[] $petChanges */
        $petChanges = [];

        $activities = [
            $this->doWeeding(...),
            $this->doWeeding(...),

            $this->doComposting(...),
        ];

        // 1/2 chance to do watering, but only if it's not raining
        if (!WeatherService::getWeather(new \DateTimeImmutable())->isRaining())
            $activities = array_merge($activities, [$this->doWatering(...), $this->doWatering(...), $this->doWatering(...)]);

        foreach($group->getMembers() as $pet)
        {
            $petWithSkills = $pet->getComputedSkills();
            $petChanges[$pet->getId()] = new PetChanges($pet);

            $roll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getNature()->getTotal());

            if($pet->hasMerit(MeritEnum::GREEN_THUMB))
                $roll += max(0, $greenThumbValue--);

            $expGainPerPet[$pet->getId()] = max(1, (int)floor($roll / 5));

            $skill += $roll;
        }

        //Progress is steady
        $progress = $this->rng->rngNextInt(10, 15);

        $group
            ->increaseProgress($progress)
            ->increaseSkillRollTotal($skill)
        ;

        if($group->getProgress() >= 100)
            $activityLogsPerPet = $this->collectHarvest($group);
        else
            $activityLogsPerPet = $this->rng->rngNextFromArray($activities)($group);

        foreach($group->getMembers() as $pet)
        {
            $this->petExperienceService->gainExp($pet, $expGainPerPet[$pet->getId()], [ PetSkillEnum::Nature ], $activityLogsPerPet[$pet->getId()]);
            $activityLogsPerPet[$pet->getId()]->setChanges($petChanges[$pet->getId()]->compare($pet));
        }

        $this->petRelationshipService->groupGathering(
            $group->getMembers(),
            '%p1% and %p2% talked a little while gardening together for ' . $group->getName() . '.',
            '%p1% and %p2% avoided talking as much as possible while gardening together for ' . $group->getName() . '.',
            'Met during a ' . $group->getName() . ' hangout.',
            '%p1% met %p2% during a ' . $group->getName() . ' hangout.',
            [ PetActivityLogTagEnum::Gardening_Club ],
            100
        );

        $group->setLastMetOn();
    }

    /**
     * @return PetActivityLog[]
     */
    public function collectHarvest(PetGroup $group): array
    {
        $activityLogsPerPet = [];

        /** @var int[] $usersAlerted */
        $usersAlerted = [];

        $groupSize = count($group->getMembers());
        $contributedSkill = $group->getSkillRollTotal() / $groupSize;

        $group
            ->clearProgress()
            ->increaseNumberOfProducts()
        ;

        $possibleProducts =
            [
                'Wheat', 'Rice', 'Apricot', 'Beans',
                'Blackberries', 'Blueberries', 'Celery',
                'Cacao Fruit', 'Coconut', 'Corn', 'Eggplant',
                'Ginger', 'Creamy Milk', 'Honeydont', 'Melowatern',
                'Mint', 'Mixed Nuts', 'Naner', 'Onion', 'Orange',
                'Pamplemousse', 'Potato', 'Smallish Pumpkin', 'Red',
                'Carrot', 'Spicy Peps', 'Crooked Stick', 'Sweet Beet',
                'Tomato', 'Algae', 'Seaweed', 'Grandparoot', 'Chanterelle',
                'Toadstool', 'Egg', 'Dandelion', 'Tea Leaves',
            ];

        $totalCrops = max(1, $contributedSkill / self::TotalCropSkillDivisor);

        $products = [];

        while($totalCrops >= 1)
        {
            // Replace with clamp ($this->rng->rngNextInt(2, 5), 1, $totalCrops);
            // In PHP 8.6
            $bunchSize = max(1, min($this->rng->rngNextInt(2, 5), $totalCrops));
            $totalCrops -= $bunchSize;
            $crop = $this->rng->rngNextFromArray($possibleProducts);

            for($i = 0; $i < $bunchSize; $i++)
                $products[] = $crop;

            $productsList = ArrayFunctions::list_nice($products);

            foreach($group->getMembers() as $member)
            {
                $member->increaseEsteem($this->rng->rngNextInt(8, 12));

                $message = $group->getName() . ' harvested ' . $productsList . '!';

                $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
                ;

                foreach($products as $product)
                    $this->inventoryService->petCollectsItem($product, $member, $group->GetName() . ' grew this!', $activityLog);


                $activityLogsPerPet[$member->getId()] = $activityLog;
            }
        }

        return $activityLogsPerPet;
    }

    /**
     * @return PetActivityLog[]
     */
    public function doWatering(PetGroup $group): array
    {
        $activityLogsPerPet = [];

        /** @var int[] $usersAlerted */
        $usersAlerted = [];

        foreach($group->getMembers() as $member)
        {
            $extra = 'It was a nice day out!';
            if($this->rng->rngNextInt(1, 3) === 1)
                $extra = 'Nothing really happened...';
            else
                $member->increaseEsteem($this->rng->rngNextInt(2, 4));

            $message = ActivityHelpers::PetName($member) . ' watered plants with ' . $group->getName() . '. ' . $extra;

            $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                ->setIcon(self::ActivityIcon)
                ->addInterestingness(PetActivityLogInterestingness::HoHum)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
            ;


            $activityLogsPerPet[$member->getId()] = $activityLog;
        }

        return $activityLogsPerPet;
    }

    /**
     * @return PetActivityLog[]
     */
    public function doWeeding(PetGroup $group): array
    {
        $activityLogsPerPet = [];

        /** @var int[] $usersAlerted */
        $usersAlerted = [];

        $weedingRewards =
            [
                'Crooked Stick',
                'Really Big Leaf',
                'Rock',
                'Line of Ants',
                'Spider',
                'Fluff'
            ];

        foreach($group->getMembers() as $member)
        {
            $roll = $this->rollWeedingSkill($member);

            if($member->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 20) == 1)
            {
                $member->increaseEsteem($this->rng->rngNextInt(2, 4));

                $item = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray($weedingRewards));

                $message = ActivityHelpers::PetName($member) . ' did some weeding with ' . $group->getName() . '. They managed to find ' . $item->getNameWithArticle() . ' while weeding! (Lucky~!)';

                $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
                ;

                $this->inventoryService->petCollectsItem($item, $member, ActivityHelpers::PetName($member) . ' found this while weeding!', $activityLog);
            }
            else if($roll > 15)
            {
                $member->increaseEsteem($this->rng->rngNextInt(2, 4));

                $item = ItemRepository::findOneByName($this->em, $this->rng->rngNextFromArray($weedingRewards));

                $message = ActivityHelpers::PetName($member) . ' did some weeding with ' . $group->getName() . '. They managed to find ' . $item->getNameWithArticle() . ' while weeding!';

                $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::HoHum)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
                ;

                $this->inventoryService->petCollectsItem($item, $member, ActivityHelpers::PetName($member) . ' found this while weeding!', $activityLog);
            }
            else if($roll < 10)
            {
                $message = ActivityHelpers::PetName($member) . ' did some weeding with ' . $group->getName() . '. It was really tough!';

                $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                    ->addInterestingness(PetActivityLogInterestingness::HoHum)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
                ;
            }
            else
            {
                $member->increaseEsteem($this->rng->rngNextInt(2, 4));

                $message = ActivityHelpers::PetName($member) . ' did some weeding with ' . $group->getName() . '.';

                $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::HoHum)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
                ;
            }

            $activityLogsPerPet[$member->getId()] = $activityLog;
        }

        return $activityLogsPerPet;
    }

    private function rollWeedingSkill(Pet $pet): int
    {
        $petWithSkills = $pet->getComputedSkills();

        $total =
            max($petWithSkills->getStrength()->getTotal(), $petWithSkills->getStamina()->getTotal()) +
            $petWithSkills->getNature()->getTotal() +
            ($pet->hasMerit(MeritEnum::GREEN_THUMB) ? 5 : 0);
        ;

        return $this->rng->rngNextInt(1, 10 + $total);
    }

    /**
     * @return PetActivityLog[]
     */
    public function doComposting(PetGroup $group): array
    {
        $activityLogsPerPet = [];

        /** @var int[] $usersAlerted */
        $usersAlerted = [];

        foreach($group->getMembers() as $member)
        {
            $roll = $this->rng->rngNextInt(1, 20);

            if ($member->hasMerit(MeritEnum::LUCKY))
                $roll = max($roll, $this->rng->rngNextInt(1, 20));

            $message = ActivityHelpers::PetName($member) . ' created compost with ' . $group->getName() . '.';

            $activityLog = $this->petGroupService->createGroupLog($member, $message, $usersAlerted)
                ->setIcon(self::ActivityIcon)
                ->addInterestingness(PetActivityLogInterestingness::HoHum)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', PetActivityLogTagEnum::Gardening_Club ]))
            ;

            if ($roll >= 5)
            {
                $fertilizer = 'Small Bag of Fertilizer';

                if ($roll == 20)
                    $fertilizer = 'Large Bag of Fertilizer';
                else if ($roll >= 15)
                    $fertilizer = 'Bag of Fertilizer';

                $activityLog->appendEntry($member->getName() . ' made some extra ' . $fertilizer . ' and brought it home.');

                $this->inventoryService->petCollectsItem($fertilizer, $member, ActivityHelpers::PetName($member) . ' made extra while making compost for ' . $group->GetName() . '!', $activityLog);
            }

            $activityLogsPerPet[$member->getId()] = $activityLog;
        }

        return $activityLogsPerPet;
    }
}
