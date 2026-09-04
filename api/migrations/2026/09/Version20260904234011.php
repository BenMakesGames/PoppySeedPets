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

final class Version20260904234011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        -- plant
        INSERT INTO plant (`id`, `sprout_image`, `medium_image`, `adult_image`, `harvestable_image`, `time_to_adult`, `time_to_fruit`, `type`, `name`, `field_guide_entry_id`, `no_pollinators`) VALUES (52,"sprout-tree","medium-tree","adult-tree","fruiting-caramel-red",124,36,"earth","Caramel-covered Red Tree",NULL,0) ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- plant yield
        INSERT INTO plant_yield (`id`, `plant_id`, `min`, `max`) VALUES (106,52,3,5) ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- plant yield item
        INSERT INTO plant_yield_item (`id`, `plant_yield_id`, `item_id`, `percent_chance`) VALUES (188,106,547,100) ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        $this->addSql("UPDATE `item` SET `plant_id` = '52' WHERE `item`.`id` = 547;");
    }

    public function down(Schema $schema): void
    {
    }
}
