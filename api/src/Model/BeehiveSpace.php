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
namespace App\Model;

use App\Enum\BeehiveSpaceTypeEnum;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * One of the 19 hexes on a beehive's grid. Stored on the Beehive entity as JSON, but worked with as this.
 */
final class BeehiveSpace
{
    public const int Count = 19;

    public function __construct(
        #[Groups(['myBeehive'])]
        public BeehiveSpaceTypeEnum $type,

        #[Groups(['myBeehive'])]
        public bool $harvested = false,
    )
    {
    }

    /**
     * @param array{type: string, harvested: bool} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: BeehiveSpaceTypeEnum::from($data['type']),
            harvested: $data['harvested'],
        );
    }

    /**
     * @return array{type: string, harvested: bool}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'harvested' => $this->harvested,
        ];
    }
}
