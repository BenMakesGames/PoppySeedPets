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

use App\Entity\DailyStats;
use App\Enum\UnlockableFeatureEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateDailyStatsCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:calculate-daily-stats')
            ->setDescription('Calculates daily stats!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTimeImmutable();

        $oneDay = $today->modify('-1 day')->format('Y-m-d');
        $threeDay = $today->modify('-3 days')->format('Y-m-d');
        $week = $today->modify('-7 days')->format('Y-m-d');
        $month = $today->modify('-28 days')->format('Y-m-d');

        $oneDayAverages = $this->getAverages($oneDay);
        $threeDayAverages = $this->getAverages($threeDay);
        $weekAverages = $this->getAverages($week);
        $monthAverages = $this->getAverages($month);
        $lifeTimeAverages = $this->getLifeTime();

        $oneDayNewPlayers = $this->getNewPlayerCount($oneDay);
        $threeDayNewPlayers = $this->getNewPlayerCount($threeDay);
        $weekNewPlayers = $this->getNewPlayerCount($week);
        $monthNewPlayers = $this->getNewPlayerCount($month);

        $dailyStats = new DailyStats()
            ->setDate($today->modify('-1 day'))

            ->setNumberOfPlayers1Day($oneDayAverages['total_users'])
            ->setNumberOfPlayers3Day($threeDayAverages['total_users'])
            ->setNumberOfPlayers7Day($weekAverages['total_users'])
            ->setNumberOfPlayers28Day($monthAverages['total_users'])
            ->setNumberOfPlayersLifetime($lifeTimeAverages['total_users'])

            ->setTotalMoneys1Day((int)$oneDayAverages['total_moneys'])
            ->setTotalMoneys3Day((int)$threeDayAverages['total_moneys'])
            ->setTotalMoneys7Day((int)$weekAverages['total_moneys'])
            ->setTotalMoneys28Day((int)$monthAverages['total_moneys'])
            ->setTotalMoneysLifetime((int)$lifeTimeAverages['total_moneys'])

            ->setNewPlayers1Day($oneDayNewPlayers['new_users'])
            ->setNewPlayers3Day($threeDayNewPlayers['new_users'])
            ->setNewPlayers7Day($weekNewPlayers['new_users'])
            ->setNewPlayers28Day($monthNewPlayers['new_users'])

            ->setUnlockedTrader1Day($this->getUnlocked(UnlockableFeatureEnum::Trader, $oneDay))
            ->setUnlockedTrader3Day($this->getUnlocked(UnlockableFeatureEnum::Trader, $threeDay))
            ->setUnlockedTrader7Day($this->getUnlocked(UnlockableFeatureEnum::Trader, $week))
            ->setUnlockedTrader28Day($this->getUnlocked(UnlockableFeatureEnum::Trader, $month))
            ->setUnlockedTraderLifetime($this->getLifetimeUnlocked(UnlockableFeatureEnum::Trader))

            ->setUnlockedFireplace1Day($this->getUnlocked(UnlockableFeatureEnum::Fireplace, $oneDay))
            ->setUnlockedFireplace3Day($this->getUnlocked(UnlockableFeatureEnum::Fireplace, $threeDay))
            ->setUnlockedFireplace7Day($this->getUnlocked(UnlockableFeatureEnum::Fireplace, $week))
            ->setUnlockedFireplace28Day($this->getUnlocked(UnlockableFeatureEnum::Fireplace, $month))
            ->setUnlockedFireplaceLifetime($this->getLifetimeUnlocked(UnlockableFeatureEnum::Fireplace))

            ->setUnlockedGreenhouse1Day($this->getUnlocked(UnlockableFeatureEnum::Greenhouse, $oneDay))
            ->setUnlockedGreenhouse3Day($this->getUnlocked(UnlockableFeatureEnum::Greenhouse, $threeDay))
            ->setUnlockedGreenhouse7Day($this->getUnlocked(UnlockableFeatureEnum::Greenhouse, $week))
            ->setUnlockedGreenhouse28Day($this->getUnlocked(UnlockableFeatureEnum::Greenhouse, $month))
            ->setUnlockedGreenhouseLifetime($this->getLifetimeUnlocked(UnlockableFeatureEnum::Greenhouse))

            ->setUnlockedBeehive1Day($this->getUnlocked(UnlockableFeatureEnum::Beehive, $oneDay))
            ->setUnlockedBeehive3Day($this->getUnlocked(UnlockableFeatureEnum::Beehive, $threeDay))
            ->setUnlockedBeehive7Day($this->getUnlocked(UnlockableFeatureEnum::Beehive, $week))
            ->setUnlockedBeehive28Day($this->getUnlocked(UnlockableFeatureEnum::Beehive, $month))
            ->setUnlockedBeehiveLifetime($this->getLifetimeUnlocked(UnlockableFeatureEnum::Beehive))

            ->setUnlockedPortal1Day($this->getUnlocked(UnlockableFeatureEnum::HollowEarth, $oneDay))
            ->setUnlockedPortal3Day($this->getUnlocked(UnlockableFeatureEnum::HollowEarth, $threeDay))
            ->setUnlockedPortal7Day($this->getUnlocked(UnlockableFeatureEnum::HollowEarth, $week))
            ->setUnlockedPortal28Day($this->getUnlocked(UnlockableFeatureEnum::HollowEarth, $month))
            ->setUnlockedPortalLifetime($this->getLifetimeUnlocked(UnlockableFeatureEnum::HollowEarth))

            ->setPetsBorn1Day($this->getBornPets($oneDay))
            ->setPetsBorn3Day($this->getBornPets($threeDay))
            ->setPetsBorn7Day($this->getBornPets($week))
            ->setPetsBorn28Day($this->getBornPets($month))
            ->setPetsBornLifetime($this->getBornPetsLifetime())

            ->setTotalPets1Day($this->getTotalPets($oneDay))
            ->setTotalPets3Day($this->getTotalPets($threeDay))
            ->setTotalPets7Day($this->getTotalPets($week))
            ->setTotalPets28Day($this->getTotalPets($month))
            ->setTotalPetsLifetime($this->getTotalPetsLifetime())
        ;

        $this->em->persist($dailyStats);
        $this->em->flush();

        return self::SUCCESS;
    }

    /**
     * @return array{new_users: int}
     */
    public function getNewPlayerCount(string $firstDate): array
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT COUNT(user.id) AS new_users
                FROM user
                WHERE user.registered_on>="' . $firstDate . '"
            ')
            ->fetchAssociative()
        ;
    }

    /**
     * @return array{total_users: int, total_moneys: int}
     */
    public function getAverages(string $firstDate): array
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(user.id) AS total_users,
                    SUM(user.moneys) AS total_moneys
                FROM user
                WHERE user.last_activity>="' . $firstDate . '"
            ')
            ->fetchAssociative()
        ;
    }

    public function getTotalPets(string $firstDate): int
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(pet.id) AS total_pets
                FROM pet
                WHERE pet.birth_date<="' . $firstDate . '"
            ')
            ->fetchAssociative()['total_pets']
        ;
    }

    public function getTotalPetsLifetime(): int
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(pet.id) AS total_pets
                FROM pet"
            ')
            ->fetchAssociative()['total_pets']
        ;
    }

    public function getBornPets(string $firstDate): int
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(pet.id) AS total_births
                FROM pet
                WHERE pet.birth_date>="' . $firstDate . '
                AND pet.mom_id IS NOT NULL"
            ')
            ->fetchAssociative()['total_births']
        ;
    }

    public function getBornPetsLifetime(): int
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(pet.id) AS total_births
                FROM pet
                WHERE pet.mom_id IS NOT NULL"
            ')
            ->fetchAssociative()['total_births']
        ;
    }

    public function getUnlocked(UnlockableFeatureEnum $featureFieldSuffix, string $firstDate): int
    {
        return (int)$this->em->getConnection()
            ->executeQuery('
                SELECT COUNT(user_unlocked_feature.id) AS qty
                FROM user_unlocked_feature
                LEFT JOIN user ON user.id=user_unlocked_feature.user_id
                WHERE
                    user_unlocked_feature.feature="' . $featureFieldSuffix->value . '"
                    AND user.last_activity>="' . $firstDate . '"
            ')
            ->fetchAssociative()['qty']
        ;
    }

    public function getLifetimeUnlocked(UnlockableFeatureEnum $featureFieldSuffix): int
    {
        return (int)$this->em->getConnection()
            ->executeQuery('
                SELECT COUNT(user_unlocked_feature.id) AS qty
                FROM user_unlocked_feature
                WHERE
                    user_unlocked_feature.feature="' . $featureFieldSuffix->value . '"
            ')
            ->fetchAssociative()['qty']
        ;
    }

    /**
     * @return array{total_users: int, total_moneys: int}
     */
    public function getLifeTime(): array
    {
        return $this->em->getConnection()
            ->executeQuery('
                SELECT
                    COUNT(user.id) AS total_users,
                    SUM(user.moneys) AS total_moneys
                FROM user
            ')
            ->fetchAssociative()
        ;
    }
}
