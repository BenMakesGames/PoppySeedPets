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

namespace App\Controller\MonsterOfTheWeek;

use App\Entity\Item;
use App\Enum\MonsterOfTheWeekEnum;
use App\Enum\PetSkillEnum;
use App\Exceptions\UnreachableException;

final class MonsterOfTheWeekHelpers
{
    public static function getConsolationPrize(MonsterOfTheWeekEnum $monster): string
    {
        return match($monster)
        {
            MonsterOfTheWeekEnum::Anhur => 'Crooked Stick',
            MonsterOfTheWeekEnum::Boshinogami => 'Fluff',
            MonsterOfTheWeekEnum::Cardea => 'String',
            MonsterOfTheWeekEnum::Dionysus => 'Blueberries',
            MonsterOfTheWeekEnum::Huehuecoyotl => 'Music Note',
            MonsterOfTheWeekEnum::EiriPersona => 'Pointer',
            MonsterOfTheWeekEnum::VafAndNir => 'Gold Ore',
            default => throw new UnreachableException()
        };
    }

    /**
     * @return int[]
     */
    public static function getBasePrizeValues(MonsterOfTheWeekEnum $monster): array
    {
        return match($monster)
        {
            // [ easy = hard / 7.5, rounding down to a nice number; medium is half-way between ]
            MonsterOfTheWeekEnum::Anhur => [ 25, 110, 200 ],
            MonsterOfTheWeekEnum::Boshinogami => [ 40, 170, 300 ],
            MonsterOfTheWeekEnum::Cardea => [ 10, 45, 75 ],
            MonsterOfTheWeekEnum::Dionysus => [ 100, 425, 750 ],
            MonsterOfTheWeekEnum::Huehuecoyotl => [ 10, 45, 75 ],
            MonsterOfTheWeekEnum::EiriPersona => [ 25, 110, 200 ],
            MonsterOfTheWeekEnum::VafAndNir => [ 60, 280, 500 ],
            default => throw new UnreachableException()
        };
    }

    public static function getCommunityContributionLevel(MonsterOfTheWeekEnum $monster, int $communityContribution)
    {
        $highValue = self::getBasePrizeValues($monster)[2];

        return max(1, (int)ceil($communityContribution / $highValue));
    }

    public static function getSpiritNameWithArticle(MonsterOfTheWeekEnum $monster): string
    {
        return match($monster)
        {
            MonsterOfTheWeekEnum::Anhur => 'a Hunter of Anhur',
            MonsterOfTheWeekEnum::Boshinogami => 'some Boshinogami',
            MonsterOfTheWeekEnum::Cardea => 'Cardea\'s Lockbearer',
            MonsterOfTheWeekEnum::Dionysus => 'Dionysus\'s Hunger',
            MonsterOfTheWeekEnum::Huehuecoyotl => 'Huehuecoyotl\'s Folly',
            MonsterOfTheWeekEnum::EiriPersona => 'an Eiri Persona',
            MonsterOfTheWeekEnum::VafAndNir => 'Vaf & Nir',
            default => throw new UnreachableException()
        };
    }

    public static function getEasyPrizes(MonsterOfTheWeekEnum $monster): array
    {
        return match($monster)
        {
            MonsterOfTheWeekEnum::Anhur => [ 'Monster-summoning Scroll', 'Potion of Brawling', 'Wolf\'s Bane' ],
            MonsterOfTheWeekEnum::Boshinogami => [ 'Handicrafts Supply Box', 'Potion of Crafts' ],
            MonsterOfTheWeekEnum::Cardea => [ 'Magpie Pouch', 'Magpie\'s Deal', 'Tile: Thieving Magpie' ],
            MonsterOfTheWeekEnum::Dionysus => [ 'Essence d\'Assortiment', 'Potion of Nature' ],
            MonsterOfTheWeekEnum::Huehuecoyotl => [ 'Potion of Music', 'Dancing Sword', 'LP' ],
            MonsterOfTheWeekEnum::EiriPersona => [ 'Magic Smoke', 'Lightning in a Bottle', 'Potion of Science' ],
            MonsterOfTheWeekEnum::VafAndNir => [ 'Dragon Flag', 'Dragonstick', 'Dragondrop', 'Dragon Polymorph Potion'  ],
            default => throw new UnreachableException()
        };
    }

