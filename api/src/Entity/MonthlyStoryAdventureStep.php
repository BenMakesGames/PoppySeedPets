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

use App\Enum\StoryAdventureTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class MonthlyStoryAdventureStep
{
    #[Groups([ "starKindredStoryStepAvailable", "starKindredStoryStepComplete" ])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MonthlyStoryAdventure::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false)]
    private MonthlyStoryAdventure $adventure;

    #[Groups([ "starKindredStoryStepAvailable", "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'string', length: 30)]
    private string $title;

    #[Groups([ "starKindredStoryStepAvailable", "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'string', length: 20, enumType: StoryAdventureTypeEnum::class)]
    private StoryAdventureTypeEnum $type;

    #[ORM\Column(type: 'integer')]
    private int $step;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $previousStep;

    #[Groups([ "starKindredStoryStepAvailable", "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'float')]
    private float $x;

    #[Groups([ "starKindredStoryStepAvailable", "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'float')]
    private float $y;

    #[Groups([ "starKindredStoryStepAvailable" ])]
    #[ORM\Column(type: 'integer')]
    private int $minPets;

    #[Groups([ "starKindredStoryStepAvailable" ])]
    #[ORM\Column(type: 'integer')]
    private int $maxPets;

    #[Groups([ "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $narrative;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $treasure;

    #[ORM\ManyToOne(targetEntity: Enchantment::class)]
    private ?Enchantment $aura;

    #[Groups([ "starKindredStoryStepAvailable" ])]
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $pinOverride = null;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getAdventure(): MonthlyStoryAdventure
    {
        return $this->adventure;
    }

    public function setAdventure(MonthlyStoryAdventure $adventure): self
    {
        $this->adventure = $adventure;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): StoryAdventureTypeEnum
    {
        return $this->type;
    }

    public function setType(StoryAdventureTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function getPreviousStep(): ?int
    {
        return $this->previousStep;
    }

    public function setPreviousStep(?int $previousStep): self
    {
        $this->previousStep = $previousStep;

        return $this;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function setX(float $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setY(float $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function getMinPets(): int
    {
        return $this->minPets;
    }

    public function setMinPets(int $minPets): self
    {
        $this->minPets = $minPets;

        return $this;
    }

    public function getMaxPets(): int
    {
        return $this->maxPets;
    }

    public function setMaxPets(int $maxPets): self
    {
        $this->maxPets = $maxPets;

        return $this;
    }

    public function getNarrative(): ?string
    {
        return $this->narrative;
    }

    public function setNarrative(?string $narrative): self
    {
        $this->narrative = $narrative;

        return $this;
    }

    public function getTreasure(): ?string
    {
        return $this->treasure;
    }

    public function setTreasure(?string $treasure): self
    {
        $this->treasure = $treasure;

        return $this;
    }

    public function getPinOverride(): ?string
    {
        return $this->pinOverride;
    }

    public function setPinOverride(?string $pinOverride): self
    {
        $this->pinOverride = $pinOverride;

        return $this;
    }

    public function getAura(): ?Enchantment
    {
        return $this->aura;
    }

    public function setAura(?Enchantment $aura): self
    {
        $this->aura = $aura;

        return $this;
    }
}
