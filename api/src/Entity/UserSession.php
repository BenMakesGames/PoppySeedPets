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

use App\Functions\NumberFunctions;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 40, unique: true)]
    private string $sessionId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $sessionExpiration;

    public function __construct(User $user, string $sessionId, int $hoursToExpiration)
    {
        $this->user = $user;
        $this->sessionId = $sessionId;
        $this->setSessionExpiration($hoursToExpiration);
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

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getSessionExpiration(): \DateTimeImmutable
    {
        return $this->sessionExpiration;
    }

    public function setSessionExpiration(int $sessionHours): self
    {
        $sessionHours = NumberFunctions::clamp($sessionHours, 1, 7 * 24); // 1 hour to 1 week

        $this->sessionExpiration = new \DateTimeImmutable()->modify('+' . $sessionHours . ' hours');

        return $this;
    }
}
