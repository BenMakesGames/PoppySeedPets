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

class SingTogetherEvent implements QualityTimeEvent
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
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $petNames = ArrayFunctions::list_nice($petNamesList);

        $message = ActivityHelpers::UserName($user, true) . " sang songs while $petNames joined in.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $singing = [
            "$randomPet hummed along to every tune.",
            "$randomPet harmonized beautifully (mostly).",
            "$randomPet chimed in with such gusto that they drowned everyone else out.",
            "$randomPet kept the beat by bobbing along enthusiastically.",
            "$randomPet hit a note so high the windows hummed.",
            "$randomPet trilled a little melody all their own.",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($singing);

        return new QualityTimeResult($message, foodBased: false);
    }
}
