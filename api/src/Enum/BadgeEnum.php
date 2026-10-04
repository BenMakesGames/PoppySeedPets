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

namespace App\Enum;

final class BadgeEnum
{
    /** @use FakeEnum<string> */
    use FakeEnum;

    public const string RECYCLED_10 = 'Recycled10';
    public const string RECYCLED_100 = 'Recycled100';
    public const string RECYCLED_1000 = 'Recycled1000';
    public const string RECYCLED_10000 = 'Recycled10000';
    public const string BAABBLES_OPENED_1 = 'BaabblesOpened1';
    public const string BAABBLES_OPENED_10 = 'BaabblesOpened10';
    public const string BAABBLES_OPENED_100 = 'BaabblesOpened100';
    public const string BAABBLES_OPENED_1000 = 'BaabblesOpened1000';
    public const string MONEYS_SPENT_10 = 'MoneysSpent10';
    public const string MONEYS_SPENT_100 = 'MoneysSpent100';
    public const string MONEYS_SPENT_1000 = 'MoneysSpent1000';
    public const string MONEYS_SPENT_10000 = 'MoneysSpent10000';
    public const string MONEYS_SPENT_100000 = 'MoneysSpent100000';

    public const string WEEKDAY_COINS_TRADED_1 = 'WeekdayCoins1';
    public const string WEEKDAY_COINS_TRADED_7 = 'WeekdayCoins7';

    public const string Petted10 = 'Petted10';
    public const string Petted100 = 'Petted100';
    public const string Petted1000 = 'Petted1000';
    public const string Petted10000 = 'Petted10000';

    public const string MaxPets4 = 'MaxPets4';
    public const string CompleteTheHeartstoneDimension = 'HeartstoneDimensionCompleted';

    public const string TROPHIES_EARNED_1 = 'TrophiesEarned1';
    public const string TROPHIES_EARNED_10 = 'TrophiesEarned10';
    public const string TROPHIES_EARNED_100 = 'TrophiesEarned100';

    public const string OPENED_CEREAL_BOX = 'OpenedCerealBox';
    public const string OPENED_CAN_OF_FOOD_10 = 'OpenedCanOfFood10';
    public const string OPENED_CAN_OF_FOOD_100 = 'OpenedCanOfFood100';
    public const string OPENED_PAPER_BAG_10 = 'OpenedPaperBag10';
    public const string OPENED_PAPER_BAG_100 = 'OpenedPaperBag100';
    public const string OPENED_PLASTIC_BOTTLE_10 = 'OpenedPlasticBottle10';
    public const string OPENED_PLASTIC_BOTTLE_100 = 'OpenedPlasticBottle100';
    public const string OPENED_CAN_OF_FOOD_PAPER_BAG_100 = 'OpenedCerealBoxCanOfFoodPaperBag100';

    public const string HORRIBLE_EGGPLANT_1 = 'HorribleEggplant1';
    public const string HORRIBLE_EGGPLANT_10 = 'HorribleEggplant10';
    public const string HOT_POTATO_TOSSED_1 = 'HotPotatoTossed1';
    public const string HOT_POTATO_TOSSED_10 = 'HotPotatoTossed10';
    public const string HOT_POTATO_TOSSED_100 = 'HotPotatoTossed100';

    public const string FERTILIZED_PLANT_10 = 'FertilizedPlant10';
    public const string FERTILIZED_PLANT_100 = 'FertilizedPlant100';
    public const string FERTILIZED_PLANT_1000 = 'FertilizedPlant1000';
    public const string HARVESTED_PLANT_10 = 'HarvestedPlant10';
    public const string HARVESTED_PLANT_100 = 'HarvestedPlant100';
    public const string HARVESTED_PLANT_1000 = 'HarvestedPlant1000';
    public const string COMPOSTED_10 = 'Composted10';
    public const string COMPOSTED_100 = 'Composted100';
    public const string COMPOSTED_1000 = 'Composted1000';
    public const string FERTILIZED_HARVESTED_COMPOSTED_1000 = 'FertilizedHarvestedComposted1000';

    public const string TREASURES_GIVEN_TO_DRAGON_10 = 'TreasuresGivenToDragon10';
    public const string TREASURES_GIVEN_TO_DRAGON_100 = 'TreasuresGivenToDragon100';
    public const string TREASURES_GIVEN_TO_DRAGON_1000 = 'TreasuresGivenToDragon1000';

    public const string Cooked10 = 'Cooked10';
    public const string Cooked100 = 'Cooked100';
    public const string Cooked1000 = 'Cooked1000';
    public const string Cooked10000 = 'Cooked10000';

