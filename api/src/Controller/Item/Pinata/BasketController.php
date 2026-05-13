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

namespace App\Controller\Item\Pinata;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Functions\ArrayFunctions;
use App\Functions\DateFunctions;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/basket")]
class BasketController
{
    #[Route("/fish/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openBasketOfFish(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService,
        EntityManagerInterface $em, IRandom $rng,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'basket/fish/#/open');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $location = $inventory->getLocation();
        $lockedToOwner = $inventory->getLockedToOwner();
        $spice = $inventory->getSpice();

        $loot = [
            'Fish',
            'Fish',
            $rng->rngNextFromArray([ 'Fish', 'Seaweed', 'Algae', 'Sand Dollar' ]),
            $rng->rngNextFromArray([ 'Silica Grounds', 'Seaweed' ]),
        ];

        if($rng->rngNextInt(1, 10) === 1)
        {
            $loot[] = 'Secret Seashell';
            $exclaim = '! (Ohh!)';
        }
        else
            $exclaim = '.';

        foreach($loot as $item)
        {
            $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from a Basket of Fish.', $location, $lockedToOwner)
                ->setSpice($spice)
            ;
        }

        $inventoryService->receiveItem('Fabric Mâché Basket', $user, $user, $user->getName() . ' took everything out of a Basket of Fish; this is what was left.', $location, $lockedToOwner);

        $em->remove($inventory);

        $em->flush();

        return $responseService->itemActionSuccess('You emptied the Basket of Fish, receiving ' . ArrayFunctions::list_nice($loot) . $exclaim . ' (And you keep the Fabric Mâché Basket as well, of course.)', [ 'itemDeleted' => true ]);
    }

    #[Route("/fruit/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openFruitBasket(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService,
        EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'basket/fruit/#/open');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $location = $inventory->getLocation();
        $lockedToOwner = $inventory->getLockedToOwner();
        $spice = $inventory->getSpice();

        $fruit = [ 'Apricot Preserves', 'Blueberries', 'Naner' ];

        foreach($fruit as $item)
        {
            $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from a Fruit Basket.', $location, $lockedToOwner)
                ->setSpice($spice)
            ;
        }

        $inventoryService->receiveItem('Fabric Mâché Basket', $user, $user, $user->getName() . ' took everything out of a Fruit Basket; this is what was left.', $location, $lockedToOwner);

        $em->remove($inventory);

        $em->flush();

        return $responseService->itemActionSuccess('You emptied the Fruit Basket, receiving Apricot Preserves, Blueberries, and a Naner. (You keep the Fabric Mâché Basket as well, of course.)', [ 'itemDeleted' => true ]);
    }

    #[Route("/flower/{inventory}/loot", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openFlowerBasket(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService,
        EntityManagerInterface $em, IRandom $rng, Clock $clock,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'basket/flower/#/loot');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $location = $inventory->getLocation();
        $lockedToOwner = $inventory->getLockedToOwner();

        $weirdItem = DateFunctions::isCornMoon($clock->now) ? null : $rng->rngNextFromArray([ 'Wheat Flour', 'Flour Tortilla' ]);

        $possibleFlowers = [
            'Rice Flower',
            'Rice Flower',
            'Agrimony',
            'Coriander Flower',
            'Sunflower',
            'Red Clover',
            'Purple Violet',
            'Merigold',
        ];

        $items = [];
        $weird = 0;

        for($i = 0; $i < 4; $i++)
        {
            if($weirdItem && $rng->rngNextInt(1, 8) === 1)
            {
                $itemName = $weirdItem;
                $weird++;
            }
            else
                $itemName = $rng->rngNextFromArray($possibleFlowers);

            $items[] = $itemName;

            $inventoryService->receiveItem($itemName, $user, $user, $user->getName() . ' found this in a Flower Basket.', $location, $lockedToOwner);
        }

        $inventoryService->receiveItem('Fabric Mâché Basket', $user, $user, $user->getName() . ' took everything out of a Flower Basket; this is what was left.', $location, $lockedToOwner);

        $em->remove($inventory);

        $em->flush();

        $message = 'You emptied the Flower Basket, receiving ' . ArrayFunctions::list_nice($items) . '.';

        if($weird > 0)
            $message .= ' (I\'m not sure all of those are "flowers", exactly... uh, but anyway, you keep the Fabric Mâché Basket, too.)';
        else
            $message .= ' (You keep the Fabric Mâché Basket as well, of course.)';

        return $responseService->itemActionSuccess($message, [ 'itemDeleted' => true ]);
    }
}
