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

final class Version20260521225231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cucumber Water never tasted so burn-y';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        UPDATE `item` SET `description` = 'Ice, being solid with crystalline structure, is a mineral, and a rock is just one or more minerals clumped up together, and magma is just melted rock, so... are you drinking magma when you drink a glass of melted ice? I dunno. I dunno. _Maybe._' WHERE `item`.`id` = 1302;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
