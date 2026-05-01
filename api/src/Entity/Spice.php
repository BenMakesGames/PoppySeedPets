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
class Spice
{
    #[Groups(["marketItem", "greenhouseFertilizer", "fireplaceFuel"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["myInventory", "marketItem", "itemEncyclopedia"])]
    #[ORM\OneToOne(targetEntity: ItemFood::class, inversedBy: 'spice', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ItemFood $effects;

    #[Groups(["myInventory", "marketItem", "itemEncyclopedia", "myPet", "dragonTreasure", "greenhouseFertilizer", "fireplaceFuel", "fireplaceMantle"])]
    #[ORM\Column(type: 'string', length: 20)]
    private string $name;

    #[Groups(["myInventory", "marketItem", "itemEncyclopedia", "myPet", "dragonTreasure", "greenhouseFertilizer", "fireplaceFuel", "fireplaceMantle"])]
    #[ORM\Column(type: 'boolean')]
    private bool $isSuffix = false;

    public function __construct(string $name, ItemFood $effects)
    {
        $this->name = $name;
        $this->effects = $effects;
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

    public function getIsSuffix(): bool
    {
        return $this->isSuffix;
    }

    public function setIsSuffix(bool $isSuffix): self
    {
        $this->isSuffix = $isSuffix;

        return $this;
    }

    public function getEffects(): ItemFood
    {
        return $this->effects;
    }

    public function setEffects(ItemFood $effects): self
    {
        $this->effects = $effects;

        return $this;
    }
}
