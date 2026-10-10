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

namespace App\Controller\Grocer;

use App\Functions\UserPhoneNumberRepository;
use App\Functions\UserQuestRepository;
use App\Service\GrocerService;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route('/grocer')]
class GetInventoryController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getInventory(
        GrocerService $grocerService, ResponseService $responseService, EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();
        $now = new \DateTimeImmutable();

        $grocerItemsQuantity = UserQuestRepository::findOrCreate($em, $user, 'Grocer Items Purchased Quantity', 0);
        $grocerItemsDay = UserQuestRepository::findOrCreate($em, $user, 'Grocer Items Purchased Date', $now->format('Y-m-d'));

        if($now->format('Y-m-d') === $grocerItemsDay->getValue())
            $maxCanPurchase = GrocerService::MaxCanPurchasePerDay - $grocerItemsQuantity->getIntValue();
        else
            $maxCanPurchase = GrocerService::MaxCanPurchasePerDay;

        return $responseService->success([
            'inventory' => $grocerService->getInventory(),
            'maxPerDay' => GrocerService::MaxCanPurchasePerDay,
            'maxRemainingToday' => $maxCanPurchase,
            // Bells recommends 1-800-ONIGIRI until the player knows it
            'knows1800Onigiri' => UserPhoneNumberRepository::knowsNumber($em, $user, '1-800-ONIGIRI'),
        ]);
    }
}