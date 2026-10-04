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

namespace App\Controller\Item\Scroll;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Entity\Pet;
use App\Entity\PetSpecies;
use App\Entity\User;
use App\Enum\PetLocationEnum;
use App\Enum\PetSpeciesName;
use App\Enum\UserStat;
use App\Functions\ActivityHelpers;
use App\Functions\GrammarFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetRepository;
use App\Functions\PetSpeciesRepository;
use App\Service\IRandom;
use App\Service\PetFactory;
use App\Service\ResponseService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/summoningScroll")]
class SummoningSomethingFriendlyController
{
    #[Route("/{inventory}/friendly", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function summonSomethingFriendly(
        Inventory $inventory, ResponseService $responseService, UserStatsService $userStatsRepository,
        EntityManagerInterface $em, PetFactory $petFactory, IRandom $rng,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'summoningScroll/#/friendly');

        $em->remove($inventory);

        $userStatsRepository->incrementStat($user, UserStat::ReadAScroll);

        $pet = null;
        $gotASentinel = false;
        $gotAReusedSentinel = false;

        if($rng->rngNextInt(1, 19) === 1)
        {
            $pet = $petFactory->createRandomPetOfSpecies(
                $user,
                PetSpeciesRepository::findOneByName($em, PetSpeciesName::Sentinel)
            );

            $gotASentinel = true;
        }

        if($pet === null)
        {
            $pet = $em->getRepository(Pet::class)->findOneBy(
                [
                    'owner' => $em->getRepository(User::class)->findOneBy([ 'email' => 'the-wilds@poppyseedpets.com' ])
                ],
                [ 'lastInteracted' => 'ASC' ]
            );

            if($pet)
            {
                if($pet->getSpecies()->getName() === 'Sentinel')
                {
                    $gotAReusedSentinel = true;
                }
                else
                {
                    $daysInTheWild = new \DateTimeImmutable()->diff($pet->getLastInteracted())->days;
                    $percentChanceOfTransformation = min(10, (int)floor($daysInTheWild / 14));

                    if($rng->rngNextInt(1, 100) <= $percentChanceOfTransformation)
                    {
                        $species = $rng->rngNextFromArray($em->getRepository(PetSpecies::class)->findAll());

                        if($species->getName() !== 'Sentinel' && !$species->getId()->equals($pet->getSpecies()->getId()))
                        {
                            PetActivityLogFactory::createUnreadLog(
                                $em,
                                $pet,
                                ActivityHelpers::PetName($pet) . ' was altered by the energies of the wilds! They were ' . GrammarFunctions::indefiniteArticle($pet->getSpecies()->getName()) . ' ' . $pet->getSpecies()->getName() . ', ' .
                                'but became ' . GrammarFunctions::indefiniteArticle($species->getName()) . ' ' . $species->getName() . '!'
                            );

                            $pet->setSpecies($species);
                        }
                    }
                }
            }
        }

        if($pet === null)
        {
            $allSpecies = $em->getRepository(PetSpecies::class)->findAll();

            $pet = $petFactory->createRandomPetOfSpecies($user, $rng->rngNextFromArray($allSpecies));

            $gotASentinel = $pet->getSpecies()->getName() === 'Sentinel';
        }

        $pet->setOwner($user);

        $numberOfPetsAtHome = PetRepository::getNumberAtHome($em, $user);

        if($numberOfPetsAtHome >= $user->getMaxPets())
        {
            $pet->setLocation(PetLocationEnum::DAYCARE);

            if($gotAReusedSentinel)
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, a Sentinel appears! (That\'s not a pet! But it looks like someone took care of it... has it done this before?) You put it in the Pet Shelter daycare...';
            else if($gotASentinel)
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, a Sentinel appears! (That\'s not a pet!) You put it in the Pet Shelter daycare...';
            else
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, ' . GrammarFunctions::indefiniteArticle($pet->getSpecies()->getName()) . ' ' . $pet->getSpecies()->getName() . ' named ' . $pet->getName() . ' opens the door, waves "hello", then closes it again before heading to the Pet Shelter!';
        }
        else
        {
            $pet->setLocation(PetLocationEnum::HOME);

            if($gotAReusedSentinel)
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, a Sentinel appears! (That\'s not a pet! But it looks like someone took care of it... has it done this before?) Well... it\'s here now, I guess...';
            else if($gotASentinel)
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, a Sentinel appears! (That\'s not a pet!) Well... it\'s here now, I guess...';
            else
                $message = 'You read the scroll... not ' . $rng->rngNextInt(3, 6) . ' seconds later, ' . GrammarFunctions::indefiniteArticle($pet->getSpecies()->getName()) . ' ' . $pet->getSpecies()->getName() . ' named ' . $pet->getName() . ' opens the door, and walks inside!';
        }

        $em->flush();

        $responseService->setReloadPets($numberOfPetsAtHome < $user->getMaxPets());

        return $responseService->itemActionSuccess($message, [ 'itemDeleted' => true ]);
    }
}
