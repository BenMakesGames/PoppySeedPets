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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MonsterOfTheWeekContribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?MonsterOfTheWeek $monsterOfTheWeek = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private int $points = 0;

    #[ORM\Column]
    private \DateTimeImmutable $modifiedOn;

    #[ORM\Column]
    private bool $rewardsClaimed = false;

    public function __construct()
    {
        $this->modifiedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getMonsterOfTheWeek(): ?MonsterOfTheWeek
    {
        return $this->monsterOfTheWeek;
    }

    public function setMonsterOfTheWeek(MonsterOfTheWeek $monsterOfTheWeek): static
    {
        $this->monsterOfTheWeek = $monsterOfTheWeek;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function addPoints(int $points): static
    {
        if($points < 1)
            throw new \InvalidArgumentException('Points must be a positive integer.');

        $this->points += $points;
        $this->modifiedOn = new \DateTimeImmutable();

        return $this;
    }

    public function getModifiedOn(): \DateTimeImmutable
    {
        return $this->modifiedOn;
    }

    public function getRewardsClaimed(): bool
    {
        return $this->rewardsClaimed;
    }

    public function setRewardsClaimed(): static
    {
        $this->rewardsClaimed = true;
        $this->modifiedOn = new \DateTimeImmutable();

        return $this;
    }
}
