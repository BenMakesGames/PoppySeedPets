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

final class Version20260718204437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add Sandstrider';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        INSERT INTO `pet_species` (`id`, `name`, `image`, `description`, `hand_x`, `hand_y`, `hand_angle`, `flip_x`, `hand_behind`, `available_from_pet_shelter`, `pregnancy_style`, `egg_image`, `hat_x`, `hat_y`, `hat_angle`, `available_from_breeding`, `sheds_id`, `family`, `name_sort`, `physical_description`, `available_at_signup`) VALUES
        (0x019f76fea10e9f7c0fbf05ad485a7566, 'Sandstrider', 'bird/strider', 'This bird can be found dashing around on the beaches of Poppy Seed Pets Island. It\'s a totally adorable sight... unless you\'re a tiny lizard, in which case the sight of it is probably the last thing you\'ll _ever_ see.', 0.46, 0.7, 74, 0, 0, 1, 0, 'speckled-small', 0.75, 0.435, -11, 1, 144, 'bird', 'Sandstrider', 'A small bird with long legs, and an even longer tail. It has a narrow, subtly-curved beak.', 0)
        ON DUPLICATE KEY UPDATE `id`=`id`;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
