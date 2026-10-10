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
use App\Entity\PhoneNumber;
use App\Entity\UserPhoneNumber;
use App\Enum\LocationEnum;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotEnoughCurrencyException;
use App\Functions\UserQuestRepository;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\TransactionService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/item/telephone")]
class CallController
{
    #[Route("/{inventory}/call", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function call(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        TransactionService $transactionService, IRandom $rng, InventoryService $inventoryService,
        Clock $clock, UserAccessor $userAccessor,

        #[MapRequestPayload]
        CallRequest $request
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'telephone');
        ItemControllerHelpers::validateLocationSpace($inventory, $em);

        // one call per day, total (across all numbers)
        $today = $clock->now->format('Y-m-d');
        $orderedDeliveryFood = UserQuestRepository::findOrCreate($em, $user, 'Ordered Delivery Food', $clock->now->modify('-1 day')->format('Y-m-d'));

        if($orderedDeliveryFood->getValue() === $today)
            throw new PSPInvalidOperationException('You already made a call today. (More than that is just irresponsible!)');

        $digits = PhoneNumber::toDigits($request->number);

        if($digits === '')
            throw new PSPFormValidationException('You have to dial a number before you can call it!');

        $phoneNumber = $em->getRepository(PhoneNumber::class)->findOneBy([ 'number' => $digits ])
            ?? throw new PSPInvalidOperationException('*bee-boo-BAA!* We\'re sorry, your call cannot be completed as dialed. Please check the number and dial again.');

        // remember the number, even if the player can't afford it right now
        $isKnownNumber = $em->getRepository(UserPhoneNumber::class)->count([ 'user' => $user, 'phoneNumber' => $phoneNumber ]) > 0;

        if(!$isKnownNumber)
        {
            $em->persist(new UserPhoneNumber($user, $phoneNumber));
            $em->flush();
        }

        $cost = $phoneNumber->getCost();

        if($user->getMoneys() < $cost)
            throw new PSPNotEnoughCurrencyException($cost . '~~m~~', $user->getMoneys() . '~~m~~');

        if($cost > 0)
            $transactionService->spendMoney($user, $cost, 'Called ' . $phoneNumber->getLabel() . '.');

        $orderedDeliveryFood->setValue($today);

        $comment = 'You got this by calling ' . $phoneNumber->getLabel() . '.';

        foreach($phoneNumber->getLoot() as $pick)
        {
            $itemNames = $rng->rngNextSubsetFromArray($pick->from, $pick->pick);

            sort($itemNames);

            foreach($itemNames as $itemName)
                $inventoryService->receiveItem($itemName, $user, $user, $comment, LocationEnum::Home);
        }

        $em->flush();

        $responseService->setReloadInventory();

        return $responseService->itemActionSuccess($phoneNumber->getMessage());
    }
}

class CallRequest
{
    public function __construct(
        public string $number = '',
    )
    {
    }
}
