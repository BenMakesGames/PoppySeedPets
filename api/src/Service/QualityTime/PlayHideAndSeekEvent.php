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
use App\Enum\UnlockableFeatureEnum;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Service\IRandom;

class PlayHideAndSeekEvent implements QualityTimeEvent
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

        $message = "$everyonesNames played hide-and-seek in the house.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $possibleHidingDescriptions = [
            "$randomPet was an exceptional seeker."
        ];

        if($user->getFireplace() && $user->getFireplace()->getHeat() === 0)
            $possibleHidingDescriptions[] = "$randomPet hid themselves inside the fireplace chimney!";

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Basement))
            $possibleHidingDescriptions[] = "It took forever for anyone to find $randomPet, who had hid themselves behind some boxes in the basement.";

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::DragonDen))
            $possibleHidingDescriptions[] = "$randomPet buried themselves in some gold coins at the foot of your dragon and hid there successfully for some time!";

        if($user->getCookingBuddy()?->getAppearance() === 'robot/mega-cooking')
        {
            if(count($petNamesList) === 1)
                $possibleHidingDescriptions[] = "It didn't occur to you to check behind your giant Cooking Buddy for forever, which is exactly where $randomPet was hiding!";
            else
                $possibleHidingDescriptions[] = "It didn't occur to anyone to check behind the giant Cooking Buddy for forever, which is exactly where $randomPet was hiding!";
        }

        $message .= "\n\n" . $this->rng->rngNextFromArray($possibleHidingDescriptions);

        return new QualityTimeResult($message, foodBased: false);
    }
}
