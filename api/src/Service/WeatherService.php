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

namespace App\Service;

use App\Functions\CalendarFunctions;
use App\Model\WeatherData;
use App\Model\WeatherSky;

class WeatherService
{
    public function __construct(private readonly CacheHelper $cache)
    {
    }

    public static function getDaySeed(\DateTimeImmutable $dt): int
    {
        return ((int)$dt->format('Y') - 2015) * 2571 + (int)$dt->format('z') * 7 + (int)$dt->format('N') - 1;
    }

    public static function getWeather(\DateTimeImmutable $dt): WeatherData
    {
        $isLeapDay = CalendarFunctions::isLeapDay($dt);

        return new WeatherData(
            $dt,
            CalendarFunctions::getEventData($dt),
            $isLeapDay ? WeatherSky::Snowy : WeatherService::getSky($dt)
        );
    }

    public static function getSky(\DateTimeImmutable $dt): WeatherSky
    {
        $daySeed = WeatherService::getDaySeed($dt);

        return match ($dt->format('M'))
        {
            'Jan' => self::getRandomSky($daySeed, 6 / 31),
            'Feb' => self::getRandomSky($daySeed, 4 / 28.25),
            'Mar' => self::getRandomSky($daySeed, 4 / 31),
            'Apr' => self::getRandomSky($daySeed, 8 / 30),
            'May' => self::getRandomSky($daySeed, 12 / 31),
            'Jun' => self::getRandomSky($daySeed, 15 / 30),
            'Jul' => self::getRandomSky($daySeed, 20 / 31),
            'Aug' => self::getRandomSky($daySeed, 20 / 31),
            'Sep' => self::getRandomSky($daySeed, 17 / 30),
            'Oct' => self::getRandomSky($daySeed, 16 / 31),
            'Nov' => self::getRandomSky($daySeed, 6 /  30),
            'Dec' => self::getRandomSky($daySeed, 10 / 31),
            default => throw new \Exception('Invalid month')
        };
    }

    public static function getRandomSky(int $seed, float $chanceOfRain): WeatherSky
    {
        $rng = new Xoshiro($seed);

        if($rng->rngNextFloat() < $chanceOfRain)
        {
            if($rng->rngNextFloat() < 0.2) // 20% of rain is storm
                return WeatherSky::Stormy;

            return WeatherSky::Rainy;
        }

        if($rng->rngNextFloat() < $chanceOfRain / 2)
            return WeatherSky::Cloudy;

        return WeatherSky::Clear;
    }

    /**
     * @return WeatherData[]
     */
    public function getWeatherForecast(): array
    {
        $weather = [];

        for($day = 0; $day <= 6; $day++)
        {
            $date = new \DateTimeImmutable()->modify('+' . $day . ' days');

            $weather[] = $this->cache->getOrCompute(
                'Weather ' . $date->format('Y-m-d'),
                \DateInterval::createFromDateString('1 day'),
                fn() => self::getWeather($date->setTime(0, 0, 0))
            );
        }

        return $weather;
    }
}