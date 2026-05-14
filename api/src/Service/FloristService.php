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

namespace App\Service;

use App\Entity\User;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\MoonNameEnum;
use App\Functions\CalendarFunctions;
use App\Functions\DateFunctions;
use App\Functions\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class FloristService
{
    public function __construct(
        private readonly EntityManagerInterface $em, private readonly Clock $clock
    )
    {
    }

    /**
     * @return array<int, array{item: array{name: string, image: string}, cost: int}>
     */
    public function getInventory(User $user): array
    {
        $flowerbomb = ItemRepository::findOneByName($this->em, 'Flowerbomb');

        $inventory = [
            [
                'item' => [ 'name' => $flowerbomb->getName(), 'image' => $flowerbomb->getImage() ],
                'cost' => (DateFunctions::isSpecificMoon($this->clock->now, MoonNameEnum::FlowerMoon) || CalendarFunctions::isAprilFools($this->clock->now)) ? 75 : 150,
            ]
        ];

        if(CalendarFunctions::isAprilFools($this->clock->now))
        {
            $glitterBomb = ItemRepository::findOneByName($this->em, 'Glitter Bomb');

            $inventory[] = [
                'item' => [ 'name' => $glitterBomb->getName(), 'image' => $glitterBomb->getImage() ],
                'cost' => 20
            ];

            $jestersCap = ItemRepository::findOneByName($this->em, 'Jester\'s Cap');

            $inventory[] = [
                'item' => [ 'name' => $jestersCap->getName(), 'image' => $jestersCap->getImage() ],
                'cost' => 20
            ];

            $foolsSpice = ItemRepository::findOneByName($this->em, 'Fool\'s Spice');

            $inventory[] = [
                'item' => [ 'name' => $foolsSpice->getName(), 'image' => $foolsSpice->getImage() ],
                'cost' => 5
            ];
        }

        if(
            CalendarFunctions::isValentinesOrAdjacent($this->clock->now) ||
            CalendarFunctions::isWhiteDay($this->clock->now) ||
            CalendarFunctions::isEaster($this->clock->now) ||
            CalendarFunctions::isHalloween($this->clock->now)
        )
        {
            $chocolateBomb = ItemRepository::findOneByName($this->em, 'Chocolate Bomb');

            $inventory[] = [
                'item' => [ 'name' => $chocolateBomb->getName(), 'image' => $chocolateBomb->getImage() ],
                'cost' => 100
            ];
        }

        if(
            CalendarFunctions::isValentinesOrAdjacent($this->clock->now) ||
            CalendarFunctions::isWhiteDay($this->clock->now)
        )
        {
            $theLovelyHaberdashers = ItemRepository::findOneByName($this->em, 'Tile: Lovely Haberdashers');

            $inventory[] = [
                'item' => [ 'name' => $theLovelyHaberdashers->getName(), 'image' => $theLovelyHaberdashers->getImage() ],
                'cost' => 50
            ];
        }

        if(CalendarFunctions::isApricotFestival($this->clock->now))
        {
            $apricrate = ItemRepository::findOneByName($this->em, 'Apricrate');

            $inventory[] = [
                'item' => [ 'name' => $apricrate->getName(), 'image' => $apricrate->getImage() ],
                'cost' => 100
            ];
        }

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::HollowEarth))
        {
            $flowerBasketTile = ItemRepository::findOneByName($this->em, 'Tile: Flower Basket');

            $inventory[] = [
                'item' => [ 'name' => $flowerBasketTile->getName(), 'image' => $flowerBasketTile->getImage() ],
                'cost' => 20
            ];
        }

        return $inventory;
    }
}