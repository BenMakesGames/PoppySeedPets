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

final class Version20260508234900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shaved ice.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOSQL

        -- food
        INSERT INTO item_food (`id`, `food`, `love`, `junk`, `alcohol`, `earthy`, `fruity`, `tannic`, `spicy`, `creamy`, `meaty`, `planty`, `fishy`, `floral`, `fatty`, `oniony`, `chemically`, `caffeine`, `psychedelic`, `granted_skill`, `chance_for_bonus_item`, `random_flavor`, `contains_tentacles`, `granted_status_effect`, `granted_status_effect_duration`, `is_candy`, `leftovers_id`, `bonus_item_group_id`) VALUES (544, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, 0, 0, "Heat-resistant", 60, 1, NULL, NULL) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- tool
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (536, 0, 0, 0, 0, 0, 0.49, 0.71, 0, 0.5, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 482, 12, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1525, "Shaved Everice", "Perfect for that cold, cold crunch. _And_ feeling lingering in your stomach? Syrup not included.", "dessert/shaved-ice", NULL, 536, 544, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 0, NULL, 0, 1) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- item group
        INSERT IGNORE INTO item_group_item (item_group_id, item_id) VALUES (46, 1525);
        
        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1605,1525,"some") ON DUPLICATE KEY UPDATE `id` = `id`;   
        EOSQL);

    }

    public function down(Schema $schema): void
    {
    }
}
