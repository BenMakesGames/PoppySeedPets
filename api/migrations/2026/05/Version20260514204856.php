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

final class Version20260514204856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add Navier Stork';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        INSERT INTO `pet_species` (`id`, `name`, `image`, `description`, `hand_x`, `hand_y`, `hand_angle`, `flip_x`, `hand_behind`, `available_from_pet_shelter`, `pregnancy_style`, `egg_image`, `hat_x`, `hat_y`, `hat_angle`, `available_from_breeding`, `sheds_id`, `family`, `name_sort`, `physical_description`) VALUES (115, 'Navier Stork', 'bird/navier-stork', 'Most birds are very clever, and the Navier Stork is no exception: it\'s _crazy_ good at math; specifically math to do with fluid dynamics.\r\n\r\nWho knows why. _Presumably_ this skill gives it some survival or mating advantage, but... 🤷‍♀️', '0.2', '0.295', '-25', '1', '1', '1', '0', 'plain', '0.38', '0.135', '18', '1', '144', 'bird', 'Navier Stork', 'It has a long, narrow beak, and long, narrow legs, but get this: it\'s body isn\'t long OR narrow; in fact, it\'s quite plump-looking!\r\n\r\nYes: you could probably make a good holiday dinner out of this stork. But then who would deliver the babies? Or mathematically express the momentum balance for Newtonian fluids??')
        ON DUPLICATE KEY UPDATE `id`=`id`;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
