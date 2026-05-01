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

// each SerializationGroup has a corresponding model class in the front-end
/** @deprecated Please stop using serialization groups. They're awful. */
final class SerializationGroupEnum
{
    /** @use FakeEnum<string> */
    use FakeEnum;

    public const string MY_ACCOUNT = 'myAccount';
    public const string MY_PET = 'myPet';
    public const string MY_PET_LOCATION = 'myPetLocation';
    public const string MY_INVENTORY = 'myInventory';
    public const string MY_DONATABLE_INVENTORY = 'myDonatableInventory';
    public const string PET_ACTIVITY_LOGS = 'petActivityLogs';
    public const string PET_ACTIVITY_LOGS_AND_PUBLIC_PET = 'petActivityLogAndPublicPet';
    public const string PET_PUBLIC_PROFILE = 'petPublicProfile';
    public const string FILTER_RESULTS = 'filterResults';
    public const string ITEM_ENCYCLOPEDIA = 'itemEncyclopedia';
    public const string PET_ENCYCLOPEDIA = 'petEncyclopedia';
    public const string USER_PUBLIC_PROFILE = 'userPublicProfile';
    public const string ARTICLE = 'article';
    public const string MUSEUM = 'museum';
    public const string PET_SHELTER_PET = 'petShelterPet';
    public const string MARKET_ITEM = 'marketItem';
    public const string TRADER_OFFER = 'traderOffer';
    public const string KNOWN_RECIPE = 'knownRecipe';
    public const string PARK_EVENT = 'parkEvent';
    public const string PET_FRIEND = 'petFriend';
    public const string PET_SPIRIT_ANCESTOR = 'petSpiritAncestor';
    public const string PET_GROUP = 'petGroup';
    public const string PET_GROUP_DETAILS = 'petGroupDetails';
    public const string PET_GROUP_INDEX = 'petGroupIndex';
    public const string GREENHOUSE_PLANT = 'greenhousePlant';
    public const string MY_GREENHOUSE = 'myGreenhouse';
    public const string MY_SEEDS = 'mySeeds';
    public const string GREENHOUSE_FERTILIZER = 'greenhouseFertilizer';
    public const string HOLLOW_EARTH = 'hollowEarth';
    public const string USER_TYPEAHEAD = 'userTypeahead';
    public const string AVAILABLE_MERITS = 'availableMerits';
    public const string FIREPLACE_MANTLE = 'fireplaceMantle';
    public const string MY_FIREPLACE = 'myFireplace';
    public const string FIREPLACE_FUEL = 'fireplaceFuel';
    public const string STORY = 'story';
    public const string MY_BEEHIVE = 'myBeehive';
    public const string MERIT_ENCYCLOPEDIA = 'meritEncyclopedia';
    public const string SPIRIT_COMPANION_PUBLIC_PROFILE = 'spiritCompanionPublicProfile';
    public const string ITEM_TYPEAHEAD = 'itemTypeahead';
    public const string MY_LETTERS = 'myLetters';
    public const string MY_DRAGON = 'myDragon';
    public const string DRAGON_TREASURE = 'dragonTreasure';
    public const string MY_MARKET_BIDS = 'myBids';
    public const string GLOBAL_STATS = 'globalStats';
    public const string DESIGN_GOAL = 'designGoal';
    public const string PUBLIC_STYLE = 'publicStyle';
    public const string MY_MENU = 'myMenu';
    public const string MY_HOLLOW_EARTH_TILES = 'myHollowEarthTiles';
    public const string MY_AURAS = 'myAura';
    public const string HELPER_PET = 'helperPet';
    public const string SURVEY_SUMMARY = 'surveySummary';
    public const string SURVEY_QUESTION = 'surveyQuestion';
    public const string SURVEY_QUESTION_ANSWER = 'surveyQuestionAnswer';
    public const string STAR_KINDRED_STORY = 'starKindredStory';
    public const string STAR_KINDRED_STORY_DETAILS = 'starKindredStoryDetails';
    public const string STAR_KINDRED_STORY_STEP_AVAILABLE = 'starKindredStoryStepAvailable';
    public const string STAR_KINDRED_STORY_STEP_COMPLETE = 'starKindredStoryStepComplete';
    public const string MY_FOLLOWERS = 'myFollowers';
    public const string USER_ACTIVITY_LOGS = 'userActivityLogs';
    public const string ZOOLOGIST_CATALOG = 'zoologistCatalog';
}
