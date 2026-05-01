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

use App\Enum\DragonHostageTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class DragonHostage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Dragon::class, inversedBy: 'hostage', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private Dragon $dragon;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 40, enumType: DragonHostageTypeEnum::class)]
    private DragonHostageTypeEnum $type;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $appearance;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $dialog;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $colorA;

    #[Groups(["myDragon"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $colorB;

    public function __construct(Dragon $dragon, DragonHostageTypeEnum $type, string $name, string $appearance, string $dialog, string $colorA, string $colorB)
    {
        $this->dragon = $dragon;
        $this->type = $type;
        $this->name = $name;
        $this->appearance = $appearance;
        $this->dialog = $dialog;
        $this->colorA = $colorA;
        $this->colorB = $colorB;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getDragon(): ?Dragon
    {
        return $this->dragon;
    }

    public function setDragon(Dragon $dragon): self
    {
        $this->dragon = $dragon;

        return $this;
    }

    public function getType(): DragonHostageTypeEnum
    {
        return $this->type;
    }

    public function setType(DragonHostageTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAppearance(): ?string
    {
        return $this->appearance;
    }

    public function setAppearance(string $appearance): self
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function getDialog(): ?string
    {
        return $this->dialog;
    }

    public function setDialog(string $dialog): self
    {
        $this->dialog = $dialog;

        return $this;
    }

    public function getColorA(): ?string
    {
        return $this->colorA;
    }

    public function setColorA(string $colorA): self
    {
        $this->colorA = $colorA;

        return $this;
    }

    public function getColorB(): ?string
    {
        return $this->colorB;
    }

    public function setColorB(string $colorB): self
    {
        $this->colorB = $colorB;

        return $this;
    }
}
