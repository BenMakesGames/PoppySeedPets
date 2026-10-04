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

final class Version20260527023125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add The First Story of Takae Su Suzi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1524,"The First Story of Takae Su Suzi",NULL,"book/takae-su-suzi-1","[[\"Read\",\"theFirstStoryOfTakaeSuSuzi\\/#\\/read\"]]",NULL,NULL,0,NULL,NULL,90,0,NULL,NULL,NULL,0,NULL,0,1) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1604,1524,NULL) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- item groups
        INSERT IGNORE INTO item_group_item (item_group_id, item_id) VALUES (40, 1524);
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