    public const string DRAGON_VASE_DIPPING_10 = 'DragonVaseDipping10';
    public const string HOT_POT_DIPPING_10 = 'HotPotDipping10';

    public const string DEFEATED_SUMMONED_MONSTER_1 = 'DefeatedSummonedMonster1';
    public const string DEFEATED_SUMMONED_MONSTER_10 = 'DefeatedSummonedMonster10';
    public const string DEFEATED_SUMMONED_MONSTER_100 = 'DefeatedSummonedMonster100';

    public const string DEFEATED_NOETALAS_WING_1 = 'DefeatedNoetalasWing1';
    public const string DEFEATED_NOETALAS_WING_2 = 'DefeatedNoetalasWing2';

    public const string ASCENDED_TOWER_OF_TRIALS_1 = 'TowerOfTrials1';
    public const string ASCENDED_TOWER_OF_TRIALS_10 = 'TowerOfTrials10';
    public const string ASCENDED_TOWER_OF_TRIALS_100 = 'TowerOfTrials100';

    public const string HOLLOW_EARTH_TRAVEL_10 = 'HollowEarthTravel10';
    public const string HOLLOW_EARTH_TRAVEL_100 = 'HollowEarthTravel100';
    public const string HOLLOW_EARTH_TRAVEL_1000 = 'HollowEarthTravel1000';

    public const string GREAT_SPIRIT_MINOR_REWARDS_1 = 'GreatSpiritMinorRewards1';
    public const string GREAT_SPIRIT_MODERATE_REWARDS_5 = 'GreatSpiritModerateRewards5';
    public const string GREAT_SPIRIT_MAJOR_REWARDS_10 = 'GreatSpiritMajorRewards10';

    public const string GREAT_SPIRIT_HUNTER_OF_ANHUR_10 = 'GreatSpiritHunterOfAnhur10';
    public const string GREAT_SPIRIT_BOSHINOGAMI_10 = 'GreatSpiritBoshinogami10';
    public const string GREAT_SPIRIT_CARDEAS_LOCKBEARER_10 = 'GreatSpiritCardeasLockbearer10';
    public const string GREAT_SPIRIT_DIONYSUSS_HUNGER_10 = 'GreatSpiritDionysussHunger10';
    public const string GREAT_SPIRIT_HUEHUECOYOTLS_FOLLY_10 = 'GreatSpiritHuehuecoyotlsFolly10';
    public const string GREAT_SPIRIT_EIRI_PERSONA_10 = 'GreatSpiritEiriPersona10';

    public const string MISREAD_SCROLL = 'MisreadScroll1';
    public const string READ_SCROLL_1 = 'ReadScroll1';
    public const string READ_SCROLL_10 = 'ReadScroll10';
    public const string READ_SCROLL_100 = 'ReadScroll100';

    public const string IceMangoes10 = 'IceMangoes10';

    public const string WhisperStone = 'WhisperStone';

    public const string HONORIFICABILITUDINITATIBUS = 'Honorificabilitudinitatibus';
    public const string SOUFFLE_STARTLER = 'SouffleStartler';

    public const string OPENED_HAT_BOX_1 = 'OpenedHatBox1';
    public const string OPENED_HAT_BOX_10 = 'OpenedHatBox10';
    public const string HATTIER_STYLES_10 = 'UnlockedHattierStyles10';
    public const string HATTIER_STYLES_20 = 'UnlockedHattierStyles20';
    public const string HATTIER_STYLES_30 = 'UnlockedHattierStyles30';

    public const string OPENED_BOX_BOX_1 = 'OpenedBoxBox1';
    public const string OPENED_BOX_BOX_10 = 'OpenedBoxBox10';
    public const string BOX_BOX_BOX_BOX = 'BoxBoxBoxBox'; // found a box box inside a box box
    public const string PLAZA_BOX_1 = 'PlazaBox1';
    public const string PLAZA_BOX_10 = 'PlazaBox10';
    public const string PLAZA_BOX_100 = 'PlazaBox100';

    public const string FEED_THE_ANTS_10 = 'FedAnts10';
    public const string FEED_THE_BEES_10 = 'FedBees10';
    public const string FEED_THE_CENTIPEDES_10 = 'FedCentipedes10';
    public const string PLAYING_BOTH_SIDES = 'FedAntsAndBees10';

    public const string WORKER_BEES_1000 = 'WorkerBees1000';
    public const string WORKER_BEES_10000 = 'WorkerBees10000';

