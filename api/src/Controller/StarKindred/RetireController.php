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

namespace App\Controller\StarKindred;

use App\Enum\LocationEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotUnlockedException;
use App\Service\InventoryService;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The "retire these adventurers" adventure: always available to level-20 characters, and the only
 * adventure they can go on. Counts as the day's adventure.
 */
#[Route("/starKindred")]
class RetireController
{
    #[Route("/retire", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor,
        StarKindredAdventureService $starKindred,

        #[MapRequestPayload]
        RetireRequest $request
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        if(InventoryService::countTotalInventory($em, $user, LocationEnum::Home) > 150)
            throw new PSPInvalidOperationException('Your house is far too cluttered to play ★Kindred!');

        $party = $starKindred->findParty($user, $request->characters);

        $starKindred->markPlayedToday($user);

        $result = $starKindred->retire($user, $party);

        $em->flush();

        $responseService->setReloadInventory();

        return $responseService->success($result);
    }
}

class RetireRequest
{
    /**
     * @param int[] $characters
     */
    public function __construct(
        public readonly array $characters,
    )
    {
    }
}
