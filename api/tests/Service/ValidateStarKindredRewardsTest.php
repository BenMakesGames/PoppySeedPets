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

use App\Entity\Enchantment;
use App\Entity\Item;
use App\Enum\HolidayEnum;
use App\Enum\StarKindredThemeEnum;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\StarKindred\StarKindredDailyAdventures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * JUSTIFICATION: ★Kindred loot tables and hat stylings are hand-typed names, resolved at runtime; a
 * typo would 500 the adventure after the player's daily play was already spent.
 */
class ValidateStarKindredRewardsTest extends KernelTestCase
{
    /**
     * @group requiresDatabase
     */
    public function testStarKindredRewardsExist(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $itemNames = [
            ...array_keys(StarKindredAdventureService::AnimalCompanionFigurines),
            ...StarKindredAdventureService::RetirementRewardsPerAdventurer,
        ];

        $auraNames = [ StarKindredAdventureService::RetirementAura ];

        foreach(StarKindredThemeEnum::cases() as $theme)
        {
            $itemNames = [ ...$itemNames, ...array_keys($theme->prizes()), ...$theme->lootTable(), ...array_keys($theme->heroTreasures()), ...array_keys($theme->treasures()) ];
            $auraNames = [ ...$auraNames, ...$theme->auras() ];

            foreach(HolidayEnum::cases() as $holiday)
            {
                foreach(StarKindredDailyAdventures::holidayRewardOptions($holiday, $theme) as $holidayReward)
                {
                    if($holidayReward->item !== null)
                        $itemNames[] = $holidayReward->item;

                    if($holidayReward->aura !== null)
                        $auraNames[] = $holidayReward->aura;
                }
            }
        }

        foreach(array_unique($itemNames) as $itemName)
            self::assertNotNull($em->getRepository(Item::class)->findOneBy([ 'name' => $itemName ]), "The item \"{$itemName}\" does not exist.");

        foreach(array_unique($auraNames) as $auraName)
            self::assertNotNull($em->getRepository(Enchantment::class)->findOneBy([ 'name' => $auraName ]), "The hat styling \"{$auraName}\" does not exist.");
    }
}
