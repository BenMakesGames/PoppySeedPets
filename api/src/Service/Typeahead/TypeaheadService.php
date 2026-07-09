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
use App\Model\FilterResults;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
        $qb = $this->buildRankedQuery($fieldToSearch, $searchString)
            ->setMaxResults($maxResults)
        ;

        /** @var T[] $entities */
        $entities = $qb->getQuery()->execute();

        return $entities;
    }

    /**
     * The paginated sibling of {@see search()}. Same ranked query, but returns a page window plus
     * the total match count wrapped in a FilterResults, so callers can offer pagination controls.
     */
    public function searchPaginated(string $fieldToSearch, string $searchString, int $page = 0, int $pageSize = 10): FilterResults
    {
        $qb = $this->buildRankedQuery($fieldToSearch, $searchString);

        // These are single-entity queries — the only joins are for filtering (no to-many collection
        // is fetched into the SELECT), so each entity appears at most once and there's nothing for
        // the fetch-join walker to de-duplicate. Turning it off keeps Doctrine on the simple
        // CountWalker, which drops the ORDER BY (and its HIDDEN prefixRank alias) when counting.
        $paginator = new Paginator($qb, fetchJoinCollection: false);

        $resultCount = $paginator->count();
        $pageCount = max(1, (int)ceil($resultCount / $pageSize));
        $page = max(0, min($page, $pageCount - 1));

        $paginator->getQuery()
            ->setFirstResult($page * $pageSize)
            ->setMaxResults($pageSize)
        ;

        $results = new FilterResults();
        $results->page = $page;
        $results->pageSize = $pageSize;
        $results->pageCount = $pageCount;
        $results->resultCount = $resultCount;
        $results->results = iterator_to_array($paginator);

        return $results;
    }

    /**
     * One ranked query returns every substring match, sorting prefix matches (LIKE 'search%')
     * ahead of substring-only matches. A single query can't return a row twice, so there's no
     * merge or de-duplication to do — and nothing binds an id array, so it stays agnostic to
     * whether the entity's id is an int or a (binary) Ulid.
     */
    private function buildRankedQuery(string $fieldToSearch, string $searchString): QueryBuilder
    {
        $search = mb_trim($searchString);

        if($search === '')
            throw new PSPFormValidationException('Search text is missing...');

        $escaped = StringFunctions::escapeMySqlWildcardCharacters($search);

        $qb = $this->repository->createQueryBuilder('e')
            ->addSelect('(CASE WHEN e.' . $fieldToSearch . ' LIKE :prefixLike THEN 0 ELSE 1 END) AS HIDDEN prefixRank')
            ->andWhere('e.' . $fieldToSearch . ' LIKE :substringLike')
            ->setParameter('prefixLike', $escaped . '%')
            ->setParameter('substringLike', '%' . $escaped . '%')
            ->orderBy('prefixRank', 'ASC')
            ->addOrderBy('e.' . $fieldToSearch, 'ASC')
        ;

        return $this->addQueryBuilderConditions($qb);
    }
}
