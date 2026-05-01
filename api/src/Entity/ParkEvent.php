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
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ParkEvent
{
    #[Groups(["parkEvent"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["parkEvent"])]
    #[ORM\ManyToMany(targetEntity: Pet::class)]
    private Collection $participants;

    #[Groups(["parkEvent"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $type;

    #[Groups(["parkEvent"])]
    #[ORM\Column(type: 'text')]
    private string $results;

    #[Groups(["parkEvent"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->date = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    /**
     * @return Collection<int, Pet>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * @param Pet[] $pets
     */
    public function addParticipants(array $pets): self
    {
        foreach($pets as $pet)
            $this->addParticipant($pet);

        return $this;
    }

    public function addParticipant(Pet $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants[] = $participant;
        }

        return $this;
    }

    public function removeParticipant(Pet $participant): self
    {
        if ($this->participants->contains($participant)) {
            $this->participants->removeElement($participant);
        }

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getResults(): ?string
    {
        return $this->results;
    }

    public function setResults(string $results): self
    {
        $this->results = $results;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }
}
