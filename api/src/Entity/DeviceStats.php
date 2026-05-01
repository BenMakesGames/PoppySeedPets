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
#[ORM\Index(name: 'time_idx', columns: ['time'])]
#[ORM\Entity]
class DeviceStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $time;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userAgent;

    #[ORM\Column(type: 'string', length: 10)]
    private string $language;

    #[ORM\Column(type: 'integer')]
    private int $touchPoints;

    #[ORM\Column(type: 'integer')]
    private int $windowWidth;

    #[ORM\Column(type: 'integer')]
    private int $screenWidth;

    public function __construct()
    {
        $this->time = new \DateTimeImmutable();
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

    public function getTime(): \DateTimeImmutable
    {
        return $this->time;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getTouchPoints(): int
    {
        return $this->touchPoints;
    }

    public function setTouchPoints(int $touchPoints): self
    {
        $this->touchPoints = $touchPoints;

        return $this;
    }

    public function getWindowWidth(): int
    {
        return $this->windowWidth;
    }

    public function setWindowWidth(int $windowWidth): self
    {
        $this->windowWidth = $windowWidth;

        return $this;
    }

    public function getScreenWidth(): int
    {
        return $this->screenWidth;
    }

    public function setScreenWidth(int $screenWidth): self
    {
        $this->screenWidth = $screenWidth;

        return $this;
    }
}
