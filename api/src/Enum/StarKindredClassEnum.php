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

enum StarKindredClassEnum: string
{
    case Fighter = 'Fighter';
    case Rogue = 'Rogue';
    case Wizard = 'Wizard';
    case Cleric = 'Cleric';
    case Ranger = 'Ranger';
    case Bard = 'Bard';
    case Paladin = 'Paladin';
    case Druid = 'Druid';
    case Monk = 'Monk';

    /**
     * Grows fastest as the character levels up.
     */
    public function primaryStat(): StarKindredStatEnum
    {
        return match($this)
        {
            self::Fighter, self::Paladin => StarKindredStatEnum::Strength,
            self::Rogue, self::Ranger, self::Monk => StarKindredStatEnum::Dexterity,
            self::Wizard => StarKindredStatEnum::Intelligence,
            self::Cleric, self::Druid => StarKindredStatEnum::Wisdom,
            self::Bard => StarKindredStatEnum::Charisma,
        };
    }

    public function secondaryStat(): StarKindredStatEnum
    {
        return match($this)
        {
            self::Fighter, self::Druid => StarKindredStatEnum::Constitution,
            self::Rogue => StarKindredStatEnum::Intelligence,
            self::Wizard, self::Ranger, self::Monk => StarKindredStatEnum::Wisdom,
            self::Cleric, self::Paladin => StarKindredStatEnum::Charisma,
            self::Bard => StarKindredStatEnum::Dexterity,
        };
    }

    /**
     * Class skills improve by a full point every level; all other skills, by half a point.
     * @return StarKindredSkillEnum[]
     */
    public function classSkills(): array
    {
        return match($this)
        {
            self::Fighter => [ StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Survival ],
            self::Rogue => [ StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Acrobatics, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Persuasion ],
            self::Wizard => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Persuasion ],
            self::Cleric => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Persuasion ],
            self::Ranger => [ StarKindredSkillEnum::Survival, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Combat ],
            self::Bard => [ StarKindredSkillEnum::Persuasion, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Acrobatics, StarKindredSkillEnum::Perception ],
            self::Paladin => [ StarKindredSkillEnum::Combat, StarKindredSkillEnum::Persuasion, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Athletics ],
            self::Druid => [ StarKindredSkillEnum::Survival, StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Athletics ],
            self::Monk => [ StarKindredSkillEnum::Acrobatics, StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Endurance ],
        };
    }

    /**
     * Used in retirement epilogues.
     */
    public function signatureGear(): string
    {
        return match($this)
        {
            self::Fighter => 'sword',
            self::Rogue => 'lockpicks',
            self::Wizard => 'spellbook',
            self::Cleric => 'holy symbol',
            self::Ranger => 'bow',
            self::Bard => 'lute',
            self::Paladin => 'shield',
            self::Druid => 'staff',
            self::Monk => 'traveling robes',
        };
    }
}
