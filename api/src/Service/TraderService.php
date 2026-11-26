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

use App\Entity\DailyMarketItemAverage;
use App\Entity\Item;
use App\Entity\MuseumItem;
use App\Entity\Trader;
use App\Entity\TradesUnlocked;
use App\Entity\User;
use App\Enum\CostOrYieldTypeEnum;
use App\Enum\LocationEnum;
use App\Enum\MonsterOfTheWeekEnum;
use App\Enum\TradeGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotEnoughCurrencyException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\UnreachableException;
use App\Functions\ArrayFunctions;
use App\Functions\CalendarFunctions;
use App\Functions\ColorFunctions;
use App\Functions\ItemRepository;
use App\Functions\SimpleDb;
use App\Model\ItemQuantity;
use App\Model\TraderOffer;
use App\Model\TraderOfferCostOrYield;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;

class TraderService
{
    /** @var string[]  */
    private const array TraderNames = [
        // Saffron
        'Azafrán', 'Zaeafran',

        // Platinum
        'Platin', 'Albalatin',

        // Taaffeite
        'Taaffeite',

        // Jade
        'Jade', 'Yù',

        // Vanilla
        'Vanille', 'Fanilana',

        // Mahlab
        'Mahlab',

        // Cardamom
        'Ilaayachee',

        // Kadupul Flower
        'Kadupul',

        // Orchid
        'Lánhuā',

        // Fugu
        'Fugu',

        // Wagyu
        'Wagyu',

        // Silk
        'Sī',

        // Truffle
        'Truffe', 'Sōnglù',

        // Caviar
        'Kyabia',

        // Salt
        'Salz', 'Milh', 'Sel',

        // Dyes
        'Purpura', 'Tekhelet', 'Murex',

        // Dekopon
        'Dekopon',

        // Kyoho
        'Kyoho',

        // Diamond
        'Almaznyye',

        // Ruby
        'Ruby', 'Rubis',
    ];

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly TransactionService $transactionService,
        private readonly IRandom $rng,
        private readonly Clock $clock,
        private readonly EntityManagerInterface $em,
        private readonly CacheHelper $cache,
        private readonly UserStatsService $userStatsService
    )
    {
    }

    /**
     * @return TradeGroupEnum[]
     */
    public function getUnlockedTradeGroups(User $user): array
    {
        $tradesUnlocked = $this->em->getRepository(TradesUnlocked::class)->findBy([
            'user' => $user->getId()
        ]);

        return array_map(fn(TradesUnlocked $tu) => $tu->getTrades(), $tradesUnlocked);
    }

    /**
     * @return TradeGroupEnum[]
     */
    public function getLockedTradeGroups(User $user): array
    {
        $locked = [];
        $unlocked = $this->getUnlockedTradeGroups($user);

        foreach(TradeGroupEnum::cases() as $group)
        {
            if(!in_array($group, $unlocked))
                $locked[] = $group;
        }

        return $locked;
    }

    public function getOfferById(User $user, string $id): ?TraderOffer
    {
        $offers = $this->getOffers($user);

        foreach($offers as $offerGroup)
        {
            $exchange = array_find($offerGroup['trades'], fn(TraderOffer $o) => $o->id === $id);

            if($exchange !== null)
                return $exchange;
        }

        return null;
    }

    /**
     * @return array{title: string, trades: TraderOffer[]}[]
     */
    public function getOffers(User $user): array
    {
        $quantities = $this->inventoryService->getInventoryQuantities($user, LocationEnum::Home, 'name');

        /** @var array{title: string, trades: TraderOffer[]}[] $offers */
        $offers = [
            [
                'title' => 'Food',
                'trades' => $this->getFoodsOffers($user, $quantities)
            ]
        ];

        $tradeGroups = $this->getUnlockedTradeGroups($user);

        foreach($tradeGroups as $group)
        {
            switch ($group)
            {
                case TradeGroupEnum::Metals:
                    $title = 'Metals';
                    $trades = $this->getMetalOffers($user, $quantities);
                    break;

                case TradeGroupEnum::DarkThings:
                    $title = 'Umbral';
                    $trades = $this->getUmbralThingsOffers($user, $quantities);
                    break;

                case TradeGroupEnum::Curiosities:
                    $title = 'Curiosities';
                    $trades = $this->getCuriositiesOffers($user, $quantities);
                    break;

                case TradeGroupEnum::Plushies:
                    $title = 'Plushies';
                    $trades = $this->getPlushyOffers($user, $quantities);
                    break;

                case TradeGroupEnum::HollowEarth:
                    $title = 'Hollow Earth';
                    $trades = $this->getHollowEarthOffers($user, $quantities);
                    break;

                case TradeGroupEnum::Bleach:
                    $title = 'Bleach';
                    $trades = $this->getBleachOffers($user, $quantities);
                    break;

                case TradeGroupEnum::Digital:
                    $title = 'Digital';
                    $trades = $this->getDigitalOffers($user, $quantities);
                    break;

                case TradeGroupEnum::Bugs:
                    $title = 'Bugs';
                    $trades = $this->getBugOffers($user, $quantities);
                    break;

                case 3: // old "FOODS" group unlock
                case 5: // old "BOX-BOX" group unlock
                    continue 2; // why "2"? see https://www.php.net/manual/en/control-structures.continue.php >_>
                default:
                    throw new \Exception('You have unlocked trade group #' . $group->name . '... which does not exist. Ben should fix this.');
            }

            $offers[] = [
                'title' => $title,
                'trades' => $trades
            ];
        }

        usort($offers, fn($a, $b) => $a['title'] <=> $b['title']);

        $holidayOffers = $this->getSpecialOffers($user, $quantities);

        if(count($holidayOffers) > 0)
        {
            array_unshift($offers, [
                'title' => 'Special Offers',
                'trades' => $holidayOffers
            ]);
        }

        return $offers;
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getBleachOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Dragon Flag'), 1),
                    TraderOfferCostOrYield::createMoney(10)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Flag'), 1) ],
                'Not the flag design you were looking for? You should try the Flag of Tell Samarzhoustia!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Sun Flag'), 1),
                    TraderOfferCostOrYield::createMoney(10)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Flag'), 1) ],
                'Not the flag design you were looking for? You should try the Flag of Tell Samarzhoustia!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Flag'), 1),
                    TraderOfferCostOrYield::createMoney(10)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Flag'), 1) ],
                'Not the flag design you were looking for? You should try the Flag of Tell Samarzhoustia!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Feathers'), 1),
                    TraderOfferCostOrYield::createMoney(10)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Feathers'), 1) ],
                'Sometimes it\'s just easier to defeat a demon than a pegasus, you know?',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Filthy Cloth'), 1),
                    TraderOfferCostOrYield::createMoney(5)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Cloth'), 1) ],
                'There you go! Good as new!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Chocolate-stained Cloth'), 1),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Cloth'), 1),
                ],
                'There you go! Good as new!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Chocolate-stained Cloth'), 1),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Cocoa Powder'), 1),
                ],
                'There you go! Usable Cocoa Powder!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Chocolate Feather Bonnet'), 1),
                    TraderOfferCostOrYield::createMoney(100)
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Chocolate Feather Bonnet'), 1),
                ],
                "It's still edible, too! Oh, but did you know there's a bee in there? I think it was a little agitated by the bleaching process, so... eat carefully, I guess is what I'm saying.",
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Baabble'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(25),
                    TraderOfferCostOrYield::createMoney(25),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Baabble'), 1) ],
                'Just don\'t tell the satyrs I did this for you. I\'m pretty sure they\'d consider it cheating...',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Red Firework'), 1),
                    TraderOfferCostOrYield::createMoney(10)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Firework'), 1) ],
                'There\'s a little Tell Samarzhoustia chemistry for you!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Blue Firework'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Firework'), 1) ],
                'There\'s a little Tell Samarzhoustia chemistry for you!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Top Hat'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Bright Top Hat'), 1) ],
                'Huh: the band bleached out to be purple? Must be some kind of powerful dye they used, there...',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Jolliest Roger'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Creamiest Roger'), 1) ],
                'Well, it came out kind of funny. I think I liked the red better, myself, but hey: whatever floats your pirate boat!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Dark Horsey Hat'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Zebra "Horsey" Hat'), 1) ],
                'Someone was telling me that zebras _aren\'t_ a kind of horse? Is that true?? I can\'t keep all your weird land animals straight.',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Zebra "Horsey" Hat'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Horsey Hat'), 1) ],
                'And you\'re _super_ sure zebras aren\'t horses? It\'s just-- I\'m not-- ... you know??',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Horsey Hat'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Horsey Hat'), 1) ],
                'It\'s fun that horses come in so many colors! But what about, like, purple horses? Are there any of those?',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Paint Stripper'), 1) ],
                'I\'ve been getting some requests to "bleach" painted items. That\'s not really how that works, but I figured I could at least sell you guys some Paint Stripper, so you can do it yourself, if you want.',
                $user,
                $quantities
            ),
        ];
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getDigitalOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'NUL'), 5),
                    TraderOfferCostOrYield::createMoney(2),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 1) ],
                'XORs are, like, one of the de facto currencies for those trading in Project-E.',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Pointer'), 3),
                    TraderOfferCostOrYield::createMoney(1),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 1) ],
                'XORs are, like, one of the de facto currencies for those trading in Project-E.',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 5) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fruits & Veggies Box'), 1) ],
                'Enjoy the fruits and veggies!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 5) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Baker\'s Box'), 1) ],
                'Enjoy the baking supplies!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 5) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Handicrafts Supply Box'), 1) ],
                'Enjoy the crafting supplies!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'XOR'), 5) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hat Box'), 1) ],
                'Enjoy the hat!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Short-range Telephone'), 1),
                    TraderOfferCostOrYield::createMoney(100),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Rotary Phone'), 1) ],
                "Enjoy the hat!\n\nIt _is_ a hat, right??",
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createRecyclingPoints(200)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Digital Camera'), 1) ],
                "An aspiring photographer, eh? Have fun!",
                $user,
                $quantities
            ),
            ...array_map(
                fn(array $upgrade) => TraderOffer::createTradeOffer(
                    [
                        TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $upgrade[0]), 1),
                        TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Pointer'), 1),
                        TraderOfferCostOrYield::createMoney(100),
                    ],
                    [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $upgrade[1]), 1) ],
                    'There! I\'ve pointed your ' . $upgrade[0] . ' at the next letter. Enjoy your ' . $upgrade[1] . '!',
                    $user,
                    $quantities
                ),
                [
                    [ 'B. PACK', 'C. PACK' ],
                    [ 'C. PACK', 'D. PACK' ],
                    [ 'D. PACK', 'E. PACK' ],
                ]
            ),
        ];
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getBugOffers(User $user, array $quantities): array
    {
        $stickInsect = ItemRepository::findOneByName($this->em, 'Stick Insect');

        return [
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 1), TraderOfferCostOrYield::createRecyclingPoints(1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Yeast'), 3) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 1), TraderOfferCostOrYield::createRecyclingPoints(1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Sunflower'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 3), TraderOfferCostOrYield::createRecyclingPoints(3) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Quintessence'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 6), TraderOfferCostOrYield::createRecyclingPoints(6) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Wrapped Sword'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 10), TraderOfferCostOrYield::createRecyclingPoints(10) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fish Statue'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 12), TraderOfferCostOrYield::createRecyclingPoints(12) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Glowing Twelve-sided Die'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 17), TraderOfferCostOrYield::createRecyclingPoints(17) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Chest'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem($stickInsect, 20), TraderOfferCostOrYield::createRecyclingPoints(20) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Ruby Chest'), 1) ],
                'Useful little fellas, these Stick Insects! Thanks a lot!',
                $user,
                $quantities
            ),
        ];
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getHollowEarthOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(100) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hollow Earth Booster Pack: Beginnings'), 1) ],
                'Have fun!',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(100) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hollow Earth Booster Pack: Community Pack'), 1) ],
                'Have fun!',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(4) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Glowing Four-sided Die'), 1) ],
                'To be honest, those dice kind of give me the willies. And only four sides? That\'s just not right.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(6) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Glowing Six-sided Die'), 1) ],
                'To be honest, those dice kind of give me the willies.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(8) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Glowing Eight-sided Die'), 1) ],
                'To be honest, those dice kind of give me the willies. And _eight_ sides?? It\'s unnatural.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createRecyclingPoints(20),
                    TraderOfferCostOrYield::createMoney(40)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Scroll of Dice'), 1) ],
                'To be honest, those dice kind of give me the willies. A whole scroll of them? Yeah, no thanks!',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Iron Key'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Key'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Key'), 1),
                    TraderOfferCostOrYield::createMoney(1),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Key Ring'), 1) ],
                'Do enjoy!',
                $user,
                $quantities
            ),
        ];
    }

    /**
     * @return array{item: Item, value: int}[]
     */
    private function getSpecialOfferItemsAndPrices(int $offers): array
    {
        if($offers <= 0) return [];

        return $this->cache->getOrCompute(
            'Trader Special Offers ' . $this->clock->now->format('Y-m-d'),
            \DateInterval::createFromDateString('1 day'),
            fn() => $this->computeSpecialOfferItemsAndPrices($offers)
        );
    }

    /**
     * @return array{item: Item, value: int}[]
     */
    private function computeSpecialOfferItemsAndPrices(int $offers): array
    {
        $results = [];

        $uniqueOfferItems = $this->findItemsForDailySpecialOffers($offers);

        foreach($uniqueOfferItems as $uniqueOfferItem)
        {
            $averageValue = $this->em->getRepository(DailyMarketItemAverage::class)->createQueryBuilder('a')
                ->select('AVG(a.averagePrice) AS averagePrice, COUNT(a.id) AS count')
                ->andWhere('a.item=:item')->setParameter('item', $uniqueOfferItem->getId())
                ->andWhere('a.date >= :date')->setParameter('date', $this->clock->now->modify('-3 months'))
                ->getQuery()
                ->getSingleResult(AbstractQuery::HYDRATE_ARRAY)
            ;

            $computedValue = $uniqueOfferItem->getRecycleValue() * 1.3334 + $uniqueOfferItem->getMuseumPoints() * 0.5;

            $value = (int)($averageValue['count'] < 28 == 0 ? $computedValue : ceil(($averageValue['averagePrice'] + $computedValue) / 2));

            $results[] = [
                'item' => $uniqueOfferItem,
                'value' => $value
            ];
        }

        return $results;
    }

    private function currentMonsterOfTheWeekType(): ?MonsterOfTheWeekEnum
    {

        $db = SimpleDb::createReadOnlyConnection();

        $query = $db->query(
            "
                SELECT monster.monster
                FROM monster_of_the_week AS monster
                WHERE ? >= monster.start_date AND ? <= monster.end_date
                LIMIT 1
            ",
            [
                date("Y-m-d"), date("Y-m-d")
            ]
        );

        $data = $query->getResults();

        return count($data) == 0
            ? null
            : MonsterOfTheWeekEnum::tryFrom($data[0]['monster'])
        ;
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getSpecialOffers(User $user, array $quantities): array
    {
        $offers = [];

        if($this->currentMonsterOfTheWeekType() === MonsterOfTheWeekEnum::VafAndNir)
        {
            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createMoney(100) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Small Offering of Riches'), 1) ],
                'Good luck with Vaf & Nir! Those two need to dial it in!',
                $user,
                $quantities
            );

            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createMoney(500) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Medium Offering of Riches'), 1) ],
                'Good luck with Vaf & Nir! Those two need to dial it in!',
                $user,
                $quantities
            );

            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createMoney(2500) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Large Offering of Riches'), 1) ],
                'Good luck with Vaf & Nir! Those two need to dial it in!',
                $user,
                $quantities
            );
        }

        if(CalendarFunctions::isApricotFestival($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hat Box'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Aprihat'), 1) ],
                'We have a similar festival in Tell Samarazhoustia. Ah, but not for Apricots, of course - those don\'t grow underwater!',
                $user,
                $quantities
            );
        }

        if(CalendarFunctions::isPsyPetsBirthday($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createMoney(20),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, '"Roy" Plushy'), 1) ],
                'This is a special offer for PsyPets\' birthday. Tess and Mia insisted. Oh, but cool bonus: unlike other plushies, you can wear this as a hat. Not sure why that is, really...',
                $user,
                $quantities
            );
        }

        if(CalendarFunctions::isPSPBirthday($this->clock->now))
        {
            $presentColors = [
                'Purple PSP B-day Present',
                'Red PSP B-day Present',
                'Yellow PSP B-day Present',
            ];

            foreach($presentColors as $fromPresent)
            {
                foreach($presentColors as $toPresent)
                {
                    if($toPresent == $fromPresent) continue;

                    $offers[] = TraderOffer::createTradeOffer(
                        [
                            TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $fromPresent), 1),
                            TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Baabble'), 1)
                        ],
                        [
                            TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $toPresent), 1),
                        ],
                        'Thanks for the baabble! Those satyr dice just aren\'t for me, you know?',
                        $user,
                        $quantities,
                        true
                    );
                }
            }
        }

        if(CalendarFunctions::isSnakeDay($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createMoney(5000),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Headsnake'), 1),
                ],
                'All proceeds go to sea snake preservation charities. (They were nearly hunted to extinction is Tell Samarazhoustia, you know!)',
                $user,
                $quantities,
                true
            );
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createRecyclingPoints(2500),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Headsnake'), 1),
                ],
                'All proceeds go to sea snake preservation charities. (They were nearly hunted to extinction is Tell Samarazhoustia, you know!)',
                $user,
                $quantities,
                true
            );
        }

        if(CalendarFunctions::isValentinesOrAdjacent($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Twu Wuv'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'White Cloth'), 1),
                    TraderOfferCostOrYield::createMoney(100),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Pink Bow'), 1),
                ],
                'Please enjoy the complimentary chocolate, and remember: all candies "recycled" during Valentine\'s are guaranteed to find their way to the Giving Tree, where any pet may collect them!',
                $user,
                $quantities,
                true
            );
        }

        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Talon'), 2) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Antlers'), 1) ],
                'These were cut from Antlerfish earlier this year. Catch and release, of course. The antlers grow back every year, mating season is already over, and the antlers actually make the fish easier targets for predators, so the system really works out for everyone! Well, except for Antlerfish predators, I suppose. They kinda\' lose out.',
                $user,
                $quantities
            );

            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(20) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Tawny Ears'), 1) ],
                'I _guess_ these could be reindeer ears? That\'s my best guess, anyway. Oh: made of plastic and fluff, of course! They\'re not _real_ ears! That would be brutal.',
                $user,
                $quantities
            );
        }

        if(self::isEasterTradesAvailable($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Blue Plastic Egg'), 10),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Yellow Plastic Egg'), 1),
                ],
                'We fish collect the things, too, you know!',
                $user,
                $quantities
            );

            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Yellow Plastic Egg'), 5),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Pink Plastic Egg'), 1),
                ],
                'We fish collect the things, too, you know!',
                $user,
                $quantities
            );
        }

        if($this->clock->now->format('M') === 'Nov')
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Turkey King'), 1)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Bleached Turkey Head'), 1) ],
                'Oh, I don\'t need any Moneys - the little crown is good enough for me!',
                $user,
                $quantities
            );
        }

        if($this->clock->now->format('M') === 'Oct')
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Talon'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Quintessence'), 1),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Unicorn Horn'), 1),
                ],
                'Triangular hats are where it\'s at. That one\'s rather... isosceles, though. (Isoscelic? Isoscelean? Whatever.)',
                $user,
                $quantities
            );

            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Toadstool'), 1),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Tinfoil Hat'), 1),
                ],
                'Triangular hats are where it\'s at. That one\'s rather... crinkly, though. And no plume!',
                $user,
                $quantities
            );
        }

        if(CalendarFunctions::isMayThe4th($this->clock->now))
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Photon'), 1),
                    TraderOfferCostOrYield::createMoney(100),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Lightpike'), 1),
                ],
                "Masters of the Lightpike can cut you down in one stab!",
                $user,
                $quantities
            );
        }

        if(CalendarFunctions::isCreepyMaskDay($this->clock->now))
        {
            if($this->clock->now->format('n') >= 10 || $this->clock->now->format('n') <= 3) // Oct - Mar
                $masks = [ 'Ashen Yew', 'Crystalline', 'Gold Devil' ];
            else
                $masks = [ 'Blue Magic', 'La Feuille', 'The Unicorn' ];

            $payment = self::getCreepyMaskDayPayment((int)$this->clock->now->format('n'));

            foreach($masks as $mask)
            {
                $offers[] = TraderOffer::createTradeOffer(
                    [
                        TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $payment[0]), $payment[1]),
                    ],
                    [
                        TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, $mask), 1),
                    ],
                    "",
                    $user,
                    $quantities,
                    true
                );
            }
        }

        if(self::isEtalocohcDay($this->clock->now))
        {
            $cost = self::getEtalocohcCost($this->clock->now);

            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createMoney($cost),
                    TraderOfferCostOrYield::createRecyclingPoints($cost)
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Etalocŏhc'), 1),
                ],
                'I happened to get my hands on a few of these today; thought you might find them interesting.',
                $user,
                $quantities,
            );
        }

        $uniqueOffers = $this->getSpecialOfferItemsAndPrices(self::NumberOfDailySpecialOffers - count($offers));

        foreach($uniqueOffers as $uniqueOffer)
        {
            $offers[] = TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem($uniqueOffer['item'], 1),
                ],
                [
                    TraderOfferCostOrYield::createMoney($uniqueOffer['value']),
                ],
                'It\'s a special offer, just for you.',
                $user,
                $quantities
            );
        }

        return $offers;
    }

    /**
     * @return array{0: string, 1: int}
     */
    public static function getCreepyMaskDayPayment(int $month): array
    {
        return match ($month)
        {
            1 => [ 'Magic Pinecone', 1 ],
            2 => [ 'Wed Bawwoon', 1 ],
            3 => [ 'Gummy Worms', 3 ],
            4 => [ 'Mysterious Seed', 1 ],
            5 => [ 'Petrichor', 1 ],
            6 => [ 'Sun-sun Flag-flag, Son', 1 ],
            7 => [ 'Rainbow Wings', 1 ],
            8 => [ 'Fermented Fish Onigiri', 3 ],
            9 => [ 'Little Strongbox', 1 ],
            10 => [ 'Tile: Bats!', 1 ],
            11 => [ 'Regular-sized Pumpkin', 1 ],
            12 => [ 'Wand of Lightning', 1 ],
            default => throw new \InvalidArgumentException('Invalid month: ' . $month),
        };
    }

    public const int NumberOfDailySpecialOffers = 5;

    /**
     * @return Item[]
     */
    public function findItemsForDailySpecialOffers(int $offers): array
    {
        $count = (int)$this->em->getRepository(Item::class)->createQueryBuilder('i')
            ->select('COUNT(i)')
            ->andWhere('i.recycleValue > 0')
            ->andWhere('i.treasure IS NULL')
            ->andWhere('i.fuel < 4')
            ->andWhere('i.fertilizer < 4')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $random = (((int)$this->clock->now->format('Ymd') - 20040404) * 6737 + 76801) % ($count - $offers + 1);

        return $this->em->getRepository(Item::class)->createQueryBuilder('i')
            ->andWhere('i.recycleValue > 0')
            ->andWhere('i.treasure IS NULL')
            ->andWhere('i.fuel < 4')
            ->andWhere('i.fertilizer < 4')
            ->orderBy('i.id', 'ASC')
            ->setFirstResult($random)
            ->setMaxResults($offers)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getMetalOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Bar'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(3),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Bar'), 1) ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Ore'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(2),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Ore'), 1) ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Iron Bar'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(3),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Bar'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Iron Ore'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(2),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Ore'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Bar'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(3),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Iron Bar'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Ore'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(2),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Iron Ore'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Bar'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(3),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Bar'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Silver Ore'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(2),
                ],
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Ore'), 1),
                ],
                'Thank you kindly.',
                $user,
                $quantities
            ),
        ];
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getUmbralThingsOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Charcoal'), 2),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Dark Matter'), 2),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Blackberries'), 2),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Tea'), 2),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Blackonite'), 1) ],
                'The technique for forging Blackonite is unknown, even to Tell Samarzhoustia. But we _have_ established trade with a creature that knows the secret. The Charcoal, etc, is essentially an offering.',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fairy Ring'), 3),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Quinacridone Magenta Dye'), 1),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fairy Swarm'), 1) ],
                'Quinacridone Magenta is pretty valuable stuff. Naturally, King Nebuludwigula XIII has a few robes dyed that color.',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Feathers'), 3),
                    TraderOfferCostOrYield::createMoney(5),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Quintessence'), 1) ],
                'In Tell Samarzhoustian mythology, birds are associated with magic...',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Quintessence'), 1),
                    TraderOfferCostOrYield::createMoney(5),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Feathers'), 3) ],
                'In Tell Samarzhoustian mythology, birds are associated with magic...',
                $user,
                $quantities
            ),
            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Heart Beetle'), 1),
                    TraderOfferCostOrYield::createRecyclingPoints(2),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Harmony-dusted Donut'), 1) ],
                'What? No, we don\'t _cook_ them! That would be barbaric...',
                $user,
                $quantities
            )
        ];
    }

    private function stillOfferingDeedForGreenhousePlot(User $user): bool
    {
        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Greenhouse))
            return true;

        $deedForGreenhousePlot = ItemRepository::findOneByName($this->em, 'Deed for Greenhouse Plot');

        // keep offering them for sale until you've donated one to the museum
        if($this->em->getRepository(MuseumItem::class)->count([ 'user' => $user, 'item' => $deedForGreenhousePlot ]) == 0)
            return true;

        return false;
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getFoodsOffers(User $user, array $quantities): array
    {
        $offers = [];

        if($this->stillOfferingDeedForGreenhousePlot($user))
        {
            $deedForGreenhousePlot = ItemRepository::findOneByName($this->em, 'Deed for Greenhouse Plot');

            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createMoney(100) ],
                [ TraderOfferCostOrYield::createItem($deedForGreenhousePlot, 1) ],
                "Oh, fun, a greenhouse! What kind of Kelp will you be gr-- oh. Right, I suppose you'll just be growing Landweed, and such.\n\nWell.\n\nHave fun with that, I suppose.",
                $user,
                $quantities,
                true
            );
            $offers[] = TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(50) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Deed for Greenhouse Plot'), 1) ],
                "Oh, fun, a greenhouse! What kind of Kelp will you be gr-- oh. Right, I suppose you'll just be growing Landweed, and such.\n\nWell.\n\nHave fun with that, I suppose.",
                $user,
                $quantities,
                true
            );
        }

        $offers[] = TraderOffer::createTradeOffer(
            [
                TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Limestone'), 2),
            ],
            [
                TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Scroll of Tell Samarzhoustian Delights'), 1),
            ],
            'Limestone is an important building material in Tell Samarzhoustia. We build beautiful palaces, and enormous chimera statues. Well, enormous by fish standards. You should visit, sometime.',
            $user,
            $quantities
        );

        $offers[] = TraderOffer::createTradeOffer(
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Moon Pearl'), 1) ],
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Cooking Buddy'), 1) ],
            'That\'s no knock-off! Tell Samarzhoustia trades directly with the Eridanus Federation!',
            $user,
            $quantities
        );

        $offers[] = TraderOffer::createTradeOffer(
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Moon Pearl'), 1) ],
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hot Pot'), 1) ],
            'That\'s no knock-off! Tell Samarzhoustia trades directly with the Eridanus Federation!',
            $user,
            $quantities
        );

        $offers[] = TraderOffer::createTradeOffer(
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Moon Pearl'), 1) ],
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Composter'), 1) ],
            'That\'s no knock-off! Tell Samarzhoustia trades directly with the Eridanus Federation!',
            $user,
            $quantities
        );

        $offers[] = TraderOffer::createTradeOffer(
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hapax Legomenon'), 1) ],
            [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Hebenon'), 1) ],
            'That\'s some wild stuff! Careful how you use it!',
            $user,
            $quantities
        );

        return $offers;
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getCuriositiesOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Secret Seashell'), 20) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Level 2 Sword'), 1) ],
                'It\'s dangerous to go alone. Take this.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Black Baabble'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, '3D Printer'), 1) ],
                'Please use this responsibly, human. The amount of Plastic ending up in the oceans these days is a bit troubling.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createMoney(400),
                    TraderOfferCostOrYield::createRecyclingPoints(200),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Submarine'), 1) ],
                'Derelict submarines sometimes drift into Tell Samarzhoustia. We fix \'em up, and sell them to land-dwellers such as yourself! Have fun!',
                $user,
                $quantities,
                true
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Music Note'), 7) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Musical Scales'), 1) ],
                'I don\'t mind letting you in on a little Tell Samarzhoustian secret: you can do this "trade" yourself at home. Just combine 7 Music Notes.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Gold Ring'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Planetary Ring'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Onion Rings'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'String'), 1)
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Rings on Strings'), 1) ],
                'This item feels kind of silly, doesn\'t it? Well, I suppose it\'s not for me to say. Oh, and thanks for the Onion Rings. I was feeling a little peckish.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Money Sink'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Garbage Disposal'), 1),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Li\'l Pocket Dimension'), 1) ],
                'I happened to get a few of these while traveling with a... well, let\'s just say he\'s a traveler. And, I mean, he didn\'t tell me _not_ to sell them, so... here we are.',
                $user,
                $quantities,
                true
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Nón Lá'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Toadstool'), 1),
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Money Sink'), 1),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Nấm Lá'), 1) ],
                'The goblin shark that sold me this told me that "nấm" mean "mushroom". Seems kind of on-the-nose, to me, but that\'s goblin sharks for you.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createMoney(1000) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Money Sink'), 1) ],
                'The Museum\'s curator insisted I make this offer...',
                $user,
                $quantities,
                true
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createRecyclingPoints(1000) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Garbage Disposal'), 1) ],
                'The Museum\'s curator insisted I make this offer...',
                $user,
                $quantities,
                true
            ),
        ];
    }

    /**
     * @param ItemQuantity[] $quantities
     * @return TraderOffer[]
     */
    private function getPlushyOffers(User $user, array $quantities): array
    {
        return [
            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Peacock Plushy'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1) ],
                'These things are so cute...',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Bulbun Plushy'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1) ],
                'These things are so cute...',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Sneqo Plushy'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1) ],
                'These things are so cute...',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Rainbow Dolphin Plushy'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1) ],
                'These things are so cute...',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Phoenix Plushy'), 1) ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1) ],
                'These things are so cute...',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Peacock Plushy'), 1) ],
                'Such a classic.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Bulbun Plushy'), 1) ],
                'These make great body pillows. Well, I guess it is a bit small for you, though.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Sneqo Plushy'), 1) ],
                'I don\'t know why these things are plushies. They have arms! Snakes shouldn\'t have arms!',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1),
                    TraderOfferCostOrYield::createMoney(12),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Rainbow Dolphin Plushy'), 1) ],
                'The extra 2~~m~~ covers a tax imposed by the Great Rainbow Dolphin Empire.',
                $user,
                $quantities
            ),

            TraderOffer::createTradeOffer(
                [
                    TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Fluff Heart'), 1),
                    TraderOfferCostOrYield::createMoney(10),
                ],
                [ TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($this->em, 'Phoenix Plushy'), 1) ],
                'A rare and beautiful bird, for a rare and beautiful customer!',
                $user,
                $quantities
            ),
        ];
    }

    public function userCanMakeExchange(User $user, TraderOffer $exchange, int $location): bool
    {
        foreach($exchange->cost as $cost)
        {
            switch ($cost->type)
            {
                case CostOrYieldTypeEnum::Item:
                    $quantity = InventoryService::countInventory($this->em, $user->getId(), $cost->item->getId(), $location);

                    if($quantity < $cost->quantity)
                        return false;

                    break;

                case CostOrYieldTypeEnum::Money:
                    if($user->getMoneys() < $cost->quantity)
                        return false;

                    break;

                case CostOrYieldTypeEnum::RecyclingPoints:
                    if($user->getRecyclePoints() < $cost->quantity)
                        return false;

                    break;

                default:
                    throw new UnreachableException();
            }
        }

        return true;
    }

    /**
     * CAREFUL: Also used by some items, to perform transmutations, and the florist!
     */
    public function makeExchange(
        User $user,
        TraderOffer $exchange,
        int $location,
        int $quantity,
        string $itemDescription = 'Received by trading with the Trader.'
    ): void
    {
        foreach($exchange->cost as $cost)
        {
            switch ($cost->type)
            {
                case CostOrYieldTypeEnum::Item:
                    $itemQuantity = $this->inventoryService->loseItem($user, $cost->item->getId(), $location, $cost->quantity * $quantity);

                    if($itemQuantity < $cost->quantity * $quantity)
                        throw new PSPNotFoundException('You do not have the items needed to make this exchange. (Needs ' . ($cost->quantity * $quantity) . ' ' . $cost->item->getName() . '; you have ' . $itemQuantity . '.)');

                    break;

                case CostOrYieldTypeEnum::Money:
                    if($user->getMoneys() < $cost->quantity * $quantity)
                        throw new PSPNotEnoughCurrencyException($cost->quantity * $quantity . '~~m~~', $user->getMoneys() . '~~m~~');

                    if($this->rng->rngNextInt(1, 50) === 1)
                        $this->transactionService->spendMoney($user, $cost->quantity * $quantity, 'Traded away at the Trader. (That\'s usually just called "buying", right?)', true, [ 'Trader' ]);
                    else
                        $this->transactionService->spendMoney($user, $cost->quantity * $quantity, 'Traded away at the Trader.', true, [ 'Trader' ]);

                    break;

                case CostOrYieldTypeEnum::RecyclingPoints:
                    if($user->getRecyclePoints() < $cost->quantity * $quantity)
                        throw new PSPNotEnoughCurrencyException($cost->quantity * $quantity . '♺', $user->getRecyclePoints() . '♺');

                    $this->transactionService->spendRecyclingPoints($user, $cost->quantity * $quantity, 'Traded away at the Trader.', [ 'Trader' ]);

                    break;

                default:
                    throw new UnreachableException();
            }
        }

        foreach($exchange->yield as $yield)
        {
            switch ($yield->type)
            {
                case CostOrYieldTypeEnum::Item:
                    for($i = 0; $i < $yield->quantity * $quantity; $i++)
                        $this->inventoryService->receiveItem($yield->item, $user, null, $itemDescription, $location, $exchange->lockedToAccount);

                    if($yield->item->getName() === 'Hebenon')
                        $this->userStatsService->incrementStat($user, 'Traded for Hebenon', $yield->quantity * $quantity);

                    break;

                case CostOrYieldTypeEnum::Money:
                    if($this->rng->rngNextInt(1, 50) === 1)
                        $this->transactionService->getMoney($user, $yield->quantity * $quantity, 'Traded for at the Trader. (That\'s usually just called "selling", right?)', [ 'Trader' ]);
                    else
                        $this->transactionService->getMoney($user, $yield->quantity * $quantity, 'Traded for at the Trader.', [ 'Trader' ]);

                    break;

                case CostOrYieldTypeEnum::RecyclingPoints:
                    $this->transactionService->getRecyclingPoints($user, $yield->quantity * $quantity, 'Traded for at the Trader.', [ 'Trader' ]);
                    break;

                default:
                    throw new UnreachableException();
            }
        }
    }

    public static function recolorTrader(IRandom $rng, Trader $trader): void
    {
        $h1 = $rng->rngNextInt(0, 255);
        $h2 = $rng->rngNextInt(0, 255);
        $h3 = $rng->rngNextInt(0, 255);

        $l2 = $rng->rngNextInt($rng->rngNextInt(40, 120), 150);

        $s3 = $rng->rngNextInt($rng->rngNextInt(0, 40), $rng->rngNextInt(160, 255));
        $l3 = $rng->rngNextInt($rng->rngNextInt(0, 40), $rng->rngNextInt(160, 255));

        if($h1 >= 30 && $h1 < 130) $h1 += 120;

        if($h2 >= 30 && $h2 < 130) $h2 += 120;

        if($h3 >= 60 && $h3 < 130) $h3 += 120;

        $h1h2TooClose = abs($h1 - $h2) <= 20;

        if($h1h2TooClose)
        {
            if($h2 > $h1)
            {
                if($h2 < 30)
                    $h2 = ($h2 - 40 + 256) % 256;
                else
                    $h2 = ($h2 + 20) % 256;
            }
            else if($h2 < $h1)
                $h2 = ($h2 - 20 + 256) % 256;
        }

        $trader
            ->setColorA(ColorFunctions::HSL2Hex($h1 / 256, $rng->rngNextInt(56, 100) / 100, 0.46))
            ->setColorB(ColorFunctions::HSL2Hex($h2 / 256, $rng->rngNextInt(56, 100) / 100, $l2 / 255))
            ->setColorC(ColorFunctions::HSL2Hex($h3 / 256, $s3 / 255, $l3 / 255))
        ;
    }

    public static function generateTrader(User $user, IRandom $rng): Trader
    {
        $trader = new Trader(user: $user, name: $rng->rngNextFromArray(self::TraderNames));

        self::recolorTrader($rng, $trader);

        return $trader;
    }

    public static function isEtalocohcDay(\DateTimeImmutable $date): bool
    {
        $n = $date->format('Ymd');

        $n = ($n * 31337) ^ ($n >> 3);
        $n = ($n * 48611) ^ ($n << 7);

        return abs($n) % 6 == 0;
    }

    public static function getEtalocohcCost(\DateTimeImmutable $date): int
    {
        $n = $date->format('mYd');

        $n = ($n * 32411) ^ ($n >> 3);
        $n = ($n * 47059) ^ ($n << 7);

        return 20 + (abs($n) % 7) * 5;
    }

    private static function isEasterTradesAvailable(\DateTimeInterface $dt): bool
    {
        if(CalendarFunctions::isEaster($dt)) return true;

        // I don't love this way of doing it, but it works for easter (whose celebrations never span two different years)
        // "z" is "the day of the year", so we can test the date that way, ignoring time
        $easter = (int)\DateTimeImmutable::createFromFormat('U', (string)easter_date((int)$dt->format('Y')))->format('z');
        $today = (int)$dt->format('z');

        return $today > $easter && $today - $easter <= 7;
    }
}
