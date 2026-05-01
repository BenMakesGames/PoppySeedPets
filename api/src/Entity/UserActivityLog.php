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

#[ORM\Entity]
class UserActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["userActivityLogs"])]
    #[ORM\Column(type: 'text')]
    private string $entry;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    /**
     * @var Collection<int, UserActivityLogTag>
     */
    #[Groups(["userActivityLogs"])]
    #[ORM\ManyToMany(targetEntity: UserActivityLogTag::class)]
    private Collection $tags;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->tags = new ArrayCollection();
        $this->createdOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEntry(): ?string
    {
        return $this->entry;
    }

    public function setEntry(string $entry): self
    {
        $this->entry = $entry;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    /**
     * @return Collection<int, UserActivityLogTag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param UserActivityLogTag[] $tags
     */
    public function addTags(array $tags): self
    {
        foreach($tags as $tag)
            $this->addTag($tag);

        return $this;
    }

    public function addTag(UserActivityLogTag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(UserActivityLogTag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
