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

/**
 * A player's most-recent ★Kindred play: one row per player, overwritten each day they play. Only
 * today's play matters (it's what stops a second play, and what the main page shows), so no history
 * is kept.
 */
#[ORM\Entity]
class StarKindredDailyPlay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType, property.onlyWritten */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $playedOn;

    /**
     * Null if the day was spent retiring adventurers, instead of going on one of the day's adventures.
     *
     * @see \App\Model\StarKindred\StarKindredAdventure::$id
     */
    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $adventureId;

    /**
     * How many of the adventure's reward tiers were won: 0 on a defeat, else 1 (Novice) to 4 (Demigod).
     */
    #[ORM\Column(type: 'smallint')]
    private int $rewardsWon = 0;

    public function __construct(User $user, \DateTimeImmutable $playedOn, ?string $adventureId)
    {
        $this->user = $user;
        $this->playedOn = $playedOn;
        $this->adventureId = $adventureId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPlayedOn(): \DateTimeImmutable
    {
        return $this->playedOn;
    }

    public function getAdventureId(): ?string
    {
        return $this->adventureId;
    }

    public function getRewardsWon(): int
    {
        return $this->rewardsWon;
    }

    public function isOn(\DateTimeImmutable $date): bool
    {
        return $this->playedOn->format('Y-m-d') === $date->format('Y-m-d');
    }

    /**
     * Starts a new day's play, forgetting the previous one.
     */
    public function replay(\DateTimeImmutable $playedOn, ?string $adventureId): void
    {
        $this->playedOn = $playedOn;
        $this->adventureId = $adventureId;
        $this->rewardsWon = 0;
    }

    public function setRewardsWon(int $rewardsWon): void
    {
        if($rewardsWon < 0)
            throw new \InvalidArgumentException('rewardsWon cannot be negative.');

        $this->rewardsWon = $rewardsWon;
    }
}
