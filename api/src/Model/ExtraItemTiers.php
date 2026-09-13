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
namespace App\Model;

/**
 * The four skill-tiered item lists that PetAssistantService::getExtraItem draws from. Within a tier, duplicates are
 * extra weight. No tier may be empty.
 */
final class ExtraItemTiers
{
    /**
     * @param string[] $base
     * @param string[] $medium
     * @param string[] $high
     * @param string[] $superHigh
     */
    public function __construct(
        public readonly array $base,
        public readonly array $medium,
        public readonly array $high,
        public readonly array $superHigh,
    )
    {
        if(count($base) === 0 || count($medium) === 0 || count($high) === 0 || count($superHigh) === 0)
            throw new \InvalidArgumentException('Every tier must have at least one item.');
    }
}
