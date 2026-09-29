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
use App\Enum\LocationEnum;
use App\Functions\ItemRepository;
use App\Functions\SpiceRepository;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserStatsService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/benjaminFranklin")]
class BenjaminFranklinController
{
    #[Route("/{inventory}/fly", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function moldButterknife(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        InventoryService $inventoryService, UserAccessor $userAccessor, IRandom $rng,
        UserStatsService $userStatsService
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'benjaminFranklin/#/fly');

        $weather = WeatherService::getWeather(new \DateTimeImmutable());

        if(!$weather->isRaining())
        {
            return $responseService->itemActionSuccess('You had a nice time, and all, but you suspect something _more interesting_ would happen if you flew the kite while it was raining out.');
        }

        $fluff = ItemRepository::findOneByName($em, 'Fluff');
        $burnt = SpiceRepository::findOneByName($em, 'Burnt');

        $reloadPets = $inventory->getHolder() || $inventory->getWearer();

        $remainsComment = 'The remains of a Benjamin Franklin.';

        if($rng->rngNextInt(1, 10) === 1)
            $remainsComment .= ' (The kite; not the person.)';

        $inventory
            ->changeItem($fluff)
            ->setSpice($burnt)
            ->addComment($remainsComment)
            ->setModifiedOn()
        ;

        $comment = $user->getName() . ' got this by flying a kite in the rain.';

        if($rng->rngNextInt(1, 10) === 1)
            $comment .= ' They also got dead. Oh, wait, no: thank goodness: this is just a video game, so they\'re actually fine.';

        $inventoryService->receiveItem('Lightning in a Bottle', $user, $user, $comment, LocationEnum::Home, $inventory->getLockedToOwner());

        $userStatsService->incrementStat($user, 'Flew a Kite in the Rain');

        $em->flush();

        $responseService->setReloadPets($reloadPets);

        return $responseService->itemActionSuccess('Predictably, the kite is struck by lightning. You get a bit of a shock, and the kite is _absolutely_ decimated. But it was all worth it: you\'re now the proud (if not slightly singed) owner of some Lightning in a Bottle.', [ 'itemDeleted' => true ]);
    }
}
