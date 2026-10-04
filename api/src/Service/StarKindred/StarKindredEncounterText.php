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

namespace App\Service\StarKindred;

use App\Enum\StarKindredSkillEnum;

/**
 * Text templates for procedurally-generated adventures. Tokens: {place}, {foe}, {relic}, {captive}.
 *
 * Every adventure's final encounter is its objective, and every skill has exactly one objective, so
 * two adventures with different objectives always lead with different skills.
 */
final class StarKindredEncounterText
{
    /**
     * @return array{title: string, summary: string}
     */
    public static function objective(StarKindredSkillEnum $skill): array
    {
        return match($skill)
        {
            StarKindredSkillEnum::Combat => [ 'title' => 'Defeat the {foe}', 'summary' => 'The {foe} of the {place} have terrorized the countryside for too long. Someone has to stop them!' ],
            StarKindredSkillEnum::Athletics => [ 'title' => 'Rescue {captive}', 'summary' => '{captive} went missing near the {place}. The trail leads somewhere steep, and there isn\'t much time.' ],
            StarKindredSkillEnum::Stealth => [ 'title' => 'Steal the {relic}', 'summary' => 'The {relic} is kept deep within the {place}, guarded by the {foe}. Best not to be seen.' ],
            StarKindredSkillEnum::Survival => [ 'title' => 'Chart the {place}', 'summary' => 'No map of the {place} has ever been finished. The royal cartographers will pay handsomely for one.' ],
            StarKindredSkillEnum::Perception => [ 'title' => 'The Mystery of the {place}', 'summary' => 'Strange things have been happening around the {place}. Travelers vanish; lights flicker at night. What\'s really going on?' ],
            StarKindredSkillEnum::Persuasion => [ 'title' => 'Parley with the {foe}', 'summary' => 'War with the {foe} of the {place} seems certain... unless someone can talk them down.' ],
            StarKindredSkillEnum::Arcana => [ 'title' => 'Break the Curse of the {place}', 'summary' => 'An ancient curse hangs over the {place}. Undo it, and its riches are free for the taking.' ],
            StarKindredSkillEnum::Lore => [ 'title' => 'Secrets of the {relic}', 'summary' => 'Scholars say the {relic} was lost in the {place}. Only someone who knows the old stories could find it.' ],
            StarKindredSkillEnum::Acrobatics => [ 'title' => 'Race Across the {place}', 'summary' => 'A message must cross the {place} by nightfall. It\'s a treacherous route, full of pitfalls - and the {foe}.' ],
            StarKindredSkillEnum::Endurance => [ 'title' => 'Survive the {place}', 'summary' => 'Few who enter the {place} return. A prize awaits anyone who can make it to the far side.' ],
        };
    }

