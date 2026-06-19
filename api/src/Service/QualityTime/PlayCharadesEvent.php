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

class PlayCharadesEvent implements QualityTimeEvent
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

        $message = ActivityHelpers::UserName($user, true) . " played charades with $petNames. ";

        $pet = $this->rng->rngNextFromArray($pets);

        $possibleObjects = [
            'a tasseled wobbegong',
            'a spiny lumpsucker',
            'a doux-rêve ouvre-boîte',
            'a pleasing fungus beetle',
            'a satanic leaf-tailed gecko',
            'a bone-eating snot flower worm',
            'a sparklemuffin peacock spider',
            'a magical liopleurodon',
        ];

        if($user->hasFieldGuideEntry('Cosmic Goat'))
            $possibleObjects[] = 'the Cosmic Goat';

        if($user->hasFieldGuideEntry('Huge Toad'))
            $possibleObjects[] = 'a Huge Toad';

        if($user->hasFieldGuideEntry('Nang Tani'))
            $possibleObjects[] = 'Nang Tani';

        if($user->hasFieldGuideEntry('Infinity Imp'))
            $possibleObjects[] = 'an Infinity Imp';

        if($user->hasFieldGuideEntry('Drizzly Bear'))
            $possibleObjects[] = 'a Drizzly Bear';

        $object = $this->rng->rngNextFromArray($possibleObjects);

        $message .= ActivityHelpers::PetName($pet) . " tried miming $object, but ";

        if(count($pets) === 1)
            $message .= ActivityHelpers::UserName($user) . ' were completely unable to figure it out!';
        else
            $message .= 'no one was able to figure it out!';

        if($object === 'an Infinity Imp' && $pet->getSpecies()->getName() === 'Infinity Imp')
            $message .= ' (How ironic!)';

        return new QualityTimeResult($message, foodBased: false);
    }
}
