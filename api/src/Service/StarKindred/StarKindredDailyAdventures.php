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

use App\Enum\HolidayEnum;
use App\Functions\CalendarFunctions;
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredThemeEnum;
use App\Model\StarKindred\StarKindredAdventure;
use App\Model\StarKindred\StarKindredEncounter;
use App\Model\StarKindred\StarKindredReward;
use App\Service\IRandom;
use App\Service\Xoshiro;

/**
 * Generates the day's two ★Kindred adventures. The RNG is seeded by the date, so every player on the
 * server gets the same two adventures, and nothing needs to be stored.
 */
final class StarKindredDailyAdventures
{
    public const int AdventuresPerDay = 2;
    private const int EncountersPerAdventure = 3;

    /**
     * Null if no adventure with that id is available on that date.
     */
    public static function find(\DateTimeImmutable $date, string $id): ?StarKindredAdventure
    {
        return array_find(self::forDate($date), fn(StarKindredAdventure $a) => $a->id === $id);
    }

    /**
     * @return StarKindredAdventure[]
     */
    public static function forDate(\DateTimeImmutable $date): array
    {
        $rng = new Xoshiro(crc32('★Kindred ' . $date->format('Y-m-d')));
        $holidays = self::holidaysCelebrated($date);

        $adventures = [];
        $usedThemes = [];
        $usedSkills = [];

        for($i = 0; $i < self::AdventuresPerDay; $i++)
        {
            $adventure = self::generate($rng, $date, $holidays, $i, $usedThemes, $usedSkills);

            $adventures[] = $adventure;
            $usedThemes[] = $adventure->theme;
            $usedSkills = [ ...$usedSkills, ...$adventure->getSkillsTested() ];
        }

        return $adventures;
    }

    /**
     * @return HolidayEnum[]
     */
    private static function holidaysCelebrated(\DateTimeImmutable $date): array
    {
        $events = CalendarFunctions::getEventData($date);

        return array_values(array_filter(
            HolidayEnum::cases(),
            fn(HolidayEnum $h) => match($h)
            {
                // Valentine's itself only - not the days around it
                HolidayEnum::Valentines => $date->format('nd') === '214',
                default => in_array($h, $events, true),
            }
        ));
    }

    /**
     * A holiday can force the setting of the day's first adventure, and optionally its foe.
     *
     * @param HolidayEnum[] $holidays
     * @return array{theme: StarKindredThemeEnum, foe: ?string}|null
     */
    private static function holidaySetting(\DateTimeImmutable $date, array $holidays): ?array
    {
        if(in_array(HolidayEnum::TalkLikeAPirateDay, $holidays, true))
            return [ 'theme' => StarKindredThemeEnum::Shipwreck, 'foe' => 'Drowned Crew' ];

        if(
            in_array(HolidayEnum::Valentines, $holidays, true) ||
            in_array(HolidayEnum::WhiteDay, $holidays, true) ||
            in_array(HolidayEnum::PiDay, $holidays, true)
        )
            return [ 'theme' => StarKindredThemeEnum::FairyMarket, 'foe' => null ];

        if(in_array(HolidayEnum::Halloween, $holidays, true))
        {
            return match($date->format('nd'))
            {
                '1029' => [ 'theme' => StarKindredThemeEnum::Shipwreck, 'foe' => 'Drowned Crew' ],
                '1030' => [ 'theme' => StarKindredThemeEnum::HauntedWoods, 'foe' => null ],
                '1031' => [ 'theme' => StarKindredThemeEnum::Graveyard, 'foe' => null ],
                default => throw new \LogicException('Halloween is Oct 29-31; update this match to cover ' . $date->format('M j') . '.'),
            };
        }

        return null;
    }

