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

namespace App\Controller\Item\PetAlteration;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Entity\Pet;
use App\Entity\PetSpecies;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPPetNotFoundException;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Ulid;
use App\Service\UserAccessor;

#[Route("/item/transmigrationSerum")]
class TransmigrationSerumController
{
    #[Route("/{inventory}/INJECT", methods: ["PATCH"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function INJECT(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em, Request $request,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'transmigrationSerum');

        $petId = $request->request->getInt('pet', 0);
        $pet = $em->getRepository(Pet::class)->find($petId);

        if(!$pet || $pet->getOwner()->getId() !== $user->getId())
            throw new PSPPetNotFoundException();

        $speciesIdRaw = $request->request->getString('species', '');

        if($speciesIdRaw === '')
            throw new PSPInvalidOperationException('A species to transmigrate to was not selected.');

        try
        {
            $speciesId = Ulid::fromString($speciesIdRaw);
        }
        catch(\InvalidArgumentException $e)
        {
            throw new PSPFormValidationException('The selected species doesn\'t exist?? Try reloading and trying again.');
        }

        if($speciesId->equals($pet->getSpecies()->getId()))
            throw new PSPInvalidOperationException('That\'s ' . $pet->getName() . '\'s current species! No sense in wasting the serum!');

        $newSpecies = $em->getRepository(PetSpecies::class)->find($speciesId);

        if(!$newSpecies)
            throw new PSPFormValidationException('The selected species doesn\'t exist?? Try reloading and trying again.');

        if($newSpecies->getFamily() !== $pet->getSpecies()->getFamily())
            throw new PSPInvalidOperationException($pet->getName() . ' can\'t be transmigrated into a ' . $newSpecies->getName() . '.');

        $em->remove($inventory);

        $pet->setSpecies($newSpecies);

        $em->flush();

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }
}
