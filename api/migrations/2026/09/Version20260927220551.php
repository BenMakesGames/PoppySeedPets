<?php

declare(strict_types=1);

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
