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

namespace App\Service\Filter;

use App\Entity\Pet;
use App\Functions\StringFunctions;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

class PetFilterService implements FilterServiceInterface
{
    /** @use FilterService<Pet> */
    use FilterService;

    public const int PageSize = 12;

    /** @var ObjectRepository<Pet>  */
    private readonly ObjectRepository $repository;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->repository = $doctrine->getRepository(Pet::class, 'readonly');

        $this->filterer = new Filterer(
            self::PageSize,
            [
                'id' => [ 'p.id' => 'asc' ],
                'level' => [ 'skills.level' => 'desc', 'p.name' => 'asc' ],
                'name' => [ 'p.name' => 'asc', 'p.id' => 'asc' ],
                'lastinteraction' => [ 'p.lastInteracted' => 'desc' ],
            ],
            [
                'name' => $this->filterName(...),
                'species' => $this->filterSpecies(...),
                'owner' => $this->filterOwner(...),
                'location' => $this->filterLocation(...),
                'merit' => $this->filterMerit(...),
                'toolOrHat' => $this->filterToolOrHat(...),
                'isPregnant' => $this->filterIsPregnant(...),
                'badge' => $this->filterBadge(...),
            ],
            [
                'nameExactMatch'
            ]
        );
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('p')
            ->join('p.skills', 'skills');
    }

    public function filterName(QueryBuilder $qb, mixed $value, array $filters): void
    {
        $name = mb_trim($value);

        if(!$name) return;

        if(array_key_exists('nameExactMatch', $filters) && StringFunctions::isTruthy($filters['nameExactMatch']))
        {
            $qb
                ->andWhere('p.name = :nameLike')
                ->setParameter('nameLike', $name)
            ;
        }
        else
        {
            $qb
                ->andWhere('p.name LIKE :nameLike')
                ->setParameter('nameLike', '%' . StringFunctions::escapeMySqlWildcardCharacters($name) . '%')
            ;
        }
    }

    public function filterSpecies(QueryBuilder $qb, mixed $value): void
    {
        $qb
            ->andWhere('p.species=:speciesId')
            ->setParameter('speciesId', $value)
        ;
    }

    public function filterOwner(QueryBuilder $qb, mixed $value): void
    {
        $qb
            ->andWhere('p.owner = :userId')
            ->setParameter('userId', $value)
        ;
    }

    public function filterLocation(QueryBuilder $qb, mixed $value): void
    {
        if(is_array($value))
        {
            $qb
                ->andWhere('p.location IN (:locations)')
                ->setParameter('locations', $value)
            ;
        }
        else
        {
            $qb
                ->andWhere('p.location = :location')
                ->setParameter('location', $value)
            ;
        }
    }

    public function filterMerit(QueryBuilder $qb, mixed $value): void
    {
        if(!in_array('merits', $qb->getAllAliases()))
            $qb->join('p.merits', 'merits');

        $qb
            ->andWhere('merits.id=:meritId')
            ->setParameter('meritId', (int)$value)
        ;
    }

    public function filterIsPregnant(QueryBuilder $qb, mixed $value): void
    {
        if(strtolower($value) === 'false' || !$value)
            $qb->andWhere('p.pregnancy IS NULL');
        else
            $qb->andWhere('p.pregnancy IS NOT NULL');
    }

    public function filterBadge(QueryBuilder $qb, mixed $value): void
    {
        if(!in_array('badges', $qb->getAllAliases()))
            $qb->leftJoin('p.badges', 'badges');

        $qb
            ->andWhere('badges.badge=:badgeName')
            ->setParameter('badgeName', $value)
        ;
    }

    public function filterToolOrHat(QueryBuilder $qb, mixed $value): void
    {
        if(!in_array('hat', $qb->getAllAliases()))
            $qb->leftJoin('p.hat', 'hat');

        if(!in_array('tool', $qb->getAllAliases()))
            $qb->leftJoin('p.tool', 'tool');

        $qb
            ->andWhere($qb->expr()->orX('hat.item=:itemId', 'tool.item=:itemId'))
            ->setParameter('itemId', (int)$value)
        ;
    }

    function applyResultCache(Query $qb, string $cacheKey): Query
    {
        return $qb;
    }

    public function allowedPageSizes(): array
    {
        return [ self::PageSize ];
    }
}
