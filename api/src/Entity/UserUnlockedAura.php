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
#[ORM\UniqueConstraint(name: 'user_id_aura_id_idx', columns: ['user_id', 'aura_id'])]
#[ORM\Entity]
class UserUnlockedAura
{
    #[Groups(["myAura"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'unlockedAuras')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["myAura"])]
    #[ORM\ManyToOne(targetEntity: Enchantment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Enchantment $aura;

    #[Groups(["myAura"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedOn;

    #[Groups(["myAura"])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $comment;

    public function __construct(User $user, Enchantment $aura)
    {
        $this->user = $user;
        $this->aura = $aura;
        $this->unlockedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAura(): Enchantment
    {
        return $this->aura;
    }

    public function getUnlockedOn(): \DateTimeImmutable
    {
        return $this->unlockedOn;
    }

    public function setUnlockedOn(\DateTimeImmutable $unlockedOn): self
    {
        $this->unlockedOn = $unlockedOn;
        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
