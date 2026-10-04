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

/**
 * A Hattier-made adjustment to how a pet's current hat sits on its head. Overrides the hat item's
 * ItemHat values when serialized (see InventoryNormalizer). Pet::setHat discards it whenever the
 * pet's hat changes, so it only ever applies to that hat, on that pet.
 */
#[ORM\Entity]
class PetHatFit
{
    public const float MinHeadXY = -0.5;
    public const float MaxHeadXY = 1.5;
    public const float MinHeadAngle = -180;
    public const float MaxHeadAngle = 180;
    public const float MinHeadScale = 0.1;
    public const float MaxHeadScale = 1.5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $headX;

    #[ORM\Column(type: 'float')]
    private float $headY;

    #[ORM\Column(type: 'float')]
    private float $headAngle;

    #[ORM\Column(type: 'float')]
    private float $headScale;

    public function __construct(float $headX, float $headY, float $headAngle, float $headScale)
    {
        if($headX < self::MinHeadXY || $headX > self::MaxHeadXY || $headY < self::MinHeadXY || $headY > self::MaxHeadXY)
            throw new \InvalidArgumentException('headX and headY must be between ' . self::MinHeadXY . ' and ' . self::MaxHeadXY . '.');

        if($headAngle < self::MinHeadAngle || $headAngle > self::MaxHeadAngle)
            throw new \InvalidArgumentException('headAngle must be between ' . self::MinHeadAngle . ' and ' . self::MaxHeadAngle . '.');

        if($headScale < self::MinHeadScale || $headScale > self::MaxHeadScale)
            throw new \InvalidArgumentException('headScale must be between ' . self::MinHeadScale . ' and ' . self::MaxHeadScale . '.');

        $this->headX = $headX;
        $this->headY = $headY;
        $this->headAngle = $headAngle;
        $this->headScale = $headScale;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getHeadX(): float
    {
        return $this->headX;
    }

    public function getHeadY(): float
    {
        return $this->headY;
    }

    public function getHeadAngle(): float
    {
        return $this->headAngle;
    }

    public function getHeadScale(): float
    {
        return $this->headScale;
    }
}
