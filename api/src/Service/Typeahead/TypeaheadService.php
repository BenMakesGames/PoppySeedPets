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

namespace App\Service\Typeahead;

use App\Exceptions\PSPFormValidationException;
use App\Functions\StringFunctions;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @template T
 */
abstract class TypeaheadService
{
    public function __construct(
        /** @var EntityRepository<T> */
        private readonly EntityRepository $repository
    )
    {
    }

    abstract public function addQueryBuilderConditions(QueryBuilder $qb): QueryBuilder;

    /**
     * @return T[]
     */
    public function search(string $fieldToSearch, string $searchString, int $maxResults = 5): array
    {
        $search = mb_trim($searchString);

        if($search === '')
            throw new PSPFormValidationException('Search text is missing...');

        $qb = $this->repository->createQueryBuilder('e')
            ->andWhere('e.' . $fieldToSearch . ' LIKE :searchLike')
            ->setParameter('searchLike', StringFunctions::escapeMySqlWildcardCharacters($search) . '%')
            ->setMaxResults($maxResults)
            ->orderBy('e.' . $fieldToSearch, 'ASC')
        ;

        $qb = $this->addQueryBuilderConditions($qb);

        /** @var T[] $entities */
        $entities = $qb->getQuery()->execute();

        if(count($entities) < $maxResults)
        {
            $ids = array_map(fn($e) => $e->getId(), $entities);

            $qb = $this->repository->createQueryBuilder('e')
                ->andWhere('e.' . $fieldToSearch . ' LIKE :searchLike')
                ->setParameter('searchLike', '%' . StringFunctions::escapeMySqlWildcardCharacters($search) . '%')
                ->orderBy('e.' . $fieldToSearch, 'ASC')
            ;

            if(count($entities) > 0)
            {
                $qb
                    ->andWhere('e.id NOT IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->setMaxResults($maxResults - count($entities))
                ;
            }
            else
            {
                $qb->setMaxResults($maxResults);
            }

            $qb = $this->addQueryBuilderConditions($qb);

            $entities = array_merge($entities, $qb->getQuery()->execute());
        }

        return $entities;
    }
}
