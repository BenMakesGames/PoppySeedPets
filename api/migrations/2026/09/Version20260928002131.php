<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928002131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '★Kindred: class features (ex: Ranger & Druid animal companions)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE star_kindred_character ADD class_features JSON NOT NULL');
        $this->addSql('UPDATE star_kindred_character SET class_features = \'[]\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE star_kindred_character DROP class_features');
    }
}
