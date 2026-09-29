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

use App\Entity\StarKindredCharacter;
use App\Enum\StarKindredRaceEnum;
use App\Enum\StarKindredSexEnum;
use App\Service\StarKindred\StarKindredNames;
use App\Service\StarKindred\StarKindredPortraits;
use App\Service\Xoshiro;
use PHPUnit\Framework\TestCase;

/**
 * JUSTIFICATION: portrait counts are hard-coded in the API, but the images live in the webapp; if the two
 * drift, characters get broken images. And every race (and sex, for non-androgynous races) must be able to
 * roll a name and portrait, or rolling a character crashes.
 */
class StarKindredPortraitsTest extends TestCase
{
    private const string PortraitDirectory = __DIR__ . '/../../../webapp/src/assets/images/star-kindred/portraits';

    public function testPortraitCountsMatchTheWebappImages(): void
    {
        $files = array_map(
            fn(string $path) => pathinfo($path, PATHINFO_FILENAME),
            glob(self::PortraitDirectory . '/*.png') ?: []
        );

        $expected = StarKindredPortraits::all();

        sort($files);
        sort($expected);

        self::assertSame($expected, $files);
    }

    public function testEveryRaceAndSexCanRollANameAndPortrait(): void
    {
        $rng = new Xoshiro();
        $portraits = StarKindredPortraits::all();

        foreach(StarKindredRaceEnum::cases() as $race)
        {
            $sexes = $race->isAndrogynous() ? [ null ] : StarKindredSexEnum::cases();

            foreach($sexes as $sex)
            {
                for($i = 0; $i < 20; $i++)
                {
                    $portrait = StarKindredPortraits::roll($rng, $race, $sex);
                    self::assertContains($portrait, $portraits);
                    self::assertLessThanOrEqual(30, strlen($portrait));

                    $name = StarKindredNames::roll($rng, $race, $sex);
                    self::assertLessThanOrEqual(60, mb_strlen($name));
                }
            }
        }
    }

    public function testSexIsRequiredExactlyForNonAndrogynousRaces(): void
    {
        $rng = new Xoshiro();

        foreach(StarKindredRaceEnum::cases() as $race)
        {
            $sex = $race->isAndrogynous() ? StarKindredSexEnum::Female : null;

            try
            {
                StarKindredPortraits::roll($rng, $race, $sex);
                self::fail($race->value . ' should have been rejected.');
            }
            catch(\InvalidArgumentException)
            {
                self::addToAssertionCount(1);
            }
        }
    }
}