    /**
     * @param HolidayEnum[] $holidays
     * @param StarKindredThemeEnum[] $usedThemes
     * @param StarKindredSkillEnum[] $usedSkills
     */
    private static function generate(IRandom $rng, \DateTimeImmutable $date, array $holidays, int $index, array $usedThemes, array $usedSkills): StarKindredAdventure
    {
        $holidaySetting = $index === 0 ? self::holidaySetting($date, $holidays) : null;

        $theme = $holidaySetting['theme'] ?? $rng->rngNextFromArray(array_values(array_filter(
            StarKindredThemeEnum::cases(),
            fn(StarKindredThemeEnum $t) => !in_array($t, $usedThemes, true)
        )));

        // prefer an objective that tests something the other adventure(s) today don't
        $freshSkills = array_values(array_filter(
            StarKindredSkillEnum::cases(),
            fn(StarKindredSkillEnum $s) => !in_array($s, $usedSkills, true)
        ));

        $objectiveSkill = $rng->rngNextFromArray(count($freshSkills) > 0 ? $freshSkills : StarKindredSkillEnum::cases());

        $themeSkills = array_values(array_filter(
            $theme->skills(),
            fn(StarKindredSkillEnum $s) => $s !== $objectiveSkill
        ));

        $encounterSkills = [
            ...$rng->rngNextSubsetFromArray($themeSkills, self::EncountersPerAdventure - 1),
            $objectiveSkill, // the objective is always the climax
        ];

        $tokens = [
            '{place}' => $rng->rngNextFromArray($theme->places()),
            '{foe}' => $holidaySetting['foe'] ?? $rng->rngNextFromArray($theme->foes()),
            '{relic}' => $rng->rngNextFromArray(StarKindredEncounterText::Relics),
            '{captive}' => $rng->rngNextFromArray(StarKindredEncounterText::Captives),
        ];

        if(!in_array($tokens['{foe}'], $theme->foes(), true))
            throw new \LogicException("\"{$tokens['{foe}']}\" is not a {$theme->value} foe. (Typo?)");

        $encounters = array_map(
            function(StarKindredSkillEnum $skill) use($rng, $tokens) {
                $text = $rng->rngNextFromArray(StarKindredEncounterText::encounters($skill));

                return new StarKindredEncounter(
                    $skill,
                    strtr($text['title'], $tokens),
                    strtr($text['success'], $tokens),
                    strtr($text['failure'], $tokens),
                    StarKindredThemeEnum::isUndeadFoe($tokens['{foe}']) && str_contains(implode($text), '{foe}'),
                );
            },
            $encounterSkills
        );

        $objective = StarKindredEncounterText::objective($objectiveSkill);
        $title = strtr($objective['title'], $tokens);
        $summary = ucfirst(strtr($objective['summary'], $tokens));
        $rewards = self::generateRewards($rng, $theme, $holidays);

        // hash everything a player sees, so ANY change to the adventure (ex: from a deploy) changes its id
        $id = substr(hash('sha256', json_encode([
            $date->format('Y-m-d'), $index, $theme->value, $title, $summary,
            array_map(fn(StarKindredEncounter $e) => [ $e->skill->value, $e->title, $e->success, $e->failure, $e->againstUndead ], $encounters),
            array_map(fn(StarKindredReward $r) => [ $r->difficulty->value, $r->item, $r->quantity, $r->aura ], $rewards),
        ], JSON_THROW_ON_ERROR)), 0, 16);

        return new StarKindredAdventure($id, $theme, $title, $summary, $encounters, $rewards);
    }

    /**
     * Four tiers of increasing value, awarded cumulatively by difficulty. A setting's hat styling is
     * only ever a Hero reward, so Demigod always offers something a player can collect again.
     *
     * @param HolidayEnum[] $holidays
     * @return StarKindredReward[]
     */
    private static function generateRewards(IRandom $rng, StarKindredThemeEnum $theme, array $holidays): array
    {
        $rewards = self::generateNormalRewards($rng, $theme);

        foreach($holidays as $holiday)
        {
            $options = self::holidayRewardOptions($holiday, $theme);

            if(count($options) === 0)
                continue;

            $holidayReward = $rng->rngNextFromArray($options);

            $rewards = array_map(
                fn(StarKindredReward $r) => $r->difficulty === $holidayReward->difficulty ? $holidayReward : $r,
                $rewards
            );
        }

        return $rewards;
    }

