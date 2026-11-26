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

final class Version20261126175800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add B. PACK, C. PACK, D. PACK, and E. PACK';
    }

    public function up(Schema $schema): void
    {
        // B. PACK
        $this->addSql(<<<EOSQL
        -- tool effect
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (541,0,0,0,0,0,0.36,0.455,0,0.67,0,4,0,0,0,0,NULL,0,0,0,0,1286,1286,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,0,0,0,0,0,0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1531,"B. PACK","It\'s just a backpack, but I guess 8 characters was too many?","tool/backpack/b",NULL,541,NULL,0,NULL,NULL,0,0,NULL,NULL,NULL,0,NULL,0,1) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1611,1531,"a") ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        // C. PACK
        $this->addSql(<<<EOSQL
        -- tool effect
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (542,0,0,0,0,0,0.36,0.455,0,0.67,4,0,0,0,0,0,NULL,0,0,0,0,126,126,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,0,0,0,0,0,0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1532,"C. PACK","Ohhhhh: because \"C\" and \"sea\" are homonyms, or whatever. I _get_ it...","tool/backpack/c",NULL,542,NULL,0,NULL,NULL,0,0,NULL,NULL,NULL,0,NULL,0,2) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1612,1532,"a") ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        // D. PACK
        $this->addSql(<<<EOSQL
        -- tool effect
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (543,0,4,0,0,0,0.36,0.455,0,0.67,0,0,0,0,0,0,NULL,0,0,0,0,961,961,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,0,0,0,0,0,0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1533,"D. PACK","Hey: just what the heck is going on here?!?","tool/backpack/d",NULL,543,NULL,0,NULL,NULL,0,0,NULL,NULL,NULL,0,NULL,0,3) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1613,1533,"a") ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);

        // E. PACK
        $this->addSql(<<<EOSQL
        -- tool effect
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (544,0,0,0,0,0,0.36,0.455,0,0.67,0,0,0,0,0,0,"stealth",0,0,0,0,NULL,NULL,0,0,0,0,0,0,0,0,0,0,0,NULL,NULL,NULL,0,0,4,0,0,0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1534,"E. PACK","\"If you want to be elite, you\'ve got to do a righteous hack!\" ~Ramon Sanchez","tool/backpack/e",NULL,544,NULL,0,NULL,NULL,0,0,NULL,NULL,NULL,0,NULL,0,4) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1614,1534,"an") ON DUPLICATE KEY UPDATE `id` = `id`;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
