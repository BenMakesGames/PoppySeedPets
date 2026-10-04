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
use App\Enum\StarKindredSchoolOfMagicEnum;
use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredStatEnum;
use App\Model\StarKindred\StarKindredAnimalCompanion;
use App\Model\StarKindred\StarKindredEncounter;
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

    // Clerics & Paladins; derived from class & level, so not stored in $classFeatures
    public const int BanishUndeadLevel = 2;
    public const int BanishUndeadBonus = 4;

    // Bards learn a song at each of these levels; each song is for a different skill (never Stealth), and
    // grants a bonus to it
    public const array SongLevels = [ 1, 6, 11, 16 ];
    public const int SongBonus = 1;

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

    /**
     * A trained skill the pet picked for itself at creation, on top of its class skills.
     */
    #[ORM\Column(type: 'string', length: 20, enumType: StarKindredSkillEnum::class)]
    private StarKindredSkillEnum $chosenSkill;

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
     * Class-specific extras, keyed by feature: "animalCompanion" (Rangers & Druids); "schoolOfMagic" (Wizards); "songs" (Bards).
     * @var array{animalCompanion?: array{name: string, species: string}, schoolOfMagic?: value-of<StarKindredSchoolOfMagicEnum>, songs?: list<value-of<StarKindredSkillEnum>>}
     */
    #[ORM\Column(type: 'json')]
    private array $classFeatures = [];

    /**
     * @param array<value-of<StarKindredStatEnum>, int> $baseStats
     */
    public function __construct(
        Pet $pet, string $name, StarKindredRaceEnum $race, StarKindredClassEnum $characterClass, string $portrait,
        StarKindredSkillEnum $chosenSkill, array $baseStats, \DateTimeImmutable $createdOn
    )
    {
        if(in_array($chosenSkill, $characterClass->classSkills(), true))
            throw new \InvalidArgumentException("{$chosenSkill->value} is already a {$characterClass->value} class skill.");

        $this->pet = $pet;
        $this->name = $name;
        $this->race = $race;
        $this->characterClass = $characterClass;
        $this->portrait = $portrait;
        $this->chosenSkill = $chosenSkill;
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

    public function getChosenSkill(): StarKindredSkillEnum
    {
        return $this->chosenSkill;
    }

    public function isTrainedSkill(StarKindredSkillEnum $skill): bool
    {
        return $skill === $this->chosenSkill || $this->isClassSkill($skill) || $skill === $this->getSchoolOfMagic()?->skill();
    }

    public function getSkill(StarKindredSkillEnum $skill): int
    {
        $training = $this->isTrainedSkill($skill)
            ? $this->level + 2
            : intdiv($this->level, 2);

        $songBonus = in_array($skill, $this->getSongs(), true) ? self::SongBonus : 0;

        return $this->getStatModifier($skill->stat()) + $training + $songBonus;
    }

    public function canBanishUndead(): bool
    {
        return $this->characterClass->canBanishUndead() && $this->level >= self::BanishUndeadLevel;
    }

    /**
     * The character's full bonus to an encounter's roll: its skill, plus any class features that apply.
     */
    public function getEncounterBonus(StarKindredEncounter $encounter): int
    {
        $bonus = $this->getSkill($encounter->skill);

        if($encounter->againstUndead && $this->canBanishUndead())
            $bonus += self::BanishUndeadBonus;

        return $bonus;
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

    /**
     * @return array{animalCompanion?: array{name: string, species: string}, schoolOfMagic?: value-of<StarKindredSchoolOfMagicEnum>, songs?: list<value-of<StarKindredSkillEnum>>}
     */
    public function getClassFeatures(): array
    {
        return $this->classFeatures;
    }

    public function getAnimalCompanion(): ?StarKindredAnimalCompanion
    {
        return isset($this->classFeatures['animalCompanion'])
            ? StarKindredAnimalCompanion::fromArray($this->classFeatures['animalCompanion'])
            : null;
    }

    public function gainAnimalCompanion(StarKindredAnimalCompanion $companion): void
    {
        if(!$this->characterClass->hasAnimalCompanion())
            throw new \LogicException($this->characterClass->value . 's do not get animal companions.');

        if($this->getAnimalCompanion() !== null)
            throw new \LogicException('This character already has an animal companion.');

        $this->classFeatures['animalCompanion'] = $companion->toArray();
    }

    public function getSchoolOfMagic(): ?StarKindredSchoolOfMagicEnum
    {
        return isset($this->classFeatures['schoolOfMagic'])
            ? StarKindredSchoolOfMagicEnum::from($this->classFeatures['schoolOfMagic'])
            : null;
    }

    public function chooseSchoolOfMagic(StarKindredSchoolOfMagicEnum $school): void
    {
        if($this->characterClass !== StarKindredClassEnum::Wizard)
            throw new \LogicException($this->characterClass->value . 's do not specialize in schools of magic.');

        if($this->getSchoolOfMagic() !== null)
            throw new \LogicException('This character has already specialized in a school of magic.');

        if($this->isTrainedSkill($school->skill()))
            throw new \LogicException("{$school->skill()->value} is already a trained skill for this character.");

        $this->classFeatures['schoolOfMagic'] = $school->value;
    }

    /**
     * @return StarKindredSkillEnum[] The skills the character's songs are for, in the order they were learned
     */
    public function getSongs(): array
    {
        return array_map(
            fn(string $skill) => StarKindredSkillEnum::from($skill),
            $this->classFeatures['songs'] ?? []
        );
    }

    /**
     * @return StarKindredSkillEnum[] The skills the character could learn a song for
     */
    public function getLearnableSongs(): array
    {
        if($this->characterClass !== StarKindredClassEnum::Bard)
            return [];

        return array_values(array_filter(
            StarKindredSkillEnum::cases(),
            fn(StarKindredSkillEnum $skill) => $skill !== StarKindredSkillEnum::Stealth && !in_array($skill, $this->getSongs(), true)
        ));
    }

    /**
     * The number of songs the character has reached the level for, but not yet learned.
     */
    public function getSongsToLearn(): int
    {
        if($this->characterClass !== StarKindredClassEnum::Bard)
            return 0;

        $songsEarned = count(array_filter(self::SongLevels, fn(int $level) => $this->level >= $level));

        return $songsEarned - count($this->getSongs());
    }

    public function learnSong(StarKindredSkillEnum $skill): void
    {
        if($this->getSongsToLearn() <= 0)
            throw new \LogicException('This character has no songs to learn.');

        if(!in_array($skill, $this->getLearnableSongs(), true))
            throw new \LogicException("This character cannot learn a song for {$skill->value}.");

        $this->classFeatures['songs'] = [ ...($this->classFeatures['songs'] ?? []), $skill->value ];
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
