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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260927201426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'StarKindred character portraits; also catches up on old schema drift (dream & pet_activity_log_pet tables, whose entities were removed long ago; pet_species.available_at_signup default).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pet_activity_log_pet DROP FOREIGN KEY `FK_BC1AD4CA1882B7CF`');
        $this->addSql('ALTER TABLE pet_activity_log_pet DROP FOREIGN KEY `FK_BC1AD4CA966F7FB6`');
        $this->addSql('ALTER TABLE pet_activity_log_pet DROP FOREIGN KEY `FK_BC1AD4CAB811BD86`');
        $this->addSql('DROP TABLE dream');
        $this->addSql('DROP TABLE pet_activity_log_pet');
        $this->addSql('ALTER TABLE pet_species CHANGE available_at_signup available_at_signup TINYINT NOT NULL');
        $this->addSql('ALTER TABLE star_kindred_character ADD portrait VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dream (id INT AUTO_INCREMENT NOT NULL, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, item_description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE pet_activity_log_pet (id INT AUTO_INCREMENT NOT NULL, activity_log_id INT NOT NULL, pet_id INT NOT NULL, equipped_item_id INT DEFAULT NULL, changes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_BC1AD4CAB811BD86 (activity_log_id), INDEX IDX_BC1AD4CA966F7FB6 (pet_id), INDEX IDX_BC1AD4CA1882B7CF (equipped_item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE pet_activity_log_pet ADD CONSTRAINT `FK_BC1AD4CA1882B7CF` FOREIGN KEY (equipped_item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE pet_activity_log_pet ADD CONSTRAINT `FK_BC1AD4CA966F7FB6` FOREIGN KEY (pet_id) REFERENCES pet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE pet_activity_log_pet ADD CONSTRAINT `FK_BC1AD4CAB811BD86` FOREIGN KEY (activity_log_id) REFERENCES pet_activity_log (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE pet_species CHANGE available_at_signup available_at_signup TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE star_kindred_character DROP portrait');
    }
}
