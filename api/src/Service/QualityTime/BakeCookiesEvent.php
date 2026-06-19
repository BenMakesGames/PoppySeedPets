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

class BakeCookiesEvent implements QualityTimeEvent
{
    public function isAvailable(User $user): bool
    {
        return true;
    }

    public function generate(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);

        $cookingBuddy = $user->getCookingBuddy();

        $names = $cookingBuddy
            ? [$cookingBuddy->getName(), ...$petNamesList]
            : $petNamesList;

        $petNames = ArrayFunctions::list_nice($names);

        $message = ActivityHelpers::UserName($user, true) . " baked cookies with $petNames.";

        if(count($petNamesList) === 1)
            $message .= " You both enjoyed them warm from the oven.";
        else
            $message .= " Everyone enjoyed them warm from the oven.";

        return new QualityTimeResult($message, foodBased: true);
    }
}
