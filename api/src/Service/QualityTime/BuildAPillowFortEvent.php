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

class BuildAPillowFortEvent implements QualityTimeEvent
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
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames built an enormous pillow fort in the living room.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);
        $title = $this->rng->rngNextFromArray(['Castellan', 'Seneschal', 'Margrave']);

        $fortLines = [
            "$randomPet established themselves as $title of the Fort and demanded snacks as tribute.",
            "$randomPet kept \"improving\" the entrance until no one could get in.",
            "$randomPet fell asleep the moment the roof was finished.",
            "$randomPet stood guard at the gate against imaginary intruders.",
            "$randomPet added a \"window\" so they could keep an eye on the kitchen.",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($fortLines);

        if($user->getFireplace() && $user->getFireplace()->getHeat() > 0)
            $message .= "\n\nThey built it right in front of the fireplace. (Not a fire hazard at all!)";

        return new QualityTimeResult($message, foodBased: false);
    }
}
