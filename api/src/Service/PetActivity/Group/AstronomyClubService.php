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

namespace App\Service\PetActivity\Group;

use App\Entity\Enchantment;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\PetGroup;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetSkillEnum;
use App\Functions\ActivityHelpers;
use App\Functions\CalendarFunctions;
use App\Functions\EnchantmentRepository;
use App\Functions\GroupNameGenerator;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\PetChanges;
use App\Model\WeatherSky;
use App\Service\Clock;
use App\Service\HattierService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\PetRelationshipService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;

class AstronomyClubService
{
    public const string ActivityIcon = 'groups/astronomy';

    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em,
        private readonly InventoryService $inventoryService,
        private readonly PetRelationshipService $petRelationshipService,
        private readonly IRandom $rng,
        private readonly HattierService $hattierService,
        private readonly Clock $clock
    )
    {
    }

    private const array Dictionary = [
        'prefix' => [
            'the First', 'the Last', 'the Second', 'the Final'
        ],
        'suffix' => [
            'Axiom', 'Law', 'Theory', 'Proposal', 'System', 'Model', 'Problem', 'Solution', 'Paradox', 'Thesis',
            'Hypothesis', 'Survey',
        ],
        'adjective' => [
            'Absolute', 'Alpha', 'Attracting', 'Beta', 'Big', 'Colliding', 'Copernican', 'Cosmic', 'Delta', 'Elementary', 'Expanding',
            'Finite', 'Galilean', 'Gamma-ray', 'Gravitational', 'Heavy', 'Infinite', 'Large-scale', 'Local', 'Microwave', 'Omega',
            'Plank', 'Quantized', 'Quantum', 'Radio', 'Really Big', 'Rotating', 'Small', 'Small-scale', 'Strongly-interacting',
            'Theta', 'Timey-wimey', 'Universal', 'Vibrating', 'Weakly-interacting', 'X-ray',
        ],
        'color' => [
            'Red', 'White', 'Black', 'Yellow', 'Dark', 'Light', 'Blue'
        ],
        'noun' => [
            'Phenomenon', 'Sun', 'Star', 'Galaxy', 'Nebula', 'Nova', 'Sphere', 'Expanse', 'Void', 'Shift', 'Particle',
            'Positron', 'Spin', 'String', 'Brane', 'Energy', 'Mass', 'Graviton', 'Field', 'Limit', 'Horizon', 'Plurality',
            'Symmetry', 'Matter', 'Force', 'Parsec', 'Quark', 'Inflation',
        ],
        'nouns' => [
            'Suns', 'Stars', 'Galaxies', 'Novas', 'Circles', 'Shifts', 'Particles', 'Metals', 'Strings', 'Branes',
            'Masses', 'Gravitons', 'Fields', 'Symmetries', 'Forces', 'Quarks', 'Super-clusters',
        ],
        'number' => [
            'Two', 'Three', 'Many', 'Multiple', '11', 'Infinite', 'Billions and Billions of', '42',
        ]
    ];

    private const array GroupNamePatterns = [
        '%prefix%/the %adjective%/%color% %noun% %suffix%',
        '%prefix%/the %number% %adjective%? %nouns% %suffix%',
        'the? %adjective% %color%? %noun%/%nouns%',
        'the? %number% %adjective%/%color% %nouns%',
        '%color%/%adjective% %nouns% and %color%/%adjective% %nouns%',
    ];

    private const int MaxSkillRoll = 85;

    public function generateGroupName(): string
    {
        return GroupNameGenerator::generateName($this->rng, self::GroupNamePatterns, self::Dictionary, 60);
    }

    public function meet(PetGroup $group): void
    {
        $activityLogsPerPet = [];
        $expGainPerPet = [];
        $groupSize = count($group->getMembers());

        $skill = 0;
        $progress = $this->rng->rngNextInt(20, 35 + $groupSize * 2);
        /** @var PetChanges[] $petChanges */ $petChanges = [];

        foreach($group->getMembers() as $pet)
        {
            $petWithSkills = $pet->getComputedSkills();
            $petChanges[$pet->getId()] = new PetChanges($pet);

            $roll = $this->rng->rngNextInt(1, 10 + $petWithSkills->getScience()->getTotal());

            $expGainPerPet[$pet->getId()] = max(1, (int)floor($roll / 5));

            $skill += $roll;
        }

        $group
            ->increaseProgress($progress)
            ->increaseSkillRollTotal($skill)
        ;

        // unlike the Leonids, the Perseids fall during the island's rainiest month, so the club only gets to watch them
        // on the rare August night with a clear sky; otherwise, the meeting goes ahead as usual
        if(CalendarFunctions::isPerseidPeakOrAdjacent($this->clock->now) && WeatherService::getSky($this->clock->now) === WeatherSky::Clear)
        {
            $messageTemplate = '%pet% watched the Perseids with %group%, and collected some of their Stardust!';

            foreach($group->getMembers() as $member)
            {
                $member->increaseEsteem($this->rng->rngNextInt(3, 6));

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $member, $this->formatMessage($messageTemplate, $member, $group, ''))
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent)
                    ->setChanges($petChanges[$member->getId()]->compare($member))
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', 'Astronomy Lab', 'Special Event', 'Perseids' ]))
                ;

                $this->inventoryService->petCollectsItem('Stardust', $member, $this->formatMessage($messageTemplate, $member, $group, 'this'), $activityLog);

                $activityLogsPerPet[$member->getId()] = $activityLog;
            }
        }
        else if(CalendarFunctions::isLeonidPeakOrAdjacent($this->clock->now))
        {
            $messageTemplate = '%pet% watched the Leonids with %group%, and collected some of their Stardust!';

            foreach($group->getMembers() as $member)
            {
                $member->increaseEsteem($this->rng->rngNextInt(3, 6));

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $member, $this->formatMessage($messageTemplate, $member, $group, ''))
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent)
                    ->setChanges($petChanges[$member->getId()]->compare($member))
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', 'Astronomy Lab', 'Special Event', 'Leonids' ]))
                ;

                $this->inventoryService->petCollectsItem('Stardust', $member, $this->formatMessage($messageTemplate, $member, $group, 'this'), $activityLog);

                $activityLogsPerPet[$member->getId()] = $activityLog;
            }
        }
        else if($group->getProgress() >= 100)
        {
            // we're expecting a very-maximum of 30 * 5 = 150. this will be exceptionally unlikely, however
            $max = min(self::MaxSkillRoll, $group->getSkillRollTotal());
            $reward = $this->rng->rngNextInt(0, $max);

            $group
                ->clearProgress()
                ->increaseNumberOfProducts()
            ;

            $messageTemplate = '%pet% discovered %this% while exploring the cosmos with %group%!';

            if($reward < 10)
            {
                $item = 'Silica Grounds';
                $description = 'a cloud of space dust';

                if($this->rng->rngNextInt(1, 20) === 1)
                    $description .= '-- I mean, Silica Grounds';
            }
            else if($reward < 20) // 10%
            {
                $item = $this->rng->rngNextFromArray([ 'Pointer', 'NUL' ]);
                $description = 'some old radio transmissions from Earth';
            }
            else if($reward < 25) // 5%
            {
                $item = 'Tentacle';
                $description = 'a tentacle';
                $messageTemplate = '%pet% discovered %this% while exploring the cosmos with %group%! (H-- how did that get there??)';
            }
            else if($reward < 40) // 15%
            {
                $item = 'Planetary Ring';
                $description = 'a Planetary Ring';
            }
            else if($reward < 45) // 5%
            {
                $item = 'Space Junk';
                $description = 'some Space Junk';
            }
            else if($reward < 50) // 5%
            {
                $item = 'Paper';
                $description = 'a Paper';
                $messageTemplate = '%group% wrote %this% based on their findings.';
            }
            else if($reward < 60) // 10%
            {
                $item = 'Dark Matter';
                $description = 'some Dark Matter';
            }
            else if($reward < 65) // 5%
            {
                $item = 'Everice';
                $description = 'a cube of Everice';
            }
            else if($reward < 75) // 10%
            {
                $item = 'Tiny Black Hole';
                $description = 'a Tiny Black Hole';
            }
            else // 10%; see self::MAX_SKILL_ROLL
            {
                $item = 'Strange Field';
                $description = 'a Strange Field';
            }

            $astralEnchantment = EnchantmentRepository::findOneByName($this->em, 'Astral');

            foreach($group->getMembers() as $member)
            {
                $member->increaseEsteem($this->rng->rngNextInt(3, 6));

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $member, $this->formatMessage($messageTemplate, $member, $group, $description))
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ->setChanges($petChanges[$member->getId()]->compare($member))
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', 'Astronomy Lab' ]))
                ;

                $this->inventoryService->petCollectsItem($item, $member, $this->formatMessage($messageTemplate, $member, $group, 'this'), $activityLog);

                $this->maybeUnlockAuraAfterMakingDiscovery(
                    $member, $activityLog, $astralEnchantment, $description, $group->getName(),
                );

                $activityLogsPerPet[$member->getId()] = $activityLog;
            }
        }
        else
        {
            foreach($group->getMembers() as $member)
            {
                if($this->rng->rngNextInt(1, 3) === 1)
                    $member->increaseLove($this->rng->rngNextInt(2, 4));
                else
                    $member->increaseEsteem($this->rng->rngNextInt(2, 4));

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $member,  $member->getName() . ' explored the cosmos with ' . $group->getName() . '.')
                    ->setIcon(self::ActivityIcon)
                    ->addInterestingness(PetActivityLogInterestingness::HoHum)
                    ->setChanges($petChanges[$member->getId()]->compare($member))
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout', 'Astronomy Lab' ]))
                ;

                $activityLogsPerPet[$member->getId()] = $activityLog;
            }
        }

        foreach($group->getMembers() as $pet)
            $this->petExperienceService->gainExp($pet, $expGainPerPet[$pet->getId()], [ PetSkillEnum::Science ], $activityLogsPerPet[$pet->getId()]);

        $this->petRelationshipService->groupGathering(
            $group->getMembers(),
            '%p1% and %p2% talked a little while exploring the cosmos together for ' . $group->getName() . '.',
            '%p1% and %p2% avoided talking as much as possible while exploring the cosmos together for ' . $group->getName() . '.',
            'Met during a ' . $group->getName() . ' meeting.',
            '%p1% met %p2% during a ' . $group->getName() . ' meeting.',
            [ 'Astronomy Lab' ],
            100
        );

        $group->setLastMetOn();
    }

    private function maybeUnlockAuraAfterMakingDiscovery(Pet $pet, PetActivityLog $activityLog, Enchantment $enchantment, string $discoveredItemDescription, string $groupName): void
    {
        if(!$pet->hasMerit(MeritEnum::BEHATTED) || $this->hattierService->userHasUnlocked($pet->getOwner(), $enchantment))
            return;

        $this->hattierService->unlockAuraDuringPetActivity(
            $pet,
            $activityLog,
            $enchantment,
            '(Wow! Space is incredible! You know what\'s also incredible? SPACE ON A HAT!)',
            '(Wow! Space is incredible!)',
            ActivityHelpers::PetName($pet) . ' was inspired by ' . $discoveredItemDescription . ' they found in space with ' . $groupName . '.'
        );
    }

    private function formatMessage(string $template, Pet $member, PetGroup $group, string $findings): string
    {
        return strtr(
            $template,
            [
                '%pet%' => $member->getName(),
                '%group%' => $group->getName(),
                '%this%' => $findings,
            ]
        );
    }
}
