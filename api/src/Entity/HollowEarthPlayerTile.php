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
#[ORM\UniqueConstraint(name: 'player_id_tile_id_idx', columns: ['player_id', 'tile_id'])]
#[ORM\Entity]
class HollowEarthPlayerTile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $player;

    #[ORM\ManyToOne(targetEntity: HollowEarthTile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private HollowEarthTile $tile;

    #[ORM\ManyToOne(targetEntity: HollowEarthTileCard::class)]
    private $card;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $goods = null;

    public function __construct(User $player, HollowEarthTile $tile, ?HollowEarthTileCard $card)
    {
        $this->player = $player;
        $this->tile = $tile;
        $this->card = $card;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPlayer(): User
    {
        return $this->player;
    }

    public function getTile(): HollowEarthTile
    {
        return $this->tile;
    }

    public function getCard(): ?HollowEarthTileCard
    {
        return $this->card;
    }

    public function setCard(?HollowEarthTileCard $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getGoods(): ?string
    {
        return $this->goods;
    }

    public function setGoods(?string $goods): self
    {
        $this->goods = $goods;

        return $this;
    }
}
