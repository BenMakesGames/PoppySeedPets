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

final class Version20260418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop pet-guild tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guild_membership DROP FOREIGN KEY FK_E7D8D2A966F7FB6');
        $this->addSql('ALTER TABLE guild_membership DROP FOREIGN KEY FK_E7D8D2A5F2131EF');
        $this->addSql('DROP TABLE guild_membership');
        $this->addSql('DROP TABLE guild');
    }

    public function down(Schema $schema): void
    {
    }
}
