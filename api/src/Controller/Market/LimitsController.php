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

namespace App\Controller\Market;

use App\Enum\LocationEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Functions\ItemRepository;
use App\Functions\PlayerLogFactory;
use App\Functions\UserPhoneNumberRepository;
use App\Service\InventoryService;
use App\Service\MarketService;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/market")]
class LimitsController
{
    #[Route("/limits", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getMarketLimits(ResponseService $responseService, MarketService $marketService,
        EntityManagerInterface $em, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        return $responseService->success([
            'offeringBulkSellUpgrade' => $marketService->canOfferWingedKey($user),
            // Argentelle recommends 1-800-CURRIES until the player knows it
            'knows1800Curries' => UserPhoneNumberRepository::knowsNumber($em, $user, '1-800-CURRIES'),
            'limits' => [
                'moneysLimit' => $user->getMaxSellPrice(),
                'itemRequired' => $marketService->getItemToRaiseLimit($user)
            ]
        ]);
    }

    #[Route("/limits/increase", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function increaseMarketLimits(
        ResponseService $responseService, MarketService $marketService, InventoryService $inventoryService,
        EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        $itemRequired = $marketService->getItemToRaiseLimit($user);

        if(!$itemRequired)
            throw new PSPInvalidOperationException('The market limits don\'t go any higher!');

        $itemRequiredId = ItemRepository::getIdByName($em, $itemRequired['itemName']);

        if($inventoryService->loseItem($user, $itemRequiredId, [ LocationEnum::Home, LocationEnum::Basement ], 1) === 0)
            throw new PSPNotFoundException('Come back when you ACTUALLY have the item.');

        $user->setMaxSellPrice($user->getMaxSellPrice() + 10);

        PlayerLogFactory::create(
            $em,
            $user,
            'You gave ' . $itemRequired['itemName'] . ' to Argentelle to increase your maximum Market sell price to ' . $user->getMaxSellPrice() . '.',
            [ 'Market' ]
        );

        $em->flush();

        return $responseService->success([
            'moneysLimit' => $user->getMaxSellPrice(),
            'itemRequired' => $marketService->getItemToRaiseLimit($user)
        ]);
    }
}
