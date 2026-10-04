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

/**
 * Wizards specialize in one at StarKindredSchoolOfMagicEnum::ChosenAtLevel, gaining its skill as a trained skill.
 * Stored in StarKindredCharacter::$classFeatures, under "schoolOfMagic".
 */
enum StarKindredSchoolOfMagicEnum: string
{
    public const int ChosenAtLevel = 10;

    case Nature = 'Nature Magic';
    case Illusion = 'Illusion Magic';
    case Enchantment = 'Enchantment';
    case Warcraft = 'Warcraft';

    public function skill(): StarKindredSkillEnum
    {
        return match($this)
        {
            self::Nature => StarKindredSkillEnum::Survival,
            self::Illusion => StarKindredSkillEnum::Stealth,
            self::Enchantment => StarKindredSkillEnum::Persuasion,
            self::Warcraft => StarKindredSkillEnum::Combat,
        };
    }
}
