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

final class Version20260510013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pets born and total pets stats.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_born_1day INT NULL DEFAULT NULL AFTER unlocked_portal_lifetime');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_born_3day INT NULL DEFAULT NULL AFTER pets_born_1day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_born_7day INT NULL DEFAULT NULL AFTER pets_born_3day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_born_28day INT NULL DEFAULT NULL AFTER pets_born_7day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_born_lifetime INT NULL DEFAULT NULL AFTER pets_born_28day');

        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_total_1day INT NULL DEFAULT NULL AFTER pets_born_lifetime');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_total_3day INT NULL DEFAULT NULL AFTER pets_total_1day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_total_7day INT NULL DEFAULT NULL AFTER pets_total_3day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_total_28day INT NULL DEFAULT NULL AFTER pets_total_7day');
        $this->addSql('ALTER TABLE daily_stats ADD COLUMN pets_total_lifetime INT NULL DEFAULT NULL AFTER pets_total_28day');
    }

    public function down(Schema $schema): void
    {
    }
}
