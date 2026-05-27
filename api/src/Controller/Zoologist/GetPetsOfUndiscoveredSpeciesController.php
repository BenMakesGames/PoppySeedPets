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

namespace App\Controller\Zoologist;

use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\SimpleDb;
use App\Model\FilterResults;
use App\Service\ResponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use App\Service\UserAccessor;

#[Route("/zoologist")]
class GetPetsOfUndiscoveredSpeciesController
{
    #[Route("/showable", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getPets(
        Request $request, ResponseService $responseService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Zoologist))
            throw new PSPNotUnlockedException('Zoologist');

        $page = $request->query->getInt('page', 0);

        $resultCount = SimpleDb::createReadOnlyConnection()
            ->query(
                'SELECT count(pet.id)
                FROM pet
                LEFT JOIN pet_species AS species ON pet.species_id=species.id
                LEFT JOIN user_species_collected AS discovered
                    ON species.id=discovered.species_id AND discovered.user_id=pet.owner_id
                WHERE
                    pet.owner_id=:userId
                    AND discovered.id IS NULL
                ',
                [
                    ':userId' => $user->getId(),
                ]
            )
            ->getSingleValue();

        $pets = SimpleDb::createReadOnlyConnection()
            ->query(
                'SELECT pet.id,pet.name,pet.color_a,pet.color_b,pet.scale,species.id AS speciesId,species.name AS speciesName,species.image
                FROM pet
                LEFT JOIN pet_species AS species ON pet.species_id=species.id
                LEFT JOIN user_species_collected AS discovered
                    ON species.id=discovered.species_id AND discovered.user_id=pet.owner_id
                WHERE
                    pet.owner_id=:userId
                    AND discovered.id IS NULL
                LIMIT :offset,20
                ',
                [
                    ':userId' => $user->getId(),
                    ':offset' => $page * 20,
                ]
            )
            ->mapResults(fn(int $petId, string $petName, string $petColorA, string $petColorB, int $petScale, string $speciesId, string $speciesName, string $speciesImage) => [
                'id' => $petId,
                'name' => $petName,
                'colorA' => $petColorA,
                'colorB' => $petColorB,
                'scale' => $petScale,
                'species' => [
                    'id' => Ulid::fromBinary($speciesId)->toBase32(),
                    'name' => $speciesName,
                    'image' => $speciesImage,
                ],
            ]);

        $results = new FilterResults();

        $results->page = $page;
        $results->pageSize = 20;
        $results->pageCount = (int)ceil($resultCount / 20);
        $results->resultCount = $resultCount;
        $results->results = $pets;

        return $responseService->success($results, [ SerializationGroupEnum::FILTER_RESULTS ]);
    }
}