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

namespace App\Service;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

class Xoshiro implements IRandom
{
    private Randomizer $rng;

    public function __construct(?int $seed = null)
    {
        $this->rng = new Randomizer(new Xoshiro256StarStar($seed));
    }

    /**
     * @inheritdoc
     */
    public function rngNext(): int
    {
        return $this->rng->nextInt();
    }

    /**
     * @inheritdoc
     */
    public function rngNextFloat(): float
    {
        return $this->rng->nextFloat();
    }

    /**
     * @inheritdoc
     */
    public function rngNextBool(): bool
    {
        return $this->rng->getInt(0, 1) === 1;
    }

    /**
     * @inheritdoc
     */
    public function rngNextInt(int $min, int $inclusiveMax): int
    {
        return $this->rng->getInt($min, $inclusiveMax);
    }

    /**
     * @inheritdoc
     */
    public function rngNextFromArray(array $array): mixed
    {
        return array_slice($array, $this->rng->getInt(0, count($array) - 1), 1)[0];
    }

    /**
     * @inheritdoc
     */
    public function rngNextShuffle(array &$array): void
    {
        $n = count($array);

        for($i = 0; $i < $n - 1; $i++)
        {
            $r = $this->rng->getInt($i, $n - 1);
            $temp = $array[$r];
            /** @phpstan-ignore-next-line phpstan is unable to follow the logic of an in-place shuffle */
            $array[$r] = $array[$i];
            /** @phpstan-ignore-next-line phpstan is unable to follow the logic of an in-place shuffle */
            $array[$i] = $temp;
        }
    }

    /**
     * @inheritdoc
     */
    public function rngNextSubsetFromArray(array $array, int $number): array
    {
        $indices = array_keys($array);

        $this->rngNextShuffle($indices);

        $return = [];

        for($i = 0; $i < $number; $i++)
            $return[] = $array[$indices[$i]];

        return $return;
    }

    public function rngNextTweakedColor(string $color, int $radius = 12): string
    {
        $newColor = '';

        for($i = 0; $i < 3; $i++)
        {
            $part = hexdec($color[$i << 1] . $color[($i << 1) + 1]); // get color part as decimal
            $part += $this->rng->getInt(-$radius, $radius);          // randomize
            $part = max(0, min(255, $part));                         // keep between 0 and 255
            $part = str_pad(dechex($part), 2, '0', STR_PAD_LEFT);    // turn back into hex

            $newColor .= $part;
        }

        return $newColor;
    }

    function rngSkillRoll(int $bonus): int
    {
        return $this->rngNextInt(1, 20 + $bonus);
    }
}
