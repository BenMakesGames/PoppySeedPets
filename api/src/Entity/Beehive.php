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
#[ORM\Index(name: 'workers_idx', columns: ['workers'])]
#[ORM\Index(name: 'flower_power_idx', columns: ['flower_power'])]
#[ORM\Entity]
class Beehive
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'beehive', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[Groups(["myBeehive"])]
    #[ORM\Column(type: 'integer')]
    private int $workers = 250;

    #[Groups(["myBeehive"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $queenName;

    #[Groups(["myBeehive"])]
    #[ORM\Column(type: 'float')]
    private float $flowerPower = 48;

    #[ORM\Column(type: 'float')]
    private float $royalJellyProgress = 0;

    #[ORM\Column(type: 'float')]
    private float $honeycombProgress = 0;

    #[ORM\Column(type: 'float')]
    private float $miscProgress = 0;

    #[Groups(["helperPet"])]
    #[ORM\OneToOne(targetEntity: Pet::class, cascade: ['persist', 'remove'])]
    private ?Pet $helper = null;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    public function __construct(User $user, string $name)
    {
        $this->user = $user;
        $this->queenName = $name;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWorkers(): int
    {
        return $this->workers;
    }

    public function addWorkers(int $workers): self
    {
        $this->workers += $workers;

        return $this;
    }

    public function getQueenName(): string
    {
        return $this->queenName;
    }

    public function setQueenName(string $queenName): self
    {
        $this->queenName = $queenName;

        return $this;
    }

    public function getMaxFlowerPower(): float
    {
        return log($this->getWorkers()) * 50;
    }

    public function getFlowerPower(): float
    {
        return $this->flowerPower;
    }

    public function addFlowerPower(float $amount): self
    {
        $this->flowerPower = min($this->getMaxFlowerPower(), $this->flowerPower + $amount);

        return $this;
    }

    #[Groups(["myBeehive"])]
    public function getFlowerPowerPercent(): float
    {
        return min(1, round($this->flowerPower / $this->getMaxFlowerPower(), 2));
    }

    #[Groups(["myBeehive"])]
    public function getFlowerPowerIsMaxed(): bool
    {
        return $this->flowerPower >= $this->getMaxFlowerPower();
    }

    #[Groups(["myBeehive"])]
    public function getIsWorking(): bool
    {
        return $this->flowerPower >= log($this->getWorkers());
    }

    public function getRoyalJellyProgress(): int
    {
        return $this->royalJellyProgress;
    }

    public function setRoyalJellyProgress(int $royalJellyProgress): self
    {
        $this->royalJellyProgress = $royalJellyProgress;

        return $this;
    }

    public function getHoneycombProgress(): int
    {
        return $this->honeycombProgress;
    }

    public function setHoneycombProgress(int $honeycombProgress): self
    {
        $this->honeycombProgress = $honeycombProgress;

        return $this;
    }

    public function getMiscProgress(): int
    {
        return $this->miscProgress;
    }

    public function setMiscProgress(int $miscProgress): self
    {
        $this->miscProgress = $miscProgress;

        return $this;
    }

    #[Groups(["myBeehive"])]
    public function getRoyalJellyPercent(): float
    {
        return min(1, round($this->royalJellyProgress / 2000, 2));
    }

    #[Groups(["myBeehive"])]
    public function getHoneycombPercent(): float
    {
        return min(1, round($this->honeycombProgress / 2000, 2));
    }

    #[Groups(["myBeehive"])]
    public function getMiscPercent(): float
    {
        return min(1, round($this->miscProgress / 2000, 2));
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
}
