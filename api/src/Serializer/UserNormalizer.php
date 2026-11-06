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

namespace App\Serializer;

use App\Entity\User;
use App\Entity\UserFollowing;
use App\Entity\UserLetter;
use App\Enum\SerializationGroupEnum;
use App\Functions\NormalizerHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private readonly Security $security,
        private readonly EntityManagerInterface $em
    )
    {
    }

    private static function getUnreadUserLetterCount(EntityManagerInterface $em, User $user): int
    {
        return (int)$em->getRepository(UserLetter::class)->createQueryBuilder('ul')
            ->select('COUNT(ul.id)')
            ->andWhere('ul.user=:userId')
            ->andWhere('ul.isRead=0')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param User $data
     */
    public function normalize($data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = NormalizerHelpers::normalizeToArray($this->normalizer, $data, $format, $context);

        if(in_array(SerializationGroupEnum::MY_ACCOUNT, $context['groups']))
        {
            $normalizedData['unreadLetters'] = self::getUnreadUserLetterCount($this->em, $data);
        }

        if(in_array(SerializationGroupEnum::USER_PUBLIC_PROFILE, $context['groups']))
        {
            $friend = $this->em->getRepository(UserFollowing::class)->findOneBy([
                'user' => $this->security->getUser(),
                'following' => $data
            ]);

            if($friend)
                $normalizedData['following'] = [ 'note' => $friend->getNote() ];
        }

        return $normalizedData;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof User;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ User::class => true ];
    }
}
