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

enum StarKindredDifficultyEnum: string
{
    case Novice = 'Novice';
    case Veteran = 'Veteran';
    case Hero = 'Hero';
    case Demigod = 'Demigod';

    /**
     * Each party member adds this much to an encounter's target, so difficulty scales with party size.
     */
    public function targetPerCharacter(): int
    {
        return match($this)
        {
            self::Novice => 12,
            self::Veteran => 17,
            self::Hero => 22,
            self::Demigod => 27,
        };
    }

    public function victoryExperience(): int
    {
        return match($this)
        {
            self::Novice => 10,
            self::Veteran => 25,
            self::Hero => 50,
            self::Demigod => 80,
        };
    }

    /**
     * Kept small, and never more than half of a victory, so throwing low-level characters at a hard
     * adventure is never a better way to level up than winning an appropriate one.
     */
    public function defeatExperience(int $encountersWon): int
    {
        return min(intdiv($this->victoryExperience(), 2), 5 * (1 + $encountersWon));
    }

    /**
     * 0 for the easiest difficulty; each harder difficulty is one higher. Rewards are cumulative by tier.
     */
    public function tier(): int
    {
        return match($this)
        {
            self::Novice => 0,
            self::Veteran => 1,
            self::Hero => 2,
            self::Demigod => 3,
        };
    }
}