    public static function getMediumPrizes(MonsterOfTheWeekEnum $monster): array
    {
        return match ($monster)
        {
            MonsterOfTheWeekEnum::Anhur => [ 'Tile: Giant Cave Toad', 'Monster Box', 'Very Strongbox' ],
            MonsterOfTheWeekEnum::Boshinogami => [ 'Hat Box' ],
            MonsterOfTheWeekEnum::Cardea => [ 'Tile: Flying Keys, Only', 'Magic Crystal Ball', 'Bag of Feathers', 'Tile: Triple Chest' ],
            MonsterOfTheWeekEnum::Dionysus => [ 'Tile: Statue Garden', 'Whisper Stone' ],
            MonsterOfTheWeekEnum::Huehuecoyotl => [ 'Magic Hourglass', 'Firefly Harp', 'Maraca', 'Tile: Very Cool Beans' ],
            MonsterOfTheWeekEnum::EiriPersona => [ 'Eiri Persona Persona' ],
            MonsterOfTheWeekEnum::VafAndNir => [ 'Scroll of Dice', 'Gold Baabble', 'Metal Detector (Gold)' ],
            default => throw new \InvalidArgumentException("Invalid monster"),
        };
    }

    public static function getHardPrizes(MonsterOfTheWeekEnum $monster): array
    {
        return match($monster)
        {
            MonsterOfTheWeekEnum::Anhur => [ 'Skill Scroll: Brawl', 'Skill Scroll: Stealth' ],
            MonsterOfTheWeekEnum::Boshinogami => [ 'Scroll of Illusions', 'Skill Scroll: Crafts', 'Behatting Scroll' ],
            MonsterOfTheWeekEnum::Cardea => [ 'Skill Scroll: Arcana', 'Forgetting Scroll' ],
            MonsterOfTheWeekEnum::Dionysus => [ 'Skill Scroll: Nature' ],
            MonsterOfTheWeekEnum::Huehuecoyotl => [ 'Skill Scroll: Music' ],
            MonsterOfTheWeekEnum::EiriPersona => [ 'Skill Scroll: Science' ],
            MonsterOfTheWeekEnum::VafAndNir => [ 'Scroll of Crowns' ],
            default => throw new UnreachableException()
        };
    }

    public static function getItemValue(MonsterOfTheWeekEnum $monster, Item $item): int
    {
        return match($monster)
        {
            MonsterOfTheWeekEnum::Anhur => self::getItemValueForAnhur($item),
            MonsterOfTheWeekEnum::Boshinogami => self::getItemValueForBoshinogami($item),
            MonsterOfTheWeekEnum::Cardea => self::getItemValueForCardea($item),
            MonsterOfTheWeekEnum::Dionysus => self::getItemValueForDionysus($item),
            MonsterOfTheWeekEnum::Huehuecoyotl => self::getItemValueForHuehuecoyotl($item),
            MonsterOfTheWeekEnum::EiriPersona => self::getItemValueForEiriPersona($item),
            MonsterOfTheWeekEnum::VafAndNir => self::getItemValueForVafAndNir($item),
            default => 0,
        };
    }

    public static function getItemValueForAnhur(Item $item): int
    {
        $points = 0;

        if($item->getTool())
        {
            $effects = $item->getTool();

            $points = max($points, $effects->getBrawl() + ($effects->getFocusSkill() == PetSkillEnum::Brawl ? 2 : 0));
        }

        if($item->getEnchants() && $item->getEnchants()->getEffects())
        {
            $effects = $item->getEnchants()->getEffects();
            $points = max($points, $effects->getBrawl() + ($effects->getFocusSkill() == PetSkillEnum::Brawl ? 2 : 0));
        }

        if($item->getFood())
        {
            $effects = $item->getFood();

            $points = max($points, $effects->getGrantedSkill() == PetSkillEnum::Brawl ? 2 : 0);
        }

        return (int)floor(pow($points, 1.3333));
    }

