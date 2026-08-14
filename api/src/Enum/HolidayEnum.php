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

enum HolidayEnum: string
{
    case Eight = '8';
    case ApricotFestival = 'Apricot Festival';
    case AprilFools = 'April Fools\'';
    case AwaOdori = 'Awa Odori';
    case BastilleDay = 'Bastille Day';
    case BlackFriday = 'Black Friday';
    case CincoDeMayo = 'Cinco de Mayo';
    case CreepyMaskDay = 'Creepy Mask Day';
    case CyberMonday = 'Cyber Monday';
    case EarthDay = 'Earth Day';
    case Easter = 'Easter';
    case FourthOfJuly = '4th of July';
    case Halloween = 'Halloween';
    case Hanukkah = 'Hanukkah';
    case Holi = 'Holi';
    case JelephantDay = 'Jelephant Day';
    case JuramaiaDay = 'Juramaia Day';
    case LeapDay = 'Leap Day';
    case LunarNewYear = 'Lunar New Year';
    case NewYearsDay = 'New Year\'s Day';
    case NoombatDay = 'Noombat Day';
    case PiDay = 'Pi Day';
    case PSPBirthday = 'PSP Birthday';
    case PsyPetsBirthday = 'PsyPets\' Birthday';
    case SaintPatricks = 'Saint Patrick\'s';
    case SnakeDay = 'Snake Day';
    case StockingStuffingSeason = 'Stocking Stuffing Season';
    case SummerSolstice = 'Summer Solstice';
    case TalkLikeAPirateDay = 'Talk Like a Pirate Day';
    case Thanksgiving = 'Thanksgiving';
    case Valentines = 'Valentine\'s';
    case WhiteDay = 'White Day';
    case WinterSolstice = 'Winter Solstice';

    // weird events?
    case Leonids = 'The Leonids Meteor Shower';
    case Perseids = 'The Perseids Meteor Shower';

    // full moons
    case BlueMoon = 'Blue Moon';
    case WolfMoon = 'Wolf Moon';
    case SnowMoon = 'Snow Moon';
    case WormMoon = 'Worm Moon';
    case PinkMoon = 'Pink Moon';
    case FlowerMoon = 'Flower Moon';
    case StrawberryMoon = 'Strawberry Moon';
    case BuckMoon = 'Buck Moon';
    case SturgeonMoon = 'Sturgeon Moon';
    case CornMoon = 'Corn Moon';
    case HuntersMoon = 'Hunter\'s Moon';
    case BeaverMoon = 'Beaver Moon';
    case ColdMoon = 'Cold Moon';
}