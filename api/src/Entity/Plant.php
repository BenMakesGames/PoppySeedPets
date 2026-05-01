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
class Plant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 40)]
    private string $sproutImage = '';

    #[ORM\Column(type: 'string', length: 40)]
    private string $mediumImage = '';

    #[ORM\Column(type: 'string', length: 40)]
    private string $adultImage = '';

    #[ORM\Column(type: 'string', length: 40)]
    private string $harvestableImage = '';

    #[ORM\OneToOne(targetEntity: Item::class, mappedBy: 'plant')]
    private Item $item;

    #[ORM\Column(type: 'integer')]
    private int $timeToAdult = 0;

    #[ORM\Column(type: 'integer')]
    private int $timeToFruit = 0;

    #[Groups(["greenhousePlant"])]
    #[ORM\Column(type: 'string', length: 20)]
    private string $type = 'earth';

    /**
     * @var Collection<int, PlantYield>
     */
    #[ORM\OneToMany(targetEntity: PlantYield::class, mappedBy: 'plant', orphanRemoval: true)]
    private Collection $plantYields;

    #[Groups(["greenhousePlant"])]
    #[ORM\Column(type: 'string', length: 40, unique: true)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: FieldGuideEntry::class)]
    private ?FieldGuideEntry $fieldGuideEntry = null;

    #[ORM\Column(type: 'boolean')]
    private bool $noPollinators = false;

    public function __construct()
    {
        $this->plantYields = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getSproutImage(): string
    {
        return $this->sproutImage;
    }

    public function setSproutImage(string $sproutImage): self
    {
        $this->sproutImage = $sproutImage;

        return $this;
    }

    public function getMediumImage(): string
    {
        return $this->mediumImage;
    }

    public function setMediumImage(string $mediumImage): self
    {
        $this->mediumImage = $mediumImage;

        return $this;
    }

    public function getAdultImage(): string
    {
        return $this->adultImage;
    }

    public function setAdultImage(string $adultImage): self
    {
        $this->adultImage = $adultImage;

        return $this;
    }

    public function getHarvestableImage(): string
    {
        return $this->harvestableImage;
    }

    public function setHarvestableImage(string $harvestableImage): self
    {
        $this->harvestableImage = $harvestableImage;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        // set (or unset) the owning side of the relation if necessary
        $newPlant = $item === null ? null : $this;
        if ($newPlant !== $item->getPlant()) {
            $item->setPlant($newPlant);
        }

        return $this;
    }

    public function getTimeToAdult(): int
    {
        return $this->timeToAdult;
    }

    public function setTimeToAdult(int $timeToAdult): self
    {
        $this->timeToAdult = $timeToAdult;

        return $this;
    }

    public function getTimeToFruit(): int
    {
        return $this->timeToFruit;
    }

    public function setTimeToFruit(int $timeToFruit): self
    {
        $this->timeToFruit = $timeToFruit;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, PlantYield>
     */
    public function getPlantYields(): Collection
    {
        return $this->plantYields;
    }

    public function addPlantYield(PlantYield $plantYield): self
    {
        if (!$this->plantYields->contains($plantYield)) {
            $this->plantYields[] = $plantYield;
            $plantYield->setPlant($this);
        }

        return $this;
    }

    public function removePlantYield(PlantYield $plantYield): self
    {
        if ($this->plantYields->contains($plantYield)) {
            $this->plantYields->removeElement($plantYield);
            // set the owning side to null (unless already changed)
            if ($plantYield->getPlant() === $this) {
                $plantYield->setPlant(null);
            }
        }

        return $this;
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

    public function getFieldGuideEntry(): ?FieldGuideEntry
    {
        return $this->fieldGuideEntry;
    }

    public function setFieldGuideEntry(?FieldGuideEntry $fieldGuideEntry): self
    {
        $this->fieldGuideEntry = $fieldGuideEntry;

        return $this;
    }

    public function getNoPollinators(): bool
    {
        return $this->noPollinators;
    }

    public function setNoPollinators(bool $noPollinators): self
    {
        $this->noPollinators = $noPollinators;

        return $this;
    }
}
