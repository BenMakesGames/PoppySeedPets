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

namespace App\Model\StarKindred;

use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredThemeEnum;

/**
 * One of the day's procedurally-generated adventures. Never stored; regenerated from the date on demand.
 */
final readonly class StarKindredAdventure
{
    /**
     * @param StarKindredEncounter[] $encounters
     */
    public function __construct(
        public int $index,
        public StarKindredThemeEnum $theme,
        public string $title,
        public string $summary,
        public array $encounters,
    )
    {
    }

    /**
     * @return StarKindredSkillEnum[] Distinct skills tested, in encounter order
     */
    public function getSkillsTested(): array
    {
        $skills = [];

        foreach($this->encounters as $encounter)
        {
            if(!in_array($encounter->skill, $skills, true))
                $skills[] = $encounter->skill;
        }

        return $skills;
    }
}
