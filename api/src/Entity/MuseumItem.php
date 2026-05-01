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

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'user_id_item_id_idx', columns: ['user_id', 'item_id'])]
class MuseumItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["museum"])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["museum"])]
    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'museumDonations')]
    #[ORM\JoinColumn(nullable: false)]
    private Item $item;

    #[Groups(["museum"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $donatedOn;

    #[Groups(["museum"])]
    #[ORM\Column(type: 'json')]
    private array $comments = [];

    #[Groups(["museum"])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    public function __construct(User $user, Item $item)
    {
        $this->user = $user;
        $this->item = $item;
        $this->donatedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getDonatedOn(): \DateTimeImmutable
    {
        return $this->donatedOn;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * @param string[] $comments
     */
    public function setComments(array $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
