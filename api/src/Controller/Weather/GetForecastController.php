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

namespace App\Controller\Weather;

use App\Attributes\DoesNotRequireHouseHours;
use App\Service\Clock;
use App\Service\ResponseService;
use App\Service\WeatherService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/weather")]
class GetForecastController
{
    #[DoesNotRequireHouseHours]
    #[Route("", methods: ["GET"])]
    public function getForecast(
        ResponseService $responseService, WeatherService $weatherService, Clock $clock
    ): JsonResponse
    {
        $data = [
            'forecast' => array_map(
                fn($forecast) => [
                    'date' => $forecast->date->format('Y-m-d'),
                    'sky' => $forecast->sky->value,
                    'holidays' => $forecast->holidays
                ],
                $weatherService->getWeatherForecast()
            ),
            // lets the client schedule its next fetch without trusting its own clock
            'secondsUntilNextDay' => $clock->now->modify('tomorrow')->getTimestamp() - $clock->now->getTimestamp(),
        ];

        return $responseService->success($data);
    }
}