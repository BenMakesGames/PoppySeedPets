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
#[ORM\UniqueConstraint(name: 'market_listing_unique', columns: ['item_id'])]
#[ORM\Entity]
class MarketListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(['marketItem'])]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[Groups(['marketItem'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minimumSellPrice;

    public function __construct(Item $item, ?int $minimumSellPrice = null)
    {
        $this->item = $item;
        $this->minimumSellPrice = $minimumSellPrice;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getMinimumSellPrice(): ?int
    {
        return $this->minimumSellPrice;
    }

    public function setMinimumSellPrice(?int $minimumSellPrice): self
    {
        $this->minimumSellPrice = $minimumSellPrice;

        return $this;
    }
}
