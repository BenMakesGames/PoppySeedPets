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

use App\Entity\Pet;
use App\Entity\PetSpecies;
use App\Entity\User;
use App\Enum\PetSpeciesName;
use App\Enum\UserStat;
use App\Enum\MoonNameEnum;
use App\Functions\CalendarFunctions;
use App\Functions\ColorFunctions;
use App\Functions\DateFunctions;
use App\Functions\PetColorFunctions;
use App\Functions\PetSpeciesRepository;
use App\Functions\RandomFunctions;
use App\Model\ChineseCalendarInfo;
use App\Model\PetShelterPet;
use Doctrine\ORM\EntityManagerInterface;

class AdoptionService
{
    private ChineseCalendarInfo $chineseCalendarInfo;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserStatsService $userStatsRepository,
        private readonly Clock $clock
    )
    {
        $this->chineseCalendarInfo = CalendarFunctions::getChineseCalendarInfo($this->clock->now);
    }

    public function getPetsAdopted(User $user): int
    {
        return $this->userStatsRepository->getStatValue($user, UserStat::PetsAdopted);
    }

    public function getAdoptionFee(User $user): int
    {
        $fee = 100;

        $petsAdopted = $this->getPetsAdopted($user);

        if($petsAdopted == 0)
            $fee = (int)ceil($fee / 2);

        if(CalendarFunctions::isBlackFriday($this->clock->now) || CalendarFunctions::isCyberMonday($this->clock->now))
            $fee = (int)ceil($fee / 10) * 5;

        return $fee;
    }

    public static function getNumberOfPets(\DateTimeImmutable $dt): int
    {
        $year = (int)$dt->format('Y');
        $monthAndDay = (int)$dt->format('nd');

        $bonus = (RandomFunctions::squirrel3Noise($year, $monthAndDay) & 31) === 1 ? 10 : 0;

        $extra = RandomFunctions::squirrel3Noise($year - 100, $monthAndDay) % 5;

        return 4 + $extra + $bonus;
    }

    /**
     * @return array{0: PetShelterPet[], 1: string}
     */
    public function getDailyPets(User $user): array
    {
        $nowString = $this->clock->now->format('Y-m-d');

        $rng = new Xoshiro($user->getDailySeed());

        $numPets = self::getNumberOfPets($this->clock->now);
        $numSeasonalPets = $this->numberOfSeasonalPets($numPets, $rng);
        $petsAdopted = $this->getPetsAdopted($user);

        if($petsAdopted == 0)
            $dialog = "Hello! Here to adopt a new friend? Your first pet is 50% off!\n\nIf ";
        else
        {
            $dialog = $numPets > 10
                ? "Oh, goodness! A bunch of pets appeared from the Hollow Earth today! It just seems to happen now and again; we're still not sure why...\n\n Anyway, if "
                : "Hello! Here to adopt a new friend?\n\nIf "
            ;
        }

        $petCount = (int)$this->em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.birthDate<:today')
            ->setParameter('today', $nowString)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $isBlueMoon = DateFunctions::isSpecificMoon($this->clock->now, MoonNameEnum::BlueMoon);
        $isPinkMoon = DateFunctions::isSpecificMoon($this->clock->now, MoonNameEnum::PinkMoon);

        /** @var PetShelterPet[] $pets */
        $pets = [];

        $allSpecies = $this->em->getRepository(PetSpecies::class)->findBy([ 'availableFromPetShelter' => true ]);

        $rarePetIndices = self::getRarePetIndices($this->clock->now);

        for($i = 0; $i < $numPets; $i++)
        {
            if(CalendarFunctions::isTalkLikeAPirateDay($this->clock->now))
                $name = PetShelterPet::generatePirateName($this->clock->now, $i);
            else if(CalendarFunctions::isPiDay($this->clock->now))
                $name = $rng->rngNextFromArray([ 'Pi',  'Pi', 'Pie', 'Pie', 'Pie', 'Pie', 'Pie', 'Cake' ]);
            else
                $name = $rng->rngNextFromArray(PetShelterPet::PetNames);

            if($i < $numSeasonalPets)
            {
                [$colorA, $colorB] = $rng->rngNextSubsetFromArray($this->getSeasonalColors(), 2);

                $seasonalNames = $this->getSeasonalNames();

                $name = $rng->rngNextFromArray($seasonalNames);
            }
            else if($isBlueMoon)
            {
                $blueA = $rng->rngNextInt(127, 255);
                $otherA = $rng->rngNextInt(0, $blueA - 16);

                $blueB = $rng->rngNextInt(127, 255);
                $otherB = $rng->rngNextInt(0, $blueB - 16);

                $colorA = ColorFunctions::RGB2Hex($otherA, $otherA, $blueA);
                $colorB = ColorFunctions::RGB2Hex($otherB, $otherB, $blueB);

                $colorA = $rng->rngNextTweakedColor($colorA);
                $colorB = $rng->rngNextTweakedColor($colorB);
            }
            else if($isPinkMoon)
            {
                $redA = $rng->rngNextInt(224, 255);
                $otherA = $rng->rngNextInt(128, $redA - 32);

                $redB = $rng->rngNextInt(224, 255);
                $otherB = $rng->rngNextInt(128, $redB - 32);

                $colorA = ColorFunctions::RGB2Hex($redA, $otherA, $otherA);
                $colorB = ColorFunctions::RGB2Hex($redB, $otherB, $otherB);

                $colorA = $rng->rngNextTweakedColor($colorA);
                $colorB = $rng->rngNextTweakedColor($colorB);
            }
            else if($petCount === 0 || $i === $numPets - 1)
            {
                $petColors = PetColorFunctions::generateRandomPetColors($rng);
                $colorA = $petColors->colorA;
                $colorB = $petColors->colorB;
            }
            else
            {
                $basePet = $this->em->getRepository(Pet::class)->createQueryBuilder('p')
                    ->andWhere('p.birthDate<:today')
                    ->setParameter('today', $nowString)
                    ->setMaxResults(1)
                    ->setFirstResult($rng->rngNextInt(0, $petCount - 1))
                    ->getQuery()
                    ->getSingleResult();

                $colorA = $rng->rngNextTweakedColor($basePet->getColorA());
                $colorB = $rng->rngNextTweakedColor($basePet->getColorB());
            }

            $pet = new PetShelterPet();

            if(CalendarFunctions::isHalloweenDay($this->clock->now))
            {
                $pet->species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::FogElemental);
                $pet->label = 'spooky!';
                $dialog = "Uh... I don't know if this is a Halloween thing, or what, but... if you want a Fog Elemental, I guess it's your pick of the litter...\n\nAlthough I guess if ";
            }
            else if(CalendarFunctions::isNoombatDay($this->clock->now))
            {
                $pet->species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::Noombat);
                $pet->label = 'noom!';
                $dialog = "Agh! This happens every year at about this time! Noombats everywhere! I don't know if it's Noombat breeding season, or what, but please adopt one of these things! If you insist, though, and ";
            }
            else if(CalendarFunctions::isJuramaiaDay($this->clock->now))
            {
                $pet->species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::Juramaia);
                $pet->label = '!?!';
                $dialog = "For some reason this happens every August 25th! Juramaia everywhere! (Or would it be Juramaias? Juramaiae??? 🤷‍♂️)\n\nOf course - as always - if ";
            }
            else if(CalendarFunctions::isLeapDay($this->clock->now))
            {
                $pet->species = PetSpeciesRepository::findOneByName(
                    $this->em,
                    [
                        PetSpeciesName::BearFrog,
                        PetSpeciesName::FalseFrog, PetSpeciesName::FalseFrog,
                        PetSpeciesName::Gelp,
                    ][$i % 4]
                );
                $pet->label = '*ribbit*';
                $dialog = "Uh... your guess is as good as mine...\n\nAnd this rain feels unnatural, too, don't you think?\n\nWell... anyway, if ";
            }
            else if(in_array($i, $rarePetIndices))
            {
                $pet->species = $rng->rngNextFromArray(
                    $this->em->getRepository(PetSpecies::class)->findBy([
                        'availableFromPetShelter' => false,
                        'availableFromBreeding' => true
                    ])
                );

                $pet->label = [
                    'gasp!',
                    'oh my!',
                    'whoa!',
                    'ooooh!',
                    'omg!',
                ][RandomFunctions::squirrel3Noise($i, (int)$this->clock->now->format('YNmd')) % 5];
            }
            else
                $pet->species = $rng->rngNextFromArray($allSpecies);

            $pet->name = $name;
            $pet->colorA = $colorA;
            $pet->colorB = $colorB;
            $pet->id = $rng->rngNextInt(100000, 999999) * 10 + $i;
            $pet->scale = $rng->rngNextInt(80, 120);

            $pets[] = $pet;
        }

        return [ $pets, $dialog ];
    }

    public static function getRarePetDayForMonth(\DateTimeImmutable $dt): int
    {
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        $daysThisMonth = (int)$dt->format('t');

        return RandomFunctions::squirrel3Noise($year, $month) % $daysThisMonth + 1;
    }

    public static function isRarePetDay(\DateTimeImmutable $dt): bool
    {
        return count(self::getRarePetIndices($dt)) > 0;
    }

    /**
     * @return int[]
     */
    public static function getRarePetIndices(\DateTimeImmutable $dt): array
    {
        $rarePetDayOfMonth = self::getRarePetDayForMonth($dt);

        $numPets = self::getNumberOfPets($dt);

        $rarePetIndices = [];

        if(RandomFunctions::squirrel3Noise(591, (int)$dt->format('NmYd')) % 100 === 1)
            $rarePetIndices[] = RandomFunctions::squirrel3Noise(1002, (int)$dt->format('1YmNd')) % $numPets;

        if($rarePetDayOfMonth == $dt->format('j'))
            $rarePetIndices[] = RandomFunctions::squirrel3Noise(314159, (int)$dt->format('YdmN')) % $numPets;

        return array_values(array_unique($rarePetIndices));
    }

    public function numberOfSeasonalPets(int $totalPets, IRandom $rng): int
    {
        $monthDay = $this->clock->getMonthAndDay();

        if(CalendarFunctions::isHalloween($this->clock->now))
            return $rng->rngNextInt(1, 2);

        // PSP Thanksgiving overlaps Black Friday, but for pet adoption purposes, we want Black Friday to win out:
        if(CalendarFunctions::isBlackFriday($this->clock->now) || CalendarFunctions::isCyberMonday($this->clock->now))
            return (int)ceil($totalPets / 2);

        if(CalendarFunctions::isThanksgiving($this->clock->now))
            return $rng->rngNextInt(1, 2);

        if(CalendarFunctions::isEaster($this->clock->now))
            return $rng->rngNextInt(1, 2);

        if(CalendarFunctions::isValentinesOrAdjacent($this->clock->now) || CalendarFunctions::isWhiteDay($this->clock->now))
            return 2;

        // winter solstice, more or less
        if($monthDay === 1221 || $monthDay === 1222)
            return (int)ceil($totalPets / 2);

        // Christmas colors
        if($monthDay >= 1223 && $monthDay <= 1225)
            return $rng->rngNextInt(1, 2);

        if(CalendarFunctions::isHanukkah($this->clock->now))
            return $rng->rngNextInt(1, 2);

        if($this->chineseCalendarInfo->month === 1 && $this->chineseCalendarInfo->day <= 6)
            return 2;

        if(CalendarFunctions::isSaintPatricksDay($this->clock->now))
            return $rng->rngNextInt(1, 3);

        return 0;
    }

    /**
     * @return string[]
     */
    public function getSeasonalNames(): array
    {
        $monthDay = $this->clock->getMonthAndDay();

        if(CalendarFunctions::isHalloween($this->clock->now))
            return PetShelterPet::PetHalloweenNames;

        // PSP Thanksgiving overlaps Black Friday, but for pet adoption purposes, we want Black Friday to win out:
        if(CalendarFunctions::isBlackFriday($this->clock->now))
            return PetShelterPet::PetBlackFridayNames;

        if(CalendarFunctions::isCyberMonday($this->clock->now))
            return PetShelterPet::PetCyberMondayNames;

        if(CalendarFunctions::isThanksgiving($this->clock->now))
            return PetShelterPet::PetThanksgivingNames;

        if(CalendarFunctions::isEaster($this->clock->now))
            return PetShelterPet::PetEasterNames;

        if(CalendarFunctions::isValentinesOrAdjacent($this->clock->now))
            return PetShelterPet::PetValentinesNames;

        if(CalendarFunctions::isWhiteDay($this->clock->now))
            return PetShelterPet::PetWhiteDayNames;

        // winter solstice, more or less
        if(CalendarFunctions::isWinterSolstice($this->clock->now))
            return PetShelterPet::PetWinterSolsticeNames;

        // Christmas colors (would normally do a 3-day range, but dec 23 isWinterSolstice())
        if($monthDay >= 1224 && $monthDay <= 1225)
            return PetShelterPet::PetChristmasNames;

        if(CalendarFunctions::isHanukkah($this->clock->now))
            return PetShelterPet::PetHanukkahNames;

        if($this->chineseCalendarInfo->month === 1 && $this->chineseCalendarInfo->day <= 6)
            return PetShelterPet::PetChineseZodiacNames[$this->chineseCalendarInfo->animal];

        if(CalendarFunctions::isSaintPatricksDay($this->clock->now))
            return PetShelterPet::PetNames;

        throw new \Exception('Today is not a day for seasonal colors.');
    }

    public function getSeasonalColors(): array
    {
        $monthDay = $this->clock->getMonthAndDay();

        if(CalendarFunctions::isHalloween($this->clock->now))
            return [ '333333', 'FF9933' ];

        // PSP Thanksgiving overlaps Black Friday, but for pet adoption purposes, we want Black Friday to win out:
        if(CalendarFunctions::isBlackFriday($this->clock->now))
            return [ '000000', '333333', '330000', '003300', '000033' ];

        if(CalendarFunctions::isCyberMonday($this->clock->now))
            return [ '000000', '005500', '00aa00', '00ff00' ];

        if(CalendarFunctions::isThanksgiving($this->clock->now))
            return [ 'CC6600', 'FFCC00', '009900', 'FF3300' ];

        if(CalendarFunctions::isEaster($this->clock->now))
            return [ 'FFCCFF', '99CCFF', 'FFFF99', 'FF9999' ];

        if(CalendarFunctions::isValentinesOrAdjacent($this->clock->now))
            return [ 'F17B7B', 'F8F8F8', 'FF0000', 'EF85FF' ];

        if(CalendarFunctions::isWhiteDay($this->clock->now))
            return [ 'FFFFFF', 'EEEEEE' ];

        // winter solstice, more or less
        if($monthDay === 1221 || $monthDay === 1222)
            return [ 'F8F8F8', '94C6F8' ];

        // Christmas colors
        if($monthDay >= 1223 && $monthDay <= 1225)
            return [ 'F8F8F8', 'CC3300', '009900' ];

        if(CalendarFunctions::isHanukkah($this->clock->now))
            return [ 'F8F8F8', '0066FF' ];

        if($this->chineseCalendarInfo->month === 1 && $this->chineseCalendarInfo->day <= 6)
            return [ 'CC232A', 'F5AC27', 'FFD84B', 'F2888B', 'A3262A', 'CC9902' ];

        if(CalendarFunctions::isSaintPatricksDay($this->clock->now))
            return [ '009900', '66CC66', '33AA00', '00AA33' ];

        throw new \Exception('Today is not a day for seasonal colors.');
    }
}
