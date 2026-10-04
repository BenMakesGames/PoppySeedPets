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

namespace App\Command;

use App\Functions\DateFunctions;

class TestMoonPhaseMathCommand extends PoppySeedPetsCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:test-moon-phase-math')
            ->setDescription('Tests horrible, horrible Moon phase math.')
        ;
    }

    protected function doCommand(): int
    {
        $currentYear = (int)(new \DateTimeImmutable())->format('Y');

        for($year = $currentYear - 1; $year <= $currentYear + 1; $year++)
        {
            for($month = 1; $month <= 12; $month++)
            {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

                for($day = 1; $day <= $daysInMonth; $day++)
                {
                    $date = DateFunctions::createFromYearMonthDay($year, $month, $day);
                    $fullMoonName = DateFunctions::getFullMoonName($date);

                    if($fullMoonName)
                    {
                        $exact = DateFunctions::getIsExactFullMoon($date) ? ' *' : '';
                        $moonAge = DateFunctions::getMoonAge($date);
                        echo $date->format('Y-m-d') . ' ' . round($moonAge, 3) . ' ' . $fullMoonName->value . $exact . "\n";
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
