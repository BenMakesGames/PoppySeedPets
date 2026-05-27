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

use App\Enum\SerializationGroupEnum;
use App\Exceptions\PSPFormValidationException;
use App\Service\ResponseService;
use App\Service\Typeahead\PetTypeaheadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use App\Service\UserAccessor;

#[Route("/pet")]
class TypeaheadController
{
    #[Route("/typeahead", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function typeaheadSearch(
        Request $request, ResponseService $responseService, PetTypeaheadService $petTypeaheadService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        $petTypeaheadService->setUser($user);

        if($request->query->has('speciesId'))
        {
            try
            {
                $petTypeaheadService->setSpeciesId(Ulid::fromString($request->query->getString('speciesId')));
            }
            catch(\InvalidArgumentException $e)
            {
                throw new PSPFormValidationException('Invalid species ID.');
            }
        }

        $suggestions = $petTypeaheadService->search('name', $request->query->getString('search'));

        return $responseService->success($suggestions, [ SerializationGroupEnum::MY_PET, SerializationGroupEnum::MY_PET_LOCATION ]);
    }
}
