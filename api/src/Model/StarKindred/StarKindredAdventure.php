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

use App\Enum\StarKindredDifficultyEnum;
use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredThemeEnum;

/**
 * One of the day's procedurally-generated adventures. Never stored; regenerated from the date on demand.
 *
 * $id is a hash of the adventure's content, so if a deploy changes what today's adventures are, a
 * player who picked one before the deploy gets a "not found" instead of a different adventure.
 */
final readonly class StarKindredAdventure
{
    /**
     * @param StarKindredEncounter[] $encounters
     * @param StarKindredReward[] $rewards One per difficulty, in difficulty order
     */
    public function __construct(
        public string $id,
        public StarKindredThemeEnum $theme,
        public string $title,
        public string $summary,
        public array $encounters,
        public array $rewards,
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

    /**
     * @return StarKindredReward[]
     */
    public function getRewardsFor(StarKindredDifficultyEnum $difficulty): array
    {
        return array_values(array_filter(
            $this->rewards,
            fn(StarKindredReward $r) => $r->difficulty->tier() <= $difficulty->tier()
        ));
    }
}
