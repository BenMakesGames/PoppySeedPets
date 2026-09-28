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

final class Version20260928204327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hattier: per-pet hat fits';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pet_hat_fit (id INT AUTO_INCREMENT NOT NULL, head_x DOUBLE PRECISION NOT NULL, head_y DOUBLE PRECISION NOT NULL, head_angle DOUBLE PRECISION NOT NULL, head_scale DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE pet ADD hat_fit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pet ADD CONSTRAINT FK_E4529B85A0F76DB3 FOREIGN KEY (hat_fit_id) REFERENCES pet_hat_fit (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E4529B85A0F76DB3 ON pet (hat_fit_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B85A0F76DB3');
        $this->addSql('DROP INDEX UNIQ_E4529B85A0F76DB3 ON pet');
        $this->addSql('ALTER TABLE pet DROP hat_fit_id');
        $this->addSql('DROP TABLE pet_hat_fit');
    }
}
