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
class UserMonthlyStoryAdventureStepCompleted
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups([ "starKindredStoryStepComplete" ])]
    #[ORM\ManyToOne(targetEntity: MonthlyStoryAdventureStep::class)]
    #[ORM\JoinColumn(nullable: false)]
    private MonthlyStoryAdventureStep $adventureStep;

    #[Groups([ "starKindredStoryStepComplete" ])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $completedOn;

    public function __construct(User $user, MonthlyStoryAdventureStep $adventureStep)
    {
        $this->user = $user;
        $this->adventureStep = $adventureStep;
        $this->completedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAdventureStep(): MonthlyStoryAdventureStep
    {
        return $this->adventureStep;
    }

    public function getCompletedOn(): \DateTimeImmutable
    {
        return $this->completedOn;
    }
}
