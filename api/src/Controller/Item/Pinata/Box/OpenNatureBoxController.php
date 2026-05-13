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

namespace App\Controller\Item\Pinata\Box;

use App\Controller\Item\ItemControllerHelpers;
use App\Controller\Item\Pinata\BoxHelpers;
use App\Entity\Inventory;
use App\Functions\DateFunctions;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OpenNatureBoxController
{
    #[Route("/item/box/nature/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openNatureBox(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService, IRandom $rng,
        UserStatsService $userStatsRepository, EntityManagerInterface $em, Clock $clock,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'box/nature/#/open');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $wheatOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat';

        /** @var Inventory[] $newInventory */
        $newInventory = [];

        $location = $inventory->getLocation();
        $spice = $inventory->getSpice();

        $quantities = [2, 3, 4];
        shuffle($quantities);

        for($i = 0; $i < $quantities[0]; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Crooked Stick', 'Tea Leaves', 'Grandparoot', $wheatOrCorn, 'Rice', 'Ginger', 'Spicy Peps', 'Red Clover' ]), $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $inventory->getLockedToOwner());

        for($i = 0; $i < $quantities[1]; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Orange', 'Red', 'Blackberries', 'Blueberries', 'Cacao Fruit', 'Avocado' ]), $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $inventory->getLockedToOwner());

        for($i = 0; $i < $quantities[2]; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Carrot', 'Onion', 'Celery', 'Carrot', 'Sweet Beet', 'Potato', 'Corn' ]), $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $inventory->getLockedToOwner());

        if($spice)
        {
            shuffle($newInventory);

            for($i = 0; $i < count($newInventory) / 3; $i++)
                $newInventory[$i]->setSpice($spice);
        }

        if($rng->rngNextInt(1, 8) === 8)
            $newInventory[] = $inventoryService->receiveItem('Duck Pond', $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $inventory->getLockedToOwner());

        return BoxHelpers::countRemoveFlushAndRespond('Opening the box revealed', $userStatsRepository, $user, $inventory, $newInventory, $responseService, $em);
    }
}
