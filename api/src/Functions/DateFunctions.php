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

namespace App\Functions;

use App\Enum\MoonPhaseEnum;
use App\Enum\MoonNameEnum;

final class DateFunctions
{
    public const float MoonCycleLength = 29.53058868;

    public const array FullMoonNames = [
        1 => MoonNameEnum::WolfMoon,
        2 => MoonNameEnum::SnowMoon,
        3 => MoonNameEnum::WormMoon,
        4 => MoonNameEnum::PinkMoon,
        5 => MoonNameEnum::FlowerMoon,
        6 => MoonNameEnum::StrawberryMoon,
        7 => MoonNameEnum::BuckMoon,
        8 => MoonNameEnum::SturgeonMoon,
        9 => MoonNameEnum::CornMoon,
        10 => MoonNameEnum::HuntersMoon,
        11 => MoonNameEnum::BeaverMoon,
        12 => MoonNameEnum::ColdMoon
    ];

    // adapted from https://github.com/BenMakesGames/PsyPets/blob/a22ba399c051f2a8ea38e9e0f48369f9606fd557/commons/moonphase.php
    // which was adapted from http://home.att.net/~srschmitt/script_moon_phase.html#contents
    // which was adapted from a BASIC program in the Astronomical Computing column of Sky & Telescope, April 1994.
    // what a history :P
    public static function moonPhase(\DateTimeInterface $dt): MoonPhaseEnum
    {
        $AG = self::getMoonAge($dt);

        if($AG < 1.84566)
            return MoonPhaseEnum::NewMoon;
        else if($AG < 5.53699)
            return MoonPhaseEnum::WaxingCrescent;
        else if($AG < 9.22831)
            return MoonPhaseEnum::FirstQuarter;
        else if($AG < 12.91963)
            return MoonPhaseEnum::WaxingGibbous;
        else if($AG < 16.61096)
            return MoonPhaseEnum::FullMoon;
        else if($AG < 20.30228)
            return MoonPhaseEnum::WaningGibbous;
        else if($AG < 23.99361)
            return MoonPhaseEnum::LastQuarter;
        else if($AG < 27.68493)
            return MoonPhaseEnum::WaningCrescent;
        else
            return MoonPhaseEnum::NewMoon;
    }

    public static function getJulianDate(int $year, int $month, int $day): int
    {
        $yy = $year - (int)((12 - $month) / 10);
        $mm = $month + 9;

        if($mm >= 12)
            $mm -= 12;

        $k1 = (int)(365.25 * ($yy + 4712));
        $k2 = (int)(30.6001 * $mm + 0.5);
        $k3 = (int)((int)(($yy / 100) + 49) * 0.75) - 38;

        $j = $k1 + $k2 + $day + 59;

        if($j > 2299160)
            $j -= $k3;

        return $j;
    }

    // this is wrong; we get the wrong moon age, by a smallish (but significant) margin,
    // which causes our "exact full moon" dates to be incorrect!
    public static function getMoonAge(\DateTimeInterface $dt): float
    {
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        $day = (int)$dt->format('j');

        $JD = gregoriantojd($month, $day, $year);

        $IP = ($JD - 2451550.1) / self::MoonCycleLength;

        // normalize IP
        $IP -= (int)floor($IP);
        if($IP < 0)
            $IP++;

        return $IP * self::MoonCycleLength;
    }

    public static function getIsExactFullMoon(\DateTimeImmutable $dt): bool
    {
        $halfMoonLength = self::MoonCycleLength / 2;

        $moonAge = self::getMoonAge($dt);

        $isFullMoonAtAll = $moonAge > 12.91963 && $moonAge <= 16.61096;

        if(!$isFullMoonAtAll)
            return false;

        if($moonAge > $halfMoonLength)
        {
            $moonAgeYesterday = self::getMoonAge($dt->modify('-1 day'));

            return abs($moonAgeYesterday - $halfMoonLength) > abs($moonAge - $halfMoonLength);
        }
        else
        {
            $moonAgeTomorrow = self::getMoonAge($dt->modify('+1 day'));

            return abs($moonAgeTomorrow - $halfMoonLength) > abs($moonAge - $halfMoonLength);
        }
    }

