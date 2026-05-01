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

use App\Enum\MonsterOfTheWeekEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MonsterOfTheWeek
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(length: 100, type: 'string', enumType: MonsterOfTheWeekEnum::class)]
    private MonsterOfTheWeekEnum $monster;

    #[ORM\Column]
    private int $communityTotal = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Item $easyPrize;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Item $mediumPrize;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Item $hardPrize;

    #[ORM\Column]
    private int $level;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    public function __construct(
        MonsterOfTheWeekEnum $monster,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        int $level,
        Item $easyPrize,
        Item $mediumPrize,
        Item $hardPrize
    )
    {
        $this->monster = $monster;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->level = $level;
        $this->easyPrize = $easyPrize;
        $this->mediumPrize = $mediumPrize;
        $this->hardPrize = $hardPrize;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getMonster(): MonsterOfTheWeekEnum
    {
        return $this->monster;
    }

    public function getCommunityTotal(): int
    {
        return $this->communityTotal;
    }

    public function getEasyPrize(): Item
    {
        return $this->easyPrize;
    }

    public function getMediumPrize(): Item
    {
        return $this->mediumPrize;
    }

    public function getHardPrize(): Item
    {
        return $this->hardPrize;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function isCurrent(\DateTimeImmutable $todaysDate): bool
    {
        return $todaysDate->setTime(0, 0, 0) >= $this->startDate && $todaysDate->setTime(0, 0, 0) <= $this->endDate;
    }
}
