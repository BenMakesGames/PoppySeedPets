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

enum StarKindredSkillEnum: string
{
    case Athletics = 'Athletics';
    case Combat = 'Combat';
    case Acrobatics = 'Acrobatics';
    case Stealth = 'Stealth';
    case Endurance = 'Endurance';
    case Survival = 'Survival';
    case Perception = 'Perception';
    case Arcana = 'Arcana';
    case Lore = 'Lore';
    case Persuasion = 'Persuasion';

    public function stat(): StarKindredStatEnum
    {
        return match($this)
        {
            self::Athletics, self::Combat => StarKindredStatEnum::Strength,
            self::Acrobatics, self::Stealth => StarKindredStatEnum::Dexterity,
            self::Endurance => StarKindredStatEnum::Constitution,
            self::Arcana, self::Lore => StarKindredStatEnum::Intelligence,
            self::Survival, self::Perception => StarKindredStatEnum::Wisdom,
            self::Persuasion => StarKindredStatEnum::Charisma,
        };
    }
}
