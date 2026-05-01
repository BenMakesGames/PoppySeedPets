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

namespace App\Service\PetActivity\SpecialLocations;

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Functions\ActivityHelpers;
use App\Functions\EnchantmentRepository;
use App\Functions\InventoryHelpers;
use App\Functions\NumberFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\StatusEffectHelpers;
use App\Functions\UserQuestRepository;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\FieldGuideService;
use App\Service\HattierService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class BurntForestService implements IPetActivity
{
    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly InventoryService $inventoryService,
        private readonly IRandom $rng,
        private readonly HattierService $hattierService,
        private readonly EntityManagerInterface $em,
        private readonly FieldGuideService $fieldGuideService
    )
    {
    }

    public function preferredWithFullHouse(): bool { return false; }

    public function groupKey(): string { return 'burntForest'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();

        if($pet->getTool()?->getEnchantment()?->getName() !== 'Burnt')
            return 0;

        $desire = $petWithSkills->getStamina()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getArcana()->getTotal() + $petWithSkills->getUmbraBonus()->getTotal();

        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getArcana() + $pet->getTool()->getItem()->getTool()->getUmbra();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::Umbra))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max([
            (int)ceil($desire * $pet->getPsychedelic() * 2 / $pet->getMaxPsychedelic()),
            $desire
        ]);
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        if($petWithSkills->getPet()->getTool()?->getEnchantment()?->getName() !== 'Burnt')
            return [];

        return [ $this->run(...) ];
    }

    public function run(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $maxSkill = 10
            + (int)(
                (
                    $petWithSkills->getStrength()->getTotal()
                    + $petWithSkills->getBrawl()->getTotal()
                    + $petWithSkills->getIntelligence()->getTotal()
                    + $petWithSkills->getArcana()->getTotal()
                ) / 2
            )
            - $pet->getAlcohol()
            + $petWithSkills->getUmbraBonus()->getTotal()
        ;

        $maxSkill = NumberFunctions::clamp($maxSkill, 1, 15);

        $roll = $this->rng->rngNextInt(1, $maxSkill);

        $changes = new PetChanges($pet);

        $activityLog = match ($roll)
        {
            1, 2, 3 => $this->failToFindAnything($petWithSkills),
            4, 5 => $this->findAWoundedFairy($petWithSkills),
            6 => $this->findABitOfCharcoal($petWithSkills),
            7 => $this->findSquirmingMass($petWithSkills),
            8, 9 => $this->findBurningTree($petWithSkills),
            10, 11 => $this->findTearInTheTapestry($petWithSkills),
            12, 13 => $this->breakToolBonus($pet),
            default => $this->findScalySquirmingMass($petWithSkills),
        };

        $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                PetActivityLogTagEnum::Location_The_Burnt_Forest
            ]))
            ->setChanges($changes->compare($pet))
        ;

        $this->fieldGuideService->maybeUnlock($pet->getOwner(), 'Burnt Forest', ActivityHelpers::PetName($pet) . ' used their ' . $pet->getTool()->getFullItemName() . ' to visit the Burnt Forest.');

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::VisitedTheBurntForest, $activityLog);

        return $activityLog;
    }

    private function failToFindAnything(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::UMBRA, false);

        return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, but all they found were ashes...')
            ->setIcon('icons/activity-logs/confused')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
        ;
    }

    private function findABitOfCharcoal(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        if($this->rng->rngNextInt(1, 2000) < $petWithSkills->getPerception()->getTotal())
        {
            $loot = 'Striped Microcline';
            $pet->increaseEsteem(4);
        }
        else
        {
            $loot = $this->rng->rngNextFromArray([
                'Charcoal', 'Charcoal',
                'Crooked Stick',
                'Chanterelle',
                'Iron Ore',
                'Silica Grounds',
                'Grandparoot',
                $this->rng->rngNextFromArray([ 'Glowing Four-sided Die', 'Glowing Six-sided Die' ])
            ]);
        }

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% explored the Burnt Forest for a while. They didn\'t encounter anything _super_ weird, but they did find ' . $loot . '!')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
        ;

        $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' found this while exploring the Burnt Forest.', $activityLog);

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

        return $activityLog;
    }

    private function findAWoundedFairy(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getArcana()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal());

        if($roll >= 11)
        {
            $petName = ActivityHelpers::PetName($pet);

            if($pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Basement) && $pet->getOwner()->getBasementSize() < User::MaxBasementSize)
            {
                $pet->getOwner()->increaseBasementSize(10);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, "$petName visited the Burnt Forest, and found a wounded fairy! They bandaged it up; thankful, the Fairy cast a minor blessing on $petName, AND on your Basement - it can now hold {$pet->getOwner()->getBasementSize()} items!")
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fae-kind' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, "$petName visited the Burnt Forest, and found a wounded fairy! They bandaged it up; thankful, the Fairy cast a minor blessing on $petName!")
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fae-kind' ]))
                ;
            }

            StatusEffectHelpers::applyStatusEffect($this->em, $pet, $this->rng->rngNextFromArray([
                StatusEffectEnum::Inspired, StatusEffectEnum::Oneiric, StatusEffectEnum::ExtraExtroverted
            ]), 4 * 60);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
            $pet->increaseEsteem($this->rng->rngNextInt(1, 2));

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            // triggers Hyssop letter #1
            $oldValue = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Can Receive Letters from Fairies', 0);
            if($oldValue->getIntValue() === 0)
                $oldValue->setValue(1);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a wounded fairy! They weren\'t able to help, though. The fairy thanked them for trying, anyway...')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fae-kind' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::UMBRA, false);
        }

        return $activityLog;
    }

    private function findSquirmingMass(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getBrawl()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal());

        $resistsFire = $petWithSkills->getHasProtectionFromHeat()->getTotal() > 0;

        if($roll >= 12)
        {
            $loot = $this->rng->rngNextFromArray([ 'Tentacle', 'Tentacle', 'Quintessence' ]);

            if($resistsFire || $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal()) >= 10)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming tentacles! They beat the tentacles back, and got a ' . $loot . '!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming tentacles! They beat the tentacles back, and got a ' . $loot . ', but not without getting burned in the fight!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
            }

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' received this by defeating a mass of flaming tentacles in the Burnt Forest!', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
        }
        else
        {
            if($resistsFire || $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal()) >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming tentacles! They tried to fight, but were forced to flee...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming tentacles! They tried to fight, but got burned by one of the tentacles, and was forced to flee...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(4, 8));
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);
        }

        return $activityLog;
    }

    private function findBurningTree(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $umbraRoll = $this->rng->rngSkillRoll($petWithSkills->getArcana()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getPerception()->getTotal());
        $brawlRoll = $this->rng->rngSkillRoll($petWithSkills->getBrawl()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal());

        $loot = $this->rng->rngNextFromArray([
            'Crooked Stick',
            'Quintessence',
            $this->rng->rngNextFromArray([ 'Red', 'Orange', 'Pamplemousse', 'Apricot', 'Naner', 'Yellowy Lime', 'Ponzu' ])
        ]);

        if($umbraRoll > $brawlRoll)
        {
            if($umbraRoll >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a fire spirit burning a still-living tree! ' . $pet->getName() . ' found a piece of Charcoal, and convinced the spirit to eat that, instead. Grateful, the tree offered them ' . $loot . '.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
                ;

                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' was given this by a tree in the Burnt Forest, as thanks for saving it!', $activityLog);
                $pet->increaseLove($this->rng->rngNextInt(2, 4));
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

                if($pet->hasMerit(MeritEnum::BEHATTED))
                {
                    $livingFlame = EnchantmentRepository::findOneByName($this->em, 'of Living Flame');

                    if(!$this->hattierService->userHasUnlocked($pet->getOwner(), $livingFlame))
                    {
                        $this->hattierService->unlockAuraDuringPetActivity(
                            $pet,
                            $activityLog,
                            $livingFlame,
                            'After finishing its Charcoal, the spirit decided to follow ' . ActivityHelpers::PetName($pet) . ' home in their hat!',
                            'After finishing its Charcoal, the spirit decided to follow ' . ActivityHelpers::PetName($pet) . ' home!',
                            ActivityHelpers::PetName($pet) . ' fed a hungry fire spirit a bit of Charcoal, and the spirit followed them home...'
                        );
                    }
                }

                // triggers Hyssop letter #2
                $oldValue = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Can Receive Letters from Fairies', 0);
                if($oldValue->getIntValue() === 1)
                    $oldValue->setValue(2);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a fire spirit burning a still-living tree! ' . $pet->getName() . ' tried to find a piece of Charcoal to distract the spirit with, but couldn\'t find any...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
                ;
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);
            }
        }
        else
        {
            if($brawlRoll >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a fire spirit burning a still-living tree! ' . $pet->getName() . ' was able to put the fire out; grateful, the tree offered them ' . $loot . '.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting' ]))
                ;

                $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' was given this by a tree in the Burnt Forest, as thanks for saving it!', $activityLog);
                $pet->increaseLove($this->rng->rngNextInt(2, 4));
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a fire spirit burning a still-living tree! ' . $pet->getName() . ' tried to put out the fire, but by the time they chased it off, the tree was already dead...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting' ]))
                ;
                $pet->increaseEsteem(-$this->rng->rngNextInt(2, 4));
                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);
            }
        }

        return $activityLog;
    }

    private function findTearInTheTapestry(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getArcana()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal());

        if($roll >= 13)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a tear in the fabric of reality! It was a little intimidating, but they managed to harvest some Quintessence!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
            ;
            $this->inventoryService->petCollectsItem('Quintessence', $pet, $pet->getName() . ' got this in the Burnt Forest from tear in the fabric of reality.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and found a tear in the fabric of reality! They thought about harvesting some Quintessence, but were worried about getting unraveled, themselves!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::UMBRA, false);
        }

        return $activityLog;
    }

    private function breakToolBonus(Pet $pet): PetActivityLog
    {
        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to visit the Burnt Forest, but a crack of light appeared on ' . $pet->getTool()->getItem()->getName() . ', and it lost its "' . $pet->getTool()->getEnchantment()->getName() . '" bonus! Before the crack faded, a strange piece of paper slipped out of it...')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
        ;

        $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);

        $this->inventoryService->petCollectsItem('Fairy\'s Scroll', $pet, 'This slipped out of a crack of light in ' . $pet->getName() . '\'s ' . $pet->getTool()->getItem()->getName() . '...', $activityLog);

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::OTHER, true);

        if($pet->getTool())
            InventoryHelpers::removeEnchantment($this->em, $pet->getTool());

        return $activityLog;
    }

    private function findScalySquirmingMass(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getBrawl()->getTotal() + $petWithSkills->getStrength()->getTotal() + $petWithSkills->getDexterity()->getTotal());

        $resistsFire = $petWithSkills->getHasProtectionFromHeat()->getTotal() > 0;

        if($roll >= 16)
        {
            $loot = $this->rng->rngNextFromArray([ 'Dark Scales', 'Quinacridone Magenta Dye', 'Quintessence' ]);

            if($resistsFire || $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal()) >= 15)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming, scaly tentacles! They beat the tentacles back, and got a Tentacle, and ' . $loot . '!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming, scaly tentacles! They beat the tentacles back, and got a Tentacle, and ' . $loot . ', but not without getting burned in the fight!')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
            }

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            $this->inventoryService->petCollectsItem('Tentacle', $pet, $pet->getName() . ' received this by defeating a mass of flaming, scaly tentacles in the Burnt Forest!', $activityLog);
            $this->inventoryService->petCollectsItem($loot, $pet, $pet->getName() . ' received this by defeating a mass of flaming, scaly tentacles in the Burnt Forest!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);
        }
        else
        {
            if($resistsFire || $this->rng->rngSkillRoll($petWithSkills->getStamina()->getTotal()) >= 20)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming, scaly tentacles! They tried to fight, but were forced to flee...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(2, 4));
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% visited the Burnt Forest, and encountered a mass of flaming, scaly tentacles! They tried to fight, but got burned by one of the tentacles, and was forced to flee...')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra', 'Fighting', 'Heatstroke' ]))
                ;
                $pet->increaseSafety(-$this->rng->rngNextInt(4, 8));
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Brawl ], $activityLog);

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, false);
        }

        return $activityLog;
    }
}
