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

use App\Enum\UnlockableFeatureEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'user_id_feature_idx', columns: ['user_id', 'feature'])]
#[ORM\Entity]
class UserUnlockedFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'unlockedFeatures')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'string', length: 40, enumType: UnlockableFeatureEnum::class)]
    private UnlockableFeatureEnum $feature;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedOn;

    public function __construct(User $user, UnlockableFeatureEnum $feature)
    {
        $this->user = $user;
        $this->feature = $feature;
        $this->unlockedOn = new \DateTimeImmutable();
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

    public function getFeature(): UnlockableFeatureEnum
    {
        return $this->feature;
    }

    public function setFeature(UnlockableFeatureEnum $feature): self
    {
        $this->feature = $feature;

        return $this;
    }

    public function getUnlockedOn(): \DateTimeImmutable
    {
        return $this->unlockedOn;
    }
}
