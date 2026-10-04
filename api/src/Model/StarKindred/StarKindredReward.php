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

/**
 * One tier of an adventure's rewards: an item stack, or a hat styling. Beating an adventure awards
 * every tier up to, and including, the difficulty it was played at.
 */
final readonly class StarKindredReward
{
    private function __construct(
        public StarKindredDifficultyEnum $difficulty,
        public ?string $item,
        public int $quantity,
        public ?string $aura,
    )
    {
    }

    public static function item(StarKindredDifficultyEnum $difficulty, string $item, int $quantity): self
    {
        return new self($difficulty, $item, $quantity, null);
    }

    public static function aura(StarKindredDifficultyEnum $difficulty, string $aura): self
    {
        return new self($difficulty, null, 0, $aura);
    }
}
