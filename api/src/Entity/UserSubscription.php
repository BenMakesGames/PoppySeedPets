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

use App\Enum\EnumInvalidValueException;
use App\Enum\PatreonTierEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class UserSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedOn;

    #[ORM\Column(type: 'integer')]
    private int $patreonUserId;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $tier = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'subscription', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    public function __construct(int $patreonUserId)
    {
        $this->patreonUserId = $patreonUserId;
        $this->updatedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUpdatedOn(): ?\DateTimeImmutable
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(): self
    {
        $this->updatedOn = new \DateTimeImmutable();

        return $this;
    }

    public function getPatreonUserId(): int
    {
        return $this->patreonUserId;
    }

    public function setPatreonUserId(int $patreonUserId): self
    {
        $this->patreonUserId = $patreonUserId;

        return $this;
    }

    public function getTier(): string
    {
        return $this->tier;
    }

    public function setTier(?string $tier): self
    {
        if($tier !== null && !PatreonTierEnum::isAValue($tier))
            throw new EnumInvalidValueException(PatreonTierEnum::class, $tier);

        $this->tier = $tier;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
