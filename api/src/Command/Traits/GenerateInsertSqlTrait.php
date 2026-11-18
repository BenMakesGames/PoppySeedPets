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

namespace App\Command\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * For commands that export game data as SQL, for import into another database.
 *
 * @property EntityManagerInterface $em
 */
trait GenerateInsertSqlTrait
{
    private static function assertDevEnvironment(): void
    {
        $env = $_SERVER['APP_ENV'] ?? null;

        if(!is_string($env) || strtolower($env) !== 'dev')
            throw new \Exception('Can only be run in dev environments.');
    }

    /**
     * @param class-string $className
     */
    private function generateInsertSql(string $className, object $entity, string $comment): string
    {
        $entityMeta = $this->em->getClassMetadata($className);
        $tableName = $entityMeta->getTableName();

        /** @var string[] $columns */
        $columns = array_map(
            fn($row) => $row['Field'],
            $this->em->getConnection()->executeQuery("DESCRIBE `$tableName`")->fetchAllAssociative()
        );

        $columnSql = '`' . implode('`, `', $columns) . '`';

        $encodedValues = array_map(fn($c) => (string)self::encodeValueToSql($entity->{'get' . $entityMeta->getFieldForColumn($c)}()), $columns);

        $valueSql = implode(',', $encodedValues);

        return "-- $comment\nINSERT INTO $tableName ($columnSql) VALUES ($valueSql) ON DUPLICATE KEY UPDATE `id` = `id`;";
    }

    /**
     * @return string|numeric
     */
    private static function encodeValueToSql(mixed $value): mixed
    {
        if($value === true)
            return 1;

        if($value === false)
            return 0;

        if($value === null)
            return 'NULL';

        if($value instanceof \BackedEnum)
            return is_int($value->value) ? $value->value : '"' . addslashes($value->value) . '"';

        if(is_array($value))
            return '"' . addslashes(json_encode($value, JSON_THROW_ON_ERROR)) . '"';

        if($value instanceof AbstractUid)
            return '0x' . bin2hex($value->toBinary());

        if(is_object($value))
        {
            if(!method_exists($value, 'getId'))
                throw new \InvalidArgumentException('Cannot encode ' . $value::class . ' to SQL: it has no getId method.');

            return self::encodeValueToSql($value->getId());
        }

        if(is_numeric($value))
            return $value;

        if(is_string($value))
            return '"' . addslashes($value) . '"';

        throw new \InvalidArgumentException('Cannot encode ' . get_debug_type($value) . ' to SQL.');
    }
}
