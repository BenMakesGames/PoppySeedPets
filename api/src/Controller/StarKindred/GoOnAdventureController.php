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
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPNotUnlockedException;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\StarKindred\StarKindredDailyAdventures;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/starKindred")]
class GoOnAdventureController
{
    #[Route("/adventure", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor,
        StarKindredAdventureService $starKindred, Clock $clock,

        #[MapRequestPayload]
        GoOnAdventureRequest $request
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        // ids are content hashes: if today's adventures changed since the player loaded the page (a new
        // day began, or a deploy changed the generator), the old id simply won't be found
        $adventure = StarKindredDailyAdventures::find($clock->now, $request->adventureId)
            ?? throw new PSPNotFoundException('That adventure could not be found. (Maybe a new day has begun?) Please reload, and try again.');

        if(InventoryService::countTotalInventory($em, $user, LocationEnum::Home) > 150)
            throw new PSPInvalidOperationException('Your house is far too cluttered to play ★Kindred!');

        $party = $starKindred->findParty($user, $request->characters);

        $result = $starKindred->goOnAdventure($user, $adventure, $request->difficulty, $party);

        $em->flush();

        $responseService->setReloadInventory();

        return $responseService->success($result);
    }
}

class GoOnAdventureRequest
{
    /**
     * @param int[] $characters
     */
    public function __construct(
        public readonly string $adventureId,
        public readonly StarKindredDifficultyEnum $difficulty,
        public readonly array $characters,
    )
    {
    }
}
