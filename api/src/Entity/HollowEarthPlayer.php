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

use App\Enum\HollowEarthActionTypeEnum;
use App\Enum\HollowEarthMoveDirectionEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class HollowEarthPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'hollowEarthPlayer')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["hollowEarth"])]
    #[ORM\ManyToOne(targetEntity: HollowEarthTile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private HollowEarthTile $currentTile;

    #[ORM\Column(type: 'json', nullable: true)]
    private array|null $currentAction = null;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $movesRemaining = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\OneToOne(targetEntity: Pet::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pet $chosenPet = null;

    #[ORM\Column(type: 'string', length: 1, enumType: HollowEarthMoveDirectionEnum::class)]
    private HollowEarthMoveDirectionEnum $currentDirection;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $jade = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $incense = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $salt = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $amber = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'integer')]
    private int $fruit = 0;

    #[Groups(["hollowEarth"])]
    #[ORM\Column(type: 'boolean')]
    private bool $showGoods = false;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    public function __construct(User $user)
    {
        $this->user = $user;
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

    public function getCurrentTile(): HollowEarthTile
    {
        return $this->currentTile;
    }

    public function setCurrentTile(HollowEarthTile $currentTile): self
    {
        $this->currentTile = $currentTile;
        $this->currentDirection = $currentTile->getMoveDirection();

        return $this;
    }

    public function getCurrentAction(): ?array
    {
        return $this->currentAction;
    }

    public function setCurrentAction(?array $currentAction): self
    {
        if($currentAction !== null && count($currentAction) === 0)
            $currentAction = null;

        $this->currentAction = $currentAction;

        return $this;
    }

    public function getMovesRemaining(): int
    {
        return $this->movesRemaining;
    }

    public function decreaseMovesRemaining(): self
    {
        $this->movesRemaining--;
        return $this;
    }

    public function setMovesRemaining(int $movesRemaining): self
    {
        $this->movesRemaining = $movesRemaining;

        return $this;
    }

    public function getChosenPet(): ?Pet
    {
        return $this->chosenPet;
    }

    public function setChosenPet(?Pet $chosenPet): self
    {
        $this->chosenPet = $chosenPet;

        return $this;
    }

    #[Groups(["hollowEarth"])]
    public function getAction()
    {
        if($this->currentAction === null)
            return null;

        $action = [];

        if(array_key_exists('type', $this->currentAction))
        {
            $action['type'] = $this->currentAction['type'];

            switch($action['type'])
            {
                case HollowEarthActionTypeEnum::PayItem:
                    $action['item'] = $this->currentAction['item'];
                    break;
                case HollowEarthActionTypeEnum::PayMoneys:
                    $action['amount'] = $this->currentAction['amount'];
                    break;
                case HollowEarthActionTypeEnum::PayItemAndMoneys:
                    $action['item'] = $this->currentAction['item'];
                    $action['amount'] = $this->currentAction['amount'];
                    break;
                case HollowEarthActionTypeEnum::ChooseOne:
                    $action['buttons'] = $this->currentAction['buttons'];
                    break;
            }
        }

        if(array_key_exists('description', $this->currentAction))
            $action['description'] = $this->currentAction['description'];

        if(array_key_exists('buttonText', $this->currentAction))
            $action['buttonText'] = $this->currentAction['buttonText'];

        return $action;
    }

    public function getCurrentDirection(): HollowEarthMoveDirectionEnum
    {
        return $this->currentDirection;
    }

    public function setCurrentDirection(HollowEarthMoveDirectionEnum $currentDirection): self
    {
        $this->currentDirection = $currentDirection;

        return $this;
    }

    public function getJade(): int
    {
        return $this->jade;
    }

    public function increaseJade(int $jade): self
    {
        $this->jade += $jade;

        return $this;
    }

    public function getIncense(): int
    {
        return $this->incense;
    }

    public function increaseIncense(int $incense): self
    {
        $this->incense += $incense;

        return $this;
    }

    public function getSalt(): int
    {
        return $this->salt;
    }

    public function increaseSalt(int $salt): self
    {
        $this->salt += $salt;

        return $this;
    }

    public function getAmber(): int
    {
        return $this->amber;
    }

    public function increaseAmber(int $amber): self
    {
        $this->amber += $amber;

        return $this;
    }

    public function getFruit(): int
    {
        return $this->fruit;
    }

    public function increaseFruit(int $fruit): self
    {
        $this->fruit += $fruit;

        return $this;
    }

    public function getShowGoods(): bool
    {
        return $this->showGoods;
    }

    public function setShowGoods(): self
    {
        $this->showGoods = true;

        return $this;
    }
}
