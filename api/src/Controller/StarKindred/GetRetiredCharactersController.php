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
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotUnlockedException;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredCharacterSheet;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The book of retired adventurers: every retired character of every pet the player currently owns.
 */
#[Route("/starKindred")]
class GetRetiredCharactersController
{
    private const int PageSize = 20;

    #[Route("/retired", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor,
        #[MapQueryParameter] int $page = 0,
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        $qb = $em->getRepository(StarKindredCharacter::class)->createQueryBuilder('c')
            ->join('c.pet', 'p')
            ->andWhere('p.owner = :user')
            ->andWhere('c.retiredOn IS NOT NULL')
            ->setParameter('user', $user)
            ->addOrderBy('c.retiredOn', 'DESC')
            ->addOrderBy('c.id', 'DESC');

        $paginator = new Paginator($qb, fetchJoinCollection: false);
        $resultCount = $paginator->count();
        $pageCount = max(1, (int)ceil($resultCount / self::PageSize));
        $page = max(0, min($page, $pageCount - 1));

        $paginator->getQuery()
            ->setFirstResult($page * self::PageSize)
            ->setMaxResults(self::PageSize);

        /** @var StarKindredCharacter[] $characters */
        $characters = iterator_to_array($paginator);

        return $responseService->success(
            [
                'page' => $page,
                'pageCount' => $pageCount,
                'pageSize' => self::PageSize,
                'resultCount' => $resultCount,
                'results' => array_map(StarKindredCharacterSheet::map(...), array_values($characters)),
            ],
            [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]
        );
    }
}
