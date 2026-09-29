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

use App\Entity\StarKindredCharacter;
use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPNotUnlockedException;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredCharacterSheet;
use App\Service\UserAccessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/starKindred")]
class GetCharacterController
{
    #[Route("/character/{character}", methods: ["GET"], requirements: ["character" => "\d+"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        StarKindredCharacter $character, ResponseService $responseService, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        // characters belong to pets; whoever owns the pet now may read its character sheets
        if($character->getPet()->getOwner()->getId() !== $user->getId())
            throw new PSPNotFoundException('That character could not be found.');

        return $responseService->success(
            StarKindredCharacterSheet::map($character),
            [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]
        );
    }
}
