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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506094100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds self-reflection merits';
    }

    public function up(Schema $schema): void
    {
        //Merits
        $this->addSql(<<<EOSQL
        INSERT INTO `merit` (`id`, `name`, `description`) VALUES (NULL, 'Of Heart and Mind', '%pet.name% understands their heart well, and gains more exp while they feel very loved.')
        ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        $this->addSql(<<<EOSQL
        INSERT INTO `merit` (`id`, `name`, `description`) VALUES (NULL, 'Precious Memories', '%pet.name% values their time spent with you! When they participate in quality time, they gain an item from the <a href="/poppyopedia/item?filter.itemGroup=Precious%20Memories">Precious Memories</a> item group.')
        ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        $this->addSql(<<<EOSQL
        INSERT INTO `merit` (`id`, `name`, `description`) VALUES (NULL, 'Second Stomach', '%pet.name% can eat past their normal stomach capacity, but only for their favorite foods!')
        ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        //Item group assignment
        //TODO
    }

    public function down(Schema $schema): void
    {
    }
}
