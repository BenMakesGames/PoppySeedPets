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

final class Version20260709013409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove reference to High Impact in Magic Hourglass item description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        UPDATE `item` SET `description` = 'Just as energy and matter are interchangeable, so, some wizards say, are space and time. This hourglass operates on that principle, converting space into time.\n\nAs for the health effects of exposure to the anti-dark energy that\'s created as a byproduct of this process, that\'s a topic of active study...' WHERE `item`.`id` = 237;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
