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

namespace App\Controller\Beehive;

use App\Entity\Beehive;
use App\Entity\Fireplace;
use App\Enum\LocationEnum;
use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\ArrayFunctions;
use App\Functions\ItemRepository;
use App\Functions\PlayerLogFactory;
use App\Functions\RequestFunctions;
use App\Service\BeehiveService;
use App\Service\InventoryService;
use App\Service\ResponseService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/beehive")]
class FeedController
{
    #[Route("/feed", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function feedItem(
        ResponseService $responseService, EntityManagerInterface $em, BeehiveService $beehiveService,
        InventoryService $inventoryService, Request $request, UserStatsService $userStatsRepository,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Beehive) || !$user->getBeehive())
            throw new PSPNotUnlockedException('Beehive');

        $itemIds = RequestFunctions::getUniqueIdsOrThrow($request, 'flowers', 'No items were selected???');

        $items = $beehiveService->findFlowers($user, $itemIds);

        if(count($items) < count($itemIds))
            throw new PSPNotFoundException('Some of the items selected could not be found. That shouldn\'t happen. Reload and try again, maybe?');

        $beehive = $user->getBeehive();

        $itemsNotUsed = [];
        $itemsUsed = [];

        foreach($items as $item)
        {
            $flowerPower = BeehiveService::computeFlowerPower($item);

            // don't feed an item if doing so would waste more than half the item's fuel
            if($beehive->getFlowerPower() + $flowerPower / 2 <= $beehive->getMaxFlowerPower())
            {
                $beehive->addFlowerPower($flowerPower);
                $em->remove($item);
                $itemsUsed[] = $item->getFullItemName();
            }
            else
            {
                $itemsNotUsed[] = $item->getFullItemName();
            }
        }

        if(count($itemsUsed) > 0)
        {
            $entry = count($itemsUsed) == 1
                ? 'You gave ' . $itemsUsed[0] . ' to your Beehive.'
                : 'You gave the following items to your Beehive: ' . ArrayFunctions::list_nice($itemsUsed) . '.';

            PlayerLogFactory::create($em, $user, $entry, [ 'Beehive' ]);

            $userStatsRepository->incrementStat($user, UserStat::FedTheBeehive, count($itemsUsed));
        }

        $em->flush();

        if(count($itemsNotUsed) > 0)
        {
            $responseService->addFlashMessage(
                'The bees can only handle so much! Giving them the ' . ArrayFunctions::list_nice($itemsNotUsed) .
                ' would be wasteful at this point, so you held on to ' . (count($itemsNotUsed) == 1 ? 'it' : 'them') . ' for now.'
            );
        }

        return $responseService->success($beehiveService->getResponseData($user), [ SerializationGroupEnum::MY_BEEHIVE, SerializationGroupEnum::HELPER_PET ]);
    }
}
