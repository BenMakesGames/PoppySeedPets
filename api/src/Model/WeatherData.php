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

namespace App\Model;

use App\Enum\HolidayEnum;

final class WeatherData
{
    /**
     * @param HolidayEnum[] $holidays
     */
    public function __construct(
        public readonly \DateTimeImmutable $date,
        public readonly array $holidays,
        public readonly WeatherSky $sky
    )
    {
    }

    public function isHoliday(HolidayEnum $holiday): bool
    {
        return in_array($holiday, $this->holidays);
    }

    public function isRaining(): bool
    {
        return $this->sky === WeatherSky::Rainy || $this->sky === WeatherSky::Stormy;
    }
}
