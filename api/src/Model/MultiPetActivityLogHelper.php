<?php
declare(strict_types = 1);

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

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Functions\PetActivityLogFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Example usage:
 *
 * <code>
 * $groupLogHelper = new MultiPetActivityLogHelper($em, 'This message is the same for all the pets.');
 *
 * foreach($pets as $pet)
 * {
 *     $groupLogHelper->createGroupLog($pet);
 * }
 * </code>
 */
class MultiPetActivityLogHelper
{
    /** @var int[] $usersAlerted */
    private array $usersAlerted = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $message,
    )
    {}

    /**
     * Creates a read or unread log, depending on if the pet passed in has an owner who is included in usersAlerted
     * For logs that are the same for multiple pets, so users don't get 'copies' of messages
     */
    public function createGroupLog(Pet $pet) : PetActivityLog
    {
        $alreadyMessagedThisPlayer = in_array($pet->getOwner()->getId(), $this->usersAlerted);

        if(!$alreadyMessagedThisPlayer)
            $this->usersAlerted[] = $pet->getOwner()->getId();

        return $alreadyMessagedThisPlayer
            ? PetActivityLogFactory::createReadLog($this->em, $pet, $this->message)
            : PetActivityLogFactory::createUnreadLog($this->em, $pet, $this->message);
    }
}