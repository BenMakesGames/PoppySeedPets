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

namespace App\Controller\Item\Scroll;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Enum\UserStat;
use App\Functions\ArrayFunctions;
use App\Functions\DateFunctions;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/scroll")]
class ResourcesController
{
    #[Route("/resources/{inventory}/invoke", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function readResourcesScroll(
        Inventory $inventory, InventoryService $inventoryService, EntityManagerInterface $em, IRandom $rng,
        ResponseService $responseService, UserStatsService $userStatsRepository,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'scroll/resources/#/invoke');

        $numberOfItems = [
            'Tiny Scroll of Resources' => 1,
            'Scroll of Resources' => 3
        ][$inventory->getItem()->getName()];

        $possibleItems = [
            'Liquid-hot Magma',
            'Plastic', 'Crooked Stick', 'Fluff', 'Pointer',
            'Iron Ore', $rng->rngNextFromArray([ 'Silver Ore', 'Silver Ore', 'Gold Ore' ]),
            'Scales', 'Yellow Dye', 'Feathers', 'Talon', 'Paper',
            'Glass', 'Gypsum'
        ];

        $location = $inventory->getLocation();
        $locked = $inventory->getLockedToOwner();

        $userStatsRepository->incrementStat($user, UserStat::ReadAScroll);

        $em->remove($inventory);

        $listOfItems = [];

        for($i = 0; $i < $numberOfItems; $i++)
        {
            $item = $rng->rngNextFromArray($possibleItems);
            $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $locked);
            $listOfItems[] = $item;
        }

        $em->flush();

        $responseService->addFlashMessage('You read the scroll, producing ' . ArrayFunctions::list_nice($listOfItems) . '.');

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }

    #[Route("/resources/{inventory}/invokeFood", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function readResourcesScrollForFood(
        Inventory $inventory, InventoryService $inventoryService, EntityManagerInterface $em, IRandom $rng,
        ResponseService $responseService, UserStatsService $userStatsRepository, Clock $clock,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'scroll/resources/#/invokeFood');

        $numberOfItems = [
            'Tiny Scroll of Resources' => 1,
            'Scroll of Resources' => 3
        ][$inventory->getItem()->getName()];

        $wheatOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat';

        $possibleItems = [
            'Smallish Pumpkin', 'Tomato', 'Ginger', 'Hot Potato', 'Toad Legs', 'Spicy Peps', 'Naner', 'Sweet Beet',
            'Seaweed', 'Apricot', 'Corn', 'Mango', 'Pamplemousse', 'Carrot', 'Celery', 'Red', 'Beans', $wheatOrCorn,
            'Rice', 'Creamy Milk', 'Orange', 'Fish', 'Onion', 'Chanterelle', 'Pineapple', 'Ponzu'
        ];

        $location = $inventory->getLocation();
        $locked = $inventory->getLockedToOwner();

        $userStatsRepository->incrementStat($user, UserStat::ReadAScroll);

        $em->remove($inventory);

        $listOfItems = [];

        for($i = 0; $i < $numberOfItems; $i++)
        {
            $item = $rng->rngNextFromArray($possibleItems);
            $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $locked);
            $listOfItems[] = $item;
        }

        $em->flush();

        $responseService->addFlashMessage('You read the scroll, producing ' . ArrayFunctions::list_nice($listOfItems) . '.');

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }
}
