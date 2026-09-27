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

namespace App\Service\StarKindred;

use App\Enum\StarKindredRaceEnum;
use App\Enum\StarKindredSexEnum;
use App\Service\IRandom;

/**
 * Portraits live in the webapp, at assets/images/star-kindred/portraits/{portrait}.png, where {portrait} is
 * "{race}-{sex}-{n}" (or "{race}-{n}" for androgynous races), numbered from 1.
 */
final class StarKindredPortraits
{
    private const array Counts = [
        'beastkin' => 10,
        'dwarf' => 10,
        'elf-female' => 8,
        'elf-male' => 8,
        'emberkin-female' => 6,
        'emberkin-male' => 6,
        'gnome-female' => 5,
        'gnome-male' => 5,
        'goblin-female' => 4,
        'goblin-male' => 4,
        'high-fae-female' => 5,
        'high-fae-male' => 5,
        'human-female' => 8,
        'human-male' => 8,
        'nymph' => 7,
    ];

    public static function roll(IRandom $rng, StarKindredRaceEnum $race, ?StarKindredSexEnum $sex): string
    {
        $group = self::group($race, $sex);

        return $group . '-' . $rng->rngNextInt(1, self::Counts[$group]);
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        $portraits = [];

        foreach(self::Counts as $group => $count)
        {
            for($i = 1; $i <= $count; $i++)
                $portraits[] = $group . '-' . $i;
        }

        return $portraits;
    }

    private static function group(StarKindredRaceEnum $race, ?StarKindredSexEnum $sex): string
    {
        if($race->isAndrogynous() !== ($sex === null))
            throw new \InvalidArgumentException('Androgynous races must not have a sex; all other races must.');

        return $sex === null ? $race->slug() : $race->slug() . '-' . $sex->value;
    }
}
