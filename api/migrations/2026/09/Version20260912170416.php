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

final class Version20260912170416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Beehive: helper progress bar + 19 terrain spaces.';
    }

    // rows of 3/4/5/4/3; exactly 7 jungle, 5 beach, 4 grassy, 3 rocky, arranged so no terrain forms a solid block
    private const array ExistingBeehiveLayout = [
        'jungle', 'beach', 'grassy',
        'rocky', 'jungle', 'beach', 'jungle',
        'beach', 'grassy', 'jungle', 'rocky', 'beach',
        'jungle', 'rocky', 'grassy', 'jungle',
        'grassy', 'beach', 'jungle',
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beehive ADD helper_progress DOUBLE PRECISION NOT NULL, ADD spaces JSON NOT NULL');

        // MySQL fills the new NOT NULL JSON column with JSON null for existing rows; give every existing beehive the same fixed layout
        $spaces = array_map(fn(string $type) => [ 'type' => $type, 'harvested' => false ], self::ExistingBeehiveLayout);

        $this->addSql('UPDATE beehive SET spaces = :spaces', [ 'spaces' => json_encode($spaces) ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beehive DROP helper_progress, DROP spaces');
    }
}
