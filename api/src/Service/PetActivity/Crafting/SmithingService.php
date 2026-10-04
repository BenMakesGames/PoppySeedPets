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

namespace App\Service\PetActivity\Crafting;

use App\Entity\PetActivityLog;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Functions\ActivityHelpers;
use App\Functions\CalendarFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Service\Clock;
use App\Service\HouseSimService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetActivity\Crafting\Helpers\GoldSmithingService;
use App\Service\PetActivity\Crafting\Helpers\HalloweenSmithingService;
use App\Service\PetActivity\Crafting\Helpers\IronSmithingService;
use App\Service\PetActivity\Crafting\Helpers\MeteoriteSmithingService;
use App\Service\PetActivity\Crafting\Helpers\SilverSmithingService;
use App\Service\PetActivity\Crafting\Helpers\TwuWuvCraftingService;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetExperienceService;
use App\Service\TransactionService;
use Doctrine\ORM\EntityManagerInterface;

class SmithingService implements IPetActivity
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly GoldSmithingService $goldSmithingService,
        private readonly SilverSmithingService $silverSmithingService,
        private readonly IRandom $rng,
        private readonly IronSmithingService $ironSmithingService,
        private readonly MeteoriteSmithingService $meteoriteSmithingService,
        private readonly HalloweenSmithingService $halloweenSmithingService,
        private readonly Clock $clock,
        private readonly EntityManagerInterface $em,
        private readonly TwuWuvCraftingService $twuWuvCraftingService,
        private readonly HouseSimService $houseSimService,
        private readonly TransactionService $transactionService
    )
    {
    }

    public function preferredWithFullHouse(): bool { return true; }

    public function groupKey(): string { return 'smithing'; }

    public function groupDesire(ComputedPetSkills $petWithSkills): int
    {
        $pet = $petWithSkills->getPet();

        if($pet->hasStatusEffect(StatusEffectEnum::Wereform))
            return 0;

        $desire = $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal();

        // when a pet is equipped, the equipment bonus counts twice for affecting a pet's desires
        if($pet->getTool() && $pet->getTool()->getItem()->getTool())
            $desire += $pet->getTool()->getItem()->getTool()->getCrafts() + $pet->getTool()->getItem()->getTool()->getSmithing();

        if($petWithSkills->getPet()->hasActivityPersonality(ActivityPersonalityEnum::CraftingSmithing))
            $desire += 4;
        else
            $desire += $this->rng->rngNextInt(1, 4);

        return max(1, (int)round($desire * (1 + $this->rng->rngNextInt(-10, 10) / 100)));
    }

    public function possibilities(ComputedPetSkills $petWithSkills): array
    {
        $pet = $petWithSkills->getPet();

        $possibilities = [];

        if($this->houseSimService->hasInventory('Twu Wuv'))
        {
            if($this->houseSimService->hasInventory('String') && $this->houseSimService->hasInventory('Silver Bar'))
                $possibilities[] = $this->twuWuvCraftingService->createCupid(...);
        }

        if($this->houseSimService->hasInventory('Charcoal'))
            $possibilities[] = $this->createCoke(...);

        if($this->houseSimService->hasInventory('Toasted Marshmallow') && $this->houseSimService->hasInventory('Blackonite'))
            $possibilities[] = $this->createBlackenedMarshmallow(...);

        if($this->houseSimService->hasInventory('Iron Ore'))
        {
            if($this->houseSimService->hasInventory('Moon Pearl') && $this->houseSimService->hasInventory('Gravitational Waves'))
                $possibilities[] = $this->createHighTide(...);
            else
                $possibilities[] = $this->createIronBar(...);
        }

        if($this->houseSimService->hasInventory('Silver Ore'))
            $possibilities[] = $this->createSilverBar(...);

        if($this->houseSimService->hasInventory('Gold Ore'))
            $possibilities[] = $this->createGoldBar(...);

        if($this->houseSimService->hasInventory('Silica Grounds') && $this->houseSimService->hasInventory('Limestone'))
            $possibilities[] = $this->createGlass(...);

        if($this->houseSimService->hasInventory('Glass'))
        {
            $possibilities[] = $this->createCrystalBall(...);

            if($this->houseSimService->hasInventory('Plastic'))
                $possibilities[] = $this->createFiberglass(...);

            if($this->houseSimService->hasInventory('Silver Bar') || $this->houseSimService->hasInventory('Dark Matter'))
                $possibilities[] = $this->createMirror(...);
        }

        if($this->houseSimService->hasInventory('Fiberglass'))
        {
            if($this->houseSimService->hasInventory('String'))
                $possibilities[] = $this->createFiberglassBow(...);

            if($this->houseSimService->hasInventory('Shiny Pail'))
                $possibilities[] = $this->createShinyNanerPicker(...);
        }

        if($this->houseSimService->hasInventory('Iron Bar'))
        {
            $possibilities[] = $this->ironSmithingService->createIronKey(...);
            $possibilities[] = $this->ironSmithingService->createBasicIronCraft(...);

            if($this->houseSimService->hasInventory('Snail Shell') && $this->houseSimService->hasInventory('Grappling Hook'))
                $possibilities[] = $this->ironSmithingService->createSnailpplingHook(...);

            if($this->houseSimService->hasInventory('Plastic'))
            {
                if($this->houseSimService->hasInventory('Yellow Dye'))
                    $possibilities[] = $this->ironSmithingService->createYellowScissors(...);

                if($this->houseSimService->hasInventory('Green Dye'))
                    $possibilities[] = $this->ironSmithingService->createGreenScissors(...);

                $possibilities[] = $this->ironSmithingService->createSaucepan(...);
            }

            if($this->houseSimService->hasInventory('Wings') && $this->houseSimService->hasInventory('Laser-guided Sword'))
                $possibilities[] = $this->ironSmithingService->createMeatSeekingClaymore(...);

            if($this->houseSimService->hasInventory('Crooked Stick'))
                $possibilities[] = $this->ironSmithingService->createScythe(...);

            if($this->houseSimService->hasInventory('String'))
                $possibilities[] = $this->ironSmithingService->createGrapplingHook(...);

            if($this->houseSimService->hasInventory('Dark Matter'))
                $possibilities[] = $this->ironSmithingService->createHeavyTool(...);

            if($this->houseSimService->hasInventory('Mirror'))
                $possibilities[] = $this->ironSmithingService->createMirrorShield(...);

            if($this->houseSimService->hasInventory('Toadstool'))
                $possibilities[] = $this->ironSmithingService->createMushketeer(...);

            if($this->houseSimService->hasInventory('Hunting Spear'))
                $possibilities[] = $this->ironSmithingService->createBecDeCorbin(...);

            if($this->houseSimService->hasInventory('Green Dye') && $this->houseSimService->hasInventory('Bug-catcher\'s Net'))
                $possibilities[] = $this->ironSmithingService->createWaterStrider(...);
        }

        if($this->houseSimService->hasInventory('Saucepan') || $this->houseSimService->hasInventory('Upside-down Saucepan'))
        {
            if($this->houseSimService->hasInventory('Rice'))
                $possibilities[] = $this->createRiceFryingPan(...);

            if($this->houseSimService->hasInventory('Scales'))
                $possibilities[] = $this->createFishFryingPan(...);
        }

        if($this->houseSimService->hasInventory('Yellow Scissors') && $this->houseSimService->hasInventory('Green Scissors') && $this->houseSimService->hasInventory('Quinacridone Magenta Dye'))
            $possibilities[] = $this->createTriColorScissors(...);

        if($this->houseSimService->hasInventory('Firestone'))
        {
            if($this->houseSimService->hasInventory('Tri-color Scissors'))
                $possibilities[] = $this->createPapersBane(...);

            if($this->houseSimService->hasInventory('Nail File'))
                $possibilities[] = $this->createCrazyHotNailFile(...);

            if($this->houseSimService->hasInventory('Warping Wand'))
                $possibilities[] = $this->createRedWarpingWand(...);

            if($this->houseSimService->hasInventory('Dragonstick'))
                $possibilities[] = $this->createDragonbreath(...);

            if($this->houseSimService->hasInventory('Cooking Buddy'))
                $possibilities[] = $this->createCookingWithFire(...);
        }

        if($this->houseSimService->hasInventory('Silver Bar'))
        {
            $possibilities[] = $this->silverSmithingService->createSilverKey(...);
            $possibilities[] = $this->silverSmithingService->createBasicSilverCraft(...);

            if($this->houseSimService->hasInventory('Mericarp'))
                $possibilities[] = $this->silverSmithingService->createSilveredMericarp(...);

            if($this->houseSimService->hasInventory('Crown Coral'))
                $possibilities[] = $this->silverSmithingService->createCoralTrident(...);

            if($this->houseSimService->hasInventory('"Rustic" Magnifying Glass'))
                $possibilities[] = $this->silverSmithingService->createElvishMagnifyingGlass(...);

            if($this->houseSimService->hasInventory('Leaf Spear'))
                $possibilities[] = $this->silverSmithingService->createSylvanFishingRod(...);

            if($this->houseSimService->hasInventory('Glass'))
            {
                if($this->houseSimService->hasInventory('Silica Grounds'))
                    $possibilities[] = $this->silverSmithingService->createHourglass(...);
            }

            if($this->houseSimService->hasInventory('Gold Key') && $this->houseSimService->hasInventory('White Cloth'))
                $possibilities[] = $this->silverSmithingService->createGoldKeyblade(...);

            if($this->houseSimService->hasInventory('Wand of Lightning'))
                $possibilities[] = $this->silverSmithingService->createLightningAxe(...);

            if(
                $this->houseSimService->hasInventory('Iron Axe') &&
                $this->houseSimService->hasInventory('String') &&
                $this->houseSimService->hasInventory('Talon')
            )
            {
                $possibilities[] = $this->silverSmithingService->createSharktoothAxe(...);
            }
        }

        if($this->houseSimService->hasInventory('Gold Bar'))
        {
            $possibilities[] = $this->goldSmithingService->createGoldKey(...);

            $possibilities[] = $this->goldSmithingService->createGoldTuningFork(...);

            if($this->houseSimService->hasInventory('Mericarp'))
                $possibilities[] = $this->goldSmithingService->createGildedMericarp(...);

            if($this->houseSimService->hasInventory('Culinary Knife') && $this->houseSimService->hasInventory('Firestone'))
                $possibilities[] = $this->goldSmithingService->createNoWhiskNoReward(...);

            if($this->houseSimService->hasInventory('Eggplant'))
                $possibilities[] = $this->goldSmithingService->createAubergineScepter(...);

            if($this->houseSimService->hasInventory('Blackonite') && $this->houseSimService->hasInventory('White Cloth'))
                $possibilities[] = $this->goldSmithingService->createVicious(...);

            if($this->houseSimService->hasInventory('Fiberglass') && $this->houseSimService->hasInventory('Moon Pearl'))
                $possibilities[] = $this->goldSmithingService->createMoonhammer(...);

            if($this->houseSimService->hasInventory('Dark Scales') && $this->houseSimService->hasInventory('Dragon Flag'))
                $possibilities[] = $this->goldSmithingService->createKundravsStandard(...);

            if($this->houseSimService->hasInventory('String'))
                $possibilities[] = $this->goldSmithingService->createGoldTriangle(...);

            if($this->houseSimService->hasInventory('Chanterelle') && $this->houseSimService->hasInventory('Flute'))
                $possibilities[] = $this->goldSmithingService->createFungalClarinet(...);

            if($this->houseSimService->hasInventory('Glass'))
                $possibilities[] = $this->goldSmithingService->createGoldTelescope(...);

            if($this->houseSimService->hasInventory('Plastic Shovel') && $this->houseSimService->hasInventory('Green Dye'))
                $possibilities[] = $this->goldSmithingService->createCoreopsis(...);

            if($this->houseSimService->hasInventory('Plastic') && $this->houseSimService->hasInventory('3D Printer'))
                $possibilities[] = $this->goldSmithingService->createGoldRod(...);

            if($this->houseSimService->hasInventory('Enchanted Compass'))
                $possibilities[] = $this->goldSmithingService->createGoldCompass(...);

            if($this->houseSimService->hasInventory('Silver Key') && $this->houseSimService->hasInventory('White Cloth'))
                $possibilities[] = $this->goldSmithingService->createSilverKeyblade(...);

            if($this->houseSimService->hasInventory('Leaf Spear') && $this->houseSimService->hasInventory('Iron Bar'))
                $possibilities[] = $this->goldSmithingService->createSiderealLeafSpear(...);
        }

        if($this->houseSimService->hasInventory('Silver Bar') && $this->houseSimService->hasInventory('Gold Bar') && $this->houseSimService->hasInventory('White Cloth'))
            $possibilities[] = $this->createCeremonialTrident(...);

        if($this->houseSimService->hasInventory('Iron Sword'))
        {
            if($this->houseSimService->hasInventory('Lotusjar') && $this->houseSimService->hasInventory('Antlers'))
                $possibilities[] = $this->createBladeOfTheGlade(...);

            if($this->houseSimService->hasInventory('Whisk'))
                $possibilities[] = $this->createCulinaryKnife(...);

            if($this->houseSimService->hasInventory('Wooden Sword'))
                $possibilities[] = $this->createWoodsMetal(...);

            if($this->houseSimService->hasInventory('Scales') && $this->houseSimService->hasInventory('Fluff'))
                $possibilities[] = $this->createDragonscale(...);

            if($this->houseSimService->hasInventory('Dark Scales') && $this->houseSimService->hasInventory('Fluff'))
                $possibilities[] = $this->createDrakkonscale(...);

            if($this->houseSimService->hasInventory('Everice') && $this->houseSimService->hasInventory('Firestone'))
                $possibilities[] = $this->createAntipode(...);
        }

        if($this->houseSimService->hasInventory('Lightning Sword'))
        {
            if($this->houseSimService->hasInventory('Antipode'))
                $possibilities[] = $this->createTrinityBlade(...);

            if($this->houseSimService->hasInventory('Wand of Lightning'))
                $possibilities[] = $this->createWayTooMuchLightning(...);
        }

        if($this->houseSimService->hasInventory('Everice'))
        {
            if($this->houseSimService->hasInventory('Poker'))
                $possibilities[] = $this->createWandOfIce(...);

            if($this->houseSimService->hasInventory('Crooked Fishing Rod'))
                $possibilities[] = $this->createIceFishing(...);
        }

        if($this->houseSimService->hasInventory('Poker') && $this->houseSimService->hasInventory('Gypsum Dragon'))
            $possibilities[] = $this->createDragonstick(...);

        if($this->houseSimService->hasInventory('Meteorite'))
        {
            if($this->houseSimService->hasInventory('Iron Bar') && $this->houseSimService->hasInventory('Gold Bar'))
                $possibilities[] = $this->meteoriteSmithingService->createIlumetsa(...);

            if($this->houseSimService->hasInventory('Moon Pearl') && $this->houseSimService->hasInventory('Dark Mirror'))
                $possibilities[] = $this->meteoriteSmithingService->createHorizonMirror(...);
        }

        if(CalendarFunctions::isHalloweenCrafting($this->clock->now))
        {
            if($this->houseSimService->hasInventory('Small, Yellow Plastic Bucket') || $this->houseSimService->hasInventory('Upside-down, Yellow Plastic Bucket'))
                $possibilities[] = $this->halloweenSmithingService->createPumpkinBucket(...);
        }

        return $possibilities;
    }

    public function createBladeOfTheGlade(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 26)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Lotusjar', 1);
            $this->houseSimService->getState()->loseItem('Antlers', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' smithed a Blade of the Glade!')
                ->setIcon('items/tool/sword/glade-blade')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 26)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Blade of the Glade', $pet, $pet->getName() . ' created this by setting a Lotusjar into an Iron Sword... with an Antler hilt for extra style!', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' had an idea for a cool sword made with Antlers, but couldn\'t quite get all the details ironed out...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createTriColorScissors(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Yellow Scissors', 1);
            $this->houseSimService->getState()->loseItem('Green Scissors', 1);
            $this->houseSimService->getState()->loseItem('Quinacridone Magenta Dye', 1);
            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% combined two pairs of scissors, creating Tri-color Scissors!')
                ->setIcon('items/tool/scissors/tri-color')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Tri-color Scissors', $pet, $pet->getName() . ' made this by combining two pairs of scissors!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Tri-color Scissors, but got confused just thinking about what it would even look like...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createPapersBane(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 20)
        {
            $this->houseSimService->getState()->loseItem('Tri-color Scissors', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% infused Tri-color Scissors with the eternal heat of Firestone!')
                ->setIcon('items/tool/scissors/papersbane')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Paper\'s Bane', $pet, $pet->getName() . ' made this by infusing Tri-color Scissors with the eternal heat of Firestone!', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Paper\'s Bane, but almost burned themselves on the Firestone...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createCrazyHotNailFile(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 30)
        {
            $this->houseSimService->getState()->loseItem('Nail File', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% infused Nail File with the eternal heat of Firestone... and had plenty of Firestone left over!')
                ->setIcon('items/tool/nail-fire-crazy-hot')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 30)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Crazy-hot Nail File', $pet, $pet->getName() . ' made this by infusing a Nail File with the eternal heat of Firestone!', $activityLog);

            $this->petExperienceService->gainExp($pet, 5, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);

        }
        else if($roll >= 20)
        {
            $this->houseSimService->getState()->loseItem('Nail File', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% infused a Nail File with the eternal heat of Firestone!')
                ->setIcon('items/tool/nail-fire-crazy-hot')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Crazy-hot Nail File', $pet, $pet->getName() . ' made this by infusing a Nail File with the eternal heat of Firestone!', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else if($roll === 1)
        {
            $this->houseSimService->getState()->loseItem('Nail File', 1);

            $pet->increaseEsteem(-4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried infusing a Nail File with the eternal heat of Firestone, but the Nail File was completely obliterated!')
                ->setIcon('icons/activity-logs/confused')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to super-heat a Nail File with some Firestone, but almost burned themselves! They decided to take a break...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createRedWarpingWand(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 26)
        {
            $this->houseSimService->getState()->loseItem('Warping Wand', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% infused a Warping Wand with the eternal heat of Firestone!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 25)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Red Warping Wand', $pet, $pet->getName() . ' made this by infusing a Warping Wand with the eternal heat of Firestone!', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            if($this->rng->rngNextInt(1, 2) === 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Red Warping Wand, but almost burned themselves on the Firestone...')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
            }
            else
            {
                $location = $this->rng->rngNextFromArray([ 'on the roof', 'in the bathtub', 'in the dishwasher', 'under your bed', 'in your closet', 'in the mailbox' ]);
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Red Warping Wand, but accidentally warped the Firestone away. They looked around for a while, and finally found it ' . $location . '.')
                    ->setIcon('icons/activity-logs/confused')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
            }

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    /**
     * note: THIS method should be private, but most methods here must be public!
     */
    private function createFryingPan(ComputedPetSkills $petWithSkills, string $otherMaterial, string $makes): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->houseSimService->getState()->loseItem($otherMaterial, 1);

            $pet->increaseEsteem(-2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to smith a ' . $makes . ', but accidentally _obliterated_ the ' . $otherMaterial . '! :(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 45), PetActivityStatEnum::SMITH, false);
        }
        else if($roll < 17)
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to smith a ' . $makes . ', but kept having trouble working the ' . $otherMaterial . ' in...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }
        else // success!
        {
            $this->houseSimService->getState()->loseOneOf($this->rng, [ 'Saucepan', 'Upside-down Saucepan' ]);
            $this->houseSimService->getState()->loseItem($otherMaterial, 1);

            $pet->increaseEsteem(2);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed a ' . $makes . '!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, true);
            $this->inventoryService->petCollectsItem($makes, $pet, $pet->getName() . ' made this.', $activityLog);
        }

        return $activityLog;
    }

    public function createFishFryingPan(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        return $this->createFryingPan($petWithSkills, 'Scales', 'Fish Frying Pan');
    }

    public function createRiceFryingPan(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        return $this->createFryingPan($petWithSkills, 'Rice', 'Rice Frying Pan');
    }

    public function createMirror(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 3)
        {
            $pet->increaseSafety(-4);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried silvering some Glass, but accidentally cut themselves! :(')
                ->setIcon('icons/activity-logs/wounded')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);

            return $activityLog;
        }
        else if($roll >= 13)
        {
            $mirrorBacking = $this->houseSimService->getState()->loseOneOf($this->rng, [ 'Silver Bar', 'Dark Matter' ]);
            $this->houseSimService->getState()->loseItem('Glass', 1);
            $pet->increaseEsteem(2);

            if($mirrorBacking === 'Dark Matter')
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Dark Mirror.')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
                $this->inventoryService->petCollectsItem('Dark Mirror', $pet, $pet->getName() . ' created this.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Mirror.')
                    ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
                ;
                $this->inventoryService->petCollectsItem('Mirror', $pet, $pet->getName() . ' created this.', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Mirror, but couldn\'t get the Glass smoooooth enough.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createHighTide(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + min($petWithSkills->getScience()->getTotal(), $petWithSkills->getCrafts()->getTotal()) + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 3)
        {
            $this->houseSimService->getState()->loseItem('Gravitational Waves', 1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried smithing a High Tide, but the Gravitational Waves got away! :(')
                ->setIcon('icons/activity-logs/wounded')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Physics' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 18)
        {
            $this->houseSimService->getState()->loseItem('Iron Ore', 1);
            $this->houseSimService->getState()->loseItem('Moon Pearl', 1);
            $this->houseSimService->getState()->loseItem('Gravitational Waves', 1);
            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a High Tide.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 18)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Smithing,
                    PetActivityLogTagEnum::Physics,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('High Tide', $pet, $pet->getName() . ' created this.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            if($this->rng->rngNextBool())
            {
                $but = 'started pulverizing the Iron Ore, and almost immediately felt exhausted';
                $pet->increaseFood(-1);
            }
            else
            {
                $but = 'kept getting stumped by the math for Gravitational Waves';
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a High Tide, but ' . $but . '.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Physics' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createGlass(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 12));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Glass, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 13)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Silica Grounds', 1);

            if($this->rng->rngNextInt(1, 3) === 1)
            {
                $this->houseSimService->getState()->loseItem('Limestone', 1);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% melted Silica Grounds and Limestone into TWO Glass!')
                    ->setIcon('items/mineral/silica-glass');

                $this->inventoryService->petCollectsItem('Glass', $pet, $pet->getName() . ' created this from Silica Grounds and Limestone.', $activityLog);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% melted Silica Grounds and Limestone into Glass. (There\'s plenty of Limestone left over, though!)')
                    ->setIcon('items/mineral/silica-glass');
            }

            $activityLog
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 13)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Smithing,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;

            $this->inventoryService->petCollectsItem('Glass', $pet, $pet->getName() . ' created this from Silica Grounds and Limestone.', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $pet->increaseEsteem(1);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Glass, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createCrystalBall(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2)
        {
            $pet->increaseSafety(-4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Crystal Ball, but accidentally cut themselves on the Glass! :(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 20 && $this->rng->rngNextInt(1, 3) === 1)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Glass', 1);

            $pet->increaseEsteem($this->rng->rngNextInt(2, 4));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made TWO Crystal Balls out of Glass!')
                ->setIcon('items/mineral/silica-glass-ball')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 20)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Smithing,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Crystal Ball', $pet, $pet->getName() . ' created this from Glass.', $activityLog);
            $this->inventoryService->petCollectsItem('Crystal Ball', $pet, $pet->getName() . ' created this from Glass.', $activityLog);

            $this->maybeMakeARainbowToo($petWithSkills, 2);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else if($roll >= 12)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Glass', 1);

            $pet->increaseEsteem(1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Crystal Ball out of Glass.')
                ->setIcon('items/mineral/silica-glass-ball')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 12)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Smithing,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Crystal Ball', $pet, $pet->getName() . ' created this from Glass.', $activityLog);

            $this->maybeMakeARainbowToo($petWithSkills, 1);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Crystal Ball, but making a perfect sphere was proving difficult!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createFiberglass(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {

            $pet->increaseSafety(-$this->rng->rngNextInt(2, 12));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Fiberglass, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 25 && $this->rng->rngNextInt(1, 3) === 1)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Glass', 1);
            $this->houseSimService->getState()->loseItem('Plastic', 1);

            $pet->increaseEsteem($this->rng->rngNextInt(3, 6));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made TWO bundles of Fiberglass from Glass and Plastic!')
                ->setIcon('items/resource/fiberglass')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 25)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Fiberglass', $pet, $pet->getName() . ' created this from Glass and Plastic.', $activityLog);
            $this->inventoryService->petCollectsItem('Fiberglass', $pet, $pet->getName() . ' created this from Glass and Plastic.', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else if($roll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Glass', 1);
            $this->houseSimService->getState()->loseItem('Plastic', 1);

            $pet->increaseEsteem(1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a bundle of Fiberglass from Glass and Plastic.')
                ->setIcon('items/resource/fiberglass')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Fiberglass', $pet, $pet->getName() . ' created this from Glass and Plastic.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Fiberglass, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createFiberglassBow(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 14)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Fiberglass', 1);
            $this->houseSimService->getState()->loseItem('String', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Fiberglass Bow.')
                ->setIcon('items/tool/bow/fiberglass')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 14)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Fiberglass Bow', $pet, $pet->getName() . ' created this from Fiberglass, and String.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);

            return $activityLog;
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Fiberglass Bow, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);

            return $activityLog;
        }
    }

    public function createShinyNanerPicker(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 18)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Fiberglass', 1);
            $this->houseSimService->getState()->loseItem('Shiny Pail', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Shiny Naner-picker.')
                ->setIcon('items/tool/bow/fiberglass')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 14)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Shiny Naner-picker', $pet, $pet->getName() . ' created this from Fiberglass, and a Shiny Pail.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Shiny Naner-picker, but the Fiberglass was proving difficult to work with...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createCeremonialTrident(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($pet->hasMerit(MeritEnum::SILVERBLOOD))
            $roll += 5;

        if($roll <= 3)
        {
            $moneys = $this->rng->rngNextInt(10, 30);

            $this->houseSimService->getState()->loseItem('Silver Bar', 1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Ceremonial Trident, but melted the heck out of the Silver Bar! :( ' . $pet->getName() . ' decided to make some coins out of it, instead, and got ' . $moneys . '~~m~~.')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Moneys' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);

            $this->transactionService->getMoney($pet->getOwner(), $moneys, $pet->getName() . ' made some silver coins after failing to forge a Ceremonial Trident.');
        }
        else if($roll >= 17)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Silver Bar', 1);
            $this->houseSimService->getState()->loseItem('Gold Bar', 1);
            $this->houseSimService->getState()->loseItem('White Cloth', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% made a Ceremonial Trident!')
                ->setIcon('items/tool/spear/trident-ceremonial')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Ceremonial Trident', $pet, $pet->getName() . ' created this from gold, silver, and cloth.', $activityLog);

            $this->petExperienceService->gainExp($pet, $this->rng->rngNextInt(2, 3), [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $message = $pet->hasMerit(MeritEnum::EIDETIC_MEMORY)
                ? '%pet:' . $pet->getId() . '.name% tried to make a Ceremonial Trident, but couldn\'t get the shape just right...'
                : '%pet:' . $pet->getId() . '.name% started making a Ceremonial Spear, but then remembered there\'s no such thing... >_>'
            ;

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, $this->rng->rngNextInt(1, 2), [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createAntipode(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll == 1)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $pet->increaseEsteem(-3);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Antipode, but accidentally warped the Iron Sword into an unrecognizable shape :|')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->inventoryService->petCollectsItem('Iron Bar', $pet, $pet->getName() . ' accidentally broke an Iron Sword; this was all that remained!', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else if($roll >= 22)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Everice', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed Antipode!')
                ->setIcon('items/tool/sword/antipode')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 22)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Antipode', $pet, $pet->getName() . ' created this by hammering Everice and Firestone into an Iron Sword!', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Antipode, but the ' . $this->rng->rngNextFromArray([ 'Everice', 'Firestone' ]) . ' was being uncooperative :|')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createWoodsMetal(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + max($petWithSkills->getCrafts()->getTotal(), $petWithSkills->getScience()->getTotal()) + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
            $pet->increaseSafety(-4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to create Wood\'s Metal, but accidentally burnt themselves trying! :(')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Physics' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else if($roll >= 17)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Wooden Sword', 1);

            $pet->increaseEsteem(4);

            $message = $pet->getName() . ' created Wood\'s Metal by alloying an Iron Sword and a Wooden Sword.';

            if($this->rng->rngNextInt(1, 4) === 1)
                $message .= ' (That\'s probably how that works, right?)';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
                ->setIcon('items/tool/sword/woodsmetal')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 17)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Physics' ]))
            ;
            $this->inventoryService->petCollectsItem('Wood\'s Metal', $pet, $pet->getName() . ' made this!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Wood\'s Metal, but couldn\'t figure it out...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing', 'Physics' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createCulinaryKnife(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Whisk', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $pet->getName() . ' created a Culinary Knife.')
                ->setIcon('items/tool/whisk/dagger')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Culinary Knife', $pet, $pet->getName() . ' made this!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Culinary Knife, but couldn\'t figure it out...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createDragonscale(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 16)
        {
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Fluff', 1);
            $this->houseSimService->getState()->loseItem('Scales', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% upgraded an Iron Sword into Dragonscale!')
                ->setIcon('items/tool/sword/dragon-scale')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Dragonscale', $pet, $pet->getName() . ' made this by inlaying Scales into an Iron Sword!', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Dragonscale, but got intimidated by all the detailing work that would be required!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createDrakkonscale(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 17)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Iron Sword', 1);
            $this->houseSimService->getState()->loseItem('Fluff', 1);
            $this->houseSimService->getState()->loseItem('Dark Scales', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% upgraded an Iron Sword into Drakkonscale!')
                ->setIcon('items/tool/sword/drakkon-scale')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 16)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Drakkonscale', $pet, $pet->getName() . ' made this by inlaying Dark Scales into an Iron Sword!', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Drakkonscale, but got intimidated by all the detailing work that would be required!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createTrinityBlade(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 25)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Antipode', 1);
            $this->houseSimService->getState()->loseItem('Lightning Sword', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed a Trinity Blade!')
                ->setIcon('items/tool/sword/elemental')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 25)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Trinity Blade', $pet, $pet->getName() . ' created this by hammering a Lightning Sword and Antipode together!', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to make a Trinity Blade, but wasn\'t sure where to begin...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createWayTooMuchLightning(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 25)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Lightning Sword', 1);
            $this->houseSimService->getState()->loseItem('Wand of Lightning', 1);

            $pet->increaseEsteem(6);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed, like, Way _(way)_ Too Much Lightning! Fortunately, some of the energy was converted into Magic Smoke. So that\'s neat.')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 25)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Way Too Much Lightning', $pet, $pet->getName() . ' created this by hammering a Lightning Sword and a Wand of Lightning together!', $activityLog);
            $this->inventoryService->petCollectsItem('Magic Smoke', $pet, 'This was created as a byproduct of ' . $pet->getName() . ' creating Way Too Much Lightning!', $activityLog);

            $this->petExperienceService->gainExp($pet, 4, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to stuff a ' . $this->rng->rngNextFromArray([ 'Wand of Lightning', 'Lightning Sword' ]) . ' full of even more lightning, but wasn\'t sure how to make it work...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createWandOfIce(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll == 1)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Poker', 1);
            $pet->increaseEsteem(-3);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Wand of Ice, but accidentally warped the Poker into an unrecognizable shape :|')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->inventoryService->petCollectsItem('Iron Bar', $pet, $pet->getName() . ' accidentally broke a Poker; this was all that remained!', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else if($roll >= 19)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Poker', 1);
            $this->houseSimService->getState()->loseItem('Everice', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed a Wand of Ice!')
                ->setIcon('items/tool/wand/ice')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 19)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Wand of Ice', $pet, $pet->getName() . ' created this by hammering Everice into a Poker!', $activityLog);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a Wand of Ice, but the Everice was being uncooperative :|')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createIceFishing(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 14)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Crooked Fishing Rod', 1);
            $this->houseSimService->getState()->loseItem('Everice', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed Ice Fishing!')
                ->setIcon('items/tool/fishing-rod/ice')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 19)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Ice Fishing', $pet, $pet->getName() . ' created this by hammering Everice into a Crooked Fishing Rod!', $activityLog);
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make Ice Fishing, but the Everice was being uncooperative :|')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createDragonstick(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getDexterity()->getTotal() + $petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 15)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Gypsum Dragon', 1);
            $this->houseSimService->getState()->loseItem('Poker', 1);

            $pet->increaseEsteem(2);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% created a Dragonstick!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 15)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Dragonstick', $pet, $pet->getName() . ' created this by affixing the head of a Gypsum Dragon to a Poker!', $activityLog);
            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started to make a dragon-inspired staff, but almost broke the Gypsum Dragon they were working with!')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createDragonbreath(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 22)
        {
            $this->houseSimService->getState()->loseItem('Dragonstick', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);

            $pet->increaseEsteem(4);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% infused a Dragonstick with the eternal heat of Firestone!')
                ->addInterestingness(PetActivityLogInterestingness::HoHum + 22)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Dragonbreath', $pet, $pet->getName() . ' made this by infusing a Dragonstick with the eternal heat of Firestone!', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to make a dragon-themed staff, but almost burned themselves on the Firestone...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    private function createCookingWithFire(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll(max($petWithSkills->getDexterity()->getTotal(), $petWithSkills->getIntelligence()->getTotal()) + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 18)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::CRAFT, true);
            $this->houseSimService->getState()->loseItem('Cooking Buddy', 1);
            $this->houseSimService->getState()->loseItem('Firestone', 1);
            $pet->increaseEsteem(4);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% set a Cooking Buddy on fire using some Firestone. (Turns out they\'re fireproof!)')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Smithing,
                    PetActivityLogTagEnum::Location_At_Home,
                ]))
            ;
            $this->inventoryService->petCollectsItem('Cooking... with Fire', $pet, $pet->getName() . ' created this by setting a Cooking Buddy on fire.', $activityLog);

            if($pet->hasMerit(MeritEnum::METATRON_S_TOUCH))
                $this->inventoryService->petCollectsItem('Rock', $pet, 'This was left over after ' . $pet->getName() . ' worked with Firestone.', $activityLog);

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% wanted to do something silly to a Cooking Buddy, but couldn\'t decide what...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Smithing, PetActivityLogTagEnum::Location_At_Home ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::CRAFT, false);
        }

        return $activityLog;
    }

    public function createBlackenedMarshmallow(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll >= 20)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Toasted Marshmallow', 1);
            $this->houseSimService->getState()->loseItem('Blackonite', 1);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% smithed a sword out of Blackonite, and imbued it with the power of a Toasted Marshmallow!')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]));

            $this->inventoryService->petCollectsItem('Blackened Marshmallow', $pet, $pet->getName() . ' smithed this from Blackonite, and imbued it with the power of a Toasted Marshmallow!', $activityLog);

            $this->petExperienceService->gainExp($pet, 3, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to smith a sword out of Blackonite, but couldn\'t get the shape right...')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createCoke(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll === 1 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine some Charcoal, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 12)
        {
            $getRareStone = $this->rng->rngNextInt(1, $pet->hasMerit(MeritEnum::LUCKY) ? 50 : 100) === 1;
            $rareStone = $this->rng->rngNextFromArray([ 'Blackonite', 'Firestone' ]);
            $attributeLuckiness = false;

            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Charcoal', 1);

            $pet->increaseEsteem($getRareStone ? 8 : 1);

            if($getRareStone)
            {
                $attributeLuckiness = $pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 4) > 1;

                if($attributeLuckiness)
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Charcoal into Coke, and what\'s this?! There was a piece of ' . $rareStone . ' inside! Lucky~!')
                        ->setIcon('items/resource/coke')
                        ->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit)
                        ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]))
                    ;
                }
                else
                {
                    $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Charcoal into Coke, and what\'s this?! There was a piece of ' . $rareStone . ' inside!')
                        ->setIcon('items/resource/coke');
                }
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Charcoal into Coke.')
                    ->setIcon('items/resource/coke');
            }

            $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]));

            $this->inventoryService->petCollectsItem('Coke', $pet, $pet->getName() . ' refined this from Charcoal.', $activityLog);

            if($getRareStone)
            {
                if($attributeLuckiness)
                {
                    $this->inventoryService->petCollectsItem($rareStone, $pet, $pet->getName() . ' found this while refining Charcoal into Coke! Lucky~!', $activityLog);
                    $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Lucky~!' ]));
                }
                else
                    $this->inventoryService->petCollectsItem($rareStone, $pet, $pet->getName() . ' found this while refining Charcoal into Coke!', $activityLog);
            }

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine Coke from Charcoal, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createIronBar(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 24));

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine some Iron Ore, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 12)
        {
            $this->houseSimService->getState()->loseItem('Iron Ore', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Iron Ore into an Iron Bar.')
                ->setIcon('items/element/iron-pure')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Iron Bar', $pet, $pet->getName() . ' refined this from Iron Ore.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine Iron Ore into an Iron Bar, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createSilverBar(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($pet->hasMerit(MeritEnum::SILVERBLOOD))
            $roll += 5;

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 12));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine some Silver Ore, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 12)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Silver Ore', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Silver Ore into a Silver Bar.')
                ->setIcon('items/element/silver-pure')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Silver Bar', $pet, $pet->getName() . ' refined this from Silver Ore.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine Silver Ore into a Silver Bar, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    public function createGoldBar(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $roll = $this->rng->rngSkillRoll($petWithSkills->getIntelligence()->getTotal() + $petWithSkills->getStamina()->getTotal() + $petWithSkills->getCrafts()->getTotal() + $petWithSkills->getSmithingBonus()->getTotal());

        if($roll <= 2 && $petWithSkills->getHasProtectionFromHeat()->getTotal() <= 0)
        {
            $pet->increaseSafety(-$this->rng->rngNextInt(2, 8));
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine some Gold Ore, but got burned while trying! :(')
                ->setIcon('icons/activity-logs/burn')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::SMITH, false);
        }
        else if($roll >= 12)
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::SMITH, true);
            $this->houseSimService->getState()->loseItem('Gold Ore', 1);
            $pet->increaseEsteem(1);
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% refined some Gold Ore into a Gold Bar.')
                ->setIcon('items/element/gold-pure')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;
            $this->inventoryService->petCollectsItem('Gold Bar', $pet, $pet->getName() . ' refined this from Gold Ore.', $activityLog);

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
        }
        else
        {
            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% tried to refine Gold Ore into a Gold Bar, but couldn\'t figure it out.')
                ->setIcon('icons/activity-logs/confused')
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Smithing' ]))
            ;

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Crafts ], $activityLog);
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 75), PetActivityStatEnum::SMITH, false);
        }

        return $activityLog;
    }

    private function maybeMakeARainbowToo(ComputedPetSkills $petWithSkills, int $numberOfCrystalBalls): ?PetActivityLog
    {
        $pet = $petWithSkills->getPet();
        $lucky = $pet->hasMerit(MeritEnum::LUCKY) && $this->rng->rngNextInt(1, 60) === 1;

        if($this->rng->rngNextInt(1, 60) <= floor(sqrt(max(1, $petWithSkills->getScience()->getTotal()))) || $lucky)
        {
            $luckySuffix = $lucky ? ' Lucky~!' : '';

            $pet->increaseEsteem($this->rng->rngNextInt(4, 8));

            $message = $numberOfCrystalBalls === 1
                ? 'While %pet:' . $pet->getId() . '.name% was making a Crystal Ball, they happened to catch the light just right, and caught a Rainbow, too!'
                : 'While %pet:' . $pet->getId() . '.name% was making some Crystal Balls, they happened to catch the light just right, and caught a Rainbow, too!'
            ;

            $tags = [ 'Physics' ];
            if($lucky) $tags[] = 'Lucky~!';

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message . $luckySuffix)
                ->addInterestingness($lucky ? PetActivityLogInterestingness::ActivityUsingMerit : PetActivityLogInterestingness::UncommonActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, $tags))
            ;

            $this->inventoryService->petCollectsItem('Rainbow', $pet, $pet->getName() . ' captured this while making a Crystal Ball!' . $luckySuffix, $activityLog);

            return $activityLog;
        }

        return null;
    }
}
