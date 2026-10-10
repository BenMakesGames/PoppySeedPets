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

namespace App\Controller\Item\Telephone;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Entity\UserPhoneNumber;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/item/telephone")]
class GetKnownNumbersController
{
    #[Route("/{inventory}/knownNumbers", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function getKnownNumbers(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'telephone');

        /** @var UserPhoneNumber[] $knownNumbers */
        $knownNumbers = $em->getRepository(UserPhoneNumber::class)->createQueryBuilder('upn')
            ->join('upn.phoneNumber', 'pn')
            ->andWhere('upn.user = :user')
            ->setParameter('user', $user)
            // numbers are strings of digits; sorting by length first gives numeric order
            ->addOrderBy('LENGTH(pn.number)', 'ASC')
            ->addOrderBy('pn.number', 'ASC')
            ->getQuery()
            ->execute();

        return $responseService->success(array_map(
            fn(UserPhoneNumber $known) => new KnownNumberResponse(
                label: $known->getPhoneNumber()->getLabel(),
                cost: $known->getPhoneNumber()->getCost(),
            ),
            $knownNumbers
        ));
    }
}

final readonly class KnownNumberResponse
{
    public function __construct(
        public string $label,
        public int $cost,
    )
    {
    }
}
