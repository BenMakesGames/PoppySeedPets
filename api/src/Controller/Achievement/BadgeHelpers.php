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

namespace App\Controller\Achievement;

use App\Entity\User;
use App\Entity\UserBadge;
use App\Entity\UserStats;
use App\Entity\UserUnlockedFeature;
use App\Enum\BadgeEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Functions\InMemoryCache;
use App\Functions\ItemRepository;
use App\Model\TraderOfferCostOrYield;
use Doctrine\ORM\EntityManagerInterface;

final class BadgeHelpers
{
    private static function getUnlockedFieldGuideEntries(User $user): int
    {
        return $user->getFieldGuideEntries()->count();
    }

    private static function getWorkerBeeCount(User $user): int
    {
        return $user->getBeehive()?->getWorkers() ?? 0;
    }

    private static function getUnlockedAuras(User $user): int
    {
        return $user->getUnlockedAuras()->count();
    }

    /**
     * @param string[] $badgeNames
     */
    private static function getCompletedBadges(User $user, array $badgeNames): int
    {
        return array_reduce(
            $user->getBadges()->getValues(),
            fn(int $carry, UserBadge $badge) => $carry + (int)in_array($badge->getBadge(), $badgeNames),
            0
        );
    }

    /**
     * @param string[] $statNames
     */
    private static function getStatTotal(User $user, array $statNames, EntityManagerInterface $em, InMemoryCache $perRequestCache): int
    {
        $key = 'UserStatTotal:' . $user->getId() . ':' . implode(',', $statNames);

        return $perRequestCache->get($key, function() use ($user, $statNames, $em) {
            return (int)($em->createQueryBuilder()
                ->select('SUM(s.value)')
                ->from(UserStats::class, 's')
                ->andWhere('s.user = :user')
                ->andWhere('s.stat IN (:stats)')
                ->setParameter('user', $user)
                ->setParameter('stats', $statNames)
                ->getQuery()
                ->getSingleScalarResult());
        });
    }

