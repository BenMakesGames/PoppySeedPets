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
use App\Enum\PetGroupTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'created_on_idx', columns: ['created_on'])]
#[ORM\Index(name: 'last_met_on_idx', columns: ['last_met_on'])]
#[ORM\Index(name: 'type_idx', columns: ['type'])]
#[ORM\Index(name: 'name_idx', columns: ['name'])]
#[ORM\Index(name: 'social_energy_idx', columns: ['social_energy'])]
#[ORM\Entity]
class PetGroup
{
    #[Groups(["petGroup", "petGroupDetails", "petGroupIndex", "petPublicProfile"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["petGroupDetails"])]
    #[ORM\ManyToMany(targetEntity: Pet::class, inversedBy: 'groups')]
    private Collection $members;

    #[Groups(["petGroup", "petGroupDetails", "petGroupIndex", "petPublicProfile"])]
    #[ORM\Column(type: 'integer', enumType: PetGroupTypeEnum::class)]
    private PetGroupTypeEnum $type;

    #[Groups(["petGroup"])]
    #[ORM\Column(type: 'integer')]
    private int $progress = 0;

    #[ORM\Column(type: 'integer')]
    private int $skillRollTotal = 0;

    #[Groups(["petGroupDetails", "petGroupIndex", "petPublicProfile"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[Groups(["petGroup", "petGroupDetails", "petGroupIndex", "petPublicProfile"])]
    #[ORM\Column(type: 'string', length: 60)]
    private string $name;

    #[Groups(["petGroupDetails", "petGroupIndex"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastMetOn;

    #[Groups(["petGroupDetails"])]
    #[ORM\Column(type: 'integer')]
    private int $numberOfProducts = 0;

    #[ORM\Column(type: 'integer')]
    private int $socialEnergy = 0;

    public function __construct(PetGroupTypeEnum $type, string $name)
    {
        $this->type = $type;
        $this->name = $name;
        $this->members = new ArrayCollection();
        $this->createdOn = new \DateTimeImmutable();
        $this->lastMetOn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Pet>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Pet $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
        }

        return $this;
    }

    public function removeMember(Pet $member): self
    {
        if ($this->members->contains($member)) {
            $this->members->removeElement($member);
        }

        return $this;
    }

    public function getType(): PetGroupTypeEnum
    {
        return $this->type;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function increaseProgress(int $progress): self
    {
        $this->progress += $progress;

        return $this;
    }

    public function clearProgress(): self
    {
        $this->progress = 0;
        $this->skillRollTotal = 0;

        return $this;
    }

    public function getSkillRollTotal(): int
    {
        return $this->skillRollTotal;
    }

    public function increaseSkillRollTotal(int $skillRoll): self
    {
        $this->skillRollTotal += $skillRoll;

        return $this;
    }

    public function getCreatedOn(): ?\DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastMetOn(): \DateTimeImmutable
    {
        return $this->lastMetOn;
    }

    public function setLastMetOn(): self
    {
        $this->lastMetOn = new \DateTimeImmutable();

        return $this;
    }

    public function getMinimumSize(): int
    {
        return match ($this->type)
        {
            PetGroupTypeEnum::Band => 2,
            PetGroupTypeEnum::Astronomy => 2,
            PetGroupTypeEnum::Gaming => 3,
            PetGroupTypeEnum::Sportsball => 4,
            PetGroupTypeEnum::Gardening => 2,
            //default => throw new \Exception('Unhandled group type in group::getMinimumSize'),
        };
    }

    public function getMaximumSize(): int
    {
        return match ($this->type)
        {
            PetGroupTypeEnum::Band => 5,
            PetGroupTypeEnum::Astronomy => 6,
            PetGroupTypeEnum::Gaming => 5,
            PetGroupTypeEnum::Sportsball => 8,
            PetGroupTypeEnum::Gardening => 6,
            //default => throw new \Exception('Unhandled group type in group::getMaximumSize'),
        };
    }

    #[Groups(["petPublicProfile"])]
    public function getMemberCount(): int
    {
        return $this->members->count();
    }

    public function getNumberOfProducts(): int
    {
        return $this->numberOfProducts;
    }

    public function increaseNumberOfProducts(): self
    {
        $this->numberOfProducts += 1;

        return $this;
    }

    public function getSocialEnergy(): int
    {
        return $this->socialEnergy;
    }

    public function spendSocialEnergy(int $socialEnergy): self
    {
        $this->socialEnergy -= $socialEnergy;

        return $this;
    }

    #[Groups(["petGroup", "petGroupDetails"])]
    public function getMakesStuff(): bool
    {
        return $this->type === PetGroupTypeEnum::Band || $this->type === PetGroupTypeEnum::Astronomy 
            || $this->type === PetGroupTypeEnum::Gardening;
    }
}
