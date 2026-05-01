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
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
class MonthlyStoryAdventure
{
    #[Groups([ "starKindredStory" ])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups([ "starKindredStory" ])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[Groups([ "starKindredStory" ])]
    #[ORM\Column(type: 'text')]
    private string $summary;

    #[Groups([ "starKindredStory" ])]
    #[ORM\Column(type: 'integer')]
    private int $releaseNumber = 0;

    #[Groups([ "starKindredStory", "starKindredStoryDetails" ])]
    #[ORM\Column(type: 'integer')]
    private int $releaseYear = 0;

    #[Groups([ "starKindredStory", "starKindredStoryDetails" ])]
    #[ORM\Column(type: 'integer')]
    private int $releaseMonth = 0;

    #[Groups([ "starKindredStoryDetails" ])]
    #[ORM\Column(type: 'boolean')]
    private bool $isDark = false;

    #[ORM\OneToMany(targetEntity: MonthlyStoryAdventureStep::class, mappedBy: 'adventure', orphanRemoval: true)]
    private Collection $steps;

    public function __construct()
    {
        $this->steps = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    #[Groups([ "starKindredStoryDetails" ])]
    #[SerializedName('isREMIX')]
    public function isREMIX()
    {
        return $this->releaseNumber === 0;
    }

    public function getReleaseNumber(): int
    {
        return $this->releaseNumber;
    }

    public function setReleaseNumber(int $releaseNumber): self
    {
        $this->releaseNumber = $releaseNumber;

        return $this;
    }

    public function getReleaseYear(): int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(int $releaseYear): self
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getReleaseMonth(): int
    {
        return $this->releaseMonth;
    }

    public function setReleaseMonth(int $releaseMonth): self
    {
        $this->releaseMonth = $releaseMonth;

        return $this;
    }

    public function getIsDark(): bool
    {
        return $this->isDark;
    }

    public function setIsDark(bool $isDark): self
    {
        $this->isDark = $isDark;

        return $this;
    }

    /**
     * @return Collection<int, MonthlyStoryAdventureStep>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }
}
