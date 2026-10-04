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

final readonly class StarKindredAdventureResult
{
    /**
     * @param StarKindredCheckResult[] $checks
     * @param string $text Markdown
     * @param string[] $loot
     * @param StarKindredCharacterProgress[] $progress
     * @param string[] $milestones Markdown; class features gained by leveling up (ex: animal companions)
     */
    public function __construct(
        public bool $victory,
        public string $title,
        public array $checks,
        public string $text,
        public array $loot,
        public array $progress,
        public array $milestones,
    )
    {
    }
}
