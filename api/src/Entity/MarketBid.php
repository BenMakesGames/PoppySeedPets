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
use App\Enum\LocationEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class MarketBid
{
    #[Groups(["myBids"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["myBids"])]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[Groups(["myBids"])]
    #[ORM\Column(type: 'integer')]
    private int $bid;

    #[Groups(["myBids"])]
    #[ORM\Column(type: 'smallint')]
    private int $quantity;

    #[Groups(["myBids"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[Groups(["myBids"])]
    #[ORM\Column(type: 'smallint')]
    private int $targetLocation;

    public function __construct(
        User $user,
        Item $item,
        int $bid,
        int $quantity,
        int $targetLocation
    )
    {
        if(!LocationEnum::isAValue($targetLocation))
            throw new EnumInvalidValueException(LocationEnum::class, $targetLocation);

        $this->user = $user;
        $this->item = $item;
        $this->bid = $bid;
        $this->quantity = $quantity;
        $this->targetLocation = $targetLocation;

        $this->createdOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getBid(): int
    {
        return $this->bid;
    }

    public function setBid(int $bid): self
    {
        $this->bid = $bid;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getTargetLocation(): int
    {
        return $this->targetLocation;
    }

    public function setTargetLocation(int $targetLocation): self
    {
        if(!LocationEnum::isAValue($targetLocation))
            throw new EnumInvalidValueException(LocationEnum::class, $targetLocation);

        $this->targetLocation = $targetLocation;

        return $this;
    }
}
