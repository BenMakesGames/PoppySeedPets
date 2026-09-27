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

use App\Entity\StarKindredCharacter;
use App\Enum\StarKindredSkillEnum;
use App\Enum\StarKindredStatEnum;
use App\Model\StarKindred\StarKindredAdventure;

/**
 * Explicit response mappings shared by the ★Kindred endpoints. The "pet" key holds the Pet entity, so
 * serialize with SerializationGroupEnum::PET_PUBLIC_PROFILE.
 */
final class StarKindredCharacterSheet
{
    /**
     * @return array<string, mixed>
     */
    public static function map(StarKindredCharacter $c): array
    {
        $class = $c->getCharacterClass();

        return [
            'id' => $c->getId(),
            'pet' => $c->getPet(),
            'name' => $c->getName(),
            'race' => $c->getRace()->value,
            'class' => $class->value,
            'level' => $c->getLevel(),
            'experience' => $c->getExperience(),
            'experienceToNextLevel' => $c->isMaxLevel() ? null : StarKindredCharacter::experienceToNextLevel($c->getLevel()),
            'adventuresCompleted' => $c->getAdventuresCompleted(),
            'adventuresWon' => $c->getAdventuresWon(),
            'createdOn' => $c->getCreatedOn()->format('c'),
            'retiredOn' => $c->getRetiredOn()?->format('c'),
            'epilogue' => $c->getEpilogue(),
            'stats' => array_map(
                fn(StarKindredStatEnum $stat) => [
                    'name' => $stat->value,
                    'value' => $c->getStat($stat),
                    'modifier' => $c->getStatModifier($stat),
                    'growth' => match($stat) {
                        $class->primaryStat() => 'primary',
                        $class->secondaryStat() => 'secondary',
                        default => null,
                    },
                ],
                StarKindredStatEnum::cases()
            ),
            'skills' => array_map(
                fn(StarKindredSkillEnum $skill) => [
                    'name' => $skill->value,
                    'stat' => $skill->stat()->value,
                    'value' => $c->getSkill($skill),
                    'isClassSkill' => $c->isClassSkill($skill),
                ],
                StarKindredSkillEnum::cases()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapAdventure(StarKindredAdventure $a): array
    {
        return [
            'index' => $a->index,
            'theme' => $a->theme->value,
            'title' => $a->title,
            'summary' => $a->summary,
            'encounters' => array_map(fn($e) => [ 'title' => $e->title, 'skill' => $e->skill->value ], $a->encounters),
            'skillsTested' => array_map(fn(StarKindredSkillEnum $s) => $s->value, $a->getSkillsTested()),
        ];
    }
}
