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
 * Merits are named traits attached to a Pet (stat bonuses, unlocks, appearance
 * tweaks, gated activities, etc.). For a full catalogue of every merit — what
 * it does, how it's acquired, and the conventions for wiring one in — see
 * docs/features/merits.md.
 */
final class MeritEnum
{
    /** @use FakeEnum<string> */
    use FakeEnum;

    // obtained through affection rewards (for additional config, check MeritInfo.php)
    public const string EIDETIC_MEMORY = 'Eidetic Memory';
    public const string BLACK_HOLE_TUM = 'Black Hole Tum';
    public const string LUCKY = 'Lucky';
    public const string MOON_BOUND = 'Moon-bound';
    public const string NATURAL_CHANNEL = 'Natural Channel';
    public const string NO_SHADOW_OR_REFLECTION = 'No Shadow; No Reflection';
    public const string SOOTHING_VOICE = 'Soothing Voice';
    public const string SPIRIT_COMPANION = 'Spirit Companion';
    public const string PROTOCOL_7 = 'Protocol 7';
    public const string INTROSPECTIVE = 'Introspective';
    public const string VOLAGAMY = 'Volagamy';
    public const string GREEN_THUMB = 'Green Thumb'; // +1 nature and bonuses when assisting in the greenhouse or beehive
    public const string SHOCK_RESISTANT = 'Shock-resistant'; // immune to electric attacks; never fails to get bottles of lightning
    public const string DARKVISION = 'Darkvision'; // can see in the dark
    public const string GECKO_FINGERS = 'Gecko Fingers'; // +2 climbing
    public const string WAY_OF_THE_EMPTY_HAND = 'Way of the Empty Hand'; // +5 brawl when equipped with a weapon that does not provide brawl
    public const string ATHENAS_GIFTS = 'Athena\'s Gifts'; // sometimes get a Handicrafts Supply Box
    public const string IRON_STOMACH = 'Iron Stomach'; // receives half as much poison from poisonous foods
    public const string CELESTIAL_CHORUSER = 'Celestial Choruser';
    public const string CACHING = 'Caching';

    // obtained through items
    public const string BEHATTED = 'Behatted';
    public const string MIRRORED = 'Mirrored'; // flips graphic
    public const string INVERTED = 'Inverted'; // inverts pet colors
    public const string VERY_INVERTED = 'Very Inverted'; // inverts pet & equipment colors
    public const string WONDROUS_STRENGTH = 'Wondrous Strength';
    public const string WONDROUS_STAMINA = 'Wondrous Stamina';
    public const string WONDROUS_DEXTERITY = 'Wondrous Dexterity';
    public const string WONDROUS_PERCEPTION = 'Wondrous Perception';
    public const string WONDROUS_INTELLIGENCE = 'Wondrous Intelligence';
    public const string BIGGER_LUNCHBOX = 'Bigger Lunchbox';
    public const string BLUSH_OF_LIFE = 'Blush of Life';
    public const string MORTARS_AND_PESTLES = 'Mortars and Pestles';

    // obtained by seeking the Philosopher's Stone
    public const string METATRON_S_TOUCH = 'Metatron\'s Touch';
    public const string ICHTHYASTRA = 'Ichthyastra';
    public const string MANXOME = 'Manxome';
    public const string LIGHTNING_REINS = 'Lightning Reins';

    // obtained through house time spent
    public const string MIND_OVER_MATTER = 'Mind Over Matter';
    public const string MATTER_OVER_MIND = 'Matter Over Mind';
    public const string MODERATION = 'Moderation';

    public const string FORCE_OF_WILL = 'Force of Will';
    public const string FORCE_OF_NATURE = 'Force of Nature';
    public const string BALANCE = 'Balance';

    // available by becoming a grandparent pet
    public const string NEVER_EMBARRASSED = 'Never Embarrassed';
    public const string EVERLASTING_LOVE = 'Everlasting Love';
    public const string NOTHING_TO_FEAR = 'Nothing to Fear';

    // for Sága Jellings, only:
    public const string SAGA_SAGA = 'Sága Saga';
    public const string AFFECTIONLESS = 'Affectionless';

    // for Phoenixes, only:
    public const string ETERNAL = 'Eternal'; // +1 to all stats

    // starting merits; every pet gets one (for additional config, check MeritInfo.php)
    public const string BURPS_MOTHS = 'Burps Moths';
    public const string FRIEND_OF_THE_WORLD = 'Friend of the World'; // treats rivals as friends; treats fwbs as mates; always gives in to relationship changes
    public const string GOURMAND = 'Gourmand'; // larger stomach
    public const string SPECTRAL = 'Spectral'; // opacity = 75%; +1 stealth
    public const string PREHENSILE_TONGUE = 'Prehensile Tongue'; // +1 dex; ";P" instead of ";)"
    public const string LOLLIGOVORE = 'Lolligovore'; // bonus from eating tentacles
    public const string HYPERCHROMATIC = 'Hyperchromatic'; // randomly changes colors
    public const string DREAMWALKER = 'Dreamwalker'; // gets items from dreams
    public const string GREGARIOUS = 'Gregarious'; // can be in up to 4 groups
    public const string SHEDS = 'Sheds';
    public const string LUMINARY_ESSENCE = 'Luminary Essence'; // +1 to umbra + attracts more bugs
    public const string SILVERBLOOD = 'Silverblood'; // cannot become a werecreature; +5 when crafting with silver
    public const string DOPPEL_GENE = 'Doppel Gene'; // always gives birth to twins
    public const string FAIRY_GODMOTHER = 'Fairy Godmother';
    public const string RUMPELSTILTSKINS_CURSE = 'Rumpelstiltskin\'s Curse'; // gold instead of wheat, and vice-versa
}