    public const string LONGEST_FIRE_1_HOUR = 'LongestFire60';
    public const string LONGEST_FIRE_1_DAY = 'LongestFire1440';
    public const string LONGEST_FIRE_1_WEEK = 'LongestFire10080';
    public const string FIREPLACE_FUEL_10 = 'FireplaceFuel10';
    public const string FIREPLACE_FUEL_100 = 'FireplaceFuel100';
    public const string FIREPLACE_FUEL_1000 = 'FireplaceFuel1000';
    public const string FIREPLACE_FUEL_10000 = 'FireplaceFuel10000';

    public const string TEACH_COOKING_BUDDY_100 = 'CookingBuddyRecipes100';
    public const string TEACH_COOKING_BUDDY_200 = 'CookingBuddyRecipes200';
    public const string TEACH_COOKING_BUDDY_300 = 'CookingBuddyRecipes300';
    public const string TEACH_COOKING_BUDDY_400 = 'CookingBuddyRecipes400';
    public const string TEACH_COOKING_BUDDY_500 = 'CookingBuddyRecipes500';
    public const string TEACH_COOKING_BUDDY_600 = 'CookingBuddyRecipes600';
    public const string TEACH_COOKING_BUDDY_700 = 'CookingBuddyRecipes700';

    public const string FIELD_GUIDE_10 = 'FieldGuide10';
    public const string FIELD_GUIDE_20 = 'FieldGuide20';

    public const string MUSEUM_100 = 'Museum100';
    public const string MUSEUM_200 = 'Museum200';
    public const string MUSEUM_300 = 'Museum300';
    public const string MUSEUM_400 = 'Museum400';
    public const string MUSEUM_500 = 'Museum500';
    public const string MUSEUM_600 = 'Museum600';
    public const string MUSEUM_700 = 'Museum700';
    public const string MUSEUM_800 = 'Museum800';
    public const string MUSEUM_900 = 'Museum900';
    public const string MUSEUM_1000 = 'Museum1000';
    public const string MUSEUM_1100 = 'Museum1100';
    public const string MUSEUM_1200 = 'Museum1200';

    public const string ZOOLOGIST_10 = 'Zoologist10';
    public const string ZOOLOGIST_20 = 'Zoologist20';
    public const string ZOOLOGIST_30 = 'Zoologist30';
    public const string ZOOLOGIST_40 = 'Zoologist40';
    public const string ZOOLOGIST_50 = 'Zoologist50';
    public const string ZOOLOGIST_60 = 'Zoologist60';
    public const string ZOOLOGIST_70 = 'Zoologist70';
    public const string ZOOLOGIST_80 = 'Zoologist80';
    public const string ZOOLOGIST_90 = 'Zoologist90';
    public const string ZOOLOGIST_100 = 'Zoologist100';

    public const string ApricotFestival1 = 'ApricotFestival1';
    public const string ApricotFestival10 = 'ApricotFestival10';

    public const string AccountAge365 = 'AccountAge365';

    public const string ACHIEVEMENTS_10 = 'Achievements10';
    public const string ACHIEVEMENTS_20 = 'Achievements20';
    public const string ACHIEVEMENTS_30 = 'Achievements30';
    public const string ACHIEVEMENTS_40 = 'Achievements40';
    public const string ACHIEVEMENTS_50 = 'Achievements50';
    public const string ACHIEVEMENTS_60 = 'Achievements60';
    public const string ACHIEVEMENTS_70 = 'Achievements70';
    public const string ACHIEVEMENTS_80 = 'Achievements80';
    public const string ACHIEVEMENTS_90 = 'Achievements90';
    public const string ACHIEVEMENTS_100 = 'Achievements100';
    public const string ACHIEVEMENTS_110 = 'Achievements110';
    public const string ACHIEVEMENTS_120 = 'Achievements120';
    public const string ACHIEVEMENTS_130 = 'Achievements130';
    public const string ACHIEVEMENTS_140 = 'Achievements140';
    public const string ACHIEVEMENTS_150 = 'Achievements150';

    public const string BASEMENT_SIZE_2000 = 'BasementSize2000';
    public const string BASEMENT_SIZE_5000 = 'BasementSize5000';
    public const string BASEMENT_SIZE_10000 = 'BasementSize10000';

    public const string OPENED_INFINITY_VAULT_1 = 'OpenedInfinityVault1';
    public const string INFINITY_VAULT_MONEYS_SPENT_9999 = 'InfinityVaultMoneysSpent9999';

    public const string STAR_KINDRED_VICTORIES_10 = 'StarKindredVictories10';
    public const string STAR_KINDRED_DEMIGOD_VICTORY_1 = 'StarKindredDemigodVictory1';
    public const string STAR_KINDRED_RETIRED_1 = 'StarKindredRetired1';
    public const string STAR_KINDRED_RETIRED_10 = 'StarKindredRetired10';
}