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

use App\Entity\PhoneNumber;
use App\Entity\User;
use App\Entity\UserPhoneNumber;
use Doctrine\ORM\EntityManagerInterface;

class UserPhoneNumberRepository
{
    /**
     * @param string $number Any format the telephone accepts; for example "1-800-PIZZA"
     */
    public static function knowsNumber(EntityManagerInterface $em, User $user, string $number): bool
    {
        return (int)$em->getRepository(UserPhoneNumber::class)->createQueryBuilder('upn')
            ->select('COUNT(upn.id)')
            ->join('upn.phoneNumber', 'pn')
            ->andWhere('upn.user = :user')
            ->andWhere('pn.number = :number')
            ->setParameter('user', $user)
            ->setParameter('number', PhoneNumber::toDigits($number))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