    /**
     * While a holiday is on, one of its reward options replaces the theme's usual reward of the same
     * difficulty. Empty if the holiday doesn't change that theme's rewards.
     *
     * @return StarKindredReward[]
     */
    public static function holidayRewardOptions(HolidayEnum $holiday, StarKindredThemeEnum $theme): array
    {
        $options = match($holiday)
        {
            HolidayEnum::SaintPatricks => match($theme)
            {
                StarKindredThemeEnum::Forest, StarKindredThemeEnum::HauntedWoods => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, '3-leaf Clover', 1) ],
                StarKindredThemeEnum::Graveyard, StarKindredThemeEnum::Quarry, StarKindredThemeEnum::HuntingGrounds => [ StarKindredReward::item(StarKindredDifficultyEnum::Novice, '1-leaf Clover', 1) ],
                StarKindredThemeEnum::FairyMarket => [ StarKindredReward::item(StarKindredDifficultyEnum::Hero, '5-leaf Clover', 1) ],
                default => [],
            },
            HolidayEnum::Easter => match($theme)
            {
                StarKindredThemeEnum::Beach, StarKindredThemeEnum::Forest, StarKindredThemeEnum::Mine, StarKindredThemeEnum::HuntingGrounds => [ StarKindredReward::item(StarKindredDifficultyEnum::Novice, 'Blue Plastic Egg', 1) ],
                StarKindredThemeEnum::Quarry, StarKindredThemeEnum::FairyMarket, StarKindredThemeEnum::UndergroundLake => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, 'Yellow Plastic Egg', 1) ],
                StarKindredThemeEnum::MagicTower, StarKindredThemeEnum::DragonLair, StarKindredThemeEnum::TreasureVault => [ StarKindredReward::item(StarKindredDifficultyEnum::Demigod, 'Pink Plastic Egg', 2) ],
                default => [],
            },
            HolidayEnum::Valentines, HolidayEnum::WhiteDay => match($theme)
            {
                StarKindredThemeEnum::FairyMarket => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, 'Fluff Heart', 1) ],
                default => [],
            },
            HolidayEnum::PiDay => match($theme)
            {
                StarKindredThemeEnum::FairyMarket => array_map(
                    fn(string $pie) => StarKindredReward::item(StarKindredDifficultyEnum::Novice, $pie, 1),
                    [ 'Slice of Blackberry Pie', 'Slice of Blueberry Pie', 'Slice of Chocolate Cream Pie', 'Slice of Pumpkin Pie', 'Slice of Red Pie' ]
                ),
                default => [],
            },
            HolidayEnum::ApricotFestival => match($theme)
            {
                StarKindredThemeEnum::Forest, StarKindredThemeEnum::HuntingGrounds => [ StarKindredReward::item(StarKindredDifficultyEnum::Novice, 'Apricot', 1) ],
                StarKindredThemeEnum::Beach => [ StarKindredReward::item(StarKindredDifficultyEnum::Novice, 'Apricot PB&J', 1) ],
                StarKindredThemeEnum::MagicTower, StarKindredThemeEnum::BanditCamp => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, 'Apricobbler', 2) ],
                StarKindredThemeEnum::FairyMarket => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, 'Apricot Preserves', 2) ],
                default => [],
            },
            HolidayEnum::Thanksgiving => match($theme)
            {
                StarKindredThemeEnum::HuntingGrounds, StarKindredThemeEnum::BanditCamp, StarKindredThemeEnum::Forest, StarKindredThemeEnum::Beach => [ StarKindredReward::item(StarKindredDifficultyEnum::Veteran, 'Giant Turkey Leg', 1) ],
                default => [],
            },
            default => [],
        };

        if(count(array_unique(array_map(fn(StarKindredReward $r) => $r->difficulty->value, $options))) > 1)
            throw new \LogicException("{$holiday->value}'s {$theme->value} reward options must all be the same difficulty.");

        return $options;
    }

    /**
     * @return StarKindredReward[]
     */
    private static function generateNormalRewards(IRandom $rng, StarKindredThemeEnum $theme): array
    {
        $prizes = $theme->prizes();
        $prize = $rng->rngNextFromArray(array_keys($prizes));

        $treasures = $theme->treasures();
        $treasure = $rng->rngNextFromArray(array_keys($treasures));

        $heroOptions = [
            ...array_map(fn(string $item, int $quantity) => StarKindredReward::item(StarKindredDifficultyEnum::Hero, $item, $quantity), array_keys($theme->heroTreasures()), $theme->heroTreasures()),
            ...array_map(fn(string $aura) => StarKindredReward::aura(StarKindredDifficultyEnum::Hero, $aura), $theme->auras()),
        ];

        return [
            StarKindredReward::item(StarKindredDifficultyEnum::Novice, $rng->rngNextFromArray($theme->lootTable()), 1),
            StarKindredReward::item(StarKindredDifficultyEnum::Veteran, $prize, $prizes[$prize]),
            $rng->rngNextFromArray($heroOptions),
            StarKindredReward::item(StarKindredDifficultyEnum::Demigod, $treasure, $treasures[$treasure]),
        ];
    }
}
