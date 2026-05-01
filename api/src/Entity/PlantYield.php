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

#[ORM\Entity]
class PlantYield
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Plant::class, inversedBy: 'plantYields')]
    #[ORM\JoinColumn(nullable: false)]
    private Plant $plant;

    #[ORM\Column(type: 'integer')]
    private int $min;

    #[ORM\Column(type: 'integer')]
    private int $max;

    /** @var Collection<int, PlantYieldItem> */
    #[ORM\OneToMany(targetEntity: PlantYieldItem::class, mappedBy: 'plantYield', orphanRemoval: true)]
    private Collection $items;

    public function __construct(int $min, int $max)
    {
        $this->items = new ArrayCollection();
        $this->min = $min;
        $this->max = $max;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPlant(): ?Plant
    {
        return $this->plant;
    }

    public function setPlant(?Plant $plant): self
    {
        $this->plant = $plant;

        return $this;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * @return Collection<int, PlantYieldItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PlantYieldItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setPlantYield($this);
        }

        return $this;
    }

    public function removeItem(PlantYieldItem $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            // set the owning side to null (unless already changed)
            if ($item->getPlantYield() === $this) {
                $item->setPlantYield(null);
            }
        }

        return $this;
    }
}
