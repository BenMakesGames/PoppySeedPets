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
final class Version20260928005438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '★Kindred: characters choose one trained skill of their own, on top of their (now two) class skills';
    }

    public function up(Schema $schema): void
    {
        // ★Kindred is unreleased; rather than backfill, start everyone over
        $this->addSql('DELETE FROM star_kindred_character');

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE star_kindred_character ADD chosen_skill VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE star_kindred_character DROP chosen_skill');
    }
}
