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

use App\Enum\PetPregnancyStyleEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class PetBaby
{
    public const int PREGNANCY_DURATION = 30240;
    public const int PREGNANCY_INTERVAL = self::PREGNANCY_DURATION / 6;
    public const int EGG_INCUBATION_TIME = self::PREGNANCY_INTERVAL * 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $growth = 0;

    #[ORM\Column(type: 'integer')]
    private int $affection = 0;

    #[ORM\ManyToOne(targetEntity: PetSpecies::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PetSpecies $species;

    #[ORM\OneToOne(targetEntity: Pet::class, mappedBy: 'pregnancy')]
    private ?Pet $parent;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    private ?Pet $otherParent;

    #[ORM\ManyToOne(targetEntity: SpiritCompanion::class)]
    private ?SpiritCompanion $spiritParent = null;

    #[ORM\Column(type: 'string', length: 6)]
    private string $colorA;

    #[ORM\Column(type: 'string', length: 6)]
    private string $colorB;

    public function __construct(PetSpecies $species, string $colorA, string $colorB)
    {
        $this->species = $species;
        $this->colorA = $colorA;
        $this->colorB = $colorB;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getGrowth(): int
    {
        return $this->growth;
    }

    public function increaseGrowth(int $growth): self
    {
        $this->growth += $growth;

        return $this;
    }

    public function getAffection(): int
    {
        return $this->affection;
    }

    public function increaseAffection(int $affection): self
    {
        $this->affection += $affection;

        return $this;
    }

    public function getSpecies(): PetSpecies
    {
        return $this->species;
    }

    public function setSpecies(PetSpecies $species): self
    {
        $this->species = $species;

        return $this;
    }

    public function getParent(): Pet
    {
        return $this->parent;
    }

    public function setParent(Pet $parent): self
    {
        $this->parent = $parent;

        // set (or unset) the owning side of the relation if necessary
        if ($this !== $parent->getPregnancy()) {
            $parent->setPregnancy($this);
        }

        return $this;
    }

    public function getOtherParent(): ?Pet
    {
        return $this->otherParent;
    }

    public function setOtherParent(?Pet $otherParent): self
    {
        $this->otherParent = $otherParent;

        return $this;
    }

    public function getColorA(): string
    {
        return $this->colorA;
    }

    public function setColorA(string $colorA): self
    {
        $this->colorA = $colorA;

        return $this;
    }

    public function getColorB(): string
    {
        return $this->colorB;
    }

    public function setColorB(string $colorB): self
    {
        $this->colorB = $colorB;

        return $this;
    }

    #[Groups(['myPet'])]
    public function getPregnancyProgress(): string
    {
        if($this->getParent()->getSpecies()->getPregnancyStyle() === PetPregnancyStyleEnum::Egg)
        {
            $messages = [
                $this->getParent()->getName() . ' just got eggnant!',
                $this->getParent()->getName() . ' is going to lay their egg soon!',
                $this->getParent()->getName() . '\'s egg was recently laid!',
                $this->getParent()->getName() . '\'s egg is starting to move around...',
                $this->getParent()->getName() . '\'s egg is moving around a bit!',
                $this->getParent()->getName() . '\'s egg is close to hatching!',
            ];
        }
        else
        {
            $messages = [
                $this->getParent()->getName() . ' just got pregnant!',
                $this->getParent()->getName() . ' looks a little bigger!',
                $this->getParent()->getName() . '\'s baby is starting to move around!',
                $this->getParent()->getName() . '\'s baby is quite active!',
                $this->getParent()->getName() . ' is preparing for parenthood!',
                $this->getParent()->getName() . ' is close to giving birth!',
            ];
        }

        $interval = min(
            count($messages) - 1,
            (int)floor($this->getGrowth() / self::PREGNANCY_INTERVAL)
        );

        return $messages[$interval];
    }

    #[Groups(["myPet", "userPublicProfile", "petPublicProfile", "petShelterPet", "petFriend"])]
    public function getEggColor(): ?string
    {
        // we only see the color expressed in an egg
        if($this->getParent()->getSpecies()->getPregnancyStyle() === PetPregnancyStyleEnum::Egg && $this->getGrowth() > self::EGG_INCUBATION_TIME)
            return $this->getColorA();
        else
            return null;
    }

    public function getSpiritParent(): ?SpiritCompanion
    {
        return $this->spiritParent;
    }

    public function setSpiritParent(?SpiritCompanion $spiritParent): self
    {
        $this->spiritParent = $spiritParent;

        return $this;
    }
}
