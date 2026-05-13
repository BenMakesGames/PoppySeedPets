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

namespace App\Service;

use App\Entity\User;
use App\Entity\UserStats;
use App\Enum\EnumInvalidValueException;
use App\Enum\LocationEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\CalendarFunctions;
use App\Functions\ItemRepository;
use App\Functions\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

class BookstoreService
{
    const string BOOKSTORE_QUEST_NAME = 'Items Given to Bookstore';

    /**
     * @var array<int, array{askingFor: string[], dialog: string}>
     */
    const array QUEST_STEPS = [
        [
            'askingFor' => [ 'Moth' ],
            'dialog' => 'Could you find a Moth for me? Sometimes they just flutter into the house, but you might also be lucky enough to have a pet that burps them!',
        ],
        [
            'askingFor' => [ 'Trout Yogurt' ],
            'dialog' => 'I\'m looking for Trout Yogurt. It may sound weird... well, I guess it kind of is. It\'s an acquired taste.',
        ],
        [
            'askingFor' => [ 'Sweet Coffee Bean Tea with Mammal Extract' ],
            'dialog' => 'Could you find some Sweet Coffee Bean Tea with Mammal Extract? It\'s kind of a specialty here on Poppy Seed Pets Island (or whatever we\'re calling this place...)',
        ],
        [
            'askingFor' => [ 'Upside-down Shiny Pail' ],
            'dialog' => 'Next on my list is an Upside-down Shiny Pail. You can find the right side-up kind in the Hollow Earth. (If you haven\'t unlocked the Hollow Earth, try checking your Library for something to help you!) (If you haven\'t unlocked your Library, give Star Kindred a read!)',
        ],
        [
            'askingFor' => [ 'Single', 'Musical Scales' ],
            'dialog' => 'Have your pets joined any music bands yet? I\'m looking for a Single, or a Musical Scale.',
        ],
        [
            'askingFor' => [ 'Scroll of Flowers' ],
            'dialog' => 'I\'ve been looking for a Scroll of Flowers for a while... I don\'t suppose you have an extra?',
        ],
        [
            'askingFor' => [ 'Bizet Cake' ],
            'dialog' => 'Could you get a Bizet Cake? It\'s... another specialty of the island, I guess you could say.',
        ],
        [
            'askingFor' => [ 'Dark Matter' ],
            'dialog' => 'I\'m looking for Dark Matter... you might have a pet that poops some... or have encountered some bats that poop it? I guess there\'s no getting around the fact that it\'s poop.',
        ],
        [
            'askingFor' => [ 'Letter from the Library of Fire' ],
            'dialog' => 'Could you bring me a Letter from the Library of Fire? It can be found in your Fireplace... if you haven\'t already gotten one, though, definitely read one before bringing one to me!',
        ],
        [
            'askingFor' => [ 'Upside-down, Yellow Plastic Bucket' ],
            'dialog' => 'I\'m looking to expand my wardrobe. Could you get me an Upside-down, Yellow Plastic Bucket?',
        ],
        [
            'askingFor' => [ 'Moon Pearl' ],
            'dialog' => 'I\'d like to get a Moon Pearl... I don\'t suppose you\'ve found any?',
        ],
        [
            'askingFor' => [ 'Weird Beetle' ],
            'dialog' => 'Sometimes, when harvesting plants in the Greenhouse, your pets may find a Weird Beetle. Could you bring me one?',
        ],
        [
            'askingFor' => [ 'Box of Ores' ],
            'dialog' => 'I\'m looking for a Box of Ores. It\'s another object that can be found in the Hollow Earth.',
        ],
        [
            'askingFor' => [ 'Planetary Ring' ],
            'dialog' => 'Do you have any skilled astronomers? I ask because I\'m looking for Planetary Ring.',
        ],
        [
            'askingFor' => [ 'Century Egg' ],
            'dialog' => 'Remember when I asked you for some Trout Yogurt? Recently, I\'m looking for something that\'s even _more_ of an acquired taste: Century Egg!',
        ],
        [
            'askingFor' => [ 'EP' ],
            'dialog' => 'I\'m looking for an EP. Do you have any pets in skilled bands?',
        ],
        [
            'askingFor' => [ 'Heart Beetle' ],
            'dialog' => 'Oh, I\'d really like a Heart Beetle. They can only be found in the Crystal Heart Dimension...',
        ],
        [
            'askingFor' => [ 'Tiny Black Hole' ],
            'dialog' => 'I\'ve been looking for a Tiny Black Hole. Astronomy groups can discover them, sometimes...',
        ],
        [
            'askingFor' => [ 'LP' ],
            'dialog' => 'I\'m looking for an LP. Do you have any pets in REALLY skilled bands?',
        ],
        [
            'askingFor' => [ 'Weird, Blue Egg', 'Unexpectedly-familiar Metal Box' ],
            'dialog' => 'A lot of people (and pets) on the island go searching for Cetgueli\'s treasure... could you bring me one of his treasures? Either a Weird, Blue Egg, or an Unexpectedly-familiar Metal Box?',
        ],
        [
            'askingFor' => [ 'Really Big Leaf' ],
            'dialog' => 'I\'d like to get my hands on a Really Big Leaf. I don\'t suppose you\'ve planted a Magic Bean Stalk?',
        ],
        [
            'askingFor' => [ 'Spirit Polymorph Potion' ],
            'dialog' => 'Could you get me a Spirit Polymorph Potion? The recipe is apparently a bit hard to come by...',
        ]
    ];

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly EntityManagerInterface $em,
        private readonly Clock $clock
    )
    {
    }

    /**
     * @param int $step
     * @return array{askingFor: string[], dialog: string}|null
     */
    public static function getBookstoreQuestStep(int $step): ?array
    {
        if($step < count(self::QUEST_STEPS))
            return self::QUEST_STEPS[$step];
        else
            return null;
    }

    public function advanceBookstoreQuest(User $user, string $itemToGive): void
    {
        if(!$this->renamingScrollAvailable($user))
            throw new PSPNotUnlockedException('Bookstore Renaming Scrolls');

        $item = ItemRepository::findOneByName($this->em, $itemToGive);

        $bookstoreQuestStep = UserQuestRepository::findOrCreate($this->em, $user, self::BOOKSTORE_QUEST_NAME, 0);

        $questStep = BookstoreService::getBookstoreQuestStep($bookstoreQuestStep->getValue());

        if(!$questStep)
            throw new PSPInvalidOperationException('You\'ve brought back everything I need! Thanks!');

        if(!in_array($itemToGive, $questStep['askingFor']))
            throw new PSPFormValidationException('That\'s not what I\'m looking for right now...');

        if($this->inventoryService->loseItem($user, $item->getId(), [ LocationEnum::Home, LocationEnum::Basement ], 1) === 0)
        {
            throw new PSPNotFoundException('You don\'t seem to have ' . $item->getNameWithArticle() . '...');
        }

        $bookstoreQuestStep->setValue($bookstoreQuestStep->getValue() + 1);
    }

    /**
     * @return array<string, int>
     */
    public function getAvailableCafe(User $user): array
    {
        $cafePrices = [
            'Coffee Bean Tea' => 8,
            'Shortbread Cookies' => 11,
            'Berry Muffin' => 12,
            'Pumpkin Bread' => 13,
            'Chocomilk' => 11
        ];

        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
            $cafePrices['Eggnog'] = 12;

        if(CalendarFunctions::isApricotFestival($this->clock->now))
            $cafePrices['Apricot Coffee Bean Tea with Mammal Extract'] = 12;

        return $cafePrices;
    }

    /**
     * @return array<string, int>
     * @throws EnumInvalidValueException
     */
    public function getAvailableGames(User $user): array
    {
        $gamePrices = [
            'Formation' => 15,
            'Lunchbox Paint' => 25,
            '★Kindred Player\'s Handbook' => 60,
        ];

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::HollowEarth))
        {
            $gamePrices['Hollow Earth Booster Pack: Beginnings'] = 200;
            $gamePrices['Hollow Earth Booster Pack: Community Pack'] = 200;

            if(CalendarFunctions::isApricotFestival($this->clock->now))
                $gamePrices['Tile: Pluot Parade'] = 20;
        }

        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
        {
            $gamePrices['Rock-painting Kit (for Kids)'] = 65; // 1 of each dye + 3 rocks
            $gamePrices['Sneqos & Ladders'] = 90; // 1 scales, 2 talon, 4 sticks + a six-sided die

            if($user->hasUnlockedFeature(UnlockableFeatureEnum::HollowEarth))
                $gamePrices['Tile: Everice Cream'] = 200;
        }

        return $gamePrices;
    }

    /**
     * @return array<string, int>
     */
    public function getAvailableSelfImprovement(User $user): array
    {
        $prices = [
            'Welcome Note' => 10, // remember: this item can be turned into plain paper
        ];

        if($this->renamingScrollAvailable($user))
            $prices['Renaming Scroll'] = $this->getRenamingScrollCost($user);

        $cookedSomething = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::CookedSomething ]);

        if($cookedSomething && $cookedSomething->getValue() >= 500)
            $prices['Ultimate Chef'] = 500;

        ksort($prices);

        return $prices;
    }

    /**
     * @return array<string, int>
     */
    public function getAvailableCooking(User $user): array
    {
        $prices = [
            'Cooking 101' => 15,
        ];

        $cookedSomething = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::CookedSomething ]);

        if($cookedSomething)
        {
            if($cookedSomething->getValue() >= 1)
                $prices['Unlocking the Secrets of Grandparoot'] = 15;

            if($cookedSomething->getValue() >= 5)
                $prices['Candy-maker\'s Cookbook'] = 20;

            if($cookedSomething->getValue() >= 10)
                $prices['Big Book of Baking'] = 25;

            if($cookedSomething->getValue() >= 20)
            {
                $prices['Fish Book'] = 20;
                $prices['Of Rice'] = 50;
            }

            if($cookedSomething->getValue() >= 50)
            {
                $prices['Juice'] = 15;
                $prices['We All Scream'] = 15;
            }

            if($cookedSomething->getValue() >= 100)
            {
                $prices['Pie Recipes'] = 15;
                $prices['Milk: The Book'] = 30;
            }

            if($cookedSomething->getValue() >= 150)
            {
                $prices['The School of Jelly'] = 15;
            }

            if($cookedSomething->getValue() >= 200)
            {
                $prices['Fried'] = 25;
                $prices['The Art of Tofu'] = 25;
            }

            if($cookedSomething->getValue() >= 300)
            {
                $prices['SOUP'] = 25;
            }

            if($cookedSomething->getValue() >= 400)
            {
                $prices['Cuckoo for Coconuts'] = 20;
            }
        }

        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
            $prices['Bûche De Noël Recipe'] = 10;

        $itemsDonatedToMuseum = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::ItemsDonatedToMuseum ]);

        if($itemsDonatedToMuseum && $itemsDonatedToMuseum->getValue() >= 600)
            $prices['Book of Noods'] = 20;

        ksort($prices);

        return $prices;
    }

    /**
     * @return array<string, int>
     */
    public function getAvailableScienceAndNature(User $user): array
    {
        $prices = [
            'A Guide to Our Weather' => 15,
        ];

        $itemsDonatedToMuseum = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::ItemsDonatedToMuseum ]);

        if($itemsDonatedToMuseum)
        {
            if($itemsDonatedToMuseum->getValue() >= 200)
            {
                $prices['Electrical Engineering Textbook'] = 50;
                $prices['Physics Textbook'] = 50;
            }

            if($itemsDonatedToMuseum->getValue() >= 300)
                $prices['The Umbra'] = 25;
        }

        $numberOfFeaturesUnlocked = count($user->getUnlockedFeatures());

        if($numberOfFeaturesUnlocked >= 15)
            $prices['The Science of Ensmallening'] = 100;

        if($numberOfFeaturesUnlocked >= 18)
            $prices['The Science of Embiggening'] = 100;

        ksort($prices);

        return $prices;
    }

    /**
     * @return array<string, int>
     * @throws EnumInvalidValueException
     */
    public function getAvailableHomeAndGarden(User $user): array
    {
        $prices = [];

        $flowersPurchased = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => 'Flowerbombs Purchased' ]);

        if($flowersPurchased && $flowersPurchased->getValue() > 0)
            $prices['Book of Flowers'] = 15;

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Fireplace))
        {
            $prices['Melt'] = 25;
            $prices['Forge Blueprint'] = 500;
        }

        $itemsDonatedToMuseum = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::ItemsDonatedToMuseum ]);

        if($itemsDonatedToMuseum && $itemsDonatedToMuseum->getValue() >= 150)
            $prices['Basement Blueprint'] = 150;

        if(
            $user->getGreenhouse() &&
            $user->getGreenhouse()->getMaxPlants() + $user->getGreenhouse()->getMaxWaterPlants() + $user->getGreenhouse()->getMaxDarkPlants() > 6
        )
        {
            $prices['Bird Bath Blueprint'] = 200;
        }

        ksort($prices);

        return $prices;
    }

    /**
     * @return array<string, int>
     * @throws EnumInvalidValueException
     */
    public function getAvailableLiterature(User $user): array
    {
        $prices = [];

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Library))
        {
            $prices['⤮'] = 25;
            $prices['The Open Window'] = 20;
        }

        ksort($prices);

        return $prices;
    }

    /**
     * @return array<string, int>
     * @throws EnumInvalidValueException
     */
    public function getAvailableBooks(User $user): array
    {
        return array_merge(
            $this->getAvailableSelfImprovement($user),
            $this->getAvailableCooking($user),
            $this->getAvailableScienceAndNature($user),
            $this->getAvailableHomeAndGarden($user),
            $this->getAvailableLiterature($user),
        );
    }

    public function getRenamingScrollCost(User $user): int
    {
        // 800 -> 250
        $bookstoreQuestStep = UserQuestRepository::findOrCreate($this->em, $user, self::BOOKSTORE_QUEST_NAME, 0);

        return max(250, 800 - $bookstoreQuestStep->getValue() * 25);
    }

    public function renamingScrollAvailable(User $user): bool
    {
        $petsAdopted = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::PetsAdopted ]);

        if($petsAdopted && $petsAdopted->getValue() > 0)
            return true;

        $petsBirthed = $this->em->getRepository(UserStats::class)->findOneBy([ 'user' => $user, 'stat' => UserStat::PetsBirthed ]);

        if($petsBirthed && $petsBirthed->getValue() > 0)
            return true;

        return false;
    }

    private function getDialog(User $user): string
    {
        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
            return 'We\'ve got some special items in for Stocking Stuffing Season! Let me know if I can get you something.';
        else
            return 'What can I get you?';
    }

    /**
     * @return array<int, array{name: string, icon: string, inventory: array{item: \App\Entity\Item, price: int}[]}>
     */
    public function getAisles(User $user): array
    {
        $aisleDefinitions = [
            ['name' => 'Self-improvement', 'icon' => 'fa-solid fa-lightbulb', 'prices' => $this->getAvailableSelfImprovement($user)],
            ['name' => 'Cooking', 'icon' => 'fa-solid fa-hat-chef', 'prices' => $this->getAvailableCooking($user)],
            ['name' => 'Science & Nature', 'icon' => 'fa-solid fa-moon-over-sun', 'prices' => $this->getAvailableScienceAndNature($user)],
            ['name' => 'Home & Garden', 'icon' => 'fa-solid fa-house', 'prices' => $this->getAvailableHomeAndGarden($user)],
            ['name' => 'Literature', 'icon' => 'fa-solid fa-pen-fancy', 'prices' => $this->getAvailableLiterature($user)],
            ['name' => 'Toys', 'icon' => 'fa-solid fa-puzzle-piece', 'prices' => $this->getAvailableGames($user)],
            ['name' => 'Café', 'icon' => 'fa-solid fa-mug-hot', 'prices' => $this->getAvailableCafe($user)],
        ];

        $aisles = [];
        foreach($aisleDefinitions as $aisle)
        {
            if(count($aisle['prices']) > 0)
            {
                $aisles[] = [
                    'name' => $aisle['name'],
                    'icon' => $aisle['icon'],
                    'inventory' => $this->serializeShopInventory($aisle['prices']),
                ];
            }
        }

        return $aisles;
    }

    public function getResponseData(User $user): array
    {
        if($this->renamingScrollAvailable($user))
        {
            $bookstoreQuestStep = UserQuestRepository::findOrCreate($this->em, $user, BookstoreService::BOOKSTORE_QUEST_NAME, 0);
            $quest = BookstoreService::getBookstoreQuestStep($bookstoreQuestStep->getValue());
        }
        else
            $quest = null;

        return [
            'dialog' => $this->getDialog($user),
            'aisles' => $this->getAisles($user),
            'quest' => $quest,
        ];
    }

    /**
     * @param array<string, int> $inventory
     * @return array{item: \App\Entity\Item, price: int}[]
     */
    private function serializeShopInventory(array $inventory): array
    {
        $itemNames = array_keys($inventory);

        return array_map(
            fn(string $itemName) => [
                'item' => ItemRepository::findOneByName($this->em, $itemName),
                'price' => $inventory[$itemName]
            ],
            $itemNames
        );
    }
}
