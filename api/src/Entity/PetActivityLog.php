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

use App\Enum\PetActivityLogInterestingness;
use App\Model\PetChangesSummary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'created_on_idx', columns: ['created_on'])]
#[ORM\Entity]
class PetActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["petActivityLogAndPublicPet"])]
    #[ORM\ManyToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'text')]
    private string $entry;

    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'pet_changes_summary', nullable: true)]
    private ?PetChangesSummary $changes = null;

    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'string', length: 100)]
    private string $icon = '';

    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'integer')]
    private int $interestingness = 0;

    #[Groups(["petActivityLogAndPublicPet"])]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    private ?Item $equippedItem;

    /**
     * @var Collection<int, PetActivityLogTag>
     */
    #[Groups(["petActivityLogs", "petActivityLogAndPublicPet"])]
    #[ORM\ManyToMany(targetEntity: PetActivityLogTag::class)]
    private Collection $tags;

    /**
     * @var Collection<int, PetActivityLogItem>
     */
    #[Groups(["petActivityLogAndPublicPet"])]
    #[ORM\OneToMany(mappedBy: 'log', targetEntity: PetActivityLogItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $createdItems;

    public function __construct(Pet $pet, string $entry)
    {
        $this->pet = $pet;
        $this->entry = $entry;
        $this->createdOn = new \DateTimeImmutable();
        $this->tags = new ArrayCollection();
        $this->createdItems = new ArrayCollection();
        $this->equippedItem = $pet->getTool()?->getItem();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): Pet
    {
        return $this->pet;
    }

    public function getEntry(): string
    {
        return $this->entry;
    }

    public function setEntry(string $entry): self
    {
        $this->entry = $entry;

        return $this;
    }

    public function appendEntry(string $entry): self
    {
        if($this->entry)
            $this->entry .= ' ' . $entry;
        else
            $this->entry = $entry;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getChanges(): ?PetChangesSummary
    {
        return $this->changes;
    }

    public function setChanges(?PetChangesSummary $changes): self
    {
        $this->changes = $changes;

        if($changes !== null && (!!$changes->level || !!$changes->affectionLevel))
            $this->addInterestingness(PetActivityLogInterestingness::LevelUp);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getInterestingness(): int
    {
        return $this->interestingness;
    }

    public function addInterestingness(int $interestingness): self
    {
        if($interestingness > $this->interestingness)
            $this->interestingness = $interestingness;

        return $this;
    }

    public function getEquippedItem(): ?Item
    {
        return $this->equippedItem;
    }

    /**
     * @return Collection<int, PetActivityLogTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param PetActivityLogTag[] $tags
     */
    public function addTags(array $tags): self
    {
        foreach($tags as $tag)
            $this->addTag($tag);

        return $this;
    }

    public function addTag(PetActivityLogTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * @return Collection<int, PetActivityLogItem>
     */
    public function getCreatedItems(): Collection
    {
        return $this->createdItems;
    }

    public function addCreatedItem(Item $item): static
    {
        if ($this->createdItems->exists(fn(int $key, PetActivityLogItem $createdItem) => $createdItem->getItem()->getId() === $item->getId()))
            return $this;

        $createdItem = new PetActivityLogItem($this, $item);

        $this->createdItems->add($createdItem);

        return $this;
    }
}
