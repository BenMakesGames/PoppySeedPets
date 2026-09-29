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

final class Version20260928202315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add Elf Ears';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL
        -- hat
        INSERT INTO item_hat (`id`, `head_x`, `head_y`, `head_angle`, `head_scale`, `head_angle_fixed`) VALUES (317,0.505,0.5,13,0.66,0) ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- food effect
        INSERT INTO item_food (`id`, `food`, `love`, `junk`, `alcohol`, `earthy`, `fruity`, `tannic`, `spicy`, `creamy`, `meaty`, `planty`, `fishy`, `floral`, `fatty`, `oniony`, `chemically`, `caffeine`, `psychedelic`, `granted_skill`, `chance_for_bonus_item`, `random_flavor`, `contains_tentacles`, `granted_status_effect`, `granted_status_effect_duration`, `is_candy`, `leftovers_id`, `bonus_item_group_id`) VALUES (546,6,0,0,0,2,0,0,0,0,2,0,0,1,0,0,0,0,2,NULL,NULL,0,0,NULL,NULL,0,NULL,NULL) ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1530,"Elf Ears","Oh, fun: it\'s, like, plastic ears to wear on your ears, right?","hat/elf-ears",NULL,NULL,546,6,NULL,317,0,0,NULL,NULL,NULL,0,NULL,0,10) ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1610,1530,"some") ON DUPLICATE KEY UPDATE `id` = `id`;
        
        -- item groups
        INSERT IGNORE INTO item_group_item (item_group_id, item_id) VALUES (46, 1530);
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
