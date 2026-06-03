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

final class Version20260602235100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add Felidna';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        INSERT INTO `pet_species` (`id`, `name`, `image`, `description`, `hand_x`, `hand_y`, `hand_angle`, `flip_x`, `hand_behind`, `available_from_pet_shelter`, `pregnancy_style`, `egg_image`, `hat_x`, `hat_y`, `hat_angle`, `available_from_breeding`, `sheds_id`, `family`, `name_sort`, `physical_description`, `available_at_signup`) VALUES (UUID_TO_BIN("f4d92c39-9c98-4d3b-9447-78d8f9ff155e"), "Felidna", "monotreme/cat", "Felidna are black and white; felidna are rather small. Felidna are merry and bright, and pleasant to hear when they caterwaul.\n\nWait, that\'s Jellicle Cats.", 0.19, 0.695, -38, 0, 0, 1, 0, "striped-small", 0.4, 0.375, 7, 1, 34, "monotreme", "Felidna", "This \"cat\" (it\'s actually an echidna!) has the usual four paws, head, and tail. However, it is only mostly assembled - its neck and back left leg exist extradimensionally. That doesn\'t seem to hold it back much!", 0)
        ON DUPLICATE KEY UPDATE `id`=`id`;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
