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
        }
    }
}
