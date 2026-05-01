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

use App\Service\IRandom;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CookingBuddy
{
    public const array Names = [
        'Asparagus', 'Arugula',
        'Biryani', 'Bisque',
        'Cake', 'Ceviche',
        'Cookie', 'Couscous',
        'Dal',
        'Egg Roll', 'Edamame',
        'Falafel',
        'Gnocchi', 'Gobi', 'Goulash', 'Gumbo',
        'Haggis', 'Halibut', 'Hummus',
        'Kabuli', 'Kebab', 'Kimchi', 'Kiwi', 'Kuli Kuli',
        'Larb',
        'Masala', 'Moose',
        'Pinto', 'Pho', 'Polenta', 'Pudding',
        'Reuben',
        'Schnitzel', 'Shawarma', 'Soba', 'Stew', 'Succotash',
        'Taco', 'Tart',
        'Walnut',
        'Yuzu',
        'Ziti',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'cookingBuddy', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column(length: 40)]
    private string $name;

    #[ORM\Column(length: 40)]
    private string $appearance;

    public function __construct(User $owner, string $name, string $appearance)
    {
        $this->owner = $owner;
        $this->name = $name;
        $this->appearance = $appearance;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function generateNewName(IRandom $rng): static
    {
        $oldName = $this->name;

        do
        {
            $this->name = $rng->rngNextFromArray(self::Names);
        } while($this->name === $oldName);

        return $this;
    }

    public function getAppearance(): string
    {
        return $this->appearance;
    }

    public function setAppearance(string $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }
}
