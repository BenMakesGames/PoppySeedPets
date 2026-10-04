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

namespace App\Service\QualityTime;

use App\Entity\Pet;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * One flavor of quality-time event. See QualityTimeService for the registry/picker.
 *
 * isAvailable() does not take Pet[] on purpose — no current event gates on pet state.
 * Add it only if a future event genuinely needs it.
 */
#[AutoconfigureTag('app.qualityTimeEvent')]
interface QualityTimeEvent
{
    public function isAvailable(User $user): bool;

    /**
     * @param Pet[] $pets
     */
    public function generate(User $user, array $pets): QualityTimeResult;
}
