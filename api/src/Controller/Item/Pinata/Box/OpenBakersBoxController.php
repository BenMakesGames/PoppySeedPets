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
use App\Functions\UserQuestRepository;
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

class OpenBakersBoxController
{
    #[Route("/item/box/bakers/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openBakers(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService, IRandom $rng,
        UserStatsService $userStatsRepository, EntityManagerInterface $em, Clock $clock,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'box/bakers/#/open');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $wheatOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat';
        $wheatFlourOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat Flour';

        /**
         * @var Inventory[] $newInventory
         */
        $newInventory = [];

        $location = $inventory->getLocation();
        $spice = $inventory->getSpice();

        $freeBasicRecipes = UserQuestRepository::findOrCreate($em, $user, 'Got free Basic Recipes', false);
        if(!$freeBasicRecipes->getValue())
        {
            $newInventory[] = $inventoryService->receiveItem('Cooking 101', $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $location, $inventory->getLockedToOwner());
            $freeBasicRecipes->setValue(true);
        }

        $newInventory[] = $inventoryService->receiveItem($wheatOrCorn, $user, $user, $user->getName() . ' got this from a weekly Care Package.', $location, $inventory->getLockedToOwner());

        for($i = 0; $i < 4; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Egg', $wheatFlourOrCorn, 'Sugar', 'Creamy Milk' ]), $user, $user, $user->getName() . ' got this from a weekly Care Package.', $location, $inventory->getLockedToOwner());

        for($i = 0; $i < 4; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Corn Syrup', 'Yeast', 'Cocoa Beans', 'Baking Soda', 'Cream of Tartar' ]), $user, $user, $user->getName() . ' got this from a weekly Care Package.', $location, $inventory->getLockedToOwner());

        if($rng->rngNextInt(1, 4) === 1)
            $newInventory[] = $inventoryService->receiveItem('Cobbler Recipe', $user, $user, $user->getName() . ' got this from a weekly Care Package.', $location, $inventory->getLockedToOwner());

        if($spice)
        {
            $inventoryToSpice = $rng->rngNextSubsetFromArray($newInventory, 3);

            foreach($inventoryToSpice as $inventoryItem)
                $inventoryItem->setSpice($spice);
        }

        return BoxHelpers::countRemoveFlushAndRespond('Opening the box revealed', $userStatsRepository, $user, $inventory, $newInventory, $responseService, $em);
    }
}
