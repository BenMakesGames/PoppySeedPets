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

        $adventures = [];
        $usedThemes = [];
        $usedSkills = [];

        for($i = 0; $i < self::AdventuresPerDay; $i++)
        {
            $adventure = self::generate($rng, $date, $i, $usedThemes, $usedSkills);

            $adventures[] = $adventure;
            $usedThemes[] = $adventure->theme;
            $usedSkills = [ ...$usedSkills, ...$adventure->getSkillsTested() ];
        }

        return $adventures;
    }

    /**
     * @param StarKindredThemeEnum[] $usedThemes
     * @param StarKindredSkillEnum[] $usedSkills
     */
    private static function generate(IRandom $rng, \DateTimeImmutable $date, int $index, array $usedThemes, array $usedSkills): StarKindredAdventure
    {
        $theme = $rng->rngNextFromArray(array_values(array_filter(
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
            '{foe}' => $rng->rngNextFromArray($theme->foes()),
            '{relic}' => $rng->rngNextFromArray(StarKindredEncounterText::Relics),
            '{captive}' => $rng->rngNextFromArray(StarKindredEncounterText::Captives),
        ];

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
        $rewards = self::generateRewards($rng, $theme);

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
     * @return StarKindredReward[]
     */
    private static function generateRewards(IRandom $rng, StarKindredThemeEnum $theme): array
    {
        $prizes = $theme->prizes();
        $prize = $rng->rngNextFromArray(array_keys($prizes));

        $heroOptions = [
            ...array_map(fn(string $item) => StarKindredReward::item(StarKindredDifficultyEnum::Hero, $item, 1), $theme->heroTreasures()),
            ...array_map(fn(string $aura) => StarKindredReward::aura(StarKindredDifficultyEnum::Hero, $aura), $theme->auras()),
        ];

        return [
            StarKindredReward::item(StarKindredDifficultyEnum::Novice, $rng->rngNextFromArray($theme->lootTable()), 1),
            StarKindredReward::item(StarKindredDifficultyEnum::Veteran, $prize, $prizes[$prize]),
            $rng->rngNextFromArray($heroOptions),
            StarKindredReward::item(StarKindredDifficultyEnum::Demigod, $rng->rngNextFromArray($theme->treasures()), 1),
        ];
    }
}
