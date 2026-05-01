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
class ItemHat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "parkEvent", "helperPet"])]
    #[ORM\Column(type: 'float')]
    private float $headX = 0;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "parkEvent", "helperPet"])]
    #[ORM\Column(type: 'float')]
    private float $headY = 0;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "parkEvent", "helperPet"])]
    #[ORM\Column(type: 'float')]
    private float $headAngle = 0;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "parkEvent", "helperPet"])]
    #[ORM\Column(type: 'float')]
    private float $headScale = 0;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "parkEvent", "helperPet"])]
    #[ORM\Column(type: 'boolean')]
    private bool $headAngleFixed = false;

    #[ORM\OneToOne(targetEntity: Item::class, mappedBy: 'hat')]
    private Item $item;

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getHeadX(): float
    {
        return $this->headX;
    }

    public function setHeadX(float $headX): self
    {
        $this->headX = $headX;

        return $this;
    }

    public function getHeadY(): float
    {
        return $this->headY;
    }

    public function setHeadY(float $headY): self
    {
        $this->headY = $headY;

        return $this;
    }

    public function getHeadAngle(): float
    {
        return $this->headAngle;
    }

    public function setHeadAngle(float $headAngle): self
    {
        $this->headAngle = $headAngle;

        return $this;
    }

    public function getHeadScale(): float
    {
        return $this->headScale;
    }

    public function setHeadScale(float $headScale): self
    {
        $this->headScale = $headScale;

        return $this;
    }

    public function getHeadAngleFixed(): bool
    {
        return $this->headAngleFixed;
    }

    public function setHeadAngleFixed(bool $headAngleFixed): self
    {
        $this->headAngleFixed = $headAngleFixed;

        return $this;
    }

    public function getItem(): Item
    {
        return $this->item;
    }
}
