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

namespace App\Functions;

use App\Entity\Dragon;
use App\Entity\User;
use App\Entity\UserStats;
use App\Enum\SerializationGroupEnum;
use App\Enum\UserStat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DragonHelpers
{
    public static function getAdultDragon(EntityManagerInterface $em, User $user): ?Dragon
    {
        return $em->getRepository(Dragon::class)->findOneBy([
            'owner' => $user,
            'isAdult' => true
        ]);
    }

    public static function createDragonResponse(EntityManagerInterface $em, NormalizerInterface $normalizer, User $user, Dragon $dragon): array
    {
        $treasuresGiven = $em->getRepository(UserStats::class)->findOneBy([
            'user' => $user,
            'stat' => UserStat::TreasuresGivenToDragonHoard
        ]);

        $response = NormalizerHelpers::normalizeToArray($normalizer, $dragon, null, [ 'groups' => [
            SerializationGroupEnum::MY_DRAGON,
            SerializationGroupEnum::HELPER_PET
        ]]);

        $response['treasureCount'] = $treasuresGiven ? $treasuresGiven->getValue() : 0;

        return $response;
    }
}