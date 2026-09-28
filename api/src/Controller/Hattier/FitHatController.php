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

namespace App\Controller\Hattier;

use App\Entity\Pet;
use App\Entity\PetHatFit;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotEnoughCurrencyException;
use App\Exceptions\PSPPetNotFoundException;
use App\Service\ResponseService;
use App\Service\TransactionService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/hattier")]
class FitHatController
{
    public const int MoneysCost = 100;
    public const int RecyclingCost = 50;

    #[Route("/fit", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function fitHat(
        TransactionService $transactionService, EntityManagerInterface $em,
        ResponseService $responseService,
        UserAccessor $userAccessor,

        #[MapRequestPayload]
        FitHatRequest $request
    ): JsonResponse
    {
        $payWith = strtolower($request->payWith);

        if($request->pet <= 0)
            throw new PSPInvalidOperationException('A pet must be selected.');

        if(
            $request->headX < PetHatFit::MinHeadXY || $request->headX > PetHatFit::MaxHeadXY ||
            $request->headY < PetHatFit::MinHeadXY || $request->headY > PetHatFit::MaxHeadXY ||
            $request->headAngle < PetHatFit::MinHeadAngle || $request->headAngle > PetHatFit::MaxHeadAngle ||
            $request->headScale < PetHatFit::MinHeadScale || $request->headScale > PetHatFit::MaxHeadScale
        )
        {
            throw new PSPFormValidationException('That hat would be... way off. Try something a little more reasonable?');
        }

        $user = $userAccessor->getUserOrThrow();

        if($payWith === 'moneys')
        {
            if($user->getMoneys() < self::MoneysCost)
                throw new PSPNotEnoughCurrencyException(self::MoneysCost . '~~m~~', $user->getMoneys() . '~~m~~');
        }
        else if($payWith === 'recycling')
        {
            if($user->getRecyclePoints() < self::RecyclingCost)
                throw new PSPNotEnoughCurrencyException(self::RecyclingCost . '♺', $user->getRecyclePoints() . '♺');
        }
        else
        {
            throw new PSPFormValidationException('You must choose whether to pay with moneys or with recycling points.');
        }

        $pet = $em->getRepository(Pet::class)->find($request->pet);

        if(!$pet || $pet->getOwner()->getId() !== $user->getId())
            throw new PSPPetNotFoundException();

        $hat = $pet->getHat();

        if(!$hat)
            throw new PSPInvalidOperationException('That pet isn\'t wearing a hat!');

        $description = 'Had ' . $pet->getName() . '\'s ' . $hat->getFullItemName() . ' fitted at the Hattier.';

        if($payWith === 'moneys')
            $transactionService->spendMoney($user, self::MoneysCost, $description, true, [ 'Hattier' ]);
        else
            $transactionService->spendRecyclingPoints($user, self::RecyclingCost, $description, [ 'Hattier' ]);

        $pet->setHatFit(new PetHatFit(
            headX: $request->headX,
            headY: $request->headY,
            headAngle: $request->headAngle,
            headScale: $request->headScale
        ));

        $em->flush();

        return $responseService->success();
    }
}

class FitHatRequest
{
    public function __construct(
        public int $pet = 0,
        public float $headX = 0,
        public float $headY = 0,
        public float $headAngle = 0,
        public float $headScale = 1,
        public string $payWith = 'moneys',
    )
    {
    }
}
