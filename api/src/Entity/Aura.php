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
class Aura
{
    #[Groups(['myPet', 'myAura'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["myInventory", "itemEncyclopedia", "marketItem"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[Groups(["myPet", "myAura", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $image;

    #[Groups(["myPet", "myAura", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    #[ORM\Column(type: 'float')]
    private float $size = 1.0;

    #[Groups(["myPet", "myAura", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    #[ORM\Column(type: 'float')]
    private float $centerX = 0.5;

    #[Groups(["myPet", "myAura", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    #[ORM\Column(type: 'float')]
    private float $centerY = 0.5;

    public function __construct(string $name, string $image)
    {
        $this->name = $name;
        $this->image = $image;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getSize(): float
    {
        return $this->size;
    }

    public function setSize(float $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getCenterX(): float
    {
        return $this->centerX;
    }

    public function setCenterX(float $centerX): self
    {
        $this->centerX = $centerX;

        return $this;
    }

    public function getCenterY(): float
    {
        return $this->centerY;
    }

    public function setCenterY(float $centerY): self
    {
        $this->centerY = $centerY;

        return $this;
    }
}
