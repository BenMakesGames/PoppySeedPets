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

use App\Entity\Inventory;

final class BulkSpicingPlan
{
    /**
     * @param array{0: Inventory, 1: Inventory}[] $pairs Foods paired with the spice to apply to them.
     * @param int $leftoverFoodCount Selected foods that won't get spiced, because there weren't enough spices for them.
     * @param int $leftoverSpiceCount Selected spices that won't get used, because there weren't enough foods for them.
     */
    public function __construct(
        public readonly array $pairs,
        public readonly int $leftoverFoodCount = 0,
        public readonly int $leftoverSpiceCount = 0,
    )
    {
    }
}
