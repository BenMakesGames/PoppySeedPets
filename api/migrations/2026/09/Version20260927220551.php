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
final class Version20260927220551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '★Kindred: track today\'s play (adventure picked, rewards won) in its own table, instead of a user quest';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE star_kindred_daily_play (id INT AUTO_INCREMENT NOT NULL, played_on DATETIME NOT NULL, adventure_id VARCHAR(16) DEFAULT NULL, rewards_won SMALLINT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_A9B19B1FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE star_kindred_daily_play ADD CONSTRAINT FK_A9B19B1FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // superseded by star_kindred_daily_play
        $this->addSql('DELETE FROM user_quest WHERE name = \'Played ★Kindred\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE star_kindred_daily_play DROP FOREIGN KEY FK_A9B19B1FA76ED395');
        $this->addSql('DROP TABLE star_kindred_daily_play');
    }
}
