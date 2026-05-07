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

namespace App\Model;

use App\Enum\MeritEnum;

final class MeritInfo
{
    public const array POSSIBLE_STARTING_MERITS = [
        ...self::POSSIBLE_FIRST_PET_STARTING_MERITS,

        // has pros and cons, so doesn't feel appropriate for a first pet
        MeritEnum::FRIEND_OF_THE_WORLD,
        MeritEnum::RUMPELSTILTSKINS_CURSE,

        // changes appearance, which a player's first pet might not want
        MeritEnum::SPECTRAL,
        MeritEnum::HYPERCHROMATIC,
    ];

    public const array POSSIBLE_FIRST_PET_STARTING_MERITS = [
        MeritEnum::BURPS_MOTHS,
        MeritEnum::GOURMAND,
        MeritEnum::PREHENSILE_TONGUE,
        MeritEnum::LOLLIGOVORE,
        MeritEnum::DREAMWALKER,
        MeritEnum::GREGARIOUS,
        MeritEnum::SHEDS,
        MeritEnum::DOPPEL_GENE,
        MeritEnum::FAIRY_GODMOTHER,
        MeritEnum::LUMINARY_ESSENCE,
        MeritEnum::SILVERBLOOD,
    ];

    public const array AFFECTION_REWARDS = [
        // stat-based:
        MeritEnum::EIDETIC_MEMORY, // int >= 3
        MeritEnum::MOON_BOUND, // str >= 3
        MeritEnum::GECKO_FINGERS, // dex => 3
        MeritEnum::DARKVISION, // per >= 3
        MeritEnum::IRON_STOMACH, // sta >= 3

        // skill-based:
        MeritEnum::GREEN_THUMB, // nature >= 5
        MeritEnum::SHOCK_RESISTANT, // science >= 5
        MeritEnum::WAY_OF_THE_EMPTY_HAND, // brawl >= 5
        MeritEnum::ATHENAS_GIFTS, // crafts >= 5
        MeritEnum::NO_SHADOW_OR_REFLECTION, // stealth >= 5
        MeritEnum::CELESTIAL_CHORUSER, // music >= 5
        MeritEnum::SPIRIT_COMPANION, // arcana >= 5

        // anytime:
        MeritEnum::LUCKY,
        MeritEnum::BLACK_HOLE_TUM,
        MeritEnum::NATURAL_CHANNEL,
        MeritEnum::PROTOCOL_7,
        MeritEnum::SOOTHING_VOICE,
        MeritEnum::CACHING,

        MeritEnum::INTROSPECTIVE, // relationship count >= 3
        MeritEnum::VOLAGAMY, // age >= 14 days
    ];

    public const array FORGETTABLE_MERITS = [
        // starting merits
        ...self::POSSIBLE_STARTING_MERITS,

        // affection rewards
        ...self::AFFECTION_REWARDS,

        // from items
        MeritEnum::BEHATTED,
        MeritEnum::MIRRORED,
        MeritEnum::INVERTED,
        MeritEnum::VERY_INVERTED,
        MeritEnum::WONDROUS_STRENGTH,
        MeritEnum::WONDROUS_STAMINA,
        MeritEnum::WONDROUS_DEXTERITY,
        MeritEnum::WONDROUS_PERCEPTION,
        MeritEnum::WONDROUS_INTELLIGENCE,
        MeritEnum::MORTARS_AND_PESTLES,

        // from seeking the Philosopher's Stone
        MeritEnum::METATRON_S_TOUCH,
        MeritEnum::ICHTHYASTRA,
        MeritEnum::MANXOME,
        MeritEnum::LIGHTNING_REINS,

        // saga jellings
        MeritEnum::SAGA_SAGA,
        MeritEnum::AFFECTIONLESS,

        // phoenixes
        MeritEnum::ETERNAL,
    ];

    public const array SELF_REFLECTION_MERITS = [
        MeritEnum::OF_HEART_AND_MIND,
        MeritEnum::PRECIOUS_MEMORIES,
        MeritEnum::SECOND_STOMACH,
    ];
}