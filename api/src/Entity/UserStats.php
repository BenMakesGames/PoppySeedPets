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

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'user_id_stat_idx', columns: ['user_id', 'stat'])]
#[ORM\Entity]
class UserStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 100)]
    private string $stat;

    #[ORM\Column(type: 'integer')]
    private int $value = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $firstTime;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastTime;

    public function __construct(User $user, string $stat)
    {
        $this->user = $user;
        $this->stat = $stat;
        $this->firstTime = new \DateTimeImmutable();
        $this->lastTime = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStat(): string
    {
        return $this->stat;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @deprecated Do not call directly; use {@see UserStatsService}
     */
    public function increaseValue(int $value): self
    {
        $this->lastTime = new \DateTimeImmutable();
        $this->value += $value;

        return $this;
    }

    public function getFirstTime(): \DateTimeImmutable
    {
        return $this->firstTime;
    }

    public function getLastTime(): \DateTimeImmutable
    {
        return $this->lastTime;
    }
}
