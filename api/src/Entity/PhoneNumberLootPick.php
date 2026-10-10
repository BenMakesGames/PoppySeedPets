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

namespace App\Entity;

/**
 * Picks `$pick` different items, at random, from `$from` (a list of item names).
 *
 * To always give a specific item, pick 1 from a list containing only that item.
 */
final readonly class PhoneNumberLootPick
{
    /**
     * @param list<string> $from
     */
    public function __construct(
        public int $pick,
        public array $from,
    )
    {
        if($pick < 1 || $pick > count($from))
            throw new \UnexpectedValueException('Phone number loot must pick between 1 and ' . count($from) . ' items; got ' . $pick . '.');
    }
}
