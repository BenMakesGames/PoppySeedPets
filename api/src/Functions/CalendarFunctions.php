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

use App\Enum\HolidayEnum;
use App\Model\ChineseCalendarInfo;

final class CalendarFunctions
{
    public static function isJelephantDay(\DateTimeInterface $dt): bool
    {
        return $dt->format('nd') === '812';
    }

    public static function isJuramaiaDay(\DateTimeInterface $dt): bool
    {
        return $dt->format('nd') === '825';
    }

    public static function isNoombatDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        // if it's not November, just get outta' here
        if($monthAndDay < 1100 || $monthAndDay >= 1200)
            return false;

        $monthAndYear = $dt->format('F Y');
        $firstSaturdayOfNovember = (int)(new \DateTimeImmutable('first Saturday of ' . $monthAndYear))->format('nd');

        return $monthAndDay === $firstSaturdayOfNovember;
    }

    public static function isPSPBirthday(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 621 && $monthAndDay <= 623;
    }

    public static function isSummerSolstice(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 619 && $monthAndDay <= 622;
    }

    public static function isWinterSolstice(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 1220 && $monthAndDay <= 1223;
    }

    public static function isEight(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 1201 && $monthAndDay <= 1208;
    }

    public static function isThanksgivingMonsters(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        // if it's not November, just get outta' here
        if($monthAndDay < 1100 || $monthAndDay >= 1200)
            return false;

        $monthAndYear = $dt->format('F Y');
        $fourthThursdayOfNovember = new \DateTimeImmutable('fourth Thursday of ' . $monthAndYear);

        return
            $monthAndDay >= (int)$fourthThursdayOfNovember->format('nd') - 14 &&
            $monthAndDay <= (int)$fourthThursdayOfNovember->format('nd')
        ;
    }

    public static function isThanksgiving(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        // if it's not November, just get outta' here
        if($monthAndDay < 1122 || $monthAndDay >= 1200)
            return false;

        $year = $dt->format('Y');
        $fourthThursdayOfNovember = new \DateTimeImmutable('fourth Thursday of November ' . $year);

        // within 1 day of thanksgiving
        return abs((int)$fourthThursdayOfNovember->format('nd') - $monthAndDay) <= 1;
    }

    public static function isBlackFriday(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        // if it's not November, just get outta' here
        if($monthAndDay < 1122 || $monthAndDay >= 1200)
            return false;

        $year = $dt->format('Y');
        $blackFriday = new \DateTimeImmutable('fourth Thursday of November ' . $year)->modify('+1 day');

        return (int)$blackFriday->format('nd') === $monthAndDay;
    }

    public static function isCyberMonday(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        if($monthAndDay < 1125 || $monthAndDay >= 1203)
            return false;

        $year = $dt->format('Y');
        $cyberMonday = new \DateTimeImmutable('fourth Thursday of November ' . $year)->modify('+4 days');

        return (int)$cyberMonday->format('nd') === $monthAndDay;
    }

    public static function isHalloweenCrafting(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 1000 && $monthAndDay < 1100;
    }

    public static function isSaintMartinsDayCrafting(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 1101 && $monthAndDay <= 1111;
    }

    public static function isSaintPatricksDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 315 && $monthAndDay <= 317;
    }

    public static function isPiDayCrafting(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        return
            ($monthAndDay >= 313 && $monthAndDay <= 315) ||
            ($monthAndDay >= 721 && $monthAndDay <= 723)
        ;
    }

    public static function isHalloween(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');

        return $monthAndDay >= 1029 && $monthAndDay <= 1031;
    }

    public static function isHalloweenDay(\DateTimeInterface $dt): bool
    {
        return $dt->format('nd') === '1031';
    }

    public static function isPiDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '314' || $monthAndDay === '722';
    }

    public static function isPsyPetsBirthday(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '321';
    }

    public static function isAprilFools(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '401';
    }

    public static function isBastilleDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 713 && $monthAndDay <= 715;
    }

    public static function isCincoDeMayo(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 504 && $monthAndDay <= 506;
    }

    public static function isWhiteDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '314';
    }

    public static function isMayThe4th(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '504';
    }

    public static function isAwaOdori(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 812 && $monthAndDay <= 815;
    }

    public static function isApricotFestival(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay === 531 || $monthAndDay === 601 || $monthAndDay === 602;
    }

    public static function isAHornOfPlentyDay(\DateTimeInterface $dt): bool
    {
        return
            self::isThanksgivingMonsters($dt) ||
            self::isChineseNewYear($dt) ||
            self::isEaster($dt) ||
            self::isHanukkah($dt) ||
            self::isNewYearsHoliday($dt) ||
            self::isSaintPatricksDay($dt) ||
            self::isCincoDeMayo($dt) ||
            self::isJuly4th($dt) ||
            self::isBastilleDay($dt) ||
            self::isSummerSolstice($dt) ||
            self::isWinterSolstice($dt);
    }

    public static function isTalkLikeAPirateDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === '919';
    }

    public static function isJuly4th(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 703 && $monthAndDay <= 705;
    }

    public static function isSnakeDay(\DateTimeImmutable $dt): bool
    {
        return $dt->format('nd') === '716';
    }

    public static function isEarthDay(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 420 && $monthAndDay <= 422;
    }

    public static function getChineseCalendarInfo(\DateTimeInterface $dt): ChineseCalendarInfo
    {
        $info = new \Overtrue\ChineseCalendar\Calendar()
            ->solar((int)$dt->format('Y'), (int)$dt->format('m'), (int)$dt->format('d'))
        ;

        $result = new ChineseCalendarInfo();

        $result->year = (int)$info['lunar_year'];
        $result->month = (int)$info['lunar_month'];
        $result->day = (int)$info['lunar_day'];
        $result->animal = $info['animal'];
        $result->isLeapYear = $info['is_leap'];

        return $result;
    }

    public static function isHanukkah(\DateTimeInterface $dt): bool
    {
        $jdCurrent = gregoriantojd((int)$dt->format('m'), (int)$dt->format('d'), (int)$dt->format('Y'));
        [$jewishYear, $jewishMonth, $jewishDay] = JewishCalendarFunctions::getJewishDate($dt);

        $hanukkahStart = jewishtojd(JewishCalendarFunctions::Kislev, 25, $jewishYear);
        $hanukkahNo = $jdCurrent - $hanukkahStart + 1;

        return $hanukkahNo >= 1 && $hanukkahNo <= 8;
    }

    public static function isValentinesOrAdjacent(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 213 && $monthAndDay <= 215;
    }

    public static function isEaster(\DateTimeInterface $dt): bool
    {
        // I don't love this way of doing it, but it works for easter (whose celebrations never span two years)
        // "z" is "the day of the year", so we can test the date that way, ignoring time
        $easter = (int)\DateTimeImmutable::createFromFormat('U', (string)easter_date((int)$dt->format('Y')))->format('z');
        $now = (int)$dt->format('z');

        if($now > $easter)
            return false;

        return $easter - $now < 3;
    }

    public static function isHoli(\DateTimeInterface $dt): bool
    {
        $monthAndDay = $dt->format('nd');
        return $monthAndDay === self::HoliMonthDays[(int)$dt->format('Y')];
    }

    public static function isNewYearsHoliday(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay === 1231 || $monthAndDay <= 102;
    }

    public static function isStockingStuffingSeason(\DateTimeInterface $dt): bool
    {
        $monthAndDay = (int)$dt->format('nd');
        return $monthAndDay >= 1200 && $monthAndDay <= 1231;
    }

    public static function isLeonidPeakOrAdjacent(\DateTimeInterface $dt): bool
    {
        $year = (int)$dt->format('Y');
        $monthAndDay = (int)$dt->format('nd');

        $leonidPeakDay = array_key_exists($year, self::LeonidPeakDays)
            ? self::LeonidPeakDays[$year]
            : self::LeonidPeakDayDefault
        ;

        return abs($monthAndDay - $leonidPeakDay) <= 1;
    }

    public static function isLeapDay(\DateTimeInterface $dt): bool
    {
        return $dt->format('md') === '0229';
    }

    public static function isCreepyMaskDay(\DateTimeInterface $dt): bool
    {
        return $dt->format('D j') === 'Fri 13';
    }

    public static function isChineseNewYear(\DateTimeInterface $dt): bool
    {
        $chineseCalendarInfo = self::getChineseCalendarInfo($dt);

        return $chineseCalendarInfo->month === 1 && $chineseCalendarInfo->day <= 6;
    }

    /**
     * @return HolidayEnum[]
     */
    public static function getEventData(\DateTimeImmutable $dt): array
    {
        /** @var HolidayEnum[] $events */
        $events = [];

        $fullMoonName = DateFunctions::getFullMoonName($dt);

        if($fullMoonName)
            $events[] = $fullMoonName->value . ' Moon';

        if(self::isStockingStuffingSeason($dt))
            $events[] = HolidayEnum::StockingStuffingSeason;

        if(self::isHalloween($dt))
            $events[] = HolidayEnum::Halloween;

        if(self::isEaster($dt))
            $events[] = HolidayEnum::Easter;

        if(self::isSaintPatricksDay($dt))
            $events[] = HolidayEnum::SaintPatricks;

        if(self::isValentinesOrAdjacent($dt))
            $events[] = HolidayEnum::Valentines;

        if(self::isCyberMonday($dt))
            $events[] = HolidayEnum::CyberMonday;

        if(self::isBlackFriday($dt))
            $events[] = HolidayEnum::BlackFriday;

        if(self::isPiDay($dt))
            $events[] = HolidayEnum::PiDay;

        if(self::isPSPBirthday($dt))
            $events[] = HolidayEnum::PSPBirthday;

        if(self::isSummerSolstice($dt))
            $events[] = HolidayEnum::SummerSolstice;

        if(self::isWinterSolstice($dt))
            $events[] = HolidayEnum::WinterSolstice;

        if(self::isEight($dt))
            $events[] = HolidayEnum::Eight;

        if(self::isPsyPetsBirthday($dt))
            $events[] = HolidayEnum::PsyPetsBirthday;

        if(self::isAprilFools($dt))
            $events[] = HolidayEnum::AprilFools;

        if(self::isHanukkah($dt))
            $events[] = HolidayEnum::Hanukkah;

        if(self::isWhiteDay($dt))
            $events[] = HolidayEnum::WhiteDay;

        if(self::isTalkLikeAPirateDay($dt))
            $events[] = HolidayEnum::TalkLikeAPirateDay;

        if(self::isThanksgiving($dt))
            $events[] = HolidayEnum::Thanksgiving;

        if(self::isNewYearsHoliday($dt))
            $events[] = HolidayEnum::NewYearsDay;

        if(self::isJuly4th($dt))
            $events[] = HolidayEnum::FourthOfJuly;

        if(self::isSnakeDay($dt))
            $events[] = HolidayEnum::SnakeDay;

        if(self::isBastilleDay($dt))
            $events[] = HolidayEnum::BastilleDay;

        if(self::isAwaOdori($dt))
            $events[] = HolidayEnum::AwaOdori;

        if(self::isEarthDay($dt))
            $events[] = HolidayEnum::EarthDay;

        if(self::isCincoDeMayo($dt))
            $events[] = HolidayEnum::CincoDeMayo;

        if(self::isNoombatDay($dt))
            $events[] = HolidayEnum::NoombatDay;

        if(self::isJelephantDay($dt))
            $events[] = HolidayEnum::JelephantDay;

        if(self::isJuramaiaDay($dt))
            $events[] = HolidayEnum::JuramaiaDay;

        if(self::isChineseNewYear($dt))
            $events[] = HolidayEnum::LunarNewYear;

        if(self::isHoli($dt))
            $events[] = HolidayEnum::Holi;

        if(self::isLeonidPeakOrAdjacent($dt))
            $events[] = HolidayEnum::Leonids;

        if(self::isLeapDay($dt))
            $events[] = HolidayEnum::LeapDay;

        if(self::isCreepyMaskDay($dt))
            $events[] = HolidayEnum::CreepyMaskDay;

        if(self::isApricotFestival($dt))
            $events[] = HolidayEnum::ApricotFestival;

        return $events;
    }

    public const array HoliMonthDays = [
        2021 => '329',
        2022 => '318',
        2023 => '308',
        2024 => '325',
        2025 => '314',
        2026 => '304',
        2027 => '322',
        2028 => '311',
        2029 => '301',
        2030 => '320',
        2031 => '309',
        2032 => '327',
        2033 => '316',
        2034 => '305',
        2035 => '324',
        2036 => '312',
        2037 => '302',
        2038 => '321',
        2039 => '311',
        2040 => '329',
    ];

    public const int LeonidPeakDayDefault = 1117;

    /**
     * for years which are omitted, {@see LeonidPeakDayDefault} is assumed.
     */
    public const array LeonidPeakDays = [
        2022 => 1119,
    ];

}
