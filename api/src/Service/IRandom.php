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

interface IRandom
{
    function __construct(?int $seed = null);

    /**
     * @return int A random integer >= 0
     * @phpstan-impure
     */
    function rngNext(): int;

    /**
     * @return float A random float >= 0.0 and < 1.0
     * @phpstan-impure
     */
    function rngNextFloat(): float;

    /**
     * @return bool A random boolean
     * @phpstan-impure
     */
    function rngNextBool(): bool;

    /**
     * @param int $min The minimum value (inclusive)
     * @param int $inclusiveMax The maximum value (inclusive)
     * @return int A random integer between $min and $inclusiveMax
     * @phpstan-impure
     */
    function rngNextInt(int $min, int $inclusiveMax): int;

    /**
     * @template T
     * @param T[] $array
     * @return T A random element from the array
     * @phpstan-impure
     */
    function rngNextFromArray(array $array);

    /**
     * @template T
     * @param list<T> $array The array to shuffle; shuffle is performed in place
     */
    function rngNextShuffle(array &$array): void;

    /**
     * @template T
     * @param T[] $array The array to select a subset from
     * @param int $number The number of elements to select
     * @return T[] A random subset of the array with the specified number of elements
     * @phpstan-impure
     */
    function rngNextSubsetFromArray(array $array, int $number): array;

    /**
     * @param string $color The base color in hex format, WITHOUT # prefix (e.g., 'FF0000')
     * @param int $radius The range of adjustment possible for each of the red, green, and blue components of the color
     * @return string A new, adjusted color, in hex format, WITHOUT # prefix
     * @phpstan-impure
     */
    function rngNextTweakedColor(string $color, int $radius = 12): string;

    /**
     * @phpstan-impure
     */
    function rngSkillRoll(int $bonus): int;
}