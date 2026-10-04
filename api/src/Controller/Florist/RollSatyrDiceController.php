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

namespace App\Controller\Florist;

use App\Enum\LocationEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPNotEnoughCurrencyException;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\CalendarFunctions;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\TransactionService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/florist")]
class RollSatyrDiceController
{
    #[Route("/rollSatyrDice", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function rollEm(
        ResponseService $responseService, EntityManagerInterface $em, InventoryService $inventoryService,
        Request $request, IRandom $rng, TransactionService $transactionService, Clock $clock,
        UserStatsService $userStatsService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Florist))
            throw new PSPNotUnlockedException('Florist');

        $bet = $request->request->getInt('bet');

        if($user->getRecyclePoints() < 100)
            throw new PSPNotEnoughCurrencyException('100♺', $user->getRecyclePoints() . '♺');

        $r1 = $rng->rngNextInt(1, 6);
        $r2 = $rng->rngNextInt(1, 6);

        if($rng->rngNextInt(1, 20) == 1)
            $r1 = 0;

        if($rng->rngNextInt(1, 20 - $r1) == 1)
            $r2 = 0;

        $total = $r1 + $r2;

        $items = [];
        $points = 0;

        if($total === 12)
        {
            $items = [ 'Shiny Baabble', 'Shiny Baabble' ];
        }
        else if($total === 11)
        {
            $items = [ 'Shiny Baabble' ];
        }
        else if($total === 10)
        {
            $items = [ 'Gold Baabble' ];
        }
        else if($total === 9)
        {
            $items = [ 'White Baabble' ];
        }
        else if($total === 8)
        {
            $items = [ 'Black Baabble' ];
            $points = 75;
        }
        else if($total >= 5 && $total <= 7)
        {
            $items = [ 'Black Baabble' ];
        }
        else if($total === 3 || $total === 4)
        {
            $items = [ 'Creamy Milk', 'Paper Bag' ];
        }
        else if($total === 2)
        {
            $items = [ 'Creamy Milk' ];
        }
        else if($total === 1)
        {
            $items = [ 'Quintessence' ];
        }
        else if($total === 0)
        {
            $items = [ 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence', 'Quintessence' ];
        }

        $dayOfTheWeek = strtolower($clock->now->format('l'));
        $dayOfTheWeekCoin = CalendarFunctions::dayOfTheWeekCoin($clock->now);

        if($r1 === 0) $items[] = $dayOfTheWeekCoin;
        if($r2 === 0) $items[] = $dayOfTheWeekCoin;

        $getDouble =
            ($total > 8 && $bet > 0) ||
            ($total === 8 && $bet === 0) ||
            ($total < 8 && $bet < 0)
        ;

        if($getDouble)
        {
            $points *= 2;
            $items = array_merge($items, $items);
        }

        sort($items);

        $transactionService->spendRecyclingPoints($user, 100, 'Spent at a game of Satyr Dice.', [ 'Satyr Dice' ]);

        if($points > 0)
            $transactionService->getRecyclingPoints($user, $points, 'Earned at a game of Satyr Dice.', [ 'Satyr Dice' ]);

        foreach($items as $itemName)
            $inventoryService->receiveItem($itemName, $user, $user, $user->getName() . ' got this from a game of Satyr Dice.', LocationEnum::Home);

        $userStatsService->incrementStat($user, UserStat::RolledSatyrDice);

        $em->flush();

        return $responseService->success([
            'dayOfTheWeek' => $dayOfTheWeek,
            'dice' => [ $r1, $r2 ],
            'getDouble' => $getDouble,
            'points' => $points,
            'items' => $items
        ]);
    }
}
