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

namespace App\Controller\StarKindred;

use App\Entity\StarKindredCharacter;
use App\Enum\SerializationGroupEnum;
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotUnlockedException;
use App\Service\Clock;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\StarKindred\StarKindredCharacterSheet;
use App\Service\StarKindred\StarKindredDailyAdventures;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/starKindred")]
class GetStarKindredController
{
    #[Route("", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor,
        StarKindredAdventureService $starKindred, Clock $clock
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        /** @var StarKindredCharacter[] $characters */
        $characters = $em->getRepository(StarKindredCharacter::class)->createQueryBuilder('c')
            ->join('c.pet', 'p')
            ->andWhere('p.owner = :user')
            ->andWhere('c.retiredOn IS NULL')
            ->setParameter('user', $user)
            ->addOrderBy('c.level', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->execute();

        return $responseService->success(
            [
                'canPlayToday' => !$starKindred->hasPlayedToday($user),
                'maxPartySize' => StarKindredAdventureService::MaxPartySize,
                'maxLevel' => StarKindredCharacter::MaxLevel,
                'adventures' => array_map(
                    StarKindredCharacterSheet::mapAdventure(...),
                    StarKindredDailyAdventures::forDate($clock->now)
                ),
                'difficulties' => array_map(
                    fn(StarKindredDifficultyEnum $d) => [
                        'name' => $d->value,
                        'targetPerAdventurer' => $d->targetPerCharacter(),
                        'victoryExperience' => $d->victoryExperience(),
                    ],
                    StarKindredDifficultyEnum::cases()
                ),
                'characters' => array_map(StarKindredCharacterSheet::map(...), $characters),
            ],
            [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]
        );
    }
}
