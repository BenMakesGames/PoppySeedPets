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

#[ORM\Entity]
class PetCraving
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Pet::class, inversedBy: 'craving')]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[ORM\ManyToOne(targetEntity: ItemGroup::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ItemGroup $foodGroup;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $satisfiedOn = null;

    public function __construct(Pet $pet, ItemGroup $foodGroup)
    {
        $this->pet = $pet;
        $this->foodGroup = $foodGroup;
        $this->createdOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getFoodGroup(): ItemGroup
    {
        return $this->foodGroup;
    }

    public function setFoodGroup(ItemGroup $foodGroup): self
    {
        $this->foodGroup = $foodGroup;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTimeImmutable $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getSatisfiedOn(): ?\DateTimeImmutable
    {
        return $this->satisfiedOn;
    }

    public function setSatisfiedOn(?\DateTimeImmutable $satisfiedOn): self
    {
        $this->satisfiedOn = $satisfiedOn;

        return $this;
    }

    public function isSatisfied(): bool
    {
        return $this->satisfiedOn !== null;
    }
}
