<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113023919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track IP addresses survey answers were submitted from.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survey_question_answer ADD ip_addresses JSON NOT NULL');

        // existing answers predate IP tracking
        $this->addSql('UPDATE survey_question_answer SET ip_addresses = \'[]\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survey_question_answer DROP ip_addresses');
    }
}
