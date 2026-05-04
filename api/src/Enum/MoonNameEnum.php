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

final class MoonNameEnum
{
    /** @use FakeEnum<string> */
    use FakeEnum;

    public const string WolfMoon = 'Wolf';
    public const string SnowMoon = 'Snow';
    public const string WormMoon = 'Worm';
    public const string PinkMoon = 'Pink';
    public const string FlowerMoon = 'Flower';
    public const string StrawberryMoon = 'Strawberry';
    public const string BuckMoon = 'Buck';
    public const string SturgeonMoon = 'Sturgeon';
    public const string CornMoon = 'Corn';
    public const string HuntersMoon = 'Hunter\'s';
    public const string BeaverMoon = 'Beaver';
    public const string ColdMoon = 'Cold';
    public const string BlueMoon = 'Blue';
}