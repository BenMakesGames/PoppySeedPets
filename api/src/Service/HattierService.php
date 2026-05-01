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

use App\Entity\Enchantment;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Entity\UserUnlockedAura;
use App\Enum\EnumInvalidValueException;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PlayerActivityLogTagEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotFoundException;
use App\Functions\ArrayFunctions;
use App\Functions\EnchantmentRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PlayerLogFactory;
use App\Functions\UserUnlockedFeatureHelpers;
use Doctrine\ORM\EntityManagerInterface;

class HattierService
{
    public function __construct(
        private readonly CommentFormatter $commentFormatter,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public function userHasUnlocked(User $user, string|Enchantment $enchantment): bool
    {
        if(is_string($enchantment))
            $enchantment = EnchantmentRepository::findOneByName($this->em, $enchantment);

        $cacheKey = $user->getId() . '-' . $enchantment->getId();

        if(!array_key_exists($cacheKey, $this->userAurasPerRequestCache))
        {
            $this->userAurasPerRequestCache[$cacheKey] = $this->em->getRepository(UserUnlockedAura::class)->findOneBy([
                'user' => $user,
                'aura' => $enchantment
            ]);
        }

        return $this->userAurasPerRequestCache[$cacheKey] !== null;
    }

    /**
     * @return array{id: int|null, unlockedOn: \DateTimeImmutable|null, comment: string|null, name: string, aura: Enchantment|null}[]
     */
    public function getAurasAvailable(User $user): array
    {
        /** @var Enchantment[] $allAuras */
        $allAuras = $this->em->getRepository(Enchantment::class)->createQueryBuilder('e')
            ->andWhere('e.aura IS NOT NULL')
            ->getQuery()
            ->execute()
        ;

        $unlocked = $user->getUnlockedAuras()->toArray();

        return array_map(
            function(Enchantment $e) use($unlocked) {
                $unlockedAura = array_find($unlocked, fn(UserUnlockedAura $a) => $a->getAura()->getId() === $e->getId());

                if($unlockedAura)
                {
                    return [
                        'id' => $unlockedAura->getId(),
                        'unlockedOn' => $unlockedAura->getUnlockedOn(),
                        'comment' => $this->commentFormatter->format($unlockedAura->getComment()),
                        'name' => $e->getAura()->getName(),
                        'aura' => $e->getAura(),
                    ];
                }
                else
                {
                    return [
                        'id' => null,
                        'unlockedOn' => null,
                        'comment' => null,
                        'name' => $e->getAura()->getName(),
                        'aura' => null,
                    ];
                }
            },
            $allAuras
        );
    }

    /**
     * @var array<string, UserUnlockedAura>
     */
    private array $userAurasPerRequestCache = [];

    public function playerUnlockAura(User $user, Enchantment $enchantment, string $comment): UserUnlockedAura
    {
        $cacheKey = $user->getId() . '-' . $enchantment->getId();

        if(!array_key_exists($cacheKey, $this->userAurasPerRequestCache) || $this->userAurasPerRequestCache[$cacheKey] === null)
        {
            $unlockedAura = $this->em->getRepository(UserUnlockedAura::class)->findOneBy([
                'user' => $user,
                'aura' => $enchantment
            ]);

            if(!$unlockedAura)
            {
                $unlockedAura = new UserUnlockedAura(user: $user, aura: $enchantment)
                    ->setComment($comment)
                ;

                $this->em->persist($unlockedAura);

                PlayerLogFactory::create($this->em, $user, 'You unlocked the "' . $enchantment->getAura()->getName() . '" styling!', [ PlayerActivityLogTagEnum::Hattier ]);
            }

            $this->userAurasPerRequestCache[$cacheKey] = $unlockedAura;
        }

        return $this->userAurasPerRequestCache[$cacheKey];
    }

    public function getAuraUnlockedCacheKey(User $user, Enchantment $enchantment): string
    {
        return $user->getId() . '-' . $enchantment->getId();
    }

    public function auraAlreadyUnlocked(User $user, Enchantment $enchantment): ?UserUnlockedAura
    {
        $cacheKey = $this->getAuraUnlockedCacheKey($user, $enchantment);

        if(!array_key_exists($cacheKey, $this->userAurasPerRequestCache) || $this->userAurasPerRequestCache[$cacheKey] === null)
        {
            $this->userAurasPerRequestCache[$cacheKey] = $this->em->getRepository(UserUnlockedAura::class)->findOneBy([
                'user' => $user,
                'aura' => $enchantment
            ]);
        }

        return $this->userAurasPerRequestCache[$cacheKey];
    }

    /**
     * @throws EnumInvalidValueException
     */
    public function petUnlockAura(User $user, Enchantment $enchantment, string $comment, PetActivityLog $activityLog, ?string $customActivityUnlockMessage = null): UserUnlockedAura
    {
        $alreadyUnlocked = $this->auraAlreadyUnlocked($user, $enchantment);

        if($alreadyUnlocked)
            return $alreadyUnlocked;

        $unlockedAura = new UserUnlockedAura(user: $user, aura: $enchantment)
            ->setComment($comment)
        ;

        $this->em->persist($unlockedAura);

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Hattier))
        {
            UserUnlockedFeatureHelpers::create($this->em, $user, UnlockableFeatureEnum::Hattier);

            if($customActivityUnlockMessage)
                $activityLog->appendEntry($customActivityUnlockMessage);
            else
                $activityLog->appendEntry('(The Hattier has been unlocked! Check it out in the menu!)');

            $this->unlockStartingAuras($user);
        }
        else
            $activityLog->appendEntry('(A new style has been added to the Hattier!)');

