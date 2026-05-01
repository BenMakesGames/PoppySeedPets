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

use App\Enum\SpiritCompanionStarEnum;
use App\Service\Xoshiro;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class SpiritCompanion
{
    const array Names = [
        'Achak', 'Âme', 'Anda', 'Arima', 'Atma', 'Avira',
        'Cheveyo',
        'Drogo', 'Du\'an', 'Duša', 'Dvasia',
        'Efrit', 'Enid', 'Espiridión', 'Euthymios',
        'Fravardin',
        'Gogo', 'Gees', 'Geist', 'Gwisin',
        'Hew', 'Huànyǐng',
        'Imamu',
        'Janan', 'Jīngshén',
        'Kneph',
        'Mamua', 'Matoatoa', 'Menos', 'Muuqasho',
        'Nafs',
        'Pneuma', 'Pret', 'Psykhe', 'Púca', 'Pӯҳ',
        'Rei', 'Roho', 'Ruh',
        'Seele', 'Spøgelse', 'Spyridon',
        'Tien', 'Tinh Thần',
        'Umoya',
        'Wairua',
        'Ysbryd', 'Yūrei',
    ];

    const array Images = [
        'blob', 'dino', 'erm', 'splat', 'jellyfish', 'sooty', 'cat-snake', 'haha', 'icicle', 'square',
        'rsuusd-bat', 'sea-monster', 'wtf'
    ];

    #[Groups(["spiritCompanionPublicProfile", "petSpiritAncestor"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["myPet", "spiritCompanionPublicProfile", "petSpiritAncestor"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[Groups(["myPet", "parkEvent", "hollowEarth", "petPublicProfile", "petGroupDetails", "spiritCompanionPublicProfile", "helperPet", "petSpiritAncestor"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $image;

    #[Groups(["spiritCompanionPublicProfile"])]
    #[ORM\OneToOne(targetEntity: Pet::class, mappedBy: 'spiritCompanion')]
    private Pet $pet;

    #[Groups(["myPet", "spiritCompanionPublicProfile"])]
    #[ORM\Column(type: 'string', length: 40, enumType: SpiritCompanionStarEnum::class)]
    private SpiritCompanionStarEnum $star;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastHangOut = null;

    #[ORM\OneToMany(targetEntity: Pet::class, mappedBy: 'spiritDad')]
    private Collection $fatheredPets;

    public function __construct()
    {
        $rng = new Xoshiro();

        $this->star = $rng->rngNextFromArray(SpiritCompanionStarEnum::cases());
        $this->name = $rng->rngNextFromArray(self::Names);
        $this->image = $rng->rngNextFromArray(self::Images);
        $this->fatheredPets = new ArrayCollection();
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

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getPet(): Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): self
    {
        $this->pet = $pet;

        // set (or unset) the owning side of the relation if necessary
        $newSpiritCompanion = $this;
        if ($newSpiritCompanion !== $pet->getSpiritCompanion()) {
            $pet->setSpiritCompanion($newSpiritCompanion);
        }

        return $this;
    }

    public function getStar(): SpiritCompanionStarEnum
    {
        return $this->star;
    }

    public function getLastHangOut(): ?\DateTimeImmutable
    {
        return $this->lastHangOut;
    }

    public function setLastHangOut(): self
    {
        $this->lastHangOut = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Pet>
     */
    public function getFatheredPets(): Collection
    {
        return $this->fatheredPets;
    }

    public function addFatheredPet(Pet $fatheredPet): self
    {
        if (!$this->fatheredPets->contains($fatheredPet)) {
            $this->fatheredPets[] = $fatheredPet;
            $fatheredPet->setSpiritDad($this);
        }

        return $this;
    }

    public function removeFatheredPet(Pet $fatheredPet): self
    {
        if ($this->fatheredPets->removeElement($fatheredPet)) {
            // set the owning side to null (unless already changed)
            if ($fatheredPet->getSpiritDad() === $this) {
                $fatheredPet->setSpiritDad(null);
            }
        }

        return $this;
    }
}
