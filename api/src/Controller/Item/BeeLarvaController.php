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

namespace App\Controller\Item;

use App\Entity\Inventory;
use App\Enum\FlavorEnum;
use App\Enum\PetLocationEnum;
use App\Enum\PetSpeciesName;
use App\Functions\ItemRepository;
use App\Functions\MeritRepository;
use App\Functions\PetColorFunctions;
use App\Functions\PetRepository;
use App\Functions\PetSpeciesRepository;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetFactory;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/beeLarva")]
class BeeLarvaController
{
    #[Route("/{inventory}/hatch", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function hatch(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        InventoryService $inventoryService, IRandom $rng, PetFactory $petFactory,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'beeLarva/#/hatch');

        $royalJellyId = ItemRepository::getIdByName($em, 'Royal Jelly');

        if($inventoryService->loseItem($user, $royalJellyId, $inventory->getLocation()) < 1)
        {
            return $responseService->itemActionSuccess('Hm... You\'ll need some Royal Jelly to hatch this larva...');
        }

        $em->remove($inventory);

        $em->flush();

        $giantBeeSpecies = PetSpeciesRepository::findOneByName($em,PetSpeciesName::MagicBee);

        $beeName = $rng->rngNextFromArray([
            'Mellifera', 'Bombus', 'Megachile', 'Eucerini', 'Xylocopa', 'Ceratina', 'Osmia', 'Anthidium',
            'Peponapis', 'Andrena', 'Cineraria', 'Halictus', 'Sphecodes', 'Nomada', 'Eucera', 'Euglossini',
            'Melecta',
        ]);

        $petColors = PetColorFunctions::generateRandomPetColors($rng);

        $newPet = $petFactory->createPet(
            $user, $beeName, $giantBeeSpecies,
            $petColors->colorA, $petColors->colorB,
            $rng->rngNextFromArray(FlavorEnum::cases()),
            MeritRepository::getRandomStartingMerit($em, $rng)
        );

        $newPet
            ->increaseLove(10)
            ->increaseSafety(10)
            ->increaseEsteem(10)
            ->increaseFood(-8)
            ->setScale($rng->rngNextInt(80, 120))
        ;

        $message = 'The larva unfurls itself, and molts, revealing a beautiful little bee!';

        $numberOfPetsAtHome = PetRepository::getNumberAtHome($em, $user);

        $petJoinsHouse = $numberOfPetsAtHome < $user->getMaxPets();

        if(!$petJoinsHouse)
        {
            $newPet->setLocation(PetLocationEnum::DAYCARE);
            $message .= "\n\nYour house is full, so it flies off to the daycare.";
        }

        $em->flush();

        $responseService
            ->setReloadPets($petJoinsHouse)
            ->setReloadInventory(true)
        ;

        return $responseService->itemActionSuccess($message, [ 'itemDeleted' => true ]);
    }

    #[Route("/{inventory}/returnToBeehive", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function returnToBeehive(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'beeLarva/#/returnToBeehive');

        if(!$user->getBeehive())
            return $responseService->itemActionSuccess('Hey, that\'s spoilers! You don\'t have the... thing you need... to be able to do that! Yet!');

        $user->getBeehive()
            ->addWorkers(1)
            ->addFlowerPower(36)
        ;

        $em->remove($inventory);
        $em->flush();

        return $responseService->itemActionSuccess('You return the larva to Queen ' . $user->getBeehive()->getQueenName() . ', who thanks you for your honor and loyalty. The colony redoubles their efforts, and hey: with 1 more worker than before! (Every bee counts!)', [ 'itemDeleted' => true ]);
    }

    #[Route("/{inventory}/giveToAntQueen", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function giveToAntQueen(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        InventoryService $inventoryService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'beeLarva/#/giveToAntQueen');

        $antQueenId = ItemRepository::getIdByName($em, 'Ant Queen');

        if($inventoryService->loseItem($user, $antQueenId, $inventory->getLocation()) < 1)
            return $responseService->itemActionSuccess('Narrator: But there was no Ant Queen for ' . $user->getName() . ' to give it to.');

        $inventoryService->receiveItem('Ant Queen\'s Favor', $user, $user, $user->getName() . ' received this from an Ant Queen in exchange for a Bee Larva...', $inventory->getLocation());

        $em->remove($inventory);
        $em->flush();

        $responseService->setReloadInventory(true);

        return $responseService->itemActionSuccess('The Ant Queen thanks you for your honor and loyalty, vows to repay the favor, and departs with the larva. (You got an Ant Queen\'s Favor!)', [ 'itemDeleted' => true ]);
    }
}
