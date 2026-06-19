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
use App\Model\WeatherSky;
use App\Service\Clock;
use App\Service\IRandom;
use App\Service\WeatherService;

class GoOnAWalkEvent implements QualityTimeEvent
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly Clock $clock,
    )
    {
    }

    public function isAvailable(User $user): bool
    {
        return WeatherService::getSky($this->clock->now) === WeatherSky::Clear;
    }

    public function generate(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames went on a walk around the neighborhood.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $endings = [
            "$randomPet saw a HUGE bug on a rock. Neat!",
            "$randomPet got too tired to walk and had to be carried home.",
            "$randomPet saw a colorful snake basking on a rock. How cool!",
            "$randomPet found the BIGGEST STICK EVER but it was too heavy to carry, so they had to leave it behind.",
            "$randomPet saw a colorful lizard basking on a rock. How cute!",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($endings);

        return new QualityTimeResult($message, foodBased: false);
    }
}
