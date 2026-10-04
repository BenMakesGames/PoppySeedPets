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

namespace App\Controller\Plaza;

use App\Functions\CalendarFunctions;
use App\Service\CacheHelper;
use App\Service\Clock;
use App\Service\ResponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/plaza")]
class GetEventCalendarController
{
    #[Route("/eventCalendar", methods: ["GET"])]
    public function getCalendar(
        ResponseService $responseService, Clock $clock, CacheHelper $cacheHelper
    ): JsonResponse
    {
        $cacheKey = 'EventCalendar-' . $clock->now->format('Y-m');

        $eventCalendar = $cacheHelper->getOrCompute($cacheKey, \DateInterval::createFromDateString('1 month'), function() use ($clock) {
            $endMonth = (int)(((int)$clock->now->format('Y') + 1) . $clock->now->format('m'));
            $today = \DateTimeImmutable::createFromFormat('Y-m-d', $clock->now->format('Y-m') . '-01')
                ?: throw new \RuntimeException('Invalid date format');

            $currentYear = 0;
            $currentMonth = 0;
            /** @var array<int, array{year: number, months: array<int, array{month: number, dayOfWeek: number, date: string, events: array<int, array{title: string, start: string, end: string}>}>}> $years */
            $years = [];
            $oneDay = \DateInterval::createFromDateString('1 day');

            while($today->format('Ym') < $endMonth)
            {
                if($today->format('Y') !== $currentYear)
                {
                    $currentYear = $today->format('Y');
                    $currentMonth = 0;

                    $years[] = [ 'year' => $currentYear, 'months' => [] ];
                }

                if($today->format('n') !== $currentMonth)
                {
                    $currentMonth = $today->format('n');

                    $years[count($years) - 1]['months'][] = [
                        'month' => $today->format('F'),
                        'days' => []
                    ];
                }

                $years[count($years) - 1]['months'][count($years[count($years) - 1]['months']) - 1]['days'][] = [
                    'dayOfWeek' => (int)$today->format('N'),
                    'date' => $today->format('Y-m-d'),
                    'holidays' => CalendarFunctions::getEventData($today),
                ];

                $today = $today->add($oneDay);
            }

            return $years;
        });

        return $responseService->success([
            'today' => $clock->now->format('Y-m-d'),
            'years' => $eventCalendar,
        ]);
    }
}
