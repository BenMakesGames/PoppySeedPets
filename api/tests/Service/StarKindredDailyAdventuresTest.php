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

namespace Service;

use App\Enum\HolidayEnum;
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\StarKindredThemeEnum;
use App\Model\StarKindred\StarKindredAdventure;
use App\Model\StarKindred\StarKindredReward;
use App\Service\StarKindred\StarKindredDailyAdventures;
use PHPUnit\Framework\TestCase;

/**
 * JUSTIFICATION: the daily adventures are never stored - every request regenerates them from the date -
 * so if generation ever stops being deterministic, a player could pick one adventure and be sent on
 * another. The two adventures must also stay meaningfully different choices.
 */
class StarKindredDailyAdventuresTest extends TestCase
{
    public function testDailyAdventuresAreDeterministicAndDistinct(): void
    {
        $day = new \DateTimeImmutable('2026-01-01 00:00:00');
        $previousIds = [];

        for($i = 0; $i < 400; $i++)
        {
            $morning = StarKindredDailyAdventures::forDate($day->modify("+{$i} days 01:00:00"));
            $evening = StarKindredDailyAdventures::forDate($day->modify("+{$i} days 23:00:00"));

            self::assertEquals($morning, $evening, 'Adventures must not change during the day.');
            self::assertCount(StarKindredDailyAdventures::AdventuresPerDay, $morning);

            [ $a, $b ] = $morning;

            self::assertNotSame($a->theme, $b->theme, 'The day\'s adventures must have different settings.');
            self::assertNotSame(
                $a->encounters[count($a->encounters) - 1]->skill,
                $b->encounters[count($b->encounters) - 1]->skill,
                'The day\'s adventures must have objectives testing different skills.'
            );
            self::assertStringNotContainsString('{', $a->title . $a->summary . $b->title . $b->summary, 'Unreplaced token!');
            self::assertNotSame($a->id, $b->id, 'The day\'s adventures must have different ids.');
            self::assertNotContains($a->id, $previousIds, 'Adventure ids must not repeat across days.');

            $previousIds[] = $a->id;
            $previousIds[] = $b->id;

            foreach($morning as $adventure)
                self::assertRewardsAreWellFormed($adventure);
        }
    }

    public function testUndeadFoesExist(): void
    {
        $allFoes = array_merge(...array_map(fn(StarKindredThemeEnum $t) => $t->foes(), StarKindredThemeEnum::cases()));

        foreach(StarKindredThemeEnum::UndeadFoes as $foe)
            self::assertContains($foe, $allFoes, "\"{$foe}\" is listed as undead, but no theme has it as a foe. (Typo?)");
    }

    public function testEveryRewardTierHasOptions(): void
    {
        foreach(StarKindredThemeEnum::cases() as $theme)
        {
            self::assertNotEmpty($theme->lootTable(), "{$theme->value} has no Novice rewards.");
            self::assertNotEmpty($theme->prizes(), "{$theme->value} has no Veteran rewards.");
            self::assertNotEmpty([ ...$theme->heroTreasures(), ...$theme->auras() ], "{$theme->value} has no Hero rewards.");
            self::assertNotEmpty($theme->treasures(), "{$theme->value} has no Demigod rewards.");
        }
    }

    private static function assertRewardsAreWellFormed(StarKindredAdventure $adventure): void
    {
        self::assertSame(
            array_map(fn(StarKindredDifficultyEnum $d) => $d->value, StarKindredDifficultyEnum::cases()),
            array_map(fn(StarKindredReward $r) => $r->difficulty->value, $adventure->rewards),
            'There must be exactly one reward per difficulty, in difficulty order.'
        );

        foreach($adventure->rewards as $reward)
        {
            self::assertTrue(($reward->item === null) !== ($reward->aura === null), 'A reward is an item OR a hat styling.');

            if($reward->aura)
                self::assertSame(StarKindredDifficultyEnum::Hero, $reward->difficulty, 'Hat stylings are always the Hero reward.');
            else
                self::assertGreaterThan(0, $reward->quantity);
        }

        $hero = $adventure->rewards[StarKindredDifficultyEnum::Hero->tier()];

        $holidayRewards = array_merge(...array_map(fn(HolidayEnum $h) => StarKindredDailyAdventures::holidayRewardOptions($h, $adventure->theme), HolidayEnum::cases()));

        if(in_array($hero, $holidayRewards))
            return;

        if($hero->aura)
            self::assertContains($hero->aura, $adventure->theme->auras());
        else
            self::assertSame($adventure->theme->heroTreasures()[$hero->item] ?? null, $hero->quantity);

        self::assertCount(3, $adventure->getRewardsFor(StarKindredDifficultyEnum::Hero), 'Rewards are cumulative.');
    }

    public function testHolidaysForceTheFirstAdventuresSetting(): void
    {
        $expected = [
            '2026-02-14' => [ StarKindredThemeEnum::FairyMarket, null ],
            '2026-03-14' => [ StarKindredThemeEnum::FairyMarket, null ],
            '2026-07-22' => [ StarKindredThemeEnum::FairyMarket, null ],
            '2026-09-19' => [ StarKindredThemeEnum::Shipwreck, 'Drowned Crew' ],
            '2026-10-29' => [ StarKindredThemeEnum::Shipwreck, 'Drowned Crew' ],
            '2026-10-30' => [ StarKindredThemeEnum::HauntedWoods, null ],
            '2026-10-31' => [ StarKindredThemeEnum::Graveyard, null ],
        ];

        foreach($expected as $date => [ $theme, $foe ])
        {
            [ $first, $second ] = StarKindredDailyAdventures::forDate(new \DateTimeImmutable($date . ' 12:00:00'));

            self::assertSame($theme, $first->theme, "Wrong setting on {$date}.");
            self::assertNotSame($theme, $second->theme, "Only the first adventure's setting is forced on {$date}.");

            if($foe)
                self::assertStringContainsString($foe, $first->summary . implode(array_map(fn($e) => $e->title . $e->success . $e->failure, $first->encounters)), "Wrong foe on {$date}.");
        }
    }
}