    public static function getItemValueForBoshinogami(Item $item): int
    {
        if(!$item->getHat() || $item->getName() === 'Anniversary Poppy Seed* Muffin')
            return 0;

        $points = $item->getRecycleValue() + (int)ceil($item->getMuseumPoints() * 1.5) - 1;

        if(str_ends_with($item->getName(), 'Baabble'))
            $points += 10;
        else if($item->getName() === 'Heart Beetle')
            $points = 2;

        return $points;
    }

    public static function getItemValueForCardea(Item $item): int
    {
        if(!$item->hasItemGroup('Key') && $item->getName() !== 'Password')
            return 0;

        $points = 2 + (int)floor($item->getRecycleValue() / 3);

        if($item->getTool() && $item->getTool()->getLeadsToAdventure())
            $points += 3;

        return $points;
    }

    public static function getItemValueForDionysus(Item $item): int
    {
        if(!$item->getFood())
            return 0;

        $food = $item->getFood();

        return $food->getFood() + $food->getLove() +
            ($food->getAlcohol() + $food->getCaffeine() + $food->getPsychedelic()) * 2;
    }

    public static function getItemValueForHuehuecoyotl(Item $item): int
    {
        if($item->getName() === 'Musical Scales')
            return 2;

        $points = 0;

        if($item->getTool())
        {
            $effects = $item->getTool();

            $points = max($points, $effects->getMusic() + ($effects->getFocusSkill() == PetSkillEnum::Music ? 2 : 0));
        }

        if($item->getEnchants() && $item->getEnchants()->getEffects())
        {
            $effects = $item->getEnchants()->getEffects();
            $points = max($points, $effects->getMusic() + ($effects->getFocusSkill() == PetSkillEnum::Music ? 2 : 0));
        }

        if($item->getFood())
        {
            $effects = $item->getFood();

            $points = max($points, $effects->getGrantedSkill() == PetSkillEnum::Music ? 2 : 0);
        }

        if($item->hasItemGroup('Musical Instrument'))
            $points += 2;

        return (int)floor(pow($points, 1.3333));
    }

    public static function getItemValueForVafAndNir(Item $item): int
    {
        return match($item->getName())
        {
            'Small Offering of Riches' => 5,
            'Medium Offering of Riches' => 30,
            'Large Offering of Riches' => 180,
            default => 0,
        };
    }

    public static function getItemValueForEiriPersona(Item $item): int
    {
        if($item->getName() === 'Eiri Persona Persona')
            return 11;

        if($item->hasItemGroup('Robot') || $item->getName() === 'Tinfoil Hat')
            return 3;

        if(in_array($item->getName(), [ 'XOR', 'Hash Table', 'Password', 'Cryptocurrency Wallet', 'Diffie-H Key' ]))
            return 2;

        if(in_array($item->getName(), [ 'Finite State Machine', 'NUL', 'Pointer', 'String' ]))
            return 1;

        $points = 0;

        if($item->getTool())
        {
            $effects = $item->getTool();

            $points = max($points, $effects->getScience() + ($effects->getFocusSkill() == PetSkillEnum::Science ? 2 : 0) + ($effects->getPreventsBugs() ? 1 : 0) + $effects->getHacking() + $effects->getElectronics());
        }

        if($item->getEnchants() && $item->getEnchants()->getEffects())
        {
            $effects = $item->getEnchants()->getEffects();
            $points = max($points, $effects->getScience() + ($effects->getFocusSkill() == PetSkillEnum::Science ? 2 : 0) + ($effects->getPreventsBugs() ? 1 : 0));
        }

        if($item->getFood())
        {
            $effects = $item->getFood();

            $points = max($points, $effects->getGrantedSkill() == PetSkillEnum::Science ? 2 : 0);
        }

        if(str_contains(mb_strtolower($item->getName()), 'fiberglass'))
            $points += 2;

        if(str_contains(mb_strtolower($item->getName()), 'phone'))
            $points += 1;

        return (int)floor(pow($points, 1.3333));
    }
}
