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

namespace App\Controller\Item;

use App\Entity\Inventory;
use App\Functions\DateFunctions;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\TransactionService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/item/illGottenGrains")]
class IllGottenGrainsController
{
    #[Route("/{inventory}/rummage", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function consume(
        Inventory $inventory,
        ResponseService $responseService,
        UserAccessor $userAccessor,
        EntityManagerInterface $em,
        Clock $clock,
        IRandom $rng,
        TransactionService $transactionService,
        InventoryService $inventoryService
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'illGottenGrains/#/rummage');

        if(DateFunctions::isCornMoon($clock->now))
            return $responseService->itemActionSuccess('It seems the magic of the Corn Moon is preventing this morally-questionable item from being used!');

        $location = $inventory->getLocation();
        $lockedToOwner = $inventory->getLockedToOwner();

        $em->remove($inventory);

        $moneys = $rng->rngNextInt(4, 8);

        $transactionService->getMoney($user, $moneys, 'Found in some Ill-gotten Grains.');

        $inventoryService->receiveItem('Wheat', $user, $user, $user->getName() . ' got this from some Ill-gotten Grains.', $location, $lockedToOwner);

        $em->flush();

        $responseService->addFlashMessage('You rummage through the Ill-gotten Grains, finding some Wheat and ' . $moneys . '~~m~~.');

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }
}
