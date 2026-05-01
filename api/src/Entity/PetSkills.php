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

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity()]
#[ORM\Index(columns: ['level'], name: 'level_idx')]
class PetSkills
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $strength = 0;

    #[ORM\Column(type: 'integer')]
    private int $dexterity = 0;

    #[ORM\Column(type: 'integer')]
    private int $intelligence = 0;

    #[ORM\Column(type: 'integer')]
    private int $perception = 0;

    #[ORM\Column(type: 'integer')]
    private int $stealth = 0;

    #[ORM\Column(type: 'integer')]
    private int $stamina = 0;

    #[ORM\Column(type: 'integer')]
    private int $nature = 0;

    #[ORM\Column(type: 'integer')]
    private int $brawl = 0;

    #[ORM\Column(type: 'integer')]
    private int $arcana = 0;

    #[ORM\Column(type: 'integer')]
    private int $crafts = 0;

    #[ORM\Column(type: 'integer')]
    private int $music = 0;

    #[ORM\Column(type: 'integer')]
    private int $science = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $talent = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expertise = null;

    #[ORM\Column(type: 'integer')]
    private int $scrollLevels = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups(['myPet', 'petPublicProfile'])]
    private int $level = 0;

    public function __construct()
    {
        for($x = 0; $x < 5; $x++)
        {
            switch(random_int(1, 5))
            {
                case 1: $this->strength++; break;
                case 2: $this->intelligence++; break;
                case 3: $this->stamina++; break;
                case 4: $this->perception++; break;
                case 5: $this->dexterity++; break;
            }
        }

        $this->computeLevel();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * Also called in code, by increaseStat and decreaseStat, to ensure the updated level
     * is available immediately in-code.
     */
    public function computeLevel(): void
    {
        $this->level =
            $this->stealth +
            $this->nature +
            $this->brawl +
            $this->arcana +
            $this->crafts +
            $this->music +
            $this->science
        ;
    }

    public function getStat(string $statName): int
    {
        if($statName === 'id' || $statName === 'pet' || !property_exists($this, $statName))
            throw new \InvalidArgumentException('Unknown stat "' . $statName . '".');

        return $this->{$statName};
    }

    public function increaseStat(string $statName): self
    {
        if($statName === 'id' || $statName === 'pet' || !property_exists($this, $statName))
            throw new \InvalidArgumentException('Unknown stat "' . $statName . '".');

        if($this->{$statName} < 20)
        {
            $this->{$statName}++;
            $this->computeLevel();
        }

        return $this;
    }

    public function decreaseStat(string $statName): self
    {
        if($statName === 'id' || $statName === 'pet' || !property_exists($this, $statName))
            throw new \InvalidArgumentException('Unknown stat "' . $statName . '".');

        if($this->{$statName} > 0)
        {
            $this->{$statName}--;
            $this->computeLevel();
        }

        return $this;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): self
    {
        $this->strength = $strength;

        return $this;
    }

    public function getDexterity(): int
    {
        return $this->dexterity;
    }

    public function setDexterity(int $dexterity): self
    {
        $this->dexterity = $dexterity;

        return $this;
    }

    public function getIntelligence(): int
    {
        return $this->intelligence;
    }

    public function setIntelligence(int $intelligence): self
    {
        $this->intelligence = $intelligence;

        return $this;
    }

    public function getPerception(): int
    {
        return $this->perception;
    }

    public function setPerception(int $perception): self
    {
        $this->perception = $perception;

        return $this;
    }

    public function getStealth(): int
    {
        return $this->stealth;
    }

    public function setStealth(int $stealth): self
    {
        $this->stealth = $stealth;

        return $this;
    }

    public function getStamina(): int
    {
        return $this->stamina;
    }

    public function setStamina(int $stamina): self
    {
        $this->stamina = $stamina;

        return $this;
    }

    public function getNature(): int
    {
        return $this->nature;
    }

    public function setNature(int $nature): self
    {
        $this->nature = $nature;

        return $this;
    }

    public function getBrawl(): int
    {
        return $this->brawl;
    }

    public function setBrawl(int $brawl): self
    {
        $this->brawl = $brawl;

        return $this;
    }

    public function getArcana(): int
    {
        return $this->arcana;
    }

    public function setArcana(int $arcana): self
    {
        $this->arcana = $arcana;

        return $this;
    }

    public function getCrafts(): int
    {
        return $this->crafts;
    }

    public function setCrafts(int $crafts): self
    {
        $this->crafts = $crafts;

        return $this;
    }

    public function getScience(): int
    {
        return $this->science;
    }

    public function setScience(int $science): self
    {
        $this->science = $science;

        return $this;
    }

    public function getMusic(): int
    {
        return $this->music;
    }

    public function setMusic(int $music): self
    {
        $this->music = $music;

        return $this;
    }

    public function getTalent(): ?\DateTimeImmutable
    {
        return $this->talent;
    }

    public function setTalent(): self
    {
        $this->talent = new \DateTimeImmutable();

        return $this;
    }

    public function getExpertise(): ?\DateTimeImmutable
    {
        return $this->expertise;
    }

    public function setExpertise(): self
    {
        $this->expertise = new \DateTimeImmutable();

        return $this;
    }

    public function getScrollLevels(): ?int
    {
        return $this->scrollLevels;
    }

    public function increaseScrollLevels(): self
    {
        $this->scrollLevels++;

        return $this;
    }
}
