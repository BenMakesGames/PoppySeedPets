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
final class Version20260927053123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'StarKindred redo: per-pet characters replace the hand-made monthly stories.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE star_kindred_character (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, race VARCHAR(20) NOT NULL, character_class VARCHAR(20) NOT NULL, level INT NOT NULL, experience INT NOT NULL, strength INT NOT NULL, dexterity INT NOT NULL, constitution INT NOT NULL, intelligence INT NOT NULL, wisdom INT NOT NULL, charisma INT NOT NULL, adventures_completed INT NOT NULL, adventures_won INT NOT NULL, created_on DATETIME NOT NULL, retired_on DATETIME DEFAULT NULL, epilogue LONGTEXT DEFAULT NULL, pet_id INT NOT NULL, INDEX IDX_CB5AAECC966F7FB6 (pet_id), INDEX pet_retired_on_idx (pet_id, retired_on), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE star_kindred_character ADD CONSTRAINT FK_CB5AAECC966F7FB6 FOREIGN KEY (pet_id) REFERENCES pet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE monthly_story_adventure_step DROP FOREIGN KEY `FK_2D91EBCA55CF40F9`');
        $this->addSql('ALTER TABLE monthly_story_adventure_step DROP FOREIGN KEY `FK_2D91EBCA72CA3BB6`');
        $this->addSql('ALTER TABLE user_monthly_story_adventure_step_completed DROP FOREIGN KEY `FK_A7C86F2826B860F8`');
        $this->addSql('ALTER TABLE user_monthly_story_adventure_step_completed DROP FOREIGN KEY `FK_A7C86F28A76ED395`');
        $this->addSql('DROP TABLE monthly_story_adventure');
        $this->addSql('DROP TABLE monthly_story_adventure_step');
        $this->addSql('DROP TABLE user_monthly_story_adventure_step_completed');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE monthly_story_adventure (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, summary LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, release_number INT NOT NULL, release_year INT NOT NULL, release_month INT NOT NULL, is_dark TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE monthly_story_adventure_step (id INT AUTO_INCREMENT NOT NULL, adventure_id INT NOT NULL, aura_id INT DEFAULT NULL, title VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, step INT NOT NULL, previous_step INT DEFAULT NULL, x DOUBLE PRECISION NOT NULL, y DOUBLE PRECISION NOT NULL, min_pets INT NOT NULL, max_pets INT NOT NULL, narrative LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, treasure VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, pin_override VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_2D91EBCA55CF40F9 (adventure_id), INDEX IDX_2D91EBCA72CA3BB6 (aura_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_monthly_story_adventure_step_completed (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, adventure_step_id INT NOT NULL, completed_on DATETIME NOT NULL, INDEX IDX_A7C86F28A76ED395 (user_id), INDEX IDX_A7C86F2826B860F8 (adventure_step_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE monthly_story_adventure_step ADD CONSTRAINT `FK_2D91EBCA55CF40F9` FOREIGN KEY (adventure_id) REFERENCES monthly_story_adventure (id)');
        $this->addSql('ALTER TABLE monthly_story_adventure_step ADD CONSTRAINT `FK_2D91EBCA72CA3BB6` FOREIGN KEY (aura_id) REFERENCES enchantment (id)');
        $this->addSql('ALTER TABLE user_monthly_story_adventure_step_completed ADD CONSTRAINT `FK_A7C86F2826B860F8` FOREIGN KEY (adventure_step_id) REFERENCES monthly_story_adventure_step (id)');
        $this->addSql('ALTER TABLE user_monthly_story_adventure_step_completed ADD CONSTRAINT `FK_A7C86F28A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE star_kindred_character DROP FOREIGN KEY FK_CB5AAECC966F7FB6');
        $this->addSql('DROP TABLE star_kindred_character');
    }
}
