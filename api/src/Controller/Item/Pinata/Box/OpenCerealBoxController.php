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
use App\Functions\EnchantmentRepository;
use App\Service\Clock;
use App\Service\HattierService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OpenCerealBoxController
{
    #[Route("/item/box/cereal/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openCerealBox(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService, IRandom $rng,
        UserStatsService $userStatsRepository, EntityManagerInterface $em, Clock $clock,
        UserAccessor $userAccessor, HattierService $hattierService
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'box/cereal/#/open');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        $newInventory = [];

        $location = $inventory->getLocation();

        $message = $user->getName() . ' got this from a Cereal Box.';

        $wheatOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat';

        $newInventory[] = $inventoryService->receiveItem('Corn', $user, $user, $message, $location, $inventory->getLockedToOwner());
        $newInventory[] = $inventoryService->receiveItem($wheatOrCorn, $user, $user, $message, $location, $inventory->getLockedToOwner());
        $newInventory[] = $inventoryService->receiveItem('Rice', $user, $user, $message, $location, $inventory->getLockedToOwner());

        for($i = 0; $i < 7; $i++)
            $newInventory[] = $inventoryService->receiveItem($rng->rngNextFromArray([ 'Corn', $wheatOrCorn, 'Rice' ]), $user, $user, $message, $location, $inventory->getLockedToOwner());

        $breakfast = EnchantmentRepository::findOneByName($em, 'Breakfast');
        $messageSuffix = null;

        if(!$hattierService->userHasUnlocked($user, $breakfast))
        {
            $hattierService->playerUnlockAura($user, $breakfast, 'You unlocked this by opening a Cereal Box!');
            $messageSuffix = ' (Also, some colorful loops fall out?! A new aura is available for you at the Hattier\'s!)';
        }

        BoxHelpers::countRemoveAndFlush($userStatsRepository, $user, $inventory, $responseService, $em);

        return BoxHelpers::createResponse($responseService, $newInventory, 'Opening the box revealed', $messageSuffix);
    }
}
