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

final class UserStat
{
    public const string TotalMoneysSpent = 'Total Moneys Spent';
    public const string TotalMoneysEarnedInMarket = 'Total Moneys Earned in Market';
    public const string ItemsSoldInMarket = 'Items Sold in Market';
    public const string ItemsBoughtInMarket = 'Items Bought in Market';
    public const string CookedSomething = 'Cooked Something';
    public const string ItemsRecycled = 'Items Recycled';
    public const string ItemsDonatedToMuseum = 'Items Donated to Museum';
    public const string FoodHoursFedToPets = 'Food Hours Fed to Pets';
    public const string PettedAPet = 'Petted a Pet';
    public const string MoneysStolenByThievingMagpies = 'Moneys Stolen by Thieving Magpies';
    public const string RecipesLearnedByCookingBuddy = 'Recipes Learned by Cooking Buddy';
    public const string BugsSquished = 'Bugs Squished';
    public const string BugsPutOutside = 'Bugs Put Outside';
    public const string BugsFed = 'Bugs Fed';
    public const string FedALineOfAnts = 'Fed a Line of Ants';
    public const string EvolvedACentipede = 'Evolved a Centipede';
    public const string FedTheBeehive = 'Fed the Beehive';
    public const string FertilizedAPlant = 'Fertilized a Plant';
    public const string HarvestedAPlant = 'Harvested a Plant';
    public const string PetsAdopted = 'Pets Adopted';
    public const string MagicHourglassesSmashed = 'Magic Hourglasses Smashed';
    public const string ReadAScroll = 'Read a Scroll';
    public const string PetsBirthed = 'Babies Born in Your House';
    public const string BurntLogsBroken = 'Broke Apart a Burnt Log';
    public const string TradedWithTheFluffmonger = 'Fluff Traded to the Fluffmonger';
    public const string EggsHarvestedFromEggplants = 'Eggs Harvested from Eggplants';
    public const string RottenEggplants = 'Eggplants Which Were Horrible and Rotten';
    public const string ItemsComposted = 'Items Composted';
    public const string LargeBirdsApproached = 'Large Birds Approached in Greenhouse';
    public const string TreasuresGivenToDragonHoard = 'Treasures Given to Dragon';
    public const string CansOfFoodOpened = 'Cans of Food Opened';
    public const string PlasticBottlesOpened = 'Plastic Bottles Opened';
    public const string LootedAPotOfGold = 'Looted a Pot of Gold';
    public const string StrangeFieldsCollapsed = 'Strange Fields Collapsed';
    public const string CompletedASagaSaga = 'Completed a Sága Saga';
    public const string TossedAHotPotato = 'Tossed a Hot Potato';
    public const string AchievementsClaimed = 'Achievements Claimed';
    public const string ItemsThrownIntoTheFireplace = 'Items Thrown into the Fireplace';
    public const string PlazaBoxesReceived = 'Boxes (and Bags) Received at the Plaza';
    public const string HollowEarthSpacesMoved = 'Spaces Moved in the Hollow Earth';
    public const string ToolsDippedInADragonVase = 'Tools Dipped in a Dragon Vase';
    public const string FoodsDippedInAHotPot = 'Foods Dipped in a Hot Pot';
    public const string RolledSatyrDice = 'Rolled Satyr Dice';
    public const string ShatteredIceMango = 'Shattered an Ice "Mango"';
    public const string SetAToucanFree = 'Set a Toucan Free';
    public const string OpenedTheInfinityVault = 'Opened the Infinity Vault';
    public const string MoneysSpentOnTheInfinityVault = 'Moneys Spent on the Infinity Vault';
    public const string RolledAStarKindredCharacter = 'Rolled a ★Kindred Character';
    public const string WentOnAStarKindredAdventure = 'Went on a ★Kindred Adventure';
    public const string WonAStarKindredAdventure = 'Won a ★Kindred Adventure';
    public const string WonADemigodStarKindredAdventure = 'Won a Demigod ★Kindred Adventure';
    public const string RetiredAStarKindredAdventurer = 'Retired a ★Kindred Adventurer';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_A_GREAT_SPIRIT = 'Received a Minor Reward from a Great Spirit';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_A_GREAT_SPIRIT = 'Received a Moderate Reward from a Great Spirit';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_A_GREAT_SPIRIT = 'Received a Major Reward from a Great Spirit';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_A_HUNTER_OF_ANHUR = 'Received a Minor Reward from a Hunter of Anhur';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_A_HUNTER_OF_ANHUR = 'Received a Moderate Reward from a Hunter of Anhur';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_A_HUNTER_OF_ANHUR = 'Received a Major Reward from a Hunter of Anhur';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_SOME_BOSHINOGAMI = 'Received a Minor Reward from some Boshinogami';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_SOME_BOSHINOGAMI = 'Received a Moderate Reward from some Boshinogami';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_SOME_BOSHINOGAMI = 'Received a Major Reward from some Boshinogami';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_CARDEAS_LOCKBEARER = 'Received a Minor Reward from Cardea\'s Lockbearer';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_CARDEAS_LOCKBEARER = 'Received a Moderate Reward from Cardea\'s Lockbearer';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_CARDEAS_LOCKBEARER = 'Received a Major Reward from Cardea\'s Lockbearer';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_DIONYSUSS_HUNGER = 'Received a Minor Reward from Dionysus\'s Hunger';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_DIONYSUSS_HUNGER = 'Received a Moderate Reward from Dionysus\'s Hunger';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_DIONYSUSS_HUNGER = 'Received a Major Reward from Dionysus\'s Hunger';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_HUEHUECOYOTLS_FOLLY = 'Received a Minor Reward from Huehuecoyotl\'s Folly';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_HUEHUECOYOTLS_FOLLY = 'Received a Moderate Reward from Huehuecoyotl\'s Folly';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_HUEHUECOYOTLS_FOLLY = 'Received a Major Reward from Huehuecoyotl\'s Folly';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_AN_EIRI_PERSONA = 'Received a Minor Reward from an Eiri Persona';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_AN_EIRI_PERSONA = 'Received a Moderate Reward from an Eiri Persona';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_AN_EIRI_PERSONA = 'Received a Major Reward from an Eiri Persona';

    public const string RECEIVED_A_MINOR_PRIZE_FROM_VAF_AND_NIR = 'Received a Minor Reward from Vaf & Nir';
    public const string RECEIVED_A_MODERATE_PRIZE_FROM_VAF_AND_NIR = 'Received a Moderate Reward from Vaf & Nir';
    public const string RECEIVED_A_MAJOR_PRIZE_FROM_VAF_AND_NIR = 'Received a Major Reward from Vaf & Nir';
}
