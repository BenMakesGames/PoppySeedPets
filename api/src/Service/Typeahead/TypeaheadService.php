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

        $escaped = StringFunctions::escapeMySqlWildcardCharacters($search);

        // One ranked query returns every substring match, sorting prefix matches (LIKE 'search%')
        // ahead of substring-only matches. A single query can't return a row twice, so there's no
        // merge or de-duplication to do — and nothing binds an id array, so it stays agnostic to
        // whether the entity's id is an int or a (binary) Ulid.
        $qb = $this->repository->createQueryBuilder('e')
            ->addSelect('(CASE WHEN e.' . $fieldToSearch . ' LIKE :prefixLike THEN 0 ELSE 1 END) AS HIDDEN prefixRank')
            ->andWhere('e.' . $fieldToSearch . ' LIKE :substringLike')
            ->setParameter('prefixLike', $escaped . '%')
            ->setParameter('substringLike', '%' . $escaped . '%')
            ->orderBy('prefixRank', 'ASC')
            ->addOrderBy('e.' . $fieldToSearch, 'ASC')
            ->setMaxResults($maxResults)
        ;

        $qb = $this->addQueryBuilderConditions($qb);

        /** @var T[] $entities */
        $entities = $qb->getQuery()->execute();

        return $entities;
    }
}
