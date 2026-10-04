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

class ExploreTidePoolsEvent implements QualityTimeEvent
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly Clock $clock,
    )
    {
    }

    public function isAvailable(User $user): bool
    {
        $sky = WeatherService::getSky($this->clock->now);

        return $sky === WeatherSky::Clear || $sky === WeatherSky::Cloudy;
    }

    public function generate(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames crouched at the tide pools to see what the sea had left behind.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $tidePoolMoments = [
            "$randomPet poked a sea anemone and got gently booped back.",
            "$randomPet counted eleven different kinds of snail (allegedly).",
            "$randomPet valiantly shooed off a particularly suspicious-looking seagull that had been eyeing the group.",
            "$randomPet stared down a crab in an epic, dignified standoff.",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($tidePoolMoments);

        return new QualityTimeResult($message, foodBased: false);
    }
}
