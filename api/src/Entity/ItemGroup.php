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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class ItemGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["itemEncyclopedia", "myInventory"])]
    #[ORM\Column(type: 'string', length: 40, unique: true)]
    private string $name;

    /** @var Collection<int, Item>  */
    #[ORM\ManyToMany(targetEntity: Item::class, inversedBy: 'itemGroups')]
    private Collection $items;

    #[ORM\Column(type: 'boolean')]
    private bool $isCraving = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isGiftShop = false;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function getIsCraving(): ?bool
    {
        return $this->isCraving;
    }

    public function setIsCraving(bool $isCraving): self
    {
        $this->isCraving = $isCraving;

        return $this;
    }

    public function getIsGiftShop(): ?bool
    {
        return $this->isGiftShop;
    }

    public function setIsGiftShop(bool $isGiftShop): self
    {
        $this->isGiftShop = $isGiftShop;

        return $this;
    }
}
