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

use App\Enum\PollinatorEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class GreenhousePlant
{
    #[Groups(["greenhousePlant"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["greenhousePlant"])]
    #[ORM\ManyToOne(targetEntity: Plant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Plant $plant;

    #[ORM\Column(type: 'integer')]
    private int $growth = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastInteraction;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'greenhousePlants')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[Groups(["greenhousePlant"])]
    #[ORM\Column(type: 'boolean')]
    private bool $isAdult = false;

    #[ORM\Column(type: 'integer')]
    private int $previousGrowth = 0;

    #[Groups(["greenhousePlant"])]
    #[ORM\Column(type: 'smallint')]
    private int $ordinal;

    #[Groups(["greenhousePlant"])]
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: PollinatorEnum::class)]
    private ?PollinatorEnum $pollinators = null;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    public function __construct(User $owner, Plant $plant, int $ordinal)
    {
        $this->owner = $owner;
        $this->plant = $plant;
        $this->ordinal = $ordinal;
        $this->lastInteraction = new \DateTimeImmutable()->modify('-1 day');
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPlant(): Plant
    {
        return $this->plant;
    }

    public function getGrowth(): int
    {
        return $this->growth;
    }

    public function clearGrowth(): self
    {
        $this->previousGrowth = 0;
        $this->growth = 0;

        return $this;
    }

    public function fertilize(Inventory $inventory): self
    {
        if($inventory->getTotalFertilizerValue() <= 0)
            return $this;

        $this->previousGrowth = $this->growth;

        $this->growth += $inventory->getTotalFertilizerValue();

        if(!$this->getIsAdult())
        {
            if($this->growth >= $this->getPlant()->getTimeToAdult())
            {
                $this->previousGrowth = 0;
                $this->growth -= $this->getPlant()->getTimeToAdult();
                $this->setIsAdult(true);
            }
        }

        if($this->getIsAdult() && $this->growth >= $this->getPlant()->getTimeToFruit())
            $this->growth = $this->getPlant()->getTimeToFruit();

        $this->setLastInteraction();

        return $this;
    }

    #[Groups(["greenhousePlant"])]
    public function getCanNextInteract(): \DateTimeImmutable
    {
        return $this->getLastInteraction()->modify('+12 hours');
    }

    public function getLastInteraction(): \DateTimeImmutable
    {
        return $this->lastInteraction;
    }

    public function setLastInteraction(): self
    {
        $this->lastInteraction = new \DateTimeImmutable();

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getIsAdult(): ?bool
    {
        return $this->isAdult;
    }

    public function setIsAdult(bool $isAdult): self
    {
        $this->isAdult = $isAdult;

        return $this;
    }

    #[Groups(["greenhousePlant"])]
    public function getProgress(): float
    {
        if($this->isAdult)
            return round($this->growth / $this->getPlant()->getTimeToFruit(), 2);
        else
            return round($this->growth / $this->getPlant()->getTimeToAdult(), 2);
    }

    #[Groups(["greenhousePlant"])]
    public function getPreviousProgress(): float
    {
        if($this->isAdult)
            return round($this->previousGrowth / $this->getPlant()->getTimeToFruit(), 1);
        else
            return round($this->previousGrowth / $this->getPlant()->getTimeToAdult(), 1);
    }

    #[Groups(["greenhousePlant"])]
    public function getImage()
    {
        if($this->isAdult)
        {
            if($this->getProgress() >= 1)
                return $this->getPlant()->getHarvestableImage();
            else
                return $this->getPlant()->getAdultImage();
        }
        else
        {
            if($this->getProgress() >= 0.5)
                return $this->getPlant()->getMediumImage();
            else
                return $this->getPlant()->getSproutImage();
        }
    }

    public function getPreviousGrowth(): int
    {
        return $this->previousGrowth;
    }

    public function getOrdinal(): int
    {
        return $this->ordinal;
    }

    public function setOrdinal(int $ordinal): self
    {
        $this->ordinal = $ordinal;

        return $this;
    }

    public function getPollinators(): ?PollinatorEnum
    {
        return $this->pollinators;
    }

    public function setPollinators(?PollinatorEnum $pollinators): self
    {
        $this->pollinators = $pollinators;

        return $this;
    }
}
