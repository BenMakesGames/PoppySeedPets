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
use Symfony\Component\Uid\Ulid;

final class Version20260516094631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert pet_species.id from auto-increment INT to ULID BINARY(16); rewire pet/pet_baby/user_species_collected FKs; add available_at_signup column backfilled from the old magic-id rule.';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop FK constraints and indexes on the three FK-holding tables
        $this->addSql('ALTER TABLE pet DROP FOREIGN KEY FK_E4529B85B2A1D860');
        $this->addSql('DROP INDEX IDX_E4529B85B2A1D860 ON pet');

        $this->addSql('ALTER TABLE pet_baby DROP FOREIGN KEY FK_9C246454B2A1D860');
        $this->addSql('DROP INDEX IDX_9C246454B2A1D860 ON pet_baby');

        $this->addSql('ALTER TABLE user_species_collected DROP FOREIGN KEY FK_681CA342B2A1D860');
        $this->addSql('DROP INDEX IDX_681CA342B2A1D860 ON user_species_collected');
        $this->addSql('DROP INDEX user_species_idx ON user_species_collected');

        // 2. Add BINARY(16) shadow columns
        $this->addSql('ALTER TABLE pet_species ADD new_id BINARY(16) NULL AFTER id');
        $this->addSql('ALTER TABLE pet ADD new_species_id BINARY(16) NULL AFTER species_id');
        $this->addSql('ALTER TABLE pet_baby ADD new_species_id BINARY(16) NULL AFTER species_id');
        $this->addSql('ALTER TABLE user_species_collected ADD new_species_id BINARY(16) NULL AFTER species_id');

        // 3. Add the available_at_signup column (defaults to 0; backfill the chosen rows in postUp before dropping old id)
        $this->addSql('ALTER TABLE pet_species ADD available_at_signup TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function postUp(Schema $schema): void
    {
        // 4. Backfill available_at_signup using the original magic-id rule. Must happen BEFORE we drop the old integer id.
        $this->connection->executeStatement('UPDATE pet_species SET available_at_signup = 1 WHERE id <= 16 OR id = 96 OR id = 100');

        // 5. Safety check: no FK row should be NULL today (all three columns are NOT NULL), but verify before relying on it.
        $orphanedPets = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM pet WHERE species_id IS NULL');
        $orphanedBabies = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM pet_baby WHERE species_id IS NULL');
        $orphanedCollected = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM user_species_collected WHERE species_id IS NULL');

        if($orphanedPets > 0 || $orphanedBabies > 0 || $orphanedCollected > 0)
            throw new \RuntimeException(sprintf('Refusing to migrate: found NULL species_id rows (pet=%d, pet_baby=%d, user_species_collected=%d).', $orphanedPets, $orphanedBabies, $orphanedCollected));

        // 6. Generate ULIDs for each existing pet_species row
        $rows = $this->connection->fetchAllAssociative('SELECT id FROM pet_species');
        $idMap = [];

        foreach($rows as $row)
        {
            $oldId = $row['id'];
            $binary = (new Ulid())->toBinary();
            $idMap[$oldId] = $binary;

            $this->connection->executeStatement(
                'UPDATE pet_species SET new_id = :newId WHERE id = :oldId',
                ['newId' => $binary, 'oldId' => $oldId]
            );
        }

        // 7. Copy FK mappings for all three dependent tables
        foreach($idMap as $oldId => $binary)
        {
            $this->connection->executeStatement(
                'UPDATE pet SET new_species_id = :newSpeciesId WHERE species_id = :oldSpeciesId',
                ['newSpeciesId' => $binary, 'oldSpeciesId' => $oldId]
            );
            $this->connection->executeStatement(
                'UPDATE pet_baby SET new_species_id = :newSpeciesId WHERE species_id = :oldSpeciesId',
                ['newSpeciesId' => $binary, 'oldSpeciesId' => $oldId]
            );
            $this->connection->executeStatement(
                'UPDATE user_species_collected SET new_species_id = :newSpeciesId WHERE species_id = :oldSpeciesId',
                ['newSpeciesId' => $binary, 'oldSpeciesId' => $oldId]
            );
        }

        // 8. Drop old columns
        $this->connection->executeStatement('ALTER TABLE pet DROP COLUMN species_id');
        $this->connection->executeStatement('ALTER TABLE pet_baby DROP COLUMN species_id');
        $this->connection->executeStatement('ALTER TABLE user_species_collected DROP COLUMN species_id');
        $this->connection->executeStatement('ALTER TABLE pet_species DROP PRIMARY KEY, DROP COLUMN id');

        // 9. Rename shadow columns to canonical names and lock NOT NULL
        $this->connection->executeStatement('ALTER TABLE pet_species CHANGE new_id id BINARY(16) NOT NULL');
        $this->connection->executeStatement('ALTER TABLE pet CHANGE new_species_id species_id BINARY(16) NOT NULL');
        $this->connection->executeStatement('ALTER TABLE pet_baby CHANGE new_species_id species_id BINARY(16) NOT NULL');
        $this->connection->executeStatement('ALTER TABLE user_species_collected CHANGE new_species_id species_id BINARY(16) NOT NULL');

        // 10. Re-add primary key, indexes, FK constraints, and the user_species composite unique
        $this->connection->executeStatement('ALTER TABLE pet_species ADD PRIMARY KEY (id)');

        $this->connection->executeStatement('CREATE INDEX IDX_E4529B85B2A1D860 ON pet (species_id)');
        $this->connection->executeStatement('ALTER TABLE pet ADD CONSTRAINT FK_E4529B85B2A1D860 FOREIGN KEY (species_id) REFERENCES pet_species (id) ON DELETE RESTRICT ON UPDATE RESTRICT');

        $this->connection->executeStatement('CREATE INDEX IDX_9C246454B2A1D860 ON pet_baby (species_id)');
        $this->connection->executeStatement('ALTER TABLE pet_baby ADD CONSTRAINT FK_9C246454B2A1D860 FOREIGN KEY (species_id) REFERENCES pet_species (id) ON DELETE RESTRICT ON UPDATE RESTRICT');

        $this->connection->executeStatement('CREATE INDEX IDX_681CA342B2A1D860 ON user_species_collected (species_id)');
        $this->connection->executeStatement('CREATE UNIQUE INDEX user_species_idx ON user_species_collected (user_id, species_id)');
        $this->connection->executeStatement('ALTER TABLE user_species_collected ADD CONSTRAINT FK_681CA342B2A1D860 FOREIGN KEY (species_id) REFERENCES pet_species (id)');
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('Cannot reverse ULID migration — original auto-increment IDs are lost.');
    }
}
