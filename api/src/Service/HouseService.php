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

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Enum\EnumInvalidValueException;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetLocationEnum;
use App\Functions\UserQuestRepository;
use App\Service\PetActivity\SagaSagaService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class HouseService
{
    public function __construct(
        private readonly PetActivityService $petActivityService,
        private readonly CacheItemPoolInterface $cache,
        private readonly EntityManagerInterface $em,
        private readonly InventoryService $inventoryService,
        private readonly IRandom $rng,
        private readonly HouseSimService $houseSimService,
        private readonly SagaSagaService $sagaSagaService,
        private readonly PetSocialActivityService $petSocialActivityService,
        private readonly PerformanceProfiler $performanceProfiler
    )
    {
    }

    public function needsToBeRun(User $user): bool
    {
        $query = $this->em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.houseTime', 'ht')
            ->andWhere('p.owner=:user')
            ->andWhere('(ht.activityTime>=60 OR (ht.socialEnergy>=:minimumSocialEnergy AND ht.canAttemptSocialHangoutAfter<CURRENT_TIMESTAMP()))')
            ->andWhere('p.location=:home')
            ->setParameter('user', $user->getId())
            ->setParameter('home', PetLocationEnum::HOME)
            ->setParameter('minimumSocialEnergy', PetExperienceService::SocialEnergyPerHangOut)
            ->setMaxResults(1)
            ->getQuery();

        $petsWithTime = (int)$query->execute();

        return $petsWithTime > 0;
    }

    public function getHouseRunLock(User $user): CacheItemInterface
    {
        return $this->cache->getItem('User #' . $user->getId() . ' - Running House Hours');
    }

    public function run(User $user): void
    {
        $item = $this->getHouseRunLock($user);

        if($item->isHit())
            return;

        $item->set(true)->expiresAfter(\DateInterval::createFromDateString('1 minute'));
        $this->cache->save($item);

        if($user->getRegisteredOn() < new \DateTimeImmutable()->modify('-8 hours'))
        {
            $fruitBasket = UserQuestRepository::findOrCreate($this->em, $user, 'Received Fruit Basket', false);

            if($fruitBasket->getValue() === false)
            {
                $fruitBasket->setValue(true);
                $this->inventoryService->receiveItem('Fruit Basket', $user, $user, 'There\'s a note attached. It says "Are you settling in alright? Here\'s a little something to help get you started. And don\'t throw away the basket! Equip it to your pet!"', LocationEnum::Home, true);
                $this->em->flush();
            }
        }

        /**
         * @var list<Pet> $petsAtHome
         */
        $petsAtHome = $this->em->getRepository(Pet::class)->createQueryBuilder('p')
            ->join('p.houseTime', 'ht')
            ->andWhere('p.owner=:ownerId')
            ->andWhere('p.location=:home')
            ->setParameter('ownerId', $user->getId())
            ->setParameter('home', PetLocationEnum::HOME)
            ->getQuery()
            ->execute()
        ;

        if(count($petsAtHome) > $user->getMaxPets())
            return;

        $now = new \DateTimeImmutable();

        /** @var list<Pet> $petsWithTime */
        // array_filter preserves keys, so we use array_values to reset them, because PHP...
        $petsWithTime = array_values(array_filter($petsAtHome, fn(Pet $pet) =>
            $pet->getHouseTime()->getActivityTime() >= 60 ||
            (
                $pet->getHouseTime()->getSocialEnergy() >= PetExperienceService::SocialEnergyPerHangOut &&
                $pet->getHouseTime()->getCanAttemptSocialHangoutAfter() < $now
            )
        ));

        if(count($petsWithTime) > 0)
        {
            $time = microtime(true);

            $this->houseSimService->begin($this->em, $user);

            while(count($petsWithTime) > 0)
            {
                $this->rng->rngNextShuffle($petsWithTime);

                $petsWithTime = $this->processPets($petsWithTime);
            }

            $this->houseSimService->end($this->em);

            $this->em->flush();

            $this->performanceProfiler->logExecutionTime(__METHOD__ . ' - Ran Hours', microtime(true) - $time);
        }
    }

    /**
     * @param Pet[] $petsWithTime
     * @return list<Pet>
     * @throws EnumInvalidValueException
     */
    private function processPets(array $petsWithTime): array
    {
        $petsRemaining = [];

        foreach($petsWithTime as $pet)
        {
            if($pet->getHouseTime()->getActivityTime() >= 60)
            {
                $time = microtime(true);
                $this->petActivityService->runHour($pet);
                $this->performanceProfiler->logExecutionTime(__METHOD__ . ' - Ran Pet Hour', microtime(true) - $time);
            }

            $hungOut = false;

            if($this->petCanRunSocialTime($pet))
            {
                $roommates = array_filter($petsWithTime, fn(Pet $p) => $p !== $pet);

                // only one social activity per request, to avoid weird bugs...
                $hungOut = $this->petSocialActivityService->runSocialTime($pet, $roommates);

                if($hungOut)
                    $this->petSocialActivityService->recomputeFriendRatings($pet);

                $this->houseSimService->setPetHasRunSocialTime($pet);
            }

            if($pet->hasMerit(MeritEnum::SAGA_SAGA))
            {
                if($this->sagaSagaService->petCompletesSagaSaga($pet))
                    break;
            }

            if($this->petCanStillProcess($pet, $hungOut))
                $petsRemaining[] = $pet;
        }

        return $petsRemaining;
    }

    private function petCanRunSocialTime(Pet $pet): bool
    {
        return
            $pet->getHouseTime()->getSocialEnergy() >= PetExperienceService::SocialEnergyPerHangOut &&
            $pet->getHouseTime()->getCanAttemptSocialHangoutAfter() < new \DateTimeImmutable() &&
            !$this->houseSimService->getPetHasRunSocialTime($pet)
        ;
    }

    private function petCanStillProcess(Pet $pet, bool $hungOut): bool
    {
        if(!$pet->isAtHome())
            return false;

        if($pet->getHouseTime()->getActivityTime() < 60 && $pet->getHouseTime()->getSocialEnergy() < PetExperienceService::SocialEnergyPerHangOut)
            return false;

        if($pet->getHouseTime()->getActivityTime() < 60 && !$hungOut)
            return false;

        return true;
    }
}