        $cacheKey = $this->getAuraUnlockedCacheKey($user, $enchantment);

        $activityLog->addInterestingness(PetActivityLogInterestingness::RareActivity);

        $this->userAurasPerRequestCache[$cacheKey] = $unlockedAura;

        return $unlockedAura;
    }

    private function unlockStartingAuras(User $user): void
    {
        $startingAuras = $this->em->getRepository(Enchantment::class)->findBy([
            'name' => [
                'Bubbling',
                '(New!)',
                'of Squares',
                'with Paint'
            ],
        ]);

        foreach($startingAuras as $aura)
        {
            if($this->auraAlreadyUnlocked($user, $aura))
                continue;

            $unlockedAura = new UserUnlockedAura(user: $user, aura: $aura)
                ->setComment('The Hattier has made this style available to you as a courtesy.')
                ->setUnlockedOn(\DateTimeImmutable::createFromFormat('Y-m-d', '2019-06-22'))
            ;

            $this->em->persist($unlockedAura);

            $cacheKey = $this->getAuraUnlockedCacheKey($user, $aura);
            $this->userAurasPerRequestCache[$cacheKey] = $unlockedAura;
        }
    }

    /**
     * @throws PSPNotFoundException
     * @throws EnumInvalidValueException
     */
    public function petMaybeUnlockAura(
        Pet $pet, string|Enchantment $enchantment, string $logIfHatGetsEnchanted, string $logIfHatDoesNotGetEnchanted,
        string $auraUnlockMessage
    ): ?PetActivityLog
    {
        if(is_string($enchantment))
            $enchantment = EnchantmentRepository::findOneByName($this->em, $enchantment);

        if($this->userHasUnlocked($pet->getOwner(), $enchantment))
            return null;

        if($pet->getHat() && !$pet->getHat()->getEnchantment())
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $logIfHatGetsEnchanted)
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;

            $pet->getHat()->setEnchantment($enchantment);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $logIfHatDoesNotGetEnchanted)
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;
        }

        $this->petUnlockAura($pet->getOwner(), $enchantment, $auraUnlockMessage, $activityLog);

        return $activityLog;
    }

    /**
     * @throws EnumInvalidValueException
     */
    public function unlockAuraDuringPetActivity(
        Pet $pet, PetActivityLog $activityLog, Enchantment $enchantment,
        string $addedToHatDescription,
        string $notAddedToHatDescription,
        string $auraUnlockMessage
    ): void
    {
        if($pet->getHat() && !$pet->getHat()->getEnchantment())
        {
            $activityLog
                ->appendEntry($addedToHatDescription)
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;

            $pet->getHat()->setEnchantment($enchantment);
        }
        else
        {
            $activityLog
                ->appendEntry($notAddedToHatDescription)
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;
        }

        $this->petUnlockAura($pet->getOwner(), $enchantment, $auraUnlockMessage, $activityLog);
    }
}