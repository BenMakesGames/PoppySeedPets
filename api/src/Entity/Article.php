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
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['article'])]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['article'])]
    private string $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['article'])]
    private string $body;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['article'])]
    private \DateTimeImmutable $createdOn;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['article'])]
    private User $author;

    #[ORM\ManyToMany(targetEntity: DesignGoal::class, inversedBy: 'articles')]
    #[Groups(['article'])]
    private Collection $designGoals;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['article'])]
    private ?string $imageUrl = null;

    public function __construct()
    {
        $this->createdOn = new \DateTimeImmutable();
        $this->designGoals = new ArrayCollection();
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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCreatedOn(): ?\DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, DesignGoal[]>
     */
    public function getDesignGoals(): Collection
    {
        return $this->designGoals;
    }

    public function addDesignGoal(DesignGoal $designGoal): self
    {
        if (!$this->designGoals->contains($designGoal)) {
            $this->designGoals[] = $designGoal;
        }

        return $this;
    }

    public function removeDesignGoal(DesignGoal $designGoal): self
    {
        $this->designGoals->removeElement($designGoal);

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }
}
