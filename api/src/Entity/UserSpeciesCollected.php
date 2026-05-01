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
#[ORM\UniqueConstraint(name: 'user_species_idx', columns: ['user_id', 'species_id'])]
#[ORM\Entity]
class UserSpeciesCollected
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["zoologistCatalog"])]
    #[ORM\ManyToOne(targetEntity: PetSpecies::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PetSpecies $species;

    #[Groups(["zoologistCatalog"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $discoveredOn;

    #[Groups(["zoologistCatalog"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $petName;

    #[Groups(["zoologistCatalog"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $colorA;

    #[Groups(["zoologistCatalog"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $colorB;

    #[Groups(["zoologistCatalog"])]
    #[ORM\Column(type: 'smallint')]
    private int $scale;

    public function __construct()
    {
        $this->discoveredOn = new \DateTimeImmutable();
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

    public function getSpecies(): PetSpecies
    {
        return $this->species;
    }

    public function setSpecies(PetSpecies $species): self
    {
        $this->species = $species;

        return $this;
    }

    public function getDiscoveredOn(): \DateTimeImmutable
    {
        return $this->discoveredOn;
    }

    public function getPetName(): ?string
    {
        return $this->petName;
    }

    public function setPetName(string $petName): self
    {
        $this->petName = $petName;

        return $this;
    }

    public function getColorA(): ?string
    {
        return $this->colorA;
    }

    public function setColorA(string $colorA): self
    {
        $this->colorA = $colorA;

        return $this;
    }

    public function getColorB(): ?string
    {
        return $this->colorB;
    }

    public function setColorB(string $colorB): self
    {
        $this->colorB = $colorB;

        return $this;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function setScale(int $scale): self
    {
        $this->scale = $scale;

        return $this;
    }
}
