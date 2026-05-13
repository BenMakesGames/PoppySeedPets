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
use App\Functions\UserQuestRepository;
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
class FarmerController
{
    #[Route("/farmers/{inventory}/invoke", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function invokeFarmerScroll(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService,
        UserStatsService $userStatsRepository, EntityManagerInterface $em, Clock $clock,
        UserAccessor $userAccessor, IRandom $rng
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'scroll/farmers/#/invoke');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $wheatOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat';

        $em->remove($inventory);

        $userStatsRepository->incrementStat($user, UserStat::ReadAScroll);

        if($user->getGreenhouse())
        {
            $expandedGreenhouseWithFarmerScroll = UserQuestRepository::findOrCreate($em, $user, 'Expanded Greenhouse with Farmer Scroll', false);

            if(!$expandedGreenhouseWithFarmerScroll->getValue())
            {
                $expandedGreenhouseWithFarmerScroll->setValue(true);

                $user->getGreenhouse()->increaseMaxPlants(1);

                $em->flush();

                return $responseService->itemActionSuccess('You read the scroll; another plot of space in your Greenhouse appears, as if by magic! In fact, thinking about it, it was _100%_ by magic!', [ 'itemDeleted' => true ]);
            }
        }

        $items = [
            $rng->rngNextFromArray([ 'Straw Hat', 'Forgotten Flowerpot' ]),
            $wheatOrCorn,
            $rng->rngNextFromArray([ 'Scythe', 'Garden Shovel' ]),
            $rng->rngNextFromArray([ 'Creamy Milk', 'Cheese', 'Plain Yogurt' ]),
            'Egg',
            $rng->rngNextFromArray([ 'Potato', 'Carrot', 'Beans', 'Onion' ]),
            'Grandparoot',
            'Crooked Stick'
        ];

        $newInventory = [];
        $location = $inventory->getLocation();

        foreach($items as $item)
            $newInventory[] = $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location);

        $itemList = array_map(fn(Inventory $i) => $i->getItem()->getName(), $newInventory);
        sort($itemList);

        $em->flush();

        $responseService->addFlashMessage('You read the scroll, summoning ' . ArrayFunctions::list_nice($itemList) . '.');

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }
}
