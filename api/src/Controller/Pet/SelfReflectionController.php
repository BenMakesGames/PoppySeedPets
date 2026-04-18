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
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\RelationshipEnum;
use App\Enum\SerializationGroupEnum;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPPetNotFoundException;
use App\Functions\PetActivityLogFactory;
use App\Service\IRandom;
use App\Service\PetRelationshipService;
use App\Service\ResponseService;
use App\Service\Typeahead\PetRelationshipTypeaheadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/pet")]
class SelfReflectionController
{
    #[Route("/{pet}/selfReflection", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getSelfReflectionData(
        Pet $pet, ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor
    ): JsonResponse
    {
        if($pet->getOwner()->getId() !== $userAccessor->getUserOrThrow()->getId())
            throw new PSPPetNotFoundException();

        $numberDisliked = (int)$em->createQueryBuilder()
            ->select('COUNT(r)')->from(PetRelationship::class, 'r')
            ->andWhere('r.pet=:pet')
            ->andWhere('r.currentRelationship IN (:currentRelationship)')
            ->setParameter('pet', $pet)
            ->setParameter('currentRelationship', [ RelationshipEnum::Dislike, RelationshipEnum::BrokeUp ])
            ->getQuery()
            ->getSingleScalarResult();

        if($numberDisliked <= 5)
        {
            $relationships = $em->getRepository(PetRelationship::class)->findBy([
                'pet' => $pet,
                'currentRelationship' => [ RelationshipEnum::Dislike, RelationshipEnum::BrokeUp ],
            ], [], $numberDisliked);

            $troubledRelationships = array_map(
                fn(PetRelationship $r) => [
                    'pet' => $r->getRelationship(),
                    'possibleRelationships' => PetRelationshipService::getRelationshipsBetween(
                        PetRelationshipService::max(RelationshipEnum::Friend, $r->getRelationshipGoal()),
                        PetRelationshipService::max(RelationshipEnum::Friend, $r->getRelationship()->getRelationshipWithOrThrow($r->getPet())->getRelationshipGoal())
                    )
                ],
                $relationships
            );
        }
        else
            $troubledRelationships = null;

        $data = [
            'troubledRelationships' => $troubledRelationships,
            'troubledRelationshipsCount' => $numberDisliked,
        ];

        return $responseService->success($data, [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]);
    }

    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("/{pet}/selfReflection/reconcile", methods: ["POST"], requirements: ["pet" => "\d+"])]
    public function reconcileWithAnotherPet(
        Pet $pet, Request $request, ResponseService $responseService, EntityManagerInterface $em, IRandom $rng,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if($user->getId() !== $pet->getOwner()->getId())
            throw new PSPPetNotFoundException();

        if($pet->getSelfReflectionPoint() < 1)
            throw new PSPInvalidOperationException($pet->getName() . ' does not have any Self-reflection Points remaining.');

        if($pet->hasMerit(MeritEnum::AFFECTIONLESS))
            throw new PSPInvalidOperationException($pet->getName() . ' is Affectionless. It\'s uninterested in reconciling.');

        $friendId = $request->request->getInt('petId');

        if(!$friendId)
            throw new PSPFormValidationException('You gotta\' choose a pet to reconcile with!');

        $relationship = $em->getRepository(PetRelationship::class)->findOneBy([ 'pet' => $pet->getId(), 'relationship' => $friendId ])
            ?? throw new PSPNotFoundException('Those pets don\'t seem to have a relationship of any kind...');

        if($relationship->getCurrentRelationship() !== RelationshipEnum::BrokeUp && $relationship->getCurrentRelationship() !== RelationshipEnum::Dislike)
            throw new PSPInvalidOperationException('Those pets are totally okay with each other already!');

        $friend = $relationship->getRelationship();

        $otherSide = $em->getRepository(PetRelationship::class)->findOneBy([ 'pet' => $friend, 'relationship' => $pet ])
            ?? throw new \Exception($pet->getName() . ' knows ' . $friend->getName() . ', but not the other way around! This is a terrible bug! Make Ben fix it!');

        $possibleRelationships = PetRelationshipService::getRelationshipsBetween(
            PetRelationshipService::max($relationship->getRelationshipGoal(), RelationshipEnum::Friend),
            PetRelationshipService::max($otherSide->getRelationshipGoal(), RelationshipEnum::Friend)
        );

        $newRelationship = $rng->rngNextFromArray($possibleRelationships);

        $minimumCommitment = PetRelationshipService::generateInitialCommitment($rng, $newRelationship, $newRelationship);

        $relationshipDescriptions = [
            RelationshipEnum::Friend->value => 'friends',
            RelationshipEnum::BFF->value => 'BFFs',
            RelationshipEnum::FWB->value => 'FWBs',
            RelationshipEnum::Mate->value => 'dating'
        ];

        $relationship
            ->setCurrentRelationship($newRelationship)
            ->setRelationshipGoal($newRelationship)
            ->setCommitment(max($relationship->getCommitment(), $minimumCommitment))
        ;

        PetActivityLogFactory::createUnreadLog($em, $pet, $pet->getName() . ' and ' . $friend->getName() . ' talked and made up! They are now ' . $relationshipDescriptions[$newRelationship->value] . '!')
            ->setIcon('icons/activity-logs/friend')
            ->addInterestingness(PetActivityLogInterestingness::PlayerActionResponse)
        ;

        $otherSide
            ->setCurrentRelationship($newRelationship)
            ->setRelationshipGoal($newRelationship)
            ->setCommitment(max($otherSide->getCommitment(), $minimumCommitment))
        ;

        $makeUpMessage = $pet->getName() . ' came over; they talked with ' . $friend->getName() . ', and the two made up! They are now ' . $relationshipDescriptions[$newRelationship->value] . '!';

        $friendActivityLog = $pet->getOwner()->getId() === $friend->getOwner()->getId()
            ? PetActivityLogFactory::createReadLog($em, $friend, $makeUpMessage)
            : PetActivityLogFactory::createUnreadLog($em, $friend, $makeUpMessage);

        $friendActivityLog
            ->setIcon('icons/activity-logs/friend')
            ->addInterestingness(PetActivityLogInterestingness::PlayerActionResponse);

        $pet->increaseSelfReflectionPoint(-1);

        $em->flush();

        return $responseService->success($pet, [ SerializationGroupEnum::MY_PET ]);
    }

    #[Route("/typeahead/troubledRelationships", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function troubledRelationshipsTypeaheadSearch(
        Request $request, ResponseService $responseService, EntityManagerInterface $em,
        PetRelationshipTypeaheadService $petRelationshipTypeaheadService, PetRelationshipService $petRelationshipService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        $petId = $request->query->getInt('petId', 0);

        if($petId <= 0)
            throw new PSPFormValidationException('You gotta\' choose a pet!');

        $pet = $em->getRepository(Pet::class)->find($petId);

        if(!$pet || $pet->getOwner()->getId() !== $user->getId())
            throw new PSPPetNotFoundException();

        $petRelationshipTypeaheadService->setParameters($pet, [
            RelationshipEnum::BrokeUp,
            RelationshipEnum::Dislike
        ]);

        $suggestions = array_map(function(Pet $otherPet) use($pet) {
            $possibleRelationships = PetRelationshipService::getRelationshipsBetween(
                PetRelationshipService::max(RelationshipEnum::Friend, $otherPet->getRelationshipWithOrThrow($pet)->getRelationshipGoal()),
                PetRelationshipService::max(RelationshipEnum::Friend, $pet->getRelationshipWithOrThrow($otherPet)->getRelationshipGoal())
            );

            return [
                'pet' => $otherPet,
                'possibleRelationships' => $possibleRelationships
            ];
        }, $petRelationshipTypeaheadService->search('name', $request->query->getString('search')));

        return $responseService->success($suggestions, [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]);
    }
}
