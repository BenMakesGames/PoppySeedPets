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

use App\Enum\EnumInvalidValueException;
use App\Enum\StatusEffectEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'time_remaining_idx', columns: ['time_remaining'])]
#[ORM\Entity]
class StatusEffect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pet::class, inversedBy: 'statusEffects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pet $pet;

    #[ORM\Column(type: 'string', length: 40, enumType: StatusEffectEnum::class)]
    #[Groups(['myPet'])]
    private StatusEffectEnum $status;

    #[ORM\Column(type: 'integer')]
    private int $totalDuration = 0;

    #[ORM\Column(type: 'integer')]
    private int $timeRemaining = 0;

    #[ORM\Column(type: 'integer')]
    private int $counter = 0;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getStatus(): StatusEffectEnum
    {
        return $this->status;
    }

    public function setStatus(StatusEffectEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(int $totalDuration): self
    {
        $this->totalDuration = $totalDuration;

        return $this;
    }

    public function getTimeRemaining(): int
    {
        return $this->timeRemaining;
    }

    public function setTimeRemaining(int $timeRemaining): self
    {
        $this->timeRemaining = $timeRemaining;

        return $this;
    }

    public function spendTime(int $time): self
    {
        // these status effects don't go away unless/until something SPECIAL happens:
        if(in_array($this->status, [
            StatusEffectEnum::BubbleGumd,
            StatusEffectEnum::OilCovered,

            StatusEffectEnum::BittenByAVampire,
            StatusEffectEnum::BittenByAWerecreature,
            StatusEffectEnum::Wereform,

            StatusEffectEnum::FocusedNature,
            StatusEffectEnum::FocusedScience,
            StatusEffectEnum::FocusedStealth,
            StatusEffectEnum::FocusedArcana,
            StatusEffectEnum::FocusedCrafts,
            StatusEffectEnum::FocusedBrawl,
            StatusEffectEnum::FocusedMusic,

            StatusEffectEnum::DaydreamingIceCream,
            StatusEffectEnum::DaydreamingPizza,
            StatusEffectEnum::DaydreamingFoodFight,
            StatusEffectEnum::DaydreamingNoodles,

            StatusEffectEnum::FatedDeliciously,
            StatusEffectEnum::FatedSoakedly,
            StatusEffectEnum::FatedElectrically,
            StatusEffectEnum::FatedFerally,
            StatusEffectEnum::FatedLunarly,
            StatusEffectEnum::FatedCheesewardly,

            StatusEffectEnum::LapineWhispers,
            StatusEffectEnum::Oneiric,
            StatusEffectEnum::GobbleGobble,
        ]))
        {
            return $this;
        }

        $this->timeRemaining -= $time;

        return $this;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function incrementCounter(): self
    {
        $this->counter++;

        return $this;
    }
}
