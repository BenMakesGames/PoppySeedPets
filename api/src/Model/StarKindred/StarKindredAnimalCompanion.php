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

namespace App\Model\StarKindred;

/**
 * Rangers & Druids gain one at StarKindredAnimalCompanion::GainedAtLevel.
 * Stored in StarKindredCharacter::$classFeatures, under "animalCompanion".
 */
final readonly class StarKindredAnimalCompanion
{
    public const int GainedAtLevel = 4;

    public function __construct(
        public string $name,
        public string $species,
    )
    {
    }

    /**
     * @return array{name: string, species: string}
     */
    public function toArray(): array
    {
        return [ 'name' => $this->name, 'species' => $this->species ];
    }

    /**
     * @param array{name: string, species: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['species']);
    }
}