    /**
     * @return list<array{title: string, success: string, failure: string}>
     */
    public static function encounters(StarKindredSkillEnum $skill): array
    {
        return match($skill)
        {
            StarKindredSkillEnum::Combat => [
                [ 'title' => 'Ambush!', 'success' => 'The {foe} leap out of hiding, but the party is ready, and drives them off!', 'failure' => 'The {foe} leap out of hiding! The party is forced to retreat, leaving some supplies behind.' ],
                [ 'title' => 'The Champion', 'success' => 'The {foe} send out their mightiest champion. After a long duel, the champion yields!', 'failure' => 'The {foe} send out their mightiest champion, who proves too much for the party.' ],
                [ 'title' => 'Hold the Line', 'success' => 'The party holds a narrow pass against wave after wave of the {foe}!', 'failure' => 'The party tries to hold a narrow pass, but the {foe} break through.' ],
            ],
            StarKindredSkillEnum::Athletics => [
                [ 'title' => 'The Cliff', 'success' => 'A sheer cliff blocks the way. The party scales it without trouble.', 'failure' => 'A sheer cliff blocks the way. After several slips, the party gives up and takes the long way around.' ],
                [ 'title' => 'Rushing River', 'success' => 'The party fights its way across a rushing river.', 'failure' => 'A rushing river sweeps the party downstream, far from where they meant to be.' ],
                [ 'title' => 'Collapsed Passage', 'success' => 'Heavy rubble blocks the path; the party heaves it aside.', 'failure' => 'Heavy rubble blocks the path, and it will not budge.' ],
            ],
            StarKindredSkillEnum::Acrobatics => [
                [ 'title' => 'Crumbling Bridge', 'success' => 'The bridge collapses just as the last hero dances across!', 'failure' => 'The bridge collapses beneath the party, sending them tumbling into the muck below.' ],
                [ 'title' => 'Trapped Hallway', 'success' => 'Blades swing and darts fly, but the party tumbles through untouched.', 'failure' => 'Blades swing and darts fly. The party makes it through, but only barely.' ],
                [ 'title' => 'Rooftop Chase', 'success' => 'A thief of the {foe} flees across the rooftops; the party leaps after them and catches up!', 'failure' => 'A thief of the {foe} flees across the rooftops, and quickly leaves the party behind.' ],
            ],
            StarKindredSkillEnum::Stealth => [
                [ 'title' => 'The Sentries', 'success' => 'The party slips past the sentries of the {foe} unseen.', 'failure' => 'A sentry spots the party and raises the alarm!' ],
                [ 'title' => 'Shadowed Path', 'success' => 'Keeping to the shadows, the party reaches the heart of the {place} unnoticed.', 'failure' => 'A careless step echoes through the {place}. Everyone now knows the party is here.' ],
                [ 'title' => 'Borrowed Disguises', 'success' => 'Dressed as the {foe}, the party walks right past their guards.', 'failure' => 'The disguises fool no one.' ],
            ],
            StarKindredSkillEnum::Endurance => [
                [ 'title' => 'The Long March', 'success' => 'Days of hard travel, but the party arrives in good spirits.', 'failure' => 'Days of hard travel leave the party exhausted and sore.' ],
                [ 'title' => 'Foul Weather', 'success' => 'A howling storm rolls over the {place}. The party presses on through it.', 'failure' => 'A howling storm rolls over the {place}, and the party must wait it out.' ],
                [ 'title' => 'Poisoned Air', 'success' => 'Strange fumes fill the {place}; the party holds on long enough to pass through.', 'failure' => 'Strange fumes fill the {place}, and the party is forced back, coughing.' ],
            ],
            StarKindredSkillEnum::Survival => [
                [ 'title' => 'Lost!', 'success' => 'The trail vanishes, but the party finds it again by reading the land.', 'failure' => 'The trail vanishes, and the party wanders in circles for a day.' ],
                [ 'title' => 'Living Off the Land', 'success' => 'Supplies run low, but the party forages plenty to eat.', 'failure' => 'Supplies run low, and the party goes hungry.' ],
                [ 'title' => 'Tracking the Quarry', 'success' => 'The party follows the tracks of the {foe} straight to their lair.', 'failure' => 'The tracks of the {foe} disappear on stony ground.' ],
            ],
            StarKindredSkillEnum::Perception => [
                [ 'title' => 'Hidden Door', 'success' => 'A faint draft gives away a hidden door!', 'failure' => 'The party searches every wall, but finds nothing.' ],
                [ 'title' => 'Something Watching', 'success' => 'The party notices the {foe} lying in wait - and turns the tables!', 'failure' => 'The party never sees the {foe} coming.' ],
                [ 'title' => 'Clues', 'success' => 'Muddy prints, a torn cloak, a dropped coin... the clues point the way.', 'failure' => 'Whatever clues there were, the party misses them.' ],
            ],
            StarKindredSkillEnum::Arcana => [
                [ 'title' => 'Magic Seal', 'success' => 'A glowing seal bars the way. The party\'s magic unravels it.', 'failure' => 'A glowing seal bars the way, and resists every spell the party tries.' ],
                [ 'title' => 'Wild Magic', 'success' => 'Magic runs wild through the {place}, but the party bends it to their will.', 'failure' => 'Magic runs wild through the {place}; the party is left dazed, and crackling with static.' ],
                [ 'title' => 'Summoning Circle', 'success' => 'The party finds a summoning circle of the {foe}, and breaks it before anything comes through.', 'failure' => 'The party finds a summoning circle, but something comes through before they can break it.' ],
            ],
            StarKindredSkillEnum::Lore => [
                [ 'title' => 'Ancient Inscription', 'success' => 'Old runes cover the walls; the party reads them and learns the {place}\'s secrets.', 'failure' => 'Old runes cover the walls, but no one can make sense of them.' ],
                [ 'title' => 'The Riddle', 'success' => 'A stone guardian asks a riddle, and the party answers correctly!', 'failure' => 'A stone guardian asks a riddle. Wrong answer!' ],
                [ 'title' => 'Old Legends', 'success' => 'Remembering an old legend about the {foe}, the party knows exactly what to expect.', 'failure' => 'There\'s surely an old legend about the {foe}, but no one can remember it.' ],
            ],
            StarKindredSkillEnum::Persuasion => [
                [ 'title' => 'The Toll', 'success' => 'The {foe} demand a toll; the party talks their way through for free.', 'failure' => 'The {foe} demand a toll, and don\'t care for the party\'s haggling.' ],
                [ 'title' => 'Friendly Locals', 'success' => 'The locals are suspicious of strangers, but warm up to the party quickly.', 'failure' => 'The locals are suspicious of strangers, and stay that way.' ],
                [ 'title' => 'A Rival Party', 'success' => 'A rival band of adventurers is also here; the party convinces them to team up!', 'failure' => 'A rival band of adventurers is also here, and they aren\'t interested in sharing.' ],
            ],
        };
    }

    public const array Relics = [
        'Amulet of Nine Winds', 'Crown of Tides', 'Starfall Lantern', 'Hourglass of Ages', 'Emerald Compass',
        'Sunken Chalice', 'Obsidian Mirror', 'Moonsilver Harp', 'Dragonbone Flute', 'Everburning Candle',
        'Book of Many Doors', 'Scepter of Echoes', 'Golden Acorn', 'Tear of the Sky', 'Clockwork Heart',
    ];

    public const array Captives = [
        'the Mayor\'s Nephew', 'a Lost Cartographer', 'a Stranded Merchant', 'the Baker\'s Apprentice',
        'a Baby Griffin', 'the Village Elder', 'a Traveling Minstrel', 'the Royal Astronomer',
        'a Runaway Prince', 'a Retired Adventurer', 'a Wandering Scholar', 'the Lighthouse Keeper',
    ];
}
