<?php

declare(strict_types=1);

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
