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

use App\Enum\StarKindredClassEnum;
use App\Enum\StarKindredRaceEnum;
use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredStatEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * A ★Kindred character, played by a pet. A pet has at most one active (un-retired) character at a time;
 * retired characters stay attached to the pet forever, forming its "book of retired adventurers".
 *
 * Characters belong to pets, not players: if a pet changes owners, its characters go with it.
 */
#[ORM\Entity]
#[ORM\Index(name: 'pet_retired_on_idx', columns: ['pet_id', 'retired_on'])]
class StarKindredCharacter
{
    public const int MaxLevel = 20;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Pet $pet;

    #[ORM\Column(type: 'string', length: 60)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, enumType: StarKindredRaceEnum::class)]
    private StarKindredRaceEnum $race;

    #[ORM\Column(type: 'string', length: 20, enumType: StarKindredClassEnum::class)]
    private StarKindredClassEnum $characterClass;

    /**
     * @see \App\Service\StarKindred\StarKindredPortraits
     */
    #[ORM\Column(type: 'string', length: 30)]
    private string $portrait;

    #[ORM\Column(type: 'integer')]
    private int $level = 1;

    /**
     * Progress toward the next level; resets on level-up.
     */
    #[ORM\Column(type: 'integer')]
    private int $experience = 0;

    // base stats: rolled at creation (race modifiers included); class growth is applied on top, by level
    #[ORM\Column(type: 'integer')]
    private int $strength;

    #[ORM\Column(type: 'integer')]
    private int $dexterity;

    #[ORM\Column(type: 'integer')]
    private int $constitution;

    #[ORM\Column(type: 'integer')]
    private int $intelligence;

    #[ORM\Column(type: 'integer')]
    private int $wisdom;

    #[ORM\Column(type: 'integer')]
    private int $charisma;

    #[ORM\Column(type: 'integer')]
    private int $adventuresCompleted = 0;

    #[ORM\Column(type: 'integer')]
    private int $adventuresWon = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $retiredOn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $epilogue = null;

    /**
     * @param array<value-of<StarKindredStatEnum>, int> $baseStats
     */
    public function __construct(
        Pet $pet, string $name, StarKindredRaceEnum $race, StarKindredClassEnum $characterClass, string $portrait,
        array $baseStats, \DateTimeImmutable $createdOn
    )
    {
        $this->pet = $pet;
        $this->name = $name;
        $this->race = $race;
        $this->characterClass = $characterClass;
        $this->portrait = $portrait;
        $this->strength = $baseStats[StarKindredStatEnum::Strength->value];
        $this->dexterity = $baseStats[StarKindredStatEnum::Dexterity->value];
        $this->constitution = $baseStats[StarKindredStatEnum::Constitution->value];
        $this->intelligence = $baseStats[StarKindredStatEnum::Intelligence->value];
        $this->wisdom = $baseStats[StarKindredStatEnum::Wisdom->value];
        $this->charisma = $baseStats[StarKindredStatEnum::Charisma->value];
        $this->createdOn = $createdOn;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): Pet
    {
        return $this->pet;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRace(): StarKindredRaceEnum
    {
        return $this->race;
    }

    public function getCharacterClass(): StarKindredClassEnum
    {
        return $this->characterClass;
    }

    public function getPortrait(): string
    {
        return $this->portrait;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isMaxLevel(): bool
    {
        return $this->level >= self::MaxLevel;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public static function experienceToNextLevel(int $level): int
    {
        return 10 * $level;
    }

    /**
     * @return int The number of levels gained
     */
    public function gainExperience(int $amount): int
    {
        if($amount < 0)
            throw new \InvalidArgumentException('Experience gained cannot be negative.');

        if($this->isMaxLevel())
            return 0;

        $this->experience += $amount;
        $levelsGained = 0;

        while(!$this->isMaxLevel() && $this->experience >= self::experienceToNextLevel($this->level))
        {
            $this->experience -= self::experienceToNextLevel($this->level);
            $this->level++;
            $levelsGained++;
        }

        if($this->isMaxLevel())
            $this->experience = 0;

        return $levelsGained;
    }

    public function getBaseStat(StarKindredStatEnum $stat): int
    {
        return match($stat)
        {
            StarKindredStatEnum::Strength => $this->strength,
            StarKindredStatEnum::Dexterity => $this->dexterity,
            StarKindredStatEnum::Constitution => $this->constitution,
            StarKindredStatEnum::Intelligence => $this->intelligence,
            StarKindredStatEnum::Wisdom => $this->wisdom,
            StarKindredStatEnum::Charisma => $this->charisma,
        };
    }

    /**
     * Base stat, plus growth from the character's class: the primary stat grows every 2 levels,
     * the secondary every 3, and all others every 6.
     */
    public function getStat(StarKindredStatEnum $stat): int
    {
        $levelsGained = $this->level - 1;

        $growth = match($stat)
        {
            $this->characterClass->primaryStat() => intdiv($levelsGained, 2),
            $this->characterClass->secondaryStat() => intdiv($levelsGained, 3),
            default => intdiv($levelsGained, 6),
        };

        return $this->getBaseStat($stat) + $growth;
    }

    public function getStatModifier(StarKindredStatEnum $stat): int
    {
        return (int)floor(($this->getStat($stat) - 10) / 2);
    }

    public function isClassSkill(StarKindredSkillEnum $skill): bool
    {
        return in_array($skill, $this->characterClass->classSkills(), true);
    }

    public function getSkill(StarKindredSkillEnum $skill): int
    {
        $training = $this->isClassSkill($skill)
            ? $this->level + 2
            : intdiv($this->level, 2);

        return $this->getStatModifier($skill->stat()) + $training;
    }

    public function getAdventuresCompleted(): int
    {
        return $this->adventuresCompleted;
    }

    public function getAdventuresWon(): int
    {
        return $this->adventuresWon;
    }

    public function recordAdventure(bool $won): void
    {
        $this->adventuresCompleted++;

        if($won)
            $this->adventuresWon++;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getRetiredOn(): ?\DateTimeImmutable
    {
        return $this->retiredOn;
    }

    public function isRetired(): bool
    {
        return $this->retiredOn !== null;
    }

    public function getEpilogue(): ?string
    {
        return $this->epilogue;
    }

    public function retire(\DateTimeImmutable $retiredOn, string $epilogue): void
    {
        if($this->isRetired())
            throw new \LogicException('This character is already retired.');

        if(!$this->isMaxLevel())
            throw new \LogicException('Only max-level characters may retire.');

        $this->retiredOn = $retiredOn;
        $this->epilogue = $epilogue;
    }
}
