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

namespace App\Controller\Pet;

use App\Entity\Inventory;
use App\Entity\Pet;
use App\Enum\LocationEnum;
use App\Enum\SerializationGroupEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPPetNotFoundException;
use App\Service\IRandom;
use App\Service\PetActivity\EatingService;
use App\Service\QualityTimeService;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/pet")]
class PetAndFeedController
{
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("/{pet}/feed", methods: ["POST"], requirements: ["pet" => "\d+"])]
    public function feed(
        Pet $pet, Request $request, ResponseService $responseService, EntityManagerInterface $em,
        IRandom $rng, EatingService $eatingService, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if($pet->getOwner()->getId() !== $userAccessor->getUserOrThrow()->getId())
            throw new PSPPetNotFoundException();

        if(!$pet->isAtHome())
            throw new PSPInvalidOperationException('Pets that aren\'t home cannot be interacted with.');

        $items = $request->request->all('items');

        $inventory = $em->getRepository(Inventory::class)->findBy([
            'owner' => $user,
            'id' => $items,
            'location' => LocationEnum::Home,
        ]);

        if(count($items) !== count($inventory))
            throw new PSPNotFoundException('At least one of the items selected doesn\'t seem to exist?? (Reload and try again...)');

        $array = $eatingService->doFeed($user, $pet, $inventory);

        $em->flush();

        $emoji = null;

        if ($array['ateFavFood'] === true)
            $emoji = $pet->getRandomAffectionExpression($rng);

        if($emoji)
            return $responseService->success([ 'pet' => $pet, 'emoji' => $emoji ], [ SerializationGroupEnum::MY_PET ]);
        else
            return $responseService->success($pet, [ SerializationGroupEnum::MY_PET ]);
    }
}
