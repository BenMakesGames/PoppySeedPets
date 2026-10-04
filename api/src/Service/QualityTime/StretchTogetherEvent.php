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

namespace App\Service\QualityTime;

use App\Entity\Pet;
use App\Entity\User;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Service\IRandom;

class StretchTogetherEvent implements QualityTimeEvent
{
    public function __construct(
        private readonly IRandom $rng,
    )
    {
    }

    public function isAvailable(User $user): bool
    {
        return true;
    }

    public function generate(User $user, array $pets): QualityTimeResult
    {
        if(count($pets) === 1)
        {
            return new QualityTimeResult(
                ActivityHelpers::UserName($user, true) . ' and ' . ActivityHelpers::PetName($pets[0]) . ' did some stretching exercises together.',
                foodBased: false
            );
        }

        $leader = $this->rng->rngNextFromArray($pets);

        $nonLeaderPets = array_filter($pets, fn(Pet $pet) => $pet !== $leader);

        $everyoneElse = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $nonLeaderPets)
        ]);

        return new QualityTimeResult(
            ActivityHelpers::PetName($leader) . " led $everyoneElse in some stretching exercises together.",
            foodBased: false
        );
    }
}
