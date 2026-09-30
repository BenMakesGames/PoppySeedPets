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

use App\Entity\InventoryEnchantment;
use App\Entity\Pet;
use App\Entity\UserUnlockedAura;
use App\Enum\PetBadgeEnum;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotEnoughCurrencyException;
use App\Exceptions\PSPNotFoundException;
use App\Exceptions\PSPPetNotFoundException;
use App\Functions\PetBadgeHelpers;
use App\Service\ResponseService;
use App\Service\TransactionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/hattier")]
class ApplyAuraController
{
    #[Route("/buy", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function applyAura(
        TransactionService $transactionService, EntityManagerInterface $em,
        ResponseService $responseService,
        UserAccessor $userAccessor,

        #[MapRequestPayload]
        ApplyAuraRequest $request
    ): JsonResponse
    {
        $payWith = strtolower($request->payWith);
        $hue = $request->hue % 360;

        if($request->brightness < InventoryEnchantment::MinBrightness || $request->brightness > InventoryEnchantment::MaxBrightness)
            throw new PSPFormValidationException('Brightness must be between ' . InventoryEnchantment::MinBrightness . ' and ' . InventoryEnchantment::MaxBrightness . '.');

        if($request->pet <= 0 || $request->aura <= 0)
            throw new PSPInvalidOperationException('A pet and style must be selected.');

        $user = $userAccessor->getUserOrThrow();

        if($payWith === 'moneys')
        {
            if($user->getMoneys() < 200)
                throw new PSPNotEnoughCurrencyException('200~~m~~', $user->getMoneys() . '~~m~~');
        }
        else if($payWith === 'recycling')
        {
            if($user->getRecyclePoints() < 100)
                throw new PSPNotEnoughCurrencyException('100♺', $user->getRecyclePoints() . '♺');
        }
        else
        {
            throw new PSPFormValidationException('You must choose whether to pay with moneys or with recycling points.');
        }

        $pet = $em->getRepository(Pet::class)->find($request->pet);

        if(!$pet || $pet->getOwner()->getId() !== $user->getId())
            throw new PSPPetNotFoundException();

        if(!$pet->getHat())
            throw new PSPInvalidOperationException('That pet isn\'t wearing a hat!');

        $unlockedAura = $em->getRepository(UserUnlockedAura::class)->find($request->aura);

        if(!$unlockedAura || $unlockedAura->getUser()->getId() !== $user->getId())
            throw new PSPNotFoundException('You haven\'t unlocked that Hattier style.');

        if($payWith === 'moneys')
            $transactionService->spendMoney($user, 200, 'Bought the ' . $unlockedAura->getAura()->getAura()->getName() . ' style from the Hattier.', true, [ 'Hattier' ]);
        else
            $transactionService->spendRecyclingPoints($user, 100, 'Bought the ' . $unlockedAura->getAura()->getAura()->getName() . ' style from the Hattier.', [ 'Hattier' ]);

        $pet->getHat()->setEnchantment($unlockedAura->getAura(), $hue, $request->brightness);

        PetBadgeHelpers::awardBadgeAndLog($em, $pet, PetBadgeEnum::TriedOnANewStyle, null);

        $em->flush();

        return $responseService->success();
    }
}

class ApplyAuraRequest
{
    public function __construct(
        public int $pet = 0,
        public int $aura = 0,
        public string $payWith = 'moneys',
        public int $hue = 0,
        public int $brightness = 100,
    )
    {
    }
}