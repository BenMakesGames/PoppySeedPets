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

use App\Entity\Pet;
use App\Entity\PetRelationship;
use App\Enum\SerializationGroupEnum;
use App\Exceptions\PSPPetNotFoundException;
use App\Service\Filter\PetRelationshipFilterService;
use App\Service\PetSocialActivityService;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route("/pet")]
class RelationshipsController
{
    #[Route("/{pet}/relationships", methods: ["GET"], requirements: ["pet" => "\d+"])]
    public function getPetRelationships(
        Pet $pet, ResponseService $responseService, Request $request,
        PetRelationshipFilterService $petRelationshipFilterService
    ): JsonResponse
    {
        $petRelationshipFilterService->addRequiredFilter('pet', $pet);

        $relationships = $petRelationshipFilterService->getResults($request->query);

        return $responseService->success($relationships, [
            SerializationGroupEnum::FILTER_RESULTS,
            SerializationGroupEnum::PET_FRIEND
        ]);
    }

    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("/{pet}/friends", methods: ["GET"], requirements: ["pet" => "\d+"])]
    public function getPetFriends(
        Pet $pet, ResponseService $responseService, NormalizerInterface $normalizer,
        PetSocialActivityService $petSocialActivityService, EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        if($pet->getOwner()->getId() !== $userAccessor->getUserOrThrow()->getId())
            throw new PSPPetNotFoundException();

        $relationships = $petSocialActivityService->getFriends($pet);

        $relationshipCount = (int)$em->createQueryBuilder()
            ->select('COUNT(r)')->from(PetRelationship::class, 'r')
            ->andWhere('r.pet=:pet')
            ->setParameter('pet', $pet)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $responseService->success([
            'spiritCompanion' => $normalizer->normalize($pet->getSpiritCompanion(), null, [ 'groups' => [ SerializationGroupEnum::MY_PET ]]),
            'groups' => $normalizer->normalize($pet->getGroups(), null, [ 'groups' => [ SerializationGroupEnum::PET_GROUP ]]),
            'relationshipCount' => $relationshipCount,
            'friends' => $normalizer->normalize($relationships, null, [ 'groups' => [ SerializationGroupEnum::PET_FRIEND ]]),
        ]);
    }

    #[Route("/{pet}/familyTree", methods: ["GET"])]
    public function getFamilyTree(
        Pet $pet, ResponseService $responseService, EntityManagerInterface $em
    ): JsonResponse
    {
        $siblings = self::findSiblings($pet, $em);
        $parents = self::findParents($pet, $em);

        $grandparents = [];
        $spiritGrandparents = [];

        foreach($parents as $parent)
        {
            $grandparents = array_merge($grandparents, self::findParents($parent, $em));

            if($parent->getSpiritDad())
                $spiritGrandparents[] = $parent->getSpiritDad();
        }

        $children = $em->createQueryBuilder()
            ->select('p')
            ->from(Pet::class, 'p')
            ->andWhere('p.mom=:petId OR p.dad=:petId')
            ->setParameter('petId', $pet->getId())
            ->getQuery()
            ->getResult();

        return $responseService->success([
            'grandparents' => $grandparents,
            'parents' => $parents,
            'spiritGrandparents' => $spiritGrandparents,
            'spiritParent' => $pet->getSpiritDad(),
            'siblings' => $siblings,
            'children' => $children,
        ], [ SerializationGroupEnum::PET_FRIEND, SerializationGroupEnum::PET_SPIRIT_ANCESTOR ]);
    }


    /**
     * @return Pet[]
     */
    private static function findSiblings(Pet $pet, EntityManagerInterface $em): array
    {
        $parents = $pet->getParents();

        if(count($parents) === 0)
            return [];

        return $em->createQueryBuilder()
            ->select('p')
            ->from(Pet::class, 'p')
            ->andWhere('p.mom IN (:petParents) OR p.dad IN (:petParents)')
            ->andWhere('p.id != :petId')
            ->setParameter('petParents', array_map(fn(Pet $p) => $p->getId(), $parents))
            ->setParameter('petId', $pet->getId())
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @return Pet[]
     */
    private static function findParents(Pet $pet, EntityManagerInterface $em): array
    {
        $parents = $pet->getParents();

        if(count($parents) === 0)
            return [];

        return $em->getRepository(Pet::class)->findBy([
            'id' => array_map(fn(Pet $p) => $p->getId(), $parents),
        ]);
    }
}
