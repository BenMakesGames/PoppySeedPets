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
use App\Functions\CalendarFunctions;
use App\Service\Clock;
use App\Service\IRandom;

class CarveGourdsEvent implements QualityTimeEvent
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly Clock $clock,
    )
    {
    }

    public function isAvailable(User $user): bool
    {
        return CalendarFunctions::isHalloweenCrafting($this->clock->now)
            && $this->rng->rngNextInt(1, 7) === 1;
    }

    public function generate(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $petNames = ArrayFunctions::list_nice($petNamesList);

        $message = ActivityHelpers::UserName($user, true) . " carved gourds with $petNames.";

        $forWho = count($pets) === 1 ? 'the both of you' : 'for everyone';

        if($user->getCookingBuddy())
            $message .= ' ' . ActivityHelpers::UserName($user, true) . ' and ' . $user->getCookingBuddy()->getName() . ' roasted the seeds for ' . $forWho . ' to eat!';
        else
            $message .= ' ' . ActivityHelpers::UserName($user, true) . ' roasted the seeds for ' . $forWho . ' to eat!';

        return new QualityTimeResult($message, foodBased: true);
    }
}
