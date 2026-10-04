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

use App\Service\IRandom;

enum BeehiveSpaceTypeEnum: string
{
    case Jungle = 'jungle';
    case Beach = 'beach';
    case Grassy = 'grassy';
    case Rocky = 'rocky';

    /**
     * How often each terrain shows up when a beehive's spaces are rolled.
     */
    public function getWeight(): int
    {
        return match($this)
        {
            self::Jungle => 7,
            self::Beach => 5,
            self::Grassy => 4,
            self::Rocky => 3,
        };
    }

    public static function roll(IRandom $rng): self
    {
        $bag = [];

        foreach(self::cases() as $type)
        {
            for($i = 0; $i < $type->getWeight(); $i++)
                $bag[] = $type;
        }

        return $rng->rngNextFromArray($bag);
    }
}
