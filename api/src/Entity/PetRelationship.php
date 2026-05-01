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

use App\Enum\MeritEnum;
use App\Enum\RelationshipEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Table]
#[ORM\Index(name: 'commitment_idx', columns: ['commitment'])]
#[ORM\Index(name: 'current_relationship_idx', columns: ['current_relationship'])]
#[ORM\Entity]
class PetRelationship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Pet::class, inversedBy: 'petRelationships')]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[Groups(["petFriend"])]
    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $relationship;

    #[ORM\Column(type: 'string', length: 255)]
    private string $metDescription;

    #[Groups(["petFriend"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $metOn;

    #[Groups(["petFriend"])]
    #[ORM\Column(type: 'string', length: 40, enumType: RelationshipEnum::class)]
    private RelationshipEnum $currentRelationship;

    #[ORM\Column(type: 'string', length: 40, enumType: RelationshipEnum::class)]
    private RelationshipEnum $relationshipGoal;

    #[ORM\Column(type: 'integer')]
    private int $timeUntilChange;

    #[Groups(["petFriend"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastMet;

    #[ORM\Column(type: 'integer')]
    private int $commitment = 0;

    #[Groups(["petFriend"])]
    #[SerializedName("commitment")]
    #[ORM\Column(type: 'smallint')]
    private int $rating = 0;

    public function __construct(Pet $pet, Pet $otherPet, RelationshipEnum $currentRelationship, RelationshipEnum $relationshipGoal)
    {
        $this->metOn = new \DateTimeImmutable();
        $this->lastMet = new \DateTimeImmutable();
        $this->timeUntilChange = random_int(random_int(20, 30), random_int(50, 80));

        $this->pet = $pet;
        $this->relationship = $otherPet;
        $this->currentRelationship = $currentRelationship;
        $this->relationshipGoal = $relationshipGoal;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    #[Groups(["petFriend"])]
    public function getRelationshipWanted(): ?RelationshipEnum
    {
        if($this->pet->hasMerit(MeritEnum::INTROSPECTIVE))
            return $this->relationshipGoal;
        else
            return null;
    }

    public function getPet(): Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getRelationship(): Pet
    {
        return $this->relationship;
    }

    public function setRelationship(Pet $relationship): self
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function getMetDescription(): string
    {
        return $this->metDescription;
    }

    public function setMetDescription(string $metDescription): self
    {
        $this->metDescription = $metDescription;

        return $this;
    }

    public function getMetOn(): \DateTimeImmutable
    {
        return $this->metOn;
    }

    public function getCurrentRelationship(): RelationshipEnum
    {
        return $this->currentRelationship;
    }

    public function setCurrentRelationship(RelationshipEnum $currentRelationship): self
    {
        $this->currentRelationship = $currentRelationship;

        return $this;
    }

    public function getRelationshipGoal(): RelationshipEnum
    {
        return $this->relationshipGoal;
    }

    public function setRelationshipGoal(RelationshipEnum $relationshipGoal): self
    {
        $this->relationshipGoal = $relationshipGoal;

        return $this;
    }

    public function getTimeUntilChange(): int
    {
        return $this->timeUntilChange;
    }

    public function setTimeUntilChange(): void
    {
        $this->timeUntilChange = random_int(random_int(20, 30), random_int(40, 50));
    }

    public function decrementTimeUntilChange(float $multiplier = 1): self
    {
        if($this->wantsDifferentRelationship())
        {
            if($this->pet->hasMerit(MeritEnum::INTROSPECTIVE))
                $this->timeUntilChange -= (int)ceil(3 * $multiplier);
            else
                $this->timeUntilChange -= (int)ceil(2 * $multiplier);
        }

        return $this;
    }

    public function wantsDifferentRelationship(): bool
    {
        return $this->currentRelationship !== $this->relationshipGoal;
    }

    public function getHappiness(): int
    {
        return match ($this->currentRelationship)
        {
            RelationshipEnum::BrokeUp => -80,
            RelationshipEnum::Dislike => -35,
            RelationshipEnum::FriendlyRival => 0,
            RelationshipEnum::Friend => 35,
            RelationshipEnum::BFF => 65,
            RelationshipEnum::FWB => 70,
            RelationshipEnum::Mate => 100,
            default => 0,
        };
    }

    public function getLastMet(): \DateTimeImmutable
    {
        return $this->lastMet;
    }

    public function setLastMet(): self
    {
        $this->lastMet = new \DateTimeImmutable();

        return $this;
    }

    public function getCommitment(): int
    {
        return $this->commitment;
    }

    public function setCommitment(int $commitment): self
    {
        $this->commitment = max(0, $commitment);

        return $this;
    }

    public function increaseCommitment(int $amount): self
    {
        $this->commitment = max(0, $this->commitment + $amount);

        return $this;
    }

    #[Groups(['petFriend'])]
    #[SerializedName('metDescription')]
    public function getFormattedMetDescription(): string
    {
        return strtr(
            $this->getMetDescription(),
            [
                '%pet.name%' => $this->getPet()->getName(),
                '%relationship.name%' => $this->getRelationship()->getName()
            ]
        );
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;

        return $this;
    }
}
