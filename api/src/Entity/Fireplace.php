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

use App\Functions\ArrayFunctions;
use App\Functions\PetColorFunctions;
use App\Service\IRandom;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Table]
#[ORM\Index(name: 'heat_index', columns: ['heat'])]
#[ORM\Index(name: 'alcohol_index', columns: ['alcohol'])]
#[ORM\Index(name: 'longest_streak_index', columns: ['longest_streak'])]
#[ORM\Entity]
class Fireplace
{
    public const int MaxHeat = 3 * 24 * 60; // 3 days

    /**
     * @var string[]
     */
    public const array StockingAppearances = [
        'fluffed',
        'tasseled',
        'snowflaked',
        'forest',
        'cow',
        'eye',
        'holly'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'fireplace')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["myFireplace"])]
    #[ORM\Column(type: 'integer')]
    private int $longestStreak = 0;

    #[Groups(["myFireplace"])]
    #[ORM\Column(type: 'integer')]
    private int $currentStreak = 0;

    #[ORM\Column(type: 'integer')]
    private int $heat = 0;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[Groups(["myFireplace"])]
    #[ORM\Column(type: 'smallint')]
    private int $mantleSize = 12;

    #[ORM\Column(type: 'string', length: 20)]
    private string $stockingAppearance;

    #[ORM\Column(type: 'string', length: 6)]
    private string $stockingColorA;

    #[ORM\Column(type: 'string', length: 6)]
    private string $stockingColorB;

    #[Groups(["helperPet"])]
    #[ORM\OneToOne(targetEntity: Pet::class, cascade: ['persist', 'remove'])]
    private ?Pet $helper = null;

    #[ORM\Column(type: 'integer')]
    private int $soot = 0;

    #[ORM\Column(type: 'integer')]
    private int $alcohol = 0;

    #[ORM\Column(type: 'integer')]
    private int $gnomePoints = 0;

    #[ORM\Column]
    private bool $hasForge = false;

    #[ORM\Column]
    private int $bricks = 0;

    public function __construct(User $user, IRandom $rng)
    {
        $this->user = $user;

        $this->stockingAppearance = $rng->rngNextFromArray(Fireplace::StockingAppearances);

        $stockingColors = PetColorFunctions::generateRandomPetColors($rng);

        $this->stockingColorA = $stockingColors[0];
        $this->stockingColorB = $stockingColors[1];
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

    public function getLongestStreak(): int
    {
        return $this->longestStreak;
    }

    public function getCurrentStreak(): int
    {
        return $this->currentStreak;
    }

    public function getHeat(): int
    {
        return $this->heat;
    }

    public function addFuel(int $fuel, int $alcohol): self
    {
        $heatToAdd = min($fuel, self::MaxHeat - $this->heat);

        $this->heat += $heatToAdd;
        $this->alcohol += $alcohol;

        if($this->getHelper())
            $this->soot += $heatToAdd;

        return $this;
    }

    public function removeHeat(int $heat): self
    {
        if($heat <= 0)
            throw new \InvalidArgumentException('Heat to remove must be positive!');

        if($heat > $this->heat)
            throw new \InvalidArgumentException('Cannot remove more heat than is present!');

        $this->heat -= $heat;

        return $this;
    }

    #[Groups(["myFireplace"])]
    #[SerializedName('heat')]
    public function getHeatPercent(): int
    {
        if($this->heat <= 0)
            return 0;
        else
            return (int)max(1, $this->heat * 100 / self::MaxHeat);
    }

    #[Groups(["myFireplace"])]
    public function getHeatDescription(): ?string
    {
        if($this->getHeat() <= 0)
            return null;

        $words = [];

        if($this->getHeatPercent() >= 90)
            $words[] = 'overwhelming';
        else if($this->getHeatPercent() >= 80)
            $words[] = 'slightly-intimidating';
        else if($this->getHeatPercent() >= 70)
            $words[] = 'very strong';
        else if($this->getHeatPercent() >= 60)
            $words[] = 'strong';
        else if($this->getHeatPercent() >= 50)
            $words[] = 'sizable';
        else if($this->getHeatPercent() >= 30)
            $words[] = 'medium';
        else if($this->getHeatPercent() >= 20)
            $words[] = 'small';
        else if($this->getHeatPercent() >= 10)
            $words[] = 'very small';
        else if($this->getHeatPercent() >= 5)
            $words[] = 'faintly-glowing';
        else
            $words[] = 'only technically warm';

        $percentAlcohol = $this->getAlcohol() / $this->getHeat();

        if($percentAlcohol >= 0.5)
            $words[] = 'exceptionally-boozy';
        else if($percentAlcohol >= 0.4)
            $words[] = 'highly boozy';
        else if($percentAlcohol >= 0.3)
            $words[] = 'very boozy';
        else if($percentAlcohol >= 0.2)
            $words[] = 'a bit boozy';
        else if($percentAlcohol >= 0.1)
            $words[] = 'booze-tinged';
        else if($this->getAlcohol() > 0)
            $words[] = 'ever-so-slightly boozy';

        $butOrAnd = $this->getHeat() < 4 * 60 && $percentAlcohol >= 0.3
            ? ', but '
            : ', and '
        ;

        return ArrayFunctions::list_nice($words, ', ', $butOrAnd);
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function clearPoints(): self
    {
        $this->points = 0;

        return $this;
    }

    public function spendPoints(int $points): self
    {
        if($points > $this->points)
            throw new \InvalidArgumentException('Cannot spend more points than you have!');

        $this->points -= $points;

        return $this;
    }

    public function spendGnomePoints(int $points): self
    {
        if($points > $this->gnomePoints)
            throw new \InvalidArgumentException('Cannot spend more Gnome points than you have!');

        $this->gnomePoints -= $points;

        return $this;
    }

    #[Groups(["myFireplace"])]
    public function getHasReward(): bool
    {
        return $this->points > 8 * 60;
    }

    #[Groups(["myFireplace"])]
    public function getStocking()
    {
        return [
            'appearance' => $this->getStockingAppearance(),
            'colorA' => $this->getStockingColorA(),
            'colorB' => $this->getStockingColorB()
        ];
    }

    public function getMantleSize(): int
    {
        return $this->mantleSize;
    }

    public function setMantleSize(int $mantleSize): self
    {
        $this->mantleSize = $mantleSize;

        return $this;
    }

    public function getStockingAppearance(): ?string
    {
        return $this->stockingAppearance;
    }

    public function setStockingAppearance(string $stockingAppearance): self
    {
        $this->stockingAppearance = $stockingAppearance;

        return $this;
    }

    public function getStockingColorA(): ?string
    {
        return $this->stockingColorA;
    }

    public function setStockingColorA(string $stockingColorA): self
    {
        $this->stockingColorA = $stockingColorA;

        return $this;
    }

    public function getStockingColorB(): ?string
    {
        return $this->stockingColorB;
    }

    public function setStockingColorB(string $stockingColorB): self
    {
        $this->stockingColorB = $stockingColorB;

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

    public function getSoot(): int
    {
        return $this->soot;
    }

    public function cleanSoot(int $soot): self
    {
        $this->soot = max(0, $this->soot - $soot);

        return $this;
    }

    public function getAlcohol(): int
    {
        return $this->alcohol;
    }

    public function setAlcohol(int $alcohol): self
    {
        $this->alcohol = $alcohol;

        return $this;
    }

    public function getGnomePoints(): int
    {
        return $this->gnomePoints;
    }

    public function setGnomePoints(int $gnomePoints): self
    {
        $this->gnomePoints = $gnomePoints;

        return $this;
    }

    #[Groups(["myFireplace"])]
    public function getHasForge(): bool
    {
        return $this->hasForge;
    }

    public function setHasForge(bool $hasForge): static
    {
        $this->hasForge = $hasForge;

        return $this;
    }

    #[Groups(["myFireplace"])]
    public function getBricks(): int
    {
        return $this->bricks;
    }

    public function addBrick(): static
    {
        $this->bricks++;

        return $this;
    }
}
