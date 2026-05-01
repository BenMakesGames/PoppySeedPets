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
use App\Service\PetExperienceService;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'activity_time_idx', columns: ['activity_time'])]
#[ORM\Index(name: 'social_energy_idx', columns: ['social_energy'])]
#[ORM\Entity]
class PetHouseTime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Pet::class, inversedBy: 'houseTime', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[ORM\Column(type: 'integer')]
    #[Groups(['myPet'])]
    private int $activityTime = 59;

    #[ORM\Column(type: 'integer')]
    private int $socialEnergy = 0;

    #[ORM\Column(type: 'integer')]
    private int $timeSpent = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $canAttemptSocialHangoutAfter;

    public function __construct(Pet $pet)
    {
        $this->pet = $pet;
        $this->canAttemptSocialHangoutAfter = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getActivityTime(): int
    {
        return $this->activityTime;
    }

    public function setActivityTime(int $activityTime): self
    {
        $this->activityTime = $activityTime;

        return $this;
    }

    public function spendActivityTime(int $amount): self
    {
        $this->activityTime -= $amount;
        $this->timeSpent += $amount;

        return $this;
    }

    public function getSocialEnergy(): int
    {
        return $this->socialEnergy;
    }

    public function setSocialEnergy(int $initialSocialEnergy): self
    {
        $this->socialEnergy = $initialSocialEnergy;
        return $this;
    }

    public function spendSocialEnergy(int $amount): self
    {
        $this->socialEnergy = NumberFunctions::clamp(
            $this->socialEnergy - $amount,
            -PetExperienceService::SocialEnergyPerHangOut,
            PetExperienceService::SocialEnergyPerHangOut * 5
        );

        return $this;
    }

    public function getTimeSpent(): int
    {
        return $this->timeSpent;
    }

    public function getCanAttemptSocialHangoutAfter(): \DateTimeImmutable
    {
        return $this->canAttemptSocialHangoutAfter;
    }

    public function setCanAttemptSocialHangoutAfter(\DateTimeImmutable $dateTime): self
    {
        $this->canAttemptSocialHangoutAfter = $dateTime;

        return $this;
    }
}