    /**
     * @return array{badge: string, progress: array{target: int, current: int}, done:bool, reward: TraderOfferCostOrYield}
     */
    public static function getBadgeProgress(string $badge, User $user, EntityManagerInterface $em, InMemoryCache $cache): array
    {
        switch($badge)
        {
            case BadgeEnum::RECYCLED_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ItemsRecycled ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Sand Dollar'), 1);
                break;

            case BadgeEnum::RECYCLED_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::ItemsRecycled ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Minor Scroll of Riches'), 1);
                break;

            case BadgeEnum::RECYCLED_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::ItemsRecycled ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Major Scroll of Riches'), 1);
                break;

            case BadgeEnum::RECYCLED_10000:
                $progress = [ 'target' => 10000, 'current' => self::getStatTotal($user, [ UserStat::ItemsRecycled ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ruby Chest'), 1);
                break;

            case BadgeEnum::BAABBLES_OPENED_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Opened a Black Baabble', 'Opened a White Baabble', 'Opened a Gold Baabble', 'Opened a Shiny Baabble' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Key Ring'), 1);
                break;

            case BadgeEnum::BAABBLES_OPENED_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Opened a Black Baabble', 'Opened a White Baabble', 'Opened a Gold Baabble', 'Opened a Shiny Baabble' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Carrot Key'), 1);
                break;

            case BadgeEnum::BAABBLES_OPENED_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Opened a Black Baabble', 'Opened a White Baabble', 'Opened a Gold Baabble', 'Opened a Shiny Baabble' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Winged Key'), 1);
                break;

            case BadgeEnum::BAABBLES_OPENED_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ 'Opened a Black Baabble', 'Opened a White Baabble', 'Opened a Gold Baabble', 'Opened a Shiny Baabble' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Skill Scroll: Crafts'), 1);
                break;

            case BadgeEnum::WEEKDAY_COINS_TRADED_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Traded for Kat\'s Gift Package' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Quintessence'), 7);
                break;

            case BadgeEnum::WEEKDAY_COINS_TRADED_7:
                $progress = [ 'target' => 7, 'current' => self::getStatTotal($user, [ 'Traded for Kat\'s Gift Package' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Quintessence'), 7);
                break;

            case BadgeEnum::MONEYS_SPENT_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::TotalMoneysSpent ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Canned Food'), 1);
                break;

            case BadgeEnum::MONEYS_SPENT_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::TotalMoneysSpent ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Sandbox'), 1);
                break;

            case BadgeEnum::MONEYS_SPENT_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::TotalMoneysSpent ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Monster Box'), 2);
                break;

            case BadgeEnum::MONEYS_SPENT_10000:
                $progress = [ 'target' => 10000, 'current' => self::getStatTotal($user, [ UserStat::TotalMoneysSpent ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Box Box'), 2);
                break;

            case BadgeEnum::MONEYS_SPENT_100000:
                $progress = [ 'target' => 100000, 'current' => self::getStatTotal($user, [ UserStat::TotalMoneysSpent ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Hat Box'), 2);
                break;

            case BadgeEnum::Petted10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::PettedAPet ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Fortune Cookie'), 1);
                break;

            case BadgeEnum::Petted100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::PettedAPet ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Renaming Scroll'), 1);
                break;

            case BadgeEnum::Petted1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::PettedAPet ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Behatting Scroll'), 1);
                break;

            case BadgeEnum::Petted10000:
                $progress = [ 'target' => 10000, 'current' => self::getStatTotal($user, [ UserStat::PettedAPet ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Forgetting Scroll'), 1);
                break;

            case BadgeEnum::MaxPets4:
                $progress = [ 'target' => 4, 'current' => $user->getMaxPets() ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Goodberries'), 4);
                break;

            case BadgeEnum::CompleteTheHeartstoneDimension:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Pet Completed the Heartstone Dimension' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Juice Box'), 1);
                break;

            case BadgeEnum::HATTIER_STYLES_10:
                $progress = [ 'target' => 10, 'current' => self::getUnlockedAuras($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Gravy'), 1);
                break;

            case BadgeEnum::HATTIER_STYLES_20:
                $progress = [ 'target' => 20, 'current' => self::getUnlockedAuras($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Googly Eyes'), 1);
                break;

            case BadgeEnum::HATTIER_STYLES_30:
                $progress = [ 'target' => 30, 'current' => self::getUnlockedAuras($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Jelling Polyp'), 1);
                break;

            case BadgeEnum::TROPHIES_EARNED_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Silver Trophies Earned', 'Gold Trophies Earned' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Little Strongbox'), 1);
                break;

            case BadgeEnum::TROPHIES_EARNED_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Silver Trophies Earned', 'Gold Trophies Earned' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Gold Chest'), 1);
                break;

            case BadgeEnum::TROPHIES_EARNED_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Silver Trophies Earned', 'Gold Trophies Earned' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ruby Chest'), 1);
                break;

            case BadgeEnum::OPENED_CEREAL_BOX:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Opened a Cereal Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Marshmallows'), 5);
                break;

            case BadgeEnum::OPENED_CAN_OF_FOOD_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Cans of Food Opened' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Resources'), 2);
                break;

            case BadgeEnum::OPENED_CAN_OF_FOOD_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Cans of Food Opened' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Resources'), 10);
                break;

            case BadgeEnum::OPENED_PAPER_BAG_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Opened a Paper Bag' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Resources'), 2);
                break;

            case BadgeEnum::OPENED_PAPER_BAG_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Opened a Paper Bag' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Resources'), 10);
                break;

            case BadgeEnum::OPENED_PLASTIC_BOTTLE_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Plastic Bottles Opened' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Lightning in a Bottle'), 1);
                break;

            case BadgeEnum::OPENED_PLASTIC_BOTTLE_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Plastic Bottles Opened' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Cucumber Water'), 10);
                break;

            case BadgeEnum::OPENED_CAN_OF_FOOD_PAPER_BAG_100:
                $progress = [ 'target' => 3, 'current' => self::getCompletedBadges($user, [ BadgeEnum::OPENED_CEREAL_BOX, BadgeEnum::OPENED_CAN_OF_FOOD_100, BadgeEnum::OPENED_PAPER_BAG_100 ]) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, '5-leaf Clover'), 1);
                break;

            case BadgeEnum::HORRIBLE_EGGPLANT_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::RottenEggplants ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Bag of Fertilizer'), 1);
                break;

            case BadgeEnum::HORRIBLE_EGGPLANT_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::RottenEggplants ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Large Bag of Fertilizer'), 10);
                break;

            case BadgeEnum::HOT_POTATO_TOSSED_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::TossedAHotPotato ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Potato'), 1);
                break;

            case BadgeEnum::HOT_POTATO_TOSSED_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::TossedAHotPotato ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Potato'), 10);
                break;

            case BadgeEnum::HOT_POTATO_TOSSED_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::TossedAHotPotato ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Potato'), 100);
                break;

            case BadgeEnum::FERTILIZED_PLANT_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::FertilizedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Moon Dust'), 1);
                break;

            case BadgeEnum::FERTILIZED_PLANT_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::FertilizedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Alien Tissue'), 2);
                break;

            case BadgeEnum::FERTILIZED_PLANT_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::FertilizedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Wormhole'), 2);
                break;

            case BadgeEnum::HARVESTED_PLANT_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::HarvestedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Moon Pearl'), 1);
                break;

            case BadgeEnum::HARVESTED_PLANT_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::HarvestedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'New Moon'), 1);
                break;

            case BadgeEnum::HARVESTED_PLANT_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::HarvestedAPlant ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Tile: The Cosmologer'), 1);
                break;

            case BadgeEnum::COMPOSTED_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ItemsComposted ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ants on a Log'), 1);
                break;

            case BadgeEnum::COMPOSTED_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::ItemsComposted ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Stinkier Bug'), 1);
                break;

            case BadgeEnum::COMPOSTED_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::ItemsComposted ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ant Queen\'s Favor'), 1);
                break;

            case BadgeEnum::FERTILIZED_HARVESTED_COMPOSTED_1000:
                $progress = [ 'target' => 3, 'current' => self::getCompletedBadges($user, [ BadgeEnum::FERTILIZED_PLANT_1000, BadgeEnum::HARVESTED_PLANT_1000, BadgeEnum::COMPOSTED_1000 ] )];
                $reward = TraderOfferCostOrYield::createRecyclingPoints(1000);
                break;

            case BadgeEnum::TREASURES_GIVEN_TO_DRAGON_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::TreasuresGivenToDragonHoard ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(10);
                break;

            case BadgeEnum::TREASURES_GIVEN_TO_DRAGON_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::TreasuresGivenToDragonHoard ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::TREASURES_GIVEN_TO_DRAGON_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::TreasuresGivenToDragonHoard ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(1000);
                break;

            case BadgeEnum::DRAGON_VASE_DIPPING_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ToolsDippedInADragonVase ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Eat Your Fruits and Veggies'), 1);
                break;

            case BadgeEnum::HOT_POT_DIPPING_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::FoodsDippedInAHotPot ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Sword'), 1);
                break;

            case BadgeEnum::Cooked10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::CookedSomething ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createRecyclingPoints(10);
                break;

            case BadgeEnum::Cooked100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::CookedSomething ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createRecyclingPoints(50);
                break;

            case BadgeEnum::Cooked1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::CookedSomething ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createRecyclingPoints(250);
                break;

            case BadgeEnum::Cooked10000:
                $progress = [ 'target' => 10000, 'current' => self::getStatTotal($user, [ UserStat::CookedSomething ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createRecyclingPoints(1000);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Baker\'s Box'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_200:
                $progress = [ 'target' => 200, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Fruits & Veggies Box'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_300:
                $progress = [ 'target' => 300, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Farmer\'s Scroll'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_400:
                $progress = [ 'target' => 400, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Nature Box'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_500:
                $progress = [ 'target' => 500, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Skill Scroll: Nature'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_600:
                $progress = [ 'target' => 600, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Tile: Wild Herbs & Vegetables'), 1);
                break;

            case BadgeEnum::TEACH_COOKING_BUDDY_700:
                $progress = [ 'target' => 700, 'current' => self::getStatTotal($user, [ UserStat::RecipesLearnedByCookingBuddy ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Etalocŏhc'), 5);
                break;


            case BadgeEnum::DEFEATED_SUMMONED_MONSTER_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Won Against Something... Unfriendly' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Gold Chest'), 1);
                break;

            case BadgeEnum::DEFEATED_SUMMONED_MONSTER_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Won Against Something... Unfriendly' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ruby Chest'), 1);
                break;

            case BadgeEnum::DEFEATED_SUMMONED_MONSTER_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Won Against Something... Unfriendly' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Skill Scroll: Brawl'), 1);
                break;

            case BadgeEnum::DEFEATED_NOETALAS_WING_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Defeated Noetala\'s Wing' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Behatting Scroll'), 1);
                break;

            case BadgeEnum::DEFEATED_NOETALAS_WING_2:
                $progress = [ 'target' => 2, 'current' => self::getStatTotal($user, [ 'Defeated Noetala\'s Wing' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Skill Scroll: Arcana'), 1);
                break;

            case BadgeEnum::ASCENDED_TOWER_OF_TRIALS_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Opened a Tower Chest' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Tell Samarzhoustian Delights'), 1);
                break;

            case BadgeEnum::ASCENDED_TOWER_OF_TRIALS_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Opened a Tower Chest' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Dice'), 2);
                break;

            case BadgeEnum::ASCENDED_TOWER_OF_TRIALS_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Opened a Tower Chest' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Skill Scroll: Brawl'), 1);
                break;

            case BadgeEnum::HOLLOW_EARTH_TRAVEL_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::HollowEarthSpacesMoved ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Megalium'), 2);
                break;

            case BadgeEnum::HOLLOW_EARTH_TRAVEL_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::HollowEarthSpacesMoved ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Piece of Cetgueli\'s Map'), 1);
                break;

            case BadgeEnum::HOLLOW_EARTH_TRAVEL_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::HollowEarthSpacesMoved ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Monster-summoning Scroll'), 2);
                break;

            case BadgeEnum::GREAT_SPIRIT_MINOR_REWARDS_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::RECEIVED_A_MINOR_PRIZE_FROM_A_GREAT_SPIRIT ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Magic Crystal Ball'), 1);
                break;

            case BadgeEnum::GREAT_SPIRIT_MODERATE_REWARDS_5:
                $progress = [ 'target' => 5, 'current' => self::getStatTotal($user, [ UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_A_GREAT_SPIRIT ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Magic Crystal Ball'), 1);
                break;

            case BadgeEnum::GREAT_SPIRIT_MAJOR_REWARDS_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_A_GREAT_SPIRIT ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Magic Crystal Ball'), 1);
                break;

            case BadgeEnum::GREAT_SPIRIT_HUNTER_OF_ANHUR_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_A_HUNTER_OF_ANHUR,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_A_HUNTER_OF_ANHUR,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_A_HUNTER_OF_ANHUR
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Major Scroll of Riches'), 2);
                break;

            case BadgeEnum::GREAT_SPIRIT_BOSHINOGAMI_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_SOME_BOSHINOGAMI,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_SOME_BOSHINOGAMI,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_SOME_BOSHINOGAMI
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Behatting Scroll'), 1);
                break;

            case BadgeEnum::GREAT_SPIRIT_CARDEAS_LOCKBEARER_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_CARDEAS_LOCKBEARER,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_CARDEAS_LOCKBEARER,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_CARDEAS_LOCKBEARER
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Cryptocurrency Wallet'), 4);
                break;

            case BadgeEnum::GREAT_SPIRIT_DIONYSUSS_HUNGER_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_DIONYSUSS_HUNGER,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_DIONYSUSS_HUNGER,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_DIONYSUSS_HUNGER
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Chocolate'), 5);
                break;

            case BadgeEnum::GREAT_SPIRIT_HUEHUECOYOTLS_FOLLY_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_HUEHUECOYOTLS_FOLLY,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_HUEHUECOYOTLS_FOLLY,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_HUEHUECOYOTLS_FOLLY
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Hollow Earth Booster Pack: Community Pack'), 3);
                break;

            case BadgeEnum::GREAT_SPIRIT_EIRI_PERSONA_10:
                $progress = [
                    'target' => 10,
                    'current' => self::getStatTotal($user, [
                        UserStat::RECEIVED_A_MINOR_PRIZE_FROM_AN_EIRI_PERSONA,
                        UserStat::RECEIVED_A_MODERATE_PRIZE_FROM_AN_EIRI_PERSONA,
                        UserStat::RECEIVED_A_MAJOR_PRIZE_FROM_AN_EIRI_PERSONA
                    ], $em, $cache)
                ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Magic Smoke'), 3);
                break;

            case BadgeEnum::MISREAD_SCROLL:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Misread a Scroll' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Pectin'), 1);
                break;

            case BadgeEnum::READ_SCROLL_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::ReadAScroll ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ponzu'), 2);
                break;

            case BadgeEnum::READ_SCROLL_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ReadAScroll ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Toad Jelly'), 2);
                break;

            case BadgeEnum::READ_SCROLL_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::ReadAScroll ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Spice Rack'), 5);
                break;

            case BadgeEnum::IceMangoes10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ShatteredIceMango ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Mango Pudding'), 1);
                break;

            case BadgeEnum::WhisperStone:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Listened to a Whisper Stone' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Quintessence'), 2);
                break;

            case BadgeEnum::HONORIFICABILITUDINITATIBUS:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Traded for Hebenon' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Magic Leaf'), 1);
                break;

            case BadgeEnum::SOUFFLE_STARTLER:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Soufflés Startled' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(10);
                break;

            case BadgeEnum::OPENED_HAT_BOX_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Opened a Hat Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Coconut Half'), 1);
                break;

            case BadgeEnum::OPENED_HAT_BOX_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Opened a Hat Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Behatting Scroll'), 1);
                break;

            case BadgeEnum::OPENED_BOX_BOX_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Opened a Box Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Glowing Six-sided Die'), 1);
                break;

            case BadgeEnum::OPENED_BOX_BOX_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Opened a Box Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Glowing Ten-sided Die'), 10);
                break;

            case BadgeEnum::BOX_BOX_BOX_BOX:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Found a Box Box Inside a Box Box' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Box Box'), 1);
                break;

            case BadgeEnum::PLAZA_BOX_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::PlazaBoxesReceived ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Sand Dollar'), 1);
                break;

            case BadgeEnum::PLAZA_BOX_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::PlazaBoxesReceived ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::PLAZA_BOX_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::PlazaBoxesReceived ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Very Strongbox'), 2);
                break;

            // Fireplace

            case BadgeEnum::LONGEST_FIRE_1_HOUR:
                $progress = [ 'target' => 60, 'current' => $user->getFireplace() ? $user->getFireplace()->getLongestStreak() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Blackberry Wine'), 2);
                break;

            case BadgeEnum::LONGEST_FIRE_1_DAY:
                $progress = [ 'target' => 1440, 'current' => $user->getFireplace() ? $user->getFireplace()->getLongestStreak() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Crooked Stick'), 2);
                break;

            case BadgeEnum::LONGEST_FIRE_1_WEEK:
                $progress = [ 'target' => 10080, 'current' => $user->getFireplace() ? $user->getFireplace()->getLongestStreak() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Firestone'), 2);
                break;

            case BadgeEnum::FIREPLACE_FUEL_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::ItemsThrownIntoTheFireplace ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Kilju'), 2);
                break;

            case BadgeEnum::FIREPLACE_FUEL_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::ItemsThrownIntoTheFireplace ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Charcoal'), 2);
                break;

            case BadgeEnum::FIREPLACE_FUEL_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::ItemsThrownIntoTheFireplace ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Witch\'s Broom'), 2);
                break;

            case BadgeEnum::FIREPLACE_FUEL_10000:
                $progress = [ 'target' => 10000, 'current' => self::getStatTotal($user, [ UserStat::ItemsThrownIntoTheFireplace ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Ceremony of Fire'), 2);
                break;

            // Bugs

            case BadgeEnum::FEED_THE_CENTIPEDES_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::EvolvedACentipede ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Wings'), 2);
                break;

            case BadgeEnum::FEED_THE_ANTS_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::FedALineOfAnts ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Sugar'), 10);
                break;

            case BadgeEnum::FEED_THE_BEES_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::FedTheBeehive ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Sugar'), 10);
                break;

            case BadgeEnum::PLAYING_BOTH_SIDES:
                $progress = [ 'target' => 2, 'current' => self::getCompletedBadges($user, [ BadgeEnum::FEED_THE_ANTS_10, BadgeEnum::FEED_THE_BEES_10 ]) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Dicerca'), 1);
                break;

            case BadgeEnum::WORKER_BEES_1000:
                $progress = [ 'target' => 1000, 'current' => self::getWorkerBeeCount($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Honeydont'), 10);
                break;

            case BadgeEnum::WORKER_BEES_10000:
                $progress = [ 'target' => 10000, 'current' => self::getWorkerBeeCount($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Honeydont'), 100);
                break;

            // Cataloging

            case BadgeEnum::FIELD_GUIDE_10:
                $progress = [ 'target' => 10, 'current' => self::getUnlockedFieldGuideEntries($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Little Strongbox'), 1);
                break;

            case BadgeEnum::FIELD_GUIDE_20:
                $progress = [ 'target' => 20, 'current' => self::getUnlockedFieldGuideEntries($user) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Very Strongbox'), 1);
                break;

            case BadgeEnum::MUSEUM_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_200:
                $progress = [ 'target' => 200, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_300:
                $progress = [ 'target' => 300, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Iron Sword'), 1);
                break;

            case BadgeEnum::MUSEUM_400:
                $progress = [ 'target' => 400, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_500:
                $progress = [ 'target' => 500, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Stardust'), 1);
                break;

            case BadgeEnum::MUSEUM_600:
                $progress = [ 'target' => 600, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_700:
                $progress = [ 'target' => 700, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_800:
                $progress = [ 'target' => 800, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_900:
                $progress = [ 'target' => 900, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Imaginary Number'), 1);
                break;

            case BadgeEnum::MUSEUM_1000:
                $progress = [ 'target' => 1000, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::MUSEUM_1100:
                $progress = [ 'target' => 1100, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'NUL'), 1);
                break;

            case BadgeEnum::MUSEUM_1200:
                $progress = [ 'target' => 1200, 'current' => self::getStatTotal($user, [ UserStat::ItemsDonatedToMuseum ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createMoney(100);
                break;

            case BadgeEnum::ZOOLOGIST_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Algae'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_20:
                $progress = [ 'target' => 20, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Algae'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_30:
                $progress = [ 'target' => 30, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Algae'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_40:
                $progress = [ 'target' => 40, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Jellyfish Jelly'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_50:
                $progress = [ 'target' => 50, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Jellyfish Jelly'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_60:
                $progress = [ 'target' => 60, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Jellyfish Jelly'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_70:
                $progress = [ 'target' => 70, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Egg'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_80:
                $progress = [ 'target' => 80, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Egg'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_90:
                $progress = [ 'target' => 90, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Egg'), 1);
                break;

            case BadgeEnum::ZOOLOGIST_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ 'Species Cataloged' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Alien Tissue'), 1);
                break;

            // Events

            case BadgeEnum::ApricotFestival1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ 'Apricrates Raided' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Apricot PB&J'), 1);
                break;

            case BadgeEnum::ApricotFestival10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ 'Apricrates Raided' ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Gold and Apricots'), 1);
                break;

            // Meta

            case BadgeEnum::AccountAge365:
                $progress = [ 'target' => 365, 'current' => new \DateTimeImmutable()->diff($user->getRegisteredOn())->days ?: 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Candle'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_10:
                $progress = [ 'target' => 10, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Bar'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_20:
                $progress = [ 'target' => 20, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Meringue'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_30:
                $progress = [ 'target' => 30, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Toffee Matzah'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_40:
                $progress = [ 'target' => 40, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Mini Chocolate Chip Cookies'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_50:
                $progress = [ 'target' => 50, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Lava Cake'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_60:
                $progress = [ 'target' => 60, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Cake Pops'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_70:
                $progress = [ 'target' => 70, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate-covered Naner'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_80:
                $progress = [ 'target' => 80, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Slice of Chocolate Cream Pie'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_90:
                $progress = [ 'target' => 90, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate-frosted Donut'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_100:
                $progress = [ 'target' => 100, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Chest'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_110:
                $progress = [ 'target' => 110, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Ice Cream Sammy'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_120:
                $progress = [ 'target' => 120, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate-covered Honeycomb'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_130:
                $progress = [ 'target' => 130, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Syrup'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_140:
                $progress = [ 'target' => 140, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Chocolate Hammer'), 1);
                break;

            case BadgeEnum::ACHIEVEMENTS_150:
                $progress = [ 'target' => 150, 'current' => self::getStatTotal($user, [ UserStat::AchievementsClaimed ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Scroll of Chocolate'), 1);
                break;

            case BadgeEnum::BASEMENT_SIZE_2000:
                $progress = [ 'target' => 2000, 'current' => $user->hasUnlockedFeature(UnlockableFeatureEnum::Basement) ? $user->getBasementSize() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Worker Bee'), 2);
                break;

            case BadgeEnum::BASEMENT_SIZE_5000:
                $progress = [ 'target' => 5000, 'current' => $user->hasUnlockedFeature(UnlockableFeatureEnum::Basement) ? $user->getBasementSize() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Worker Bee'), 5);
                break;

            case BadgeEnum::BASEMENT_SIZE_10000:
                $progress = [ 'target' => 10000, 'current' => $user->hasUnlockedFeature(UnlockableFeatureEnum::Basement) ? $user->getBasementSize() : 0 ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Shiny Baabble'), 1);
                break;

            case BadgeEnum::OPENED_INFINITY_VAULT_1:
                $progress = [ 'target' => 1, 'current' => self::getStatTotal($user, [ UserStat::OpenedTheInfinityVault ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Hollow Earth Booster Pack: Beginnings'), 3);
                break;

            case BadgeEnum::INFINITY_VAULT_MONEYS_SPENT_9999:
                $progress = [ 'target' => 9999, 'current' => self::getStatTotal($user, [ UserStat::MoneysSpentOnTheInfinityVault ], $em, $cache) ];
                $reward = TraderOfferCostOrYield::createItem(ItemRepository::findOneByName($em, 'Tiny Rocketship'), 1);
                break;

            default:
                throw new \Exception('Oops! Badge not implemented! Ben was a bad programmer!');
        }

        return [
            'badge' => $badge,
            'progress' => $progress,
            'done' => $progress['current'] >= $progress['target'],
            'reward' => $reward
        ];
    }

}