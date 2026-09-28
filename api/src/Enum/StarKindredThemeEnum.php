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

namespace App\Enum;

/**
 * The setting of a procedurally-generated ★Kindred adventure. Loot tables are carried over from the
 * original, hand-made ★Kindred stories (and their REMIX).
 *
 * Item and hat-styling names here are validated by ValidateStarKindredRewardsTest.
 */
enum StarKindredThemeEnum: string
{
    case Shipwreck = 'Shipwreck';
    case Beach = 'Beach';
    case Forest = 'Forest';
    case Mine = 'Mine';
    case UndergroundLake = 'UndergroundLake';
    case MagicTower = 'MagicTower';
    case UmbralFields = 'UmbralFields';
    case DragonLair = 'DragonLair';
    case Graveyard = 'Graveyard';
    case TheDeep = 'TheDeep';
    case TreasureVault = 'TreasureVault';
    case BanditCamp = 'BanditCamp';
    case FairyMarket = 'FairyMarket';
    case HauntedWoods = 'HauntedWoods';
    case HuntingGrounds = 'HuntingGrounds';
    case Quarry = 'Quarry';

    /**
     * @return string[]
     */
    public function places(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Wreck of the Merry Kraken', 'Wreck of the Gilded Gull', 'Sunken Galleon', 'Ship Graveyard', 'Wreck of the Salt Maiden' ],
            self::Beach => [ 'Shimmering Shore', 'Sandy Cove', 'Seagull Strand', 'Coconut Coast', 'Tidepool Beach' ],
            self::Forest => [ 'Whispering Wood', 'Greenmantle Forest', 'Old Oak Grove', 'Mossy Hollow', 'Tangleroot Forest' ],
            self::Mine => [ 'Abandoned Gold Mine', 'Glittering Caverns', 'Old Dwarven Mine', 'Echoing Mineshaft', 'Crystal Caves' ],
            self::UndergroundLake => [ 'Mirror Lake', 'Still Waters Below', 'Frozen Grotto', 'Lake of Echoes', 'Glowcap Lagoon' ],
            self::MagicTower => [ 'Amethyst Tower', 'Tower of the Mad Magus', 'Leaning Spire', 'Starlit Observatory', 'Sealed Athenaeum' ],
            self::UmbralFields => [ 'Purple Plains', 'Umbral Meadow', 'Twilight Fields', 'Swaying Violet Steppe', 'Gloaming Prairie' ],
            self::DragonLair => [ 'Dragon\'s Roost', 'Smoldering Peak', 'Wyrm\'s Hoard', 'Ashen Caldera', 'Serpent Throne' ],
            self::Graveyard => [ 'Forgotten Graveyard', 'Silent Crypt', 'Catacombs of the Old Kings', 'Moonlit Cemetery', 'Bone Garden' ],
            self::TheDeep => [ 'Magma Rivers', 'Deep Dark', 'Roots of the World', 'Abyssal Passage', 'Burning Depths' ],
            self::TreasureVault => [ 'Forgotten Vault', 'Hall of Riches', 'Miser\'s Treasury', 'Gilded Labyrinth', 'Lost King\'s Hoard' ],
            self::BanditCamp => [ 'Bandit Encampment', 'Outlaws\' Hideout', 'Robbers\' Roost', 'Highwayman\'s Pass', 'Smugglers\' Den' ],
            self::FairyMarket => [ 'Fairy Market', 'Moonlit Bazaar', 'Mushroom Ring Fair', 'Market of Strange Bargains', 'Glimmering Glade' ],
            self::HauntedWoods => [ 'Haunted Woods', 'Wailing Thicket', 'Restless Glade', 'Shadowed Canopy', 'Spirit-wood' ],
            self::HuntingGrounds => [ 'Great Plains', 'Thundering Valley', 'Wild Highlands', 'Hunters\' Meadow', 'Tall Grass Savanna' ],
            self::Quarry => [ 'Old Quarry', 'Limestone Cliffs', 'Golem Pits', 'Boulder Fields', 'Rockfall Canyon' ],
        };
    }

    /**
     * Foes Clerics & Paladins can Banish. Every name here must appear in some theme's foes() (see StarKindredDailyAdventuresTest).
     */
    public const array UndeadFoes = [
        'Spectral Sailors', 'Drowned Crew', 'Frost Wraiths', 'Restless Dead', 'Bone Knights', 'Wailing Banshees',
        'Restless Animal Spirits',
    ];

    public static function isUndeadFoe(string $foe): bool
    {
        return in_array($foe, self::UndeadFoes, true);
    }

    /**
     * @return string[]
     */
    public function foes(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Goblin Scavengers', 'Spectral Sailors', 'Drowned Crew', 'Giant Crabs' ],
            self::Beach => [ 'Seagull Raiders', 'Seagull Wizards', 'Sand Elementals', 'Pirate Raiders' ],
            self::Forest => [ 'Forest Dragons', 'Thorn Beasts', 'Grumpy Treants', 'Dire Wolves' ],
            self::Mine => [ 'Cave Trolls', 'Rock Worms', 'Kobold Miners', 'Gold-hungry Imps' ],
            self::UndergroundLake => [ 'Lake Serpents', 'Frost Wraiths', 'Fish-folk Warriors', 'Lurking Reflections' ],
            self::MagicTower => [ 'Animated Armor', 'Rogue Apprentices', 'Living Spellbooks', 'Arcane Sentinels' ],
            self::UmbralFields => [ 'Tentacled Stalkers', 'Umbral Shades', 'Children of Noetala', 'Grass Whisperers' ],
            self::DragonLair => [ 'Red Dragons', 'Dragon Cultists', 'Wyverns', 'Ancient Wyrms' ],
            self::Graveyard => [ 'Restless Dead', 'Grave Robbers', 'Bone Knights', 'Wailing Banshees' ],
            self::TheDeep => [ 'Deep Horrors', 'Magma Elementals', 'Blind Crawlers', 'Things Without Names' ],
            self::TreasureVault => [ 'Vault Guardians', 'Mimics', 'Golden Golems', 'Rival Treasure Hunters' ],
            self::BanditCamp => [ 'Bandit Lords', 'Outlaw Archers', 'Smugglers', 'Hired Thugs' ],
            self::FairyMarket => [ 'Fairy Swindlers', 'Trickster Sprites', 'Pixie Pickpockets', 'Market Wardens' ],
            self::HauntedWoods => [ 'Restless Animal Spirits', 'Will-o\'-Wisps', 'Shadow Stags', 'Hollow Hunters' ],
            self::HuntingGrounds => [ 'Great Beasts', 'Thunderbirds', 'Stampeding Aurochs', 'Dire Boars' ],
            self::Quarry => [ 'Limestone Golems', 'Rock Trolls', 'Gravel Gremlins', 'Boulder Giants' ],
        };
    }

    /**
     * Skills this setting tends to test, beyond the adventure's main objective.
     * @return StarKindredSkillEnum[]
     */
    public function skills(): array
    {
        return match($this)
        {
            self::Shipwreck => [ StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Lore ],
            self::Beach => [ StarKindredSkillEnum::Survival, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Perception ],
            self::Forest => [ StarKindredSkillEnum::Survival, StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Acrobatics ],
            self::Mine => [ StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Combat ],
            self::UndergroundLake => [ StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Endurance ],
            self::MagicTower => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Acrobatics, StarKindredSkillEnum::Combat ],
            self::UmbralFields => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Survival, StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Endurance ],
            self::DragonLair => [ StarKindredSkillEnum::Combat, StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Persuasion, StarKindredSkillEnum::Endurance ],
            self::Graveyard => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Stealth ],
            self::TheDeep => [ StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Perception ],
            self::TreasureVault => [ StarKindredSkillEnum::Perception, StarKindredSkillEnum::Acrobatics, StarKindredSkillEnum::Lore, StarKindredSkillEnum::Stealth ],
            self::BanditCamp => [ StarKindredSkillEnum::Stealth, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Persuasion, StarKindredSkillEnum::Acrobatics ],
            self::FairyMarket => [ StarKindredSkillEnum::Persuasion, StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Perception, StarKindredSkillEnum::Lore ],
            self::HauntedWoods => [ StarKindredSkillEnum::Arcana, StarKindredSkillEnum::Survival, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Stealth ],
            self::HuntingGrounds => [ StarKindredSkillEnum::Survival, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Stealth ],
            self::Quarry => [ StarKindredSkillEnum::Athletics, StarKindredSkillEnum::Endurance, StarKindredSkillEnum::Combat, StarKindredSkillEnum::Lore ],
        };
    }

    /**
     * The Veteran reward is one of these, as a stack of the given quantity.
     * @return array<string, int> item name => quantity
     */
    public function prizes(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Seaweed' => 4, 'Enchanted Compass' => 1 ],
            self::Beach => [ 'Sand Dollar' => 2, 'Fish Bag' => 1, 'Rainbow' => 1 ],
            self::Forest => [ 'Nature Box' => 1, 'Wrapped Sword' => 1 ],
            self::UmbralFields => [ 'Nature Box' => 2 ],
            self::Mine => [ 'Box of Ores' => 1, 'Dark Matter' => 2 ],
            self::UndergroundLake => [ 'Fish Bag' => 2 ],
            self::MagicTower, self::Graveyard => [ 'Quintessence' => 2 ],
            self::DragonLair, self::HauntedWoods, self::HuntingGrounds => [ 'Monster Box' => 2 ],
            self::TheDeep => [ 'Liquid-hot Magma' => 2 ],
            self::TreasureVault => [ 'Gold Bar' => 2 ],
            self::BanditCamp => [ 'White Cloth' => 2 ],
            self::FairyMarket => [ 'Music Note' => 2 ],
            self::Quarry => [ 'Rock' => 2 ],
        };
    }

    /**
     * The Novice reward is one of these.
     * @return string[]
     */
    public function lootTable(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Seaweed', 'Silica Grounds', 'Crooked Stick', 'String', 'Rock', 'Plastic Bottle', 'Canned Food', 'Gold Bar', 'Fish Stew', 'Quintessence', 'Paper Boat' ],
            self::Beach => [ 'Scales', 'Fish', 'Coconut', 'Really Big Leaf', 'Naner', 'Silica Grounds', 'Crooked Stick', 'Seaweed', 'Feathers' ],
            self::Forest => [ 'Orange', 'Naner', 'Red', 'Fluff', 'Crooked Stick', 'Blackberries', 'Blueberries', 'Sweet Beet' ],
            self::Mine => [ 'Gold Ore', 'Silver Ore', 'Iron Ore', 'Iron Ore', 'Rock', 'Silica Grounds', 'Gypsum' ],
            self::UndergroundLake => [ 'Toadstool', 'Toadstool', 'Rock', 'Chanterelle', 'Chanterelle', 'Everice', 'Everice', 'Fish Bones', 'Cobweb', 'Quintessence' ],
            self::MagicTower => [ 'Quintessence', 'Tiny Scroll of Resources', 'Crystal Ball', 'Silver Bar', 'Glass', 'Mikronium', 'Megalium', 'Gold Tuning Fork', 'Quinacridone Magenta Dye', 'White Cloth', 'Viscaria', 'Wolf\'s Bane', 'Witch-hazel', 'Liquid-hot Magma' ],
            self::UmbralFields => [ 'Purple Corn', 'Purple Corn', 'Tentacle', 'Quinacridone Magenta Dye', 'Quintessence' ],
            self::DragonLair => [ 'Talon', 'Scales', 'Gold Bar', 'Gold Bar', 'Gold Bar', 'Silver Bar', 'Silver Bar', 'Dino Skull', 'Gold Key', 'Silver Colander', 'Liquid-hot Magma', 'Dragon Tongue', 'Striped Microcline', 'Burnt Log' ],
            self::Graveyard => [ 'Rock', 'Quintessence', 'Quintessence', 'Filthy Cloth', 'Grandparoot', 'Stereotypical Bone', 'Cobweb' ],
            self::TheDeep => [ 'Liquid-hot Magma', 'Liquid-hot Magma', 'Iron Ore', 'Silver Ore', 'Gold Ore', 'Striped Microcline', 'Tentacle', 'Talon', 'Scales', 'Dark Matter', 'Gravitational Waves', 'Quintessence' ],
            self::TreasureVault => [ 'Gold Bar', 'Gold Bar', 'Gold Bar', 'Silver Bar', 'Silver Bar', 'Gold Key', 'Silver Key', 'Gold Triangle', 'Silver Colander', 'Scroll of Resources', 'Tiny Scroll of Resources', 'Firestone', '"Gold" Idol', 'Rib', 'Scroll of Tell Samarzhoustian Delights', 'Gold Ring' ],
            self::BanditCamp => [ 'White Cloth', 'Stereotypical Torch', 'Gold Bar', 'Fish Stew', 'Takoyaki', 'Kilju', 'Grilled Fish', 'Onigiri', 'Potato' ],
            self::FairyMarket => [ 'Quintessence', 'Jar of Fireflies', 'World\'s Best Sugar Cookie', 'Wings', 'Music Note', 'Pink Fairy Floss', 'Coriander Flower', 'Moon Pearl' ],
            self::HauntedWoods => [ 'Crooked Stick', 'Quintessence', 'Quintessence', 'Talon', 'Dark Scales', 'Music Note', 'Feathers' ],
            self::HuntingGrounds => [ 'Feathers', 'Fluff', 'Talon', 'Scales', 'Egg', 'Fish' ],
            self::Quarry => [ 'Rock', 'Rock', 'Silica Grounds', 'Limestone', 'Limestone', 'Iron Ore', 'Gypsum' ],
        };
    }

    /**
     * The Demigod reward is one of these.
     * @return string[]
     */
    public function treasures(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Secret Seashell', 'Scroll of the Sea' ],
            self::Beach => [ 'Secret Seashell', 'Scroll of the Sea' ],
            self::Forest => [ 'Monster Box', 'Raven\'s Beak' ],
            self::Mine => [ 'Fierierstone', 'Very Strongbox' ],
            self::UndergroundLake => [ 'Ice "Mango"', 'Cup of Life', 'Fish Bag' ],
            self::MagicTower => [ 'Tower Chest', 'Scroll of Illusions', 'Scroll of Dice', 'Twilight Box' ],
            self::UmbralFields => [ 'Noetala Egg', 'Twilight Box', 'Quinacridone Magenta Dye' ],
            self::DragonLair => [ 'Gold Chest', 'Ruby Chest', 'Monster Box' ],
            self::Graveyard => [ 'Blackonite', 'Dino Skull', 'Stereotypical Bone' ],
            self::TheDeep => [ 'Firestone', 'Monster Box', 'Box of Ores' ],
            self::TreasureVault => [ 'Gold Chest', 'Ruby Chest', 'Piece of Cetgueli\'s Map' ],
            self::BanditCamp => [ 'Wrapped Sword', 'Piece of Cetgueli\'s Map', 'Black Flag' ],
            self::FairyMarket => [ 'Glowing Protojelly', 'Magpie Pouch', 'Espophone', 'Rainbow' ],
            self::HauntedWoods => [ 'Monster-summoning Scroll', 'Twilight Box', 'Cup of Life' ],
            self::HuntingGrounds => [ 'Handicrafts Supply Box', 'Stereotypical Bone', 'Monster Box' ],
            self::Quarry => [ 'Box of Ores', 'Sand-covered... Something', 'Dino Skull' ],
        };
    }

    /**
     * The Hero reward is one of these or one of the setting's hat stylings (all equally likely).
     * @return string[]
     */
    public function heroTreasures(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'Rusted, Busted Mechanism', 'Ceremonial Trident' ],
            self::Forest => [ 'Magic Leaf', 'Monster Box' ],
            self::BanditCamp => [ 'Wrapped Sword', 'Piece of Cetgueli\'s Map', 'Black Flag' ],
            self::Mine => [ 'Sand-covered... Something', 'Blackonite' ],
            self::Beach, self::UndergroundLake, self::MagicTower, self::UmbralFields,
            self::DragonLair, self::Graveyard, self::TheDeep, self::TreasureVault,
            self::FairyMarket, self::HauntedWoods, self::HuntingGrounds, self::Quarry => [],
        };
    }

    /**
     * Hat stylings the Hero reward may be (see heroTreasures).
     * @return string[]
     */
    public function auras(): array
    {
        return match($this)
        {
            self::Shipwreck => [ 'with Anchor', 'Sea Prince\'s' ],
            self::Beach => [ 'Lobster', 'with Anchor' ],
            self::Mine, self::Quarry => [ 'with Cave Mushrooms' ],
            self::UndergroundLake => [ 'Sea Prince\'s' ],
            self::MagicTower, self::FairyMarket => [ 'Amethyst' ],
            self::DragonLair, self::TreasureVault => [ 'of Tishpak' ],
            self::Graveyard, self::HauntedWoods => [ 'Bandaged' ],
            self::TheDeep, self::UmbralFields, self::HuntingGrounds => [ 'with Monster Skull' ],
            self::Forest, self::BanditCamp => [],
        };
    }
}
