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

use App\Enum\HollowEarthMoveDirectionEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class HollowEarthTile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $x = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $y = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'string', length: 1, enumType: HollowEarthMoveDirectionEnum::class)]
    private HollowEarthMoveDirectionEnum $moveDirection;

    /** @var Collection<int, HollowEarthTileType> */
    #[ORM\ManyToMany(targetEntity: HollowEarthTileType::class)]
    private Collection $types;

    #[ORM\ManyToOne(targetEntity: HollowEarthTileCard::class)]
    private ?HollowEarthTileCard $card = null;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $goodsSide = null;

    #[ORM\Column(type: 'simple_array', nullable: true)]
    private ?array $goods = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isTradingDepot = false;

    public function __construct()
    {
        $this->types = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function getMoveDirection(): HollowEarthMoveDirectionEnum
    {
        return $this->moveDirection;
    }

    public function setMoveDirection(HollowEarthMoveDirectionEnum $moveDirection): self
    {
        $this->moveDirection = $moveDirection;

        return $this;
    }

    /**
     * @return Collection<int, HollowEarthTileType>
     */
    public function getTypes(): Collection
    {
        return $this->types;
    }

    public function addType(HollowEarthTileType $type): self
    {
        if (!$this->types->contains($type)) {
            $this->types[] = $type;
        }

        return $this;
    }

    public function removeType(HollowEarthTileType $type): self
    {
        $this->types->removeElement($type);

        return $this;
    }

    public function getCard(): ?HollowEarthTileCard
    {
        return $this->card;
    }

    public function setCard(?HollowEarthTileCard $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getGoodsSide(): ?string
    {
        return $this->goodsSide;
    }

    public function setGoodsSide(?string $goodsSide): self
    {
        $this->goodsSide = $goodsSide;

        return $this;
    }

    public function getGoods(): ?array
    {
        return $this->goods;
    }

    public function setGoods(?array $goods): self
    {
        $this->goods = $goods;

        return $this;
    }

    public function getIsTradingDepot(): bool
    {
        return $this->isTradingDepot;
    }

    public function setIsTradingDepot(bool $isTradingDepot): self
    {
        $this->isTradingDepot = $isTradingDepot;

        return $this;
    }
}
