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
use App\Entity\User;
use App\Enum\PetLocationEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPPetNotFoundException;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\EquipmentFunctions;
use App\Functions\PetActivityLogFactory;
use App\Model\PetChanges;
use App\Model\PetShelterPet;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/pet")]
class ReleaseController
{
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    #[Route("/{pet}/release", methods: ["POST"], requirements: ["pet" => "\d+"])]
    public function releasePet(
        Pet $pet, Request $request, ResponseService $responseService, UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface $em, IRandom $rng,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if($pet->getOwner()->getId() !== $user->getId())
            throw new PSPPetNotFoundException();

        if($pet->getLocation() !== PetLocationEnum::DAYCARE && $pet->getLocation() !== PetLocationEnum::HOME)
            throw new PSPInvalidOperationException('Only pets at home, or in the daycare, may be released to the wilds.');

        $petCount = self::getTotalPetsOwned($em, $user);

        if($petCount === 1)
            throw new PSPInvalidOperationException('You can\'t release your very last pet! That would be FOOLISH!');

        if(!$passwordEncoder->isPasswordValid($user, $request->request->getString('confirmPassphrase')))
            throw new AccessDeniedHttpException('Passphrase is not correct.');

        $state = new PetChanges($pet);

        EquipmentFunctions::unequipPet($pet);
        EquipmentFunctions::unhatPet($pet);

        // a released pet's ★Kindred adventurer is simply gone - not retired into the owner's book
        $starKindredCharacter = StarKindredAdventureService::findActiveCharacter($em, $pet);

        if($starKindredCharacter)
            $em->remove($starKindredCharacter);

        // to prevent people from releasing rude names for other players to pick up, rename the pet unless it has one
        // of the game's default names:
        $sanitizedOriginalPetName = ucwords(strtolower(trim($pet->getName())));

        $newName = array_any(PetShelterPet::PetNames, fn($n) => $n == $sanitizedOriginalPetName)
            ? $sanitizedOriginalPetName // do NOT preserve the original capitalization, in case the player hid a bad word in just the capital letters, for example
            : $rng->rngNextFromArray(PetShelterPet::PetNames);

        $pet
            ->setName($newName)
            ->setOwner($em->getRepository(User::class)->findOneBy([ 'email' => 'the-wilds@poppyseedpets.com' ]))
            ->setParkEventType(null)
            ->setNote('')
            ->setCostume('')
            ->setLocation(PetLocationEnum::DAYCARE)
            ->increaseEsteem(-5 * ($pet->getLevel() + 1))
            ->increaseSafety(-5 * ($pet->getLevel() + 1))
            ->increaseLove(-6 * ($pet->getLevel() + 1))
            ->setLastInteracted(new \DateTimeImmutable())
        ;

        if($user->getHollowEarthPlayer() && $user->getHollowEarthPlayer()->getChosenPet())
        {
            if($user->getHollowEarthPlayer()->getChosenPet()->getId() === $pet->getId())
                $user->getHollowEarthPlayer()->setChosenPet(null);
        }

        PetActivityLogFactory::createUnreadLog($em, $pet, $user->getName() . ' gave up ' . ActivityHelpers::PetName($pet) . ', releasing them to The Wilds.')
            ->setChanges($state->compare($pet));

        $em->flush();

        return $responseService->success();
    }

    private static function getTotalPetsOwned(EntityManagerInterface $em, User $user): int
    {
        return (int)$em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.owner=:owner')
            ->setParameter('owner', $user->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