    public static function getClosestExactFullMoon(\DateTimeImmutable $dt): \DateTimeImmutable
    {
        if(self::getIsExactFullMoon($dt))
            return $dt;

        for($d = 1; ; $d++)
        {
            if(self::getIsExactFullMoon($dt->modify('+' . $d . ' days')))
                return $dt->modify('+' . $d . ' days');

            if(self::getIsExactFullMoon($dt->modify('-' . $d . ' days')))
                return $dt->modify('-' . $d . ' days');
        }
    }

    /**
     * @return int[]
     */
    public static function getFullExactMoonDays(int $year, int $month): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $fullMoonDays = [];

        for($d = 1; $d <= $daysInMonth; $d++)
        {
            if(self::getIsExactFullMoon(self::createFromYearMonthDay($year, $month, $d)))
                $fullMoonDays[] = $d;
        }

        return $fullMoonDays;
    }

    public static function createFromYearMonthDay(int $year, int $month, int $day): \DateTimeImmutable
    {
        return (\DateTimeImmutable::createFromFormat('Y n j', $year . ' ' . $month . ' ' . $day))->setTime(0, 0, 0);
    }

    public static function isCornMoon(\DateTimeImmutable $dt): bool
    {
        return self::isSpecificMoon($dt, MoonNameEnum::CornMoon);
    }

    public static function isSpecificMoon(\DateTimeImmutable $dt, MoonNameEnum $moonName): bool
    {
        return DateFunctions::getFullMoonName($dt) === $moonName;
    }

    public static function getFullMoonName(\DateTimeImmutable $dt): ?MoonNameEnum
    {
        if(self::moonPhase($dt) !== MoonPhaseEnum::FullMoon)
            return null;

        $exactFullMoon = self::getClosestExactFullMoon($dt);

        $fullMoonYear = (int)$exactFullMoon->format('Y');
        $fullMoonMonth = (int)$exactFullMoon->format('n');

        $fullMoonDaysThatMonth = self::getFullExactMoonDays($fullMoonYear, $fullMoonMonth);

        if(count($fullMoonDaysThatMonth) === 1)
            return self::FullMoonNames[$fullMoonMonth];

        $exactFullMoonDay = (int)$exactFullMoon->format('d');

        if($exactFullMoonDay == $fullMoonDaysThatMonth[0])
            return self::FullMoonNames[$fullMoonMonth];
        else
            return MoonNameEnum::BlueMoon;
    }

    public static function moonStrength(\DateTimeInterface $dt): int
    {
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        $day = (int)$dt->format('j');
        $YY = $year - (int)floor((12 - $month) / 10);
        $MM = ($month + 9) % 12;
        $K1 = (int)floor(365.25 * ($YY + 4712));
        $K2 = (int)floor(30.6 * $MM + .5);
        $K3 = (int)floor(floor(($YY / 100) + 49) * .75) - 38;

        $JD = $K1 + $K2 + $day + 59;
        if($JD > 2299160)
            $JD -= $K3;
        $IP = ($JD - 2451550.1) / 29.530588853;
        // normalize IP
        $IP = $IP - floor($IP);
        if($IP < 0)
            $IP++;
        $AG = $IP * 29.53;

        if($AG < 1.84566)
            return -1; // new
        else if($AG < 5.53699)
            return 0;
        else if($AG < 9.22831)
            return 1;
        else if($AG < 12.91963)
            return 2;
        else if($AG < 16.61096)
            return 3; // full
        else if($AG < 20.30228)
            return 2;
        else if($AG < 23.99361)
            return 1;
        else if($AG < 27.68493)
            return 0;
        else
            return -1; // new
    }
}
