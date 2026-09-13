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

use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\InventoryHelpers;
use App\Functions\PlayerLogFactory;
use App\Service\BeehiveService;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/beehive")]
class RerollController
{
    #[Route("/reroll", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function reroll(
        ResponseService $responseService, EntityManagerInterface $em, BeehiveService $beehiveService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Beehive) || !$user->getBeehive())
            throw new PSPNotUnlockedException('Beehive');

        $compass = InventoryHelpers::findOneToConsume($em, $user, 'Gold Compass');

        if(!$compass)
            throw new PSPInvalidOperationException('You need a Gold Compass (at home, or in your Basement) to do that.');

        $em->remove($compass);
        $responseService->setReloadInventory();

        $beehiveService->rerollSpaceTypes($user->getBeehive());

        PlayerLogFactory::create($em, $user, 'You used a Gold Compass on your Beehive; the bees rearranged their spaces.', [ 'Beehive' ]);

        $em->flush();

        $responseService->addFlashMessage('The needle spins... and the bees rearrange themselves!');

        return $responseService->success($beehiveService->getResponseData($user), [ SerializationGroupEnum::MY_BEEHIVE, SerializationGroupEnum::HELPER_PET ]);
    }
}
