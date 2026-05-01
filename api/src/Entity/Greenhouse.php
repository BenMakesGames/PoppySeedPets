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

use App\Enum\BirdBathBirdEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'has_unoccupied_bird_bath_idx', columns: ['has_bird_bath', 'visiting_bird'])]
class Greenhouse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'greenhouse', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'smallint')]
    private int $maxPlants = 3;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'boolean')]
    private bool $hasBirdBath = false;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: BirdBathBirdEnum::class)]
    private ?BirdBathBirdEnum $visitingBird = null;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'smallint')]
    private int $maxWaterPlants = 0;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'smallint')]
    private int $maxDarkPlants = 0;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'boolean')]
    private bool $hasComposter = false;

    #[ORM\Column(type: 'integer')]
    private int $composterFood = 0;

    #[ORM\Column(type: 'integer')]
    private int $composterBonusCountdown = 0;

    #[Groups(["helperPet"])]
    #[ORM\OneToOne(targetEntity: Pet::class, cascade: ['persist', 'remove'])]
    private ?Pet $helper = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $butterfliesDismissedOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $beesDismissedOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $bees2DismissedOn;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column(type: 'boolean')]
    private bool $hasFishStatue = false;

    #[Groups(["myGreenhouse"])]
    #[ORM\Column]
    private bool $hasMoondial = false;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
        $this->setComposterBonusCountdown();
        $this->butterfliesDismissedOn = new \DateTimeImmutable();
        $this->beesDismissedOn = new \DateTimeImmutable();
        $this->bees2DismissedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
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

    public function getMaxPlants(): int
    {
        return $this->maxPlants;
    }

    public function increaseMaxPlants(int $amount): self
    {
        $this->maxPlants += $amount;

        return $this;
    }

    public function getHasBirdBath(): bool
    {
        return $this->hasBirdBath;
    }

    public function setHasBirdBath(bool $hasBirdBath): self
    {
        $this->hasBirdBath = $hasBirdBath;

        return $this;
    }

    public function getVisitingBird(): ?BirdBathBirdEnum
    {
        return $this->visitingBird;
    }

    public function setVisitingBird(?BirdBathBirdEnum $visitingBird): self
    {
        $this->visitingBird = $visitingBird;

        return $this;
    }

    public function getMaxWaterPlants(): int
    {
        return $this->maxWaterPlants;
    }

    public function increaseMaxWaterPlants(int $amount): self
    {
        $this->maxWaterPlants += $amount;

        return $this;
    }

    public function getMaxDarkPlants(): int
    {
        return $this->maxDarkPlants;
    }

    public function increaseMaxDarkPlants(int $amount): self
    {
        $this->maxDarkPlants += $amount;

        return $this;
    }

    public function getHasComposter(): bool
    {
        return $this->hasComposter;
    }

    public function setHasComposter(bool $hasComposter): self
    {
        $this->hasComposter = $hasComposter;

        return $this;
    }

    public function getComposterFood(): int
    {
        return $this->composterFood;
    }

    public function setComposterFood(int $composterFood): self
    {
        $this->composterFood = $composterFood;

        return $this;
    }

    public function getComposterBonusCountdown(): int
    {
        return $this->composterBonusCountdown;
    }

    public function setComposterBonusCountdown(): self
    {
        if($this->composterBonusCountdown <= 0)
            $this->composterBonusCountdown += random_int(3 * 20, 7 * 20);

        return $this;
    }

    public function decreaseComposterBonusCountdown(int $amount): self
    {
        $this->composterBonusCountdown -= $amount;

        return $this;
    }

    public function getHelper(): ?Pet
    {
        return $this->helper;
    }

    public function setHelper(?Pet $helper): self
    {
        $this->helper = $helper;

        return $this;
    }

    public function getButterfliesDismissedOn(): \DateTimeImmutable
    {
        return $this->butterfliesDismissedOn;
    }

    public function setButterfliesDismissedOn(\DateTimeImmutable $butterfliesDismissedOn): self
    {
        $this->butterfliesDismissedOn = $butterfliesDismissedOn;

        return $this;
    }

    public function getBeesDismissedOn(): \DateTimeImmutable
    {
        return $this->beesDismissedOn;
    }

    public function setBeesDismissedOn(\DateTimeImmutable $beesDismissedOn): self
    {
        $this->beesDismissedOn = $beesDismissedOn;

        return $this;
    }

    public function getBees2DismissedOn(): \DateTimeImmutable
    {
        return $this->bees2DismissedOn;
    }

    public function setBees2DismissedOn(\DateTimeImmutable $bees2DismissedOn): self
    {
        $this->bees2DismissedOn = $bees2DismissedOn;

        return $this;
    }

    public function isHasFishStatue(): bool
    {
        return $this->hasFishStatue;
    }

    public function setHasFishStatue(bool $hasFishStatue): self
    {
        $this->hasFishStatue = $hasFishStatue;

        return $this;
    }

    public function hasMoondial(): bool
    {
        return $this->hasMoondial;
    }

    public function setHasMoondial(bool $hasMoondial): static
    {
        $this->hasMoondial = $hasMoondial;

        return $this;
    }
}
