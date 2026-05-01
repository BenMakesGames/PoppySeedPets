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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class DailyStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['globalStats'])]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $numberOfPlayers1Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $numberOfPlayers3Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $numberOfPlayers7Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $numberOfPlayers28Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $numberOfPlayersLifetime = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $totalMoneys1Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $totalMoneys3Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $totalMoneys7Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $totalMoneys28Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $totalMoneysLifetime = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $newPlayers1Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $newPlayers3Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $newPlayers7Day = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['globalStats'])]
    private int $newPlayers28Day = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedTrader1Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedTrader3Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedTrader7Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedTrader28Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedTraderLifetime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedFireplace1Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedFireplace3Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedFireplace7Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedFireplace28Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedFireplaceLifetime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedGreenhouse1Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedGreenhouse3Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedGreenhouse7Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedGreenhouse28Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedGreenhouseLifetime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedBeehive1Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedBeehive3Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedBeehive7Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedBeehive28Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedBeehiveLifetime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedPortal1Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedPortal3Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedPortal7Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedPortal28Day = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['globalStats'])]
    private ?int $unlockedPortalLifetime = null;

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getNumberOfPlayers1Day(): ?int
    {
        return $this->numberOfPlayers1Day;
    }

    public function setNumberOfPlayers1Day(int $numberOfPlayers1Day): self
    {
        $this->numberOfPlayers1Day = $numberOfPlayers1Day;

        return $this;
    }

    public function getNumberOfPlayers3Day(): ?int
    {
        return $this->numberOfPlayers3Day;
    }

    public function setNumberOfPlayers3Day(int $numberOfPlayers3Day): self
    {
        $this->numberOfPlayers3Day = $numberOfPlayers3Day;

        return $this;
    }

    public function getNumberOfPlayers7Day(): ?int
    {
        return $this->numberOfPlayers7Day;
    }

    public function setNumberOfPlayers7Day(int $numberOfPlayers7Day): self
    {
        $this->numberOfPlayers7Day = $numberOfPlayers7Day;

        return $this;
    }

    public function getNumberOfPlayers28Day(): ?int
    {
        return $this->numberOfPlayers28Day;
    }

    public function setNumberOfPlayers28Day(int $numberOfPlayers28Day): self
    {
        $this->numberOfPlayers28Day = $numberOfPlayers28Day;

        return $this;
    }

    public function getNumberOfPlayersLifetime(): ?int
    {
        return $this->numberOfPlayersLifetime;
    }

    public function setNumberOfPlayersLifetime(int $numberOfPlayersLifetime): self
    {
        $this->numberOfPlayersLifetime = $numberOfPlayersLifetime;

        return $this;
    }

    public function getTotalMoneys1Day(): ?int
    {
        return $this->totalMoneys1Day;
    }

    public function setTotalMoneys1Day(int $totalMoneys1Day): self
    {
        $this->totalMoneys1Day = $totalMoneys1Day;

        return $this;
    }

    public function getTotalMoneys3Day(): ?int
    {
        return $this->totalMoneys3Day;
    }

    public function setTotalMoneys3Day(int $totalMoneys3Day): self
    {
        $this->totalMoneys3Day = $totalMoneys3Day;

        return $this;
    }

    public function getTotalMoneys7Day(): ?int
    {
        return $this->totalMoneys7Day;
    }

    public function setTotalMoneys7Day(int $totalMoneys7Day): self
    {
        $this->totalMoneys7Day = $totalMoneys7Day;

        return $this;
    }

    public function getTotalMoneys28Day(): ?int
    {
        return $this->totalMoneys28Day;
    }

    public function setTotalMoneys28Day(int $totalMoneys28Day): self
    {
        $this->totalMoneys28Day = $totalMoneys28Day;

        return $this;
    }

    public function getTotalMoneysLifetime(): ?int
    {
        return $this->totalMoneysLifetime;
    }

    public function setTotalMoneysLifetime(int $totalMoneysLifetime): self
    {
        $this->totalMoneysLifetime = $totalMoneysLifetime;

        return $this;
    }

    public function getNewPlayers1Day(): ?int
    {
        return $this->newPlayers1Day;
    }

    public function setNewPlayers1Day(int $newPlayers1Day): self
    {
        $this->newPlayers1Day = $newPlayers1Day;

        return $this;
    }

    public function getNewPlayers3Day(): ?int
    {
        return $this->newPlayers3Day;
    }

    public function setNewPlayers3Day(int $newPlayers3Day): self
    {
        $this->newPlayers3Day = $newPlayers3Day;

        return $this;
    }

    public function getNewPlayers7Day(): ?int
    {
        return $this->newPlayers7Day;
    }

    public function setNewPlayers7Day(int $newPlayers7Day): self
    {
        $this->newPlayers7Day = $newPlayers7Day;

        return $this;
    }

    public function getNewPlayers28Day(): ?int
    {
        return $this->newPlayers28Day;
    }

    public function setNewPlayers28Day(int $newPlayers28Day): self
    {
        $this->newPlayers28Day = $newPlayers28Day;

        return $this;
    }

    public function getUnlockedFireplace1Day(): ?int
    {
        return $this->unlockedFireplace1Day;
    }

    public function setUnlockedFireplace1Day(?int $unlockedFireplace1Day): self
    {
        $this->unlockedFireplace1Day = $unlockedFireplace1Day;

        return $this;
    }

    public function getUnlockedFireplace3Day(): ?int
    {
        return $this->unlockedFireplace3Day;
    }

    public function setUnlockedFireplace3Day(?int $unlockedFireplace3Day): self
    {
        $this->unlockedFireplace3Day = $unlockedFireplace3Day;

        return $this;
    }

    public function getUnlockedFireplace7Day(): ?int
    {
        return $this->unlockedFireplace7Day;
    }

    public function setUnlockedFireplace7Day(?int $unlockedFireplace7Day): self
    {
        $this->unlockedFireplace7Day = $unlockedFireplace7Day;

        return $this;
    }

    public function getUnlockedFireplace28Day(): ?int
    {
        return $this->unlockedFireplace28Day;
    }

    public function setUnlockedFireplace28Day(?int $unlockedFireplace28Day): self
    {
        $this->unlockedFireplace28Day = $unlockedFireplace28Day;

        return $this;
    }

    public function getUnlockedTrader1Day()
    {
        return $this->unlockedTrader1Day;
    }

    public function setUnlockedTrader1Day($unlockedTrader1Day): self
    {
        $this->unlockedTrader1Day = $unlockedTrader1Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedTrader3Day(): ?int
    {
        return $this->unlockedTrader3Day;
    }

    public function setUnlockedTrader3Day(?int $unlockedTrader3Day): self
    {
        $this->unlockedTrader3Day = $unlockedTrader3Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedTrader7Day(): ?int
    {
        return $this->unlockedTrader7Day;
    }

    public function setUnlockedTrader7Day(?int $unlockedTrader7Day): self
    {
        $this->unlockedTrader7Day = $unlockedTrader7Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedTrader28Day(): ?int
    {
        return $this->unlockedTrader28Day;
    }

    public function setUnlockedTrader28Day(?int $unlockedTrader28Day): self
    {
        $this->unlockedTrader28Day = $unlockedTrader28Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedTraderLifetime(): ?int
    {
        return $this->unlockedTraderLifetime;
    }

    public function setUnlockedTraderLifetime(?int $unlockedTraderLifetime): self
    {
        $this->unlockedTraderLifetime = $unlockedTraderLifetime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedFireplaceLifetime(): ?int
    {
        return $this->unlockedFireplaceLifetime;
    }

    public function setUnlockedFireplaceLifetime(?int $unlockedFireplaceLifetime): self
    {
        $this->unlockedFireplaceLifetime = $unlockedFireplaceLifetime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedGreenhouse1Day(): ?int
    {
        return $this->unlockedGreenhouse1Day;
    }

    public function setUnlockedGreenhouse1Day(?int $unlockedGreenhouse1Day): self
    {
        $this->unlockedGreenhouse1Day = $unlockedGreenhouse1Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedGreenhouse3Day(): ?int
    {
        return $this->unlockedGreenhouse3Day;
    }

    public function setUnlockedGreenhouse3Day(?int $unlockedGreenhouse3Day): self
    {
        $this->unlockedGreenhouse3Day = $unlockedGreenhouse3Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedGreenhouse7Day(): ?int
    {
        return $this->unlockedGreenhouse7Day;
    }

    public function setUnlockedGreenhouse7Day(?int $unlockedGreenhouse7Day): self
    {
        $this->unlockedGreenhouse7Day = $unlockedGreenhouse7Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedGreenhouse28Day(): ?int
    {
        return $this->unlockedGreenhouse28Day;
    }

    public function setUnlockedGreenhouse28Day(?int $unlockedGreenhouse28Day): self
    {
        $this->unlockedGreenhouse28Day = $unlockedGreenhouse28Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedGreenhouseLifetime(): ?int
    {
        return $this->unlockedGreenhouseLifetime;
    }

    public function setUnlockedGreenhouseLifetime(?int $unlockedGreenhouseLifetime): self
    {
        $this->unlockedGreenhouseLifetime = $unlockedGreenhouseLifetime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedBeehive1Day(): ?int
    {
        return $this->unlockedBeehive1Day;
    }

    public function setUnlockedBeehive1Day(?int $unlockedBeehive1Day): self
    {
        $this->unlockedBeehive1Day = $unlockedBeehive1Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedBeehive3Day(): ?int
    {
        return $this->unlockedBeehive3Day;
    }

    public function setUnlockedBeehive3Day(?int $unlockedBeehive3Day): self
    {
        $this->unlockedBeehive3Day = $unlockedBeehive3Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedBeehive7Day(): ?int
    {
        return $this->unlockedBeehive7Day;
    }

    public function setUnlockedBeehive7Day(?int $unlockedBeehive7Day): self
    {
        $this->unlockedBeehive7Day = $unlockedBeehive7Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedBeehive28Day(): ?int
    {
        return $this->unlockedBeehive28Day;
    }

    public function setUnlockedBeehive28Day(?int $unlockedBeehive28Day): self
    {
        $this->unlockedBeehive28Day = $unlockedBeehive28Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedBeehiveLifetime(): ?int
    {
        return $this->unlockedBeehiveLifetime;
    }

    public function setUnlockedBeehiveLifetime(?int $unlockedBeehiveLifetime): self
    {
        $this->unlockedBeehiveLifetime = $unlockedBeehiveLifetime;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedPortal1Day(): ?int
    {
        return $this->unlockedPortal1Day;
    }

    public function setUnlockedPortal1Day(?int $unlockedPortal1Day): self
    {
        $this->unlockedPortal1Day = $unlockedPortal1Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedPortal3Day(): ?int
    {
        return $this->unlockedPortal3Day;
    }

    public function setUnlockedPortal3Day(?int $unlockedPortal3Day): self
    {
        $this->unlockedPortal3Day = $unlockedPortal3Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedPortal7Day(): ?int
    {
        return $this->unlockedPortal7Day;
    }

    public function setUnlockedPortal7Day(?int $unlockedPortal7Day): self
    {
        $this->unlockedPortal7Day = $unlockedPortal7Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedPortal28Day(): ?int
    {
        return $this->unlockedPortal28Day;
    }

    public function setUnlockedPortal28Day(?int $unlockedPortal28Day): self
    {
        $this->unlockedPortal28Day = $unlockedPortal28Day;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUnlockedPortalLifetime(): ?int
    {
        return $this->unlockedPortalLifetime;
    }

    public function setUnlockedPortalLifetime(?int $unlockedPortalLifetime): self
    {
        $this->unlockedPortalLifetime = $unlockedPortalLifetime;
        return $this;
    }
}
