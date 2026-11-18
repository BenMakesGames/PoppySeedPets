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

final class Version20261118003501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update Rapier description; add new pet species';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        UPDATE `item` SET `description` = 'Being of French origin, you might expect this item to be said like _rah-PYAY_, but the original French spelling is \"Rapière\", which is said _rah-PYERH_, so... eh: language: so confusing.' WHERE `item`.`id` = 107;
        EOSQL);
        
        $this->addSql(<<<EOSQL
        INSERT INTO pet_species (`id`, `name`, `image`, `description`, `hand_x`, `hand_y`, `hand_angle`, `flip_x`, `hand_behind`, `available_from_pet_shelter`, `pregnancy_style`, `egg_image`, `hat_x`, `hat_y`, `hat_angle`, `available_from_breeding`, `sheds_id`, `family`, `name_sort`, `physical_description`, `available_at_signup`) VALUES (0xe8dc0ee8e2ef11f1b96efcaa1495120e,"Otter-eared Mouse","mammal/otter-eared-mouse","It looks so plump, like a tasty little plum! But presumably more full of bones??",0.595,0.905,63,0,1,1,1,NULL,0.735,0.575,-2,1,34,"mammal","Otter-eared Mouse","It has the general shape of a mouse or rat, with a longer face, and short, slightly-pointed ears. Though it only has two stick-thin legs, it moves with great speed!",0) ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
