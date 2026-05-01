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

#[ORM\Entity()]
#[ORM\Index(name: 'badge_idx', columns: ['badge'])]
#[ORM\UniqueConstraint('pet_badge_unique', ['pet_id', 'badge'])]
class PetBadge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'badges')]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[ORM\Column(length: 40)]
    #[Groups(['myPet', 'petPublicProfile'])]
    private string $badge;

    #[ORM\Column]
    #[Groups(['myPet', 'petPublicProfile'])]
    private \DateTimeImmutable $dateAcquired;

    public function __construct(Pet $pet, string $badge)
    {
        $this->pet = $pet;
        $this->badge = $badge;
        $this->dateAcquired = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): static
    {
        $this->pet = $pet;

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getDateAcquired(): \DateTimeImmutable
    {
        return $this->dateAcquired;
    }
}
