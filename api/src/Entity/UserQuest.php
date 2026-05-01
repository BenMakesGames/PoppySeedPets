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

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'user_id_name_idx', columns: ['user_id', 'name'])]
#[ORM\Entity]
class UserQuest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'json')]
    private $value;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastUpdated;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    public function __construct(User $user, string $name, $value)
    {
        $this->user = $user;
        $this->name = $name;
        $this->value = $value;
        $this->lastUpdated = new \DateTimeImmutable();
        $this->createdOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function hasId(): bool { return $this->id !== null; }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getIntValue(): int
    {
        return is_int($this->value)
            ? $this->value
            : throw new \RuntimeException('Value must be an integer');
    }

    public function setValue(mixed $value): self
    {
        $this->lastUpdated = new \DateTimeImmutable();
        $this->value = $value;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getLastUpdated(): \DateTimeImmutable
    {
        return $this->lastUpdated;
    }
}
