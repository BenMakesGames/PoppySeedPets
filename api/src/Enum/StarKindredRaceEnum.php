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

enum StarKindredRaceEnum: string
{
    case Human = 'Human';
    case Elf = 'Elf';
    case Dwarf = 'Dwarf';
    case Brownie = 'Brownie';
    case Gnome = 'Gnome';
    case Beastkin = 'Beastkin';
    case Goblin = 'Goblin';
    case Emberkin = 'Emberkin';
    case HighFae = 'High Fae';
    case Nymph = 'Nymph';

    /**
     * Races most players already know from other fantasy games.
     * @return self[]
     */
    public static function familiar(): array
    {
        return [ self::Human, self::Elf, self::Dwarf, self::Gnome, self::Goblin ];
    }

    /**
     * @return self[]
     */
    public static function unfamiliar(): array
    {
        return array_values(array_filter(self::cases(), fn(self $r) => !in_array($r, self::familiar(), true)));
    }

    /**
     * Applied once, when the character is rolled.
     * @return array<value-of<StarKindredStatEnum>, int>
     */
    public function statModifiers(): array
    {
        return match($this)
        {
            self::Human => [ 'Strength' => 1, 'Dexterity' => 1, 'Constitution' => 1, 'Intelligence' => 1, 'Wisdom' => 1, 'Charisma' => 1 ],
            self::Elf => [ 'Dexterity' => 2, 'Wisdom' => 1, 'Constitution' => -1 ],
            self::Dwarf => [ 'Constitution' => 2, 'Strength' => 1, 'Charisma' => -1 ],
            self::Brownie => [ 'Dexterity' => 2, 'Charisma' => 1, 'Strength' => -1 ],
            self::Gnome => [ 'Intelligence' => 2, 'Dexterity' => 1, 'Strength' => -1 ],
            self::Beastkin => [ 'Strength' => 2, 'Constitution' => 1, 'Intelligence' => -1 ],
            self::Goblin => [ 'Dexterity' => 2, 'Intelligence' => 1, 'Wisdom' => -1 ],
            self::Emberkin => [ 'Charisma' => 2, 'Intelligence' => 1, 'Wisdom' => -1 ],
            self::HighFae => [ 'Wisdom' => 2, 'Charisma' => 1, 'Constitution' => -1 ],
            self::Nymph => [ 'Wisdom' => 2, 'Dexterity' => 1, 'Intelligence' => -1 ],
        };
    }
}
