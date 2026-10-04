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

final class Version20260928120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'New item: Faded Treasure Map; Magic Brush is no longer Event Exclusive';
    }

    public function up(Schema $schema): void
    {
        // Faded Treasure Map: tool stats mirror Cetgueli's Treasure Map (item 100, tool 6)
        $this->addSql(<<<EOSQL
        -- tool effect
        INSERT INTO item_tool (`id`, `stealth`, `nature`, `brawl`, `arcana`, `crafts`, `grip_x`, `grip_y`, `grip_angle`, `grip_scale`, `fishing`, `gathering`, `music`, `smithing`, `science`, `grip_angle_fixed`, `focus_skill`, `provides_light`, `protection_from_heat`, `always_in_front`, `is_ranged`, `when_gather_id`, `when_gather_also_gather_id`, `climbing`, `leads_to_adventure`, `prevents_bugs`, `attracts_bugs`, `can_be_nibbled`, `increases_pooping`, `dreamcatcher`, `is_grayscaling`, `social_energy_modifier`, `sex_drive`, `when_gather_prevent_gather`, `adventure_description`, `when_gather_apply_status_effect`, `when_gather_apply_status_effect_duration`, `physics`, `electronics`, `hacking`, `umbra`, `magic_binding`, `mining`) VALUES (540,0,0,0,0,0,0.265625,0.703125,13,0.5,0,0,0,0,0,0,NULL,0,0,0,0,NULL,NULL,0,1,0,0,0,0,0,0,0,0,0,"a treasure hunt!",NULL,NULL,0,0,0,0,0,0) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- the item itself!
        INSERT INTO item (`id`, `name`, `description`, `image`, `use_actions`, `tool_id`, `food_id`, `fertilizer`, `plant_id`, `hat_id`, `fuel`, `recycle_value`, `enchants_id`, `spice_id`, `treasure_id`, `is_bug`, `hollow_earth_tile_card_id`, `cannot_be_thrown_out`, `museum_points`) VALUES (1529,"Faded Treasure Map","The ink has faded almost to nothing, and the landmarks are anyone's guess, but there's still an X on it!

        Equip it to a pet to send them on a treasure hunt.","map/faded",NULL,540,NULL,0,NULL,NULL,600,0,NULL,NULL,NULL,0,NULL,0,20) ON DUPLICATE KEY UPDATE `id` = `id`;

        -- grammar
        INSERT INTO item_grammar (`id`, `item_id`, `article`) VALUES (1609,1529,"a") ON DUPLICATE KEY UPDATE `id` = `id`;

        -- Magic Brush (707) can now be found with a Faded Treasure Map, so is no longer Event Exclusive (55)
        DELETE FROM item_group_item WHERE item_group_id = 55 AND item_id = 707;
        EOSQL);
    }

    public function down(Schema $schema): void
    {
    }
}
