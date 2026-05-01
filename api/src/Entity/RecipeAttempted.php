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

#[ORM\Entity]
class RecipeAttempted
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipe;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $firstAttemptedOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastAttemptedOn;

    #[ORM\Column(type: 'integer')]
    private int $timesAttempted = 1;

    public function __construct(User $user, string $recipe)
    {
        $this->user = $user;
        $this->recipe = $recipe;
        $this->firstAttemptedOn = new \DateTimeImmutable();
        $this->lastAttemptedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRecipe(): string
    {
        return $this->recipe;
    }

    public function getFirstAttemptedOn(): \DateTimeImmutable
    {
        return $this->firstAttemptedOn;
    }

    public function getLastAttemptedOn(): \DateTimeImmutable
    {
        return $this->lastAttemptedOn;
    }

    public function setLastAttemptedOn(): self
    {
        $this->lastAttemptedOn = new \DateTimeImmutable();

        return $this;
    }

    public function getTimesAttempted(): int
    {
        return $this->timesAttempted;
    }

    public function incrementTimesAttempted(): self
    {
        $this->timesAttempted++;

        return $this;
    }
}
