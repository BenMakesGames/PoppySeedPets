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
#[ORM\Table(
    name: 'inventory_enchantment',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'inventory_unique', columns: ['inventory_id']),
    ]
)]
class InventoryEnchantment
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Inventory::class, inversedBy: 'enchantmentData')]
    #[ORM\JoinColumn(nullable: false)]
    private Inventory $inventory;

    #[ORM\ManyToOne(targetEntity: Enchantment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Enchantment $enchantment;

    #[ORM\Column(type: 'integer')]
    private int $hue = 0;

    public const int MinBrightness = 0;
    public const int MaxBrightness = 200;

    #[ORM\Column(type: 'integer', options: [ 'default' => 100 ])]
    private int $brightness = 100;

    public function __construct(Inventory $inventory, Enchantment $enchantment, int $hue = 0, int $brightness = 100)
    {
        $this->inventory = $inventory;
        $this->enchantment = $enchantment;
        $this->setHue($hue);
        $this->setBrightness($brightness);
    }

    public function getInventory(): Inventory
    {
        return $this->inventory;
    }

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getEnchantment(): Enchantment
    {
        return $this->enchantment;
    }

    public function setEnchantment(Enchantment $enchantment): self
    {
        $this->enchantment = $enchantment;

        return $this;
    }

    public function getHue(): int
    {
        return $this->hue;
    }

    public function setHue(int $hue): self
    {
        if($hue < 0)
            $hue = 360 - abs($hue);
        else if($hue >= 360)
            $hue = $hue % 360;

        $this->hue = $hue;

        return $this;
    }

    public function getBrightness(): int
    {
        return $this->brightness;
    }

    public function setBrightness(int $brightness): self
    {
        $this->brightness = max(self::MinBrightness, min(self::MaxBrightness, $brightness));

        return $this;
    }
}
