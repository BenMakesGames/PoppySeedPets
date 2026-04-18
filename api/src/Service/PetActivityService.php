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

namespace App\Service;

use App\Entity\LunchboxItem;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\PetBaby;
use App\Entity\User;
use App\Enum\ActivityPersonalityEnum;
use App\Enum\GatheringHolidayEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\CalendarFunctions;
use App\Functions\ColorFunctions;
use App\Functions\InventoryModifierFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\StatusEffectHelpers;
use App\Functions\UserQuestRepository;
use App\Model\ComputedPetSkills;
use App\Model\FoodWithSpice;
use App\Model\PetChanges;
use App\Model\PetChangesSummary;
use App\Service\PetActivity\CachingMeritAdventureService;
use App\Service\PetActivity\DreamingAndDaydreamingService;
use App\Service\PetActivity\EatingService;
use App\Service\PetActivity\FatedAdventureService;
use App\Service\PetActivity\GatheringHolidayAdventureService;
use App\Service\PetActivity\GenericAdventureService;
use App\Service\PetActivity\GivingTreeGatheringService;
use App\Service\PetActivity\Holiday\HuntTurkeyDragon;
use App\Service\PetActivity\IPetActivity;
use App\Service\PetActivity\LetterService;
use App\Service\PetActivity\PetCleaningSelfService;
use App\Service\PetActivity\PetSummonedAwayService;
use App\Service\PetActivity\PoopingService;
use App\Service\PetActivity\PregnancyService;
use App\Service\PetActivity\ToolAdventures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class PetActivityService
{
    public function __construct(
        private readonly Clock $clock,
        private readonly EntityManagerInterface $em,
        private readonly ResponseService $responseService,
        private readonly GenericAdventureService $genericAdventureService,
        private readonly PetSummonedAwayService $petSummonedAwayService,
        private readonly PoopingService $poopingService,
        private readonly GivingTreeGatheringService $givingTreeGatheringService,
        private readonly PregnancyService $pregnancyService,
        private readonly IRandom $rng,
        private readonly PetExperienceService $petExperienceService,
        private readonly DreamingAndDaydreamingService $dreamingAndDaydreamingService,
        private readonly EatingService $eatingService,
        private readonly GatheringHolidayAdventureService $gatheringHolidayAdventureService,
        private readonly InventoryService $inventoryService,
        private readonly LetterService $letterService,
        private readonly HouseSimService $houseSimService,
        private readonly CravingService $cravingService,
        private readonly FatedAdventureService $fatedAdventureService,
        private readonly PetCleaningSelfService $petCleaningSelfService,
        private readonly CachingMeritAdventureService $cachingMeritAdventureService,
        private readonly HuntTurkeyDragon $huntTurkeyDragon,
        private readonly ToolAdventures $toolAdventures,
        /**
         * @var iterable<IPetActivity>
         */
        #[AutowireIterator('app.petActivity')]
        private readonly iterable $petActivities
    )
    {
    }

    public function runHour(Pet $pet): void
    {
        $hasEventPersonality = $pet->hasActivityPersonality(ActivityPersonalityEnum::EventsAndMaps);

        if(!$pet->isAtHome())
            throw new \InvalidArgumentException('Trying to run activities for a pet that is not at home! (Ben did something horrible; please let him know.)');

        if($pet->getHouseTime()->getActivityTime() < 60)
            throw new \InvalidArgumentException('Trying to run activities for a pet that does not have enough time! (Ben did something horrible; please let him know.)');

        $this->responseService->setReloadPets();

        if($pet->hasMerit(MeritEnum::HYPERCHROMATIC))
            $this->doHyperchromaticTweak($pet);

        $this->updatePetNeeds($pet);

        $this->cravingService->maybeRemoveCraving($pet);

        if($this->pregnancyService->advancePetPregnancy($pet))
            return;

        if($this->processPoison($pet))
            return;

        if($this->petCanPoop($pet))
            $this->poopingService->poopDarkMatter($pet);

        if($pet->hasMerit(MeritEnum::SHEDS) && $this->rng->rngNextInt(1, 180) === 1)
            $this->poopingService->shed($pet);

        $petWithSkills = $pet->getComputedSkills();

        if($this->rng->rngNextInt(1, 4000) === 1)
        {
            $this->petSummonedAwayService->adventure($petWithSkills);
            return;
        }

        $this->maybeEatOutOfLunchbox($pet);
        $this->maybeDoWereformTransformation($pet);

        if($pet->hasMerit(MeritEnum::CACHING) && $pet->getFullnessPercent() < -0.25)
        {
            if($this->cachingMeritAdventureService->doAdventure($petWithSkills))
                return;
        }

        if($pet->hasStatusEffect(StatusEffectEnum::OilCovered))
        {
            if($this->petCleaningSelfService->cleanUpStatusEffect($pet, StatusEffectEnum::OilCovered, 'Oil'))
                return;
        }

        if($pet->hasStatusEffect(StatusEffectEnum::BubbleGumd))
        {
            if($this->petCleaningSelfService->cleanUpStatusEffect($pet, StatusEffectEnum::BubbleGumd, 'Bubblegum'))
                return;
        }

        if($pet->hasStatusEffect(StatusEffectEnum::GobbleGobble) && $this->rng->rngNextInt(1, 2) === 1)
        {
            $changes = new PetChanges($pet);
            $activityLog = $this->huntTurkeyDragon->hunt($petWithSkills);
            $activityLog->setChanges($changes->compare($pet));
            return;
        }

        if($pet->hasStatusEffect(StatusEffectEnum::LapineWhispers) && $this->rng->rngNextInt(1, 2) === 1)
        {
            $changes = new PetChanges($pet);
            $activityLog = $this->speakToBunnySpirit($pet);
            $activityLog->setChanges($changes->compare($pet));
            $pet->removeStatusEffect(StatusEffectEnum::LapineWhispers);
            return;
        }

        if($this->dreamingAndDaydreamingService->maybeDreamOrDaydream($petWithSkills))
            return;

        if($this->maybeReceiveFairyGodmotherVisit($pet))
            return;

        if($this->maybeReceiveAthenasGift($pet))
            return;

        $itemsInHouse = $this->houseSimService->getState()->getInventoryCount();

        $houseTooFull = $this->rng->rngNextInt(1, 10) > User::MaxHouseInventory - $itemsInHouse;

        if($houseTooFull)
        {
            if($itemsInHouse >= User::MaxHouseInventory)
                $description = '%user:' . $pet->getOwner()->getId() . '.Name\'s% house is crazy-full.';
            else
                $description = '%user:' . $pet->getOwner()->getId() . '.Name\'s% house is getting pretty full.';

            $activity = $this->pickActivity($petWithSkills, true);

            if(!$activity)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::OTHER, null);

                PetActivityLogFactory::createUnreadLog($this->em, $pet, $description . ' %pet:' . $pet->getId() . '.name% wanted to make something, but couldn\'t find any materials to work with.')
                    ->setIcon('icons/activity-logs/house-too-full')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::House_Too_Full ]))
                ;
            }
            else
            {
                $changes = new PetChanges($pet);

                $activityLog = $activity($petWithSkills);

                $activityLog
                    ->setEntry($description . ' ' . $activityLog->getEntry())
                    ->setChanges($changes->compare($pet))
                ;

                PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::CraftedWithAFullHouse, $activityLog);
            }

            return;
        }

        if($this->fatedAdventureService->maybeResolveFate($petWithSkills))
            return;

        if($this->rng->rngNextInt(1, $hasEventPersonality ? 48 : 50) === 1)
        {
            if($this->letterService->adventure($petWithSkills))
                return;

            $this->genericAdventureService->adventure($petWithSkills);
            return;
        }

        if($this->discoverNewFeature($pet))
            return;

        if($this->toolAdventures->maybeDoToolAdventure($petWithSkills))
            return;

        if($this->rng->rngNextInt(1, $hasEventPersonality ? 48 : 50) === 1)
        {
            $activityLog = $this->givingTreeGatheringService->gatherFromGivingTree($pet);
            if($activityLog)
                return;
        }

        if($this->rng->rngNextInt(1, 100) <= ($hasEventPersonality ? 24 : 16) && CalendarFunctions::isSaintPatricksDay($this->clock->now))
        {
            $this->gatheringHolidayAdventureService->adventure($petWithSkills, GatheringHolidayEnum::SaintPatricks);
            return;
        }

        if($this->rng->rngNextInt(1, 100) <= ($hasEventPersonality ? 30 : 25) && CalendarFunctions::isEaster($this->clock->now))
        {
            $this->gatheringHolidayAdventureService->adventure($petWithSkills, GatheringHolidayEnum::Easter);
            return;
        }

        if($this->rng->rngNextInt(1, 100) <= ($hasEventPersonality ? 9 : 6) && CalendarFunctions::isChineseNewYear($this->clock->now))
        {
            $this->gatheringHolidayAdventureService->adventure($petWithSkills, GatheringHolidayEnum::LunarNewYear);
            return;
        }

        $activity = $this->pickActivity($petWithSkills, false)
            ?? $this->doNothing(...);

        $changes = new PetChanges($pet);

        $activityLog = $activity($petWithSkills);

        $activityLog->setChanges($changes->compare($pet));
    }

    private function doNothing(ComputedPetSkills $petWithSkills): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::OTHER, null);

        return PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% hung around the house.');
    }

    /**
     * @return (callable(ComputedPetSkills): PetActivityLog)|null
     */
    private function pickActivity(ComputedPetSkills $petWithSkills, bool $houseIsFull): ?callable
    {
        $groupedPossibilities = [];

        foreach($this->petActivities as $activity)
        {
            if($houseIsFull && !$activity->preferredWithFullHouse())
                continue;

            $desire = $activity->groupDesire($petWithSkills);

            if($desire <= 0)
                continue;

            $possibilities = $activity->possibilities($petWithSkills);

            if(count($possibilities) === 0)
                continue;

            $groupedPossibilities[$activity->groupKey()] = [
                'desire' => $desire,
                'possibilities' => $possibilities,
            ];
        }

        if(count($groupedPossibilities) === 0)
            return null;

        $group = ArrayFunctions::pick_one_weighted($groupedPossibilities, fn($group) => $group['desire']);

        return $this->rng->rngNextFromArray($group['possibilities']);
    }

    private function petCanPoop(Pet $pet): bool
    {
        if($pet->hasMerit(MeritEnum::BLACK_HOLE_TUM) && $this->rng->rngNextInt(1, 180) === 1)
            return true;

        if($pet->getTool() && $this->rng->rngNextInt(1, 180) <= $pet->getTool()->increasesPooping())
            return true;

        return false;
    }

    private function maybeReceiveFairyGodmotherVisit(Pet $pet): bool
    {
        if(!$pet->hasMerit(MeritEnum::FAIRY_GODMOTHER))
            return false;

        if($pet->hasStatusEffect(StatusEffectEnum::BittenByAVampire) && $this->rng->rngNextInt(1, 20) === 1)
        {
            $changes = new PetChanges($pet);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' was thinking about what to do, when their Fairy Godmother showed up! "Let\'s do something about that nasty Vampire bite!" she said, and with a flick of her wand, ' . ActivityHelpers::PetName($pet) . '\'s vampire bite was healed! "Much better! <small>Nasty creatures, those!</small> You take care, now!"')
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ;

            $pet
                ->increaseSafety(12)
                ->increaseLove(12)
                ->increaseEsteem(12)
            ;

            $pet->removeStatusEffect(StatusEffectEnum::BittenByAVampire);

            $this->petExperienceService->spendTime($pet, 15, PetActivityStatEnum::OTHER, null);

            $activityLog->setChanges($changes->compare($pet));

            return true;
        }

        if($this->rng->rngNextInt(1, 650) !== 1)
            return false;

        $randomChat = $this->rng->rngNextFromArray([
            'In the face of darkness, remember that your light shines brightest',
            'Embrace your uniqueness, for it is the key to unlocking your dreams',
            'Believe in yourself, my dear, for magic lies within your heart',
            'The power of imagination will lead you to realms where dreams come true',
            'Let kindness be your wand, and you\'ll create wonders wherever you go',
            'Never underestimate the strength of a kind heart, for it can move mountains',
            'In the garden of life, cultivate gratitude, and watch your blessings bloom',
            'Every day is a new page in the book of your adventures; write it with joy and wonder',
            'In every challenge, there lies a hidden spell of growth and wisdom',
            'The world will try to define you, but remember, you are the only one who can determine your true worth',
            'Change, like the tides, is inevitable and often unpredictable, but it\'s what keeps life\'s oceans alive and vibrant',
            'Your dreams are your soul\'s whispers, guiding you to your true destiny; listen to them attentively',
        ]);

        $randomGoody = $this->rng->rngNextFromArray([
            'Quintessence', 'Berry Cobbler', 'Tile: Mushroom Hunting',
            'Book of Flowers', 'Witch-hazel', 'Blackberry Wine',
            'Piece of Cetgueli\'s Map', 'World\'s Best Sugar Cookie', 'Champignon',
            'Scroll of Fruit', 'Bag of Beans', 'Secret Seashell', 'Trout Yogurt',
            'Sand Dollar', 'Sunflower', 'Sunflower', 'Merigold',
            'Magic Hourglass', 'Brownie', 'Flower Basket', 'Fish Stew',
            'Slice of Pumpkin Pie', 'Mysterious Seed', 'Decorated Flute',
            'Laufabrauð', 'Fisherman\'s Pie', 'Magic Smoke', 'Wings', 'Wings', 'Wings',
            'Coreopsis', 'Harvest Staff', 'Pumpkin Bread', 'White Feathers',
            'Really Big Leaf', 'Caramel-covered Red', 'Largish Bowl of Smallish Pumpkin Soup',
            'Whisper Stone', 'Everybeans', 'Dreamwalker\'s Tea', 'Dreamwalker\'s Tea',
            'Dreamwalker\'s Tea', 'Hat Box', 'Tiny Tea', 'Tremendous Tea',
            'Crystal Ball', 'Moon Dust', 'Magpie Pouch', 'Mericarp',
            'Wolf\'s Bane', 'Wolf\'s Bane', 'Wolf\'s Bane', 'Tawny Ears',
            'Tile: Lovely Haberdashers', 'Treat of Crispy Rice',
        ]);

        $soNice = $this->rng->rngNextFromArray([
            'Gosh dang, she\'s so nice!',
            'How\'d she get so friggin\' sweet!',
            'She\'s just the best!',
        ]);

        $changes = new PetChanges($pet);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' was thinking about what to do, when their Fairy Godmother showed up! They chatted for a while before she delivered these parting words: "' . $randomChat . '"... and a parting gift: ' . $randomGoody . '. (' . $soNice . ')')
            ->addInterestingness(PetActivityLogInterestingness::RareActivity)
        ;

        $pet
            ->increaseSafety(12)
            ->increaseLove(12)
            ->increaseEsteem(12)
        ;

        $this->inventoryService->petCollectsItem($randomGoody, $pet, $pet->getName() . ' received this from their Fairy Godmother!', $activityLog);
        $this->petExperienceService->spendTime($pet, 90, PetActivityStatEnum::OTHER, null);

        $activityLog->setChanges($changes->compare($pet));

        return true;
    }

    private function maybeReceiveAthenasGift(Pet $pet): bool
    {
        if(!$pet->hasMerit(MeritEnum::ATHENAS_GIFTS))
            return false;

        if($this->rng->rngNextInt(1, 300) !== 1)
            return false;

        $randomExclamation = $this->rng->rngNextFromArray([
            'Neat-o!', 'Rad!', 'Dope!', 'Sweet!', 'Hot diggity!', 'Epic!', 'Let\'s go!',
        ]);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, ActivityHelpers::PetName($pet) . ' was thinking about what to do, when they spotted a Handicrafts Supply Box nearby! (Athena\'s Gifts! ' . $randomExclamation . ')')
            ->addInterestingness(PetActivityLogInterestingness::RareActivity)
        ;

        $this->inventoryService->petCollectsItem('Handicrafts Supply Box', $pet, $pet->getName() . ' received this - a gift from the gods!', $activityLog);
        $this->petExperienceService->spendTime($pet, 30, PetActivityStatEnum::OTHER, null);

        return true;
    }

    private function discoverNewFeature(Pet $pet): ?PetActivityLog
    {
        $hasUnlockedMuseum = $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Museum);
        $hasUnlockedBookstore = $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Bookstore);
        $hasUnlockedMarket = $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Market);
        $hasUnlockedZoologist = $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Zoologist);

        if($hasUnlockedMuseum && $hasUnlockedBookstore && $hasUnlockedMarket && $hasUnlockedZoologist)
            return null;

        $progress = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Feature Discovery Counter', 0);

        if($progress->getValue() < 40)
        {
            $progress->setValue($progress->getIntValue() + $this->rng->rngNextInt(1, 4));
            return null;
        }

        $progress->setValue(0);

        if(!$pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Museum))
            return $this->genericAdventureService->discoverFeature($pet, UnlockableFeatureEnum::Museum, 'Museum');

        if(!$pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Market))
            return $this->genericAdventureService->discoverFeature($pet, UnlockableFeatureEnum::Market, 'Market');

        if(!$pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Bookstore))
            return $this->genericAdventureService->discoverFeature($pet, UnlockableFeatureEnum::Bookstore, 'Bookstore');

        if(!$pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Zoologist))
            return $this->genericAdventureService->discoverFeature($pet, UnlockableFeatureEnum::Zoologist, 'Zoologist');

        return null;
    }

    public function speakToBunnySpirit(Pet $pet): PetActivityLog
    {
        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'A rabbit spirit visited %pet:' . $pet->getId() . '.name%, and the two talked for a while, about this world, and the other...')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'The Umbra' ]))
        ;
        $this->petExperienceService->gainExp($pet, 10, [ PetSkillEnum::Arcana, PetSkillEnum::Nature ], $activityLog);
        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

        return $activityLog;
    }

    private function updatePetNeeds(Pet $pet): void
    {
        if($pet->getTool() && $pet->getTool()->canBeNibbled() && $this->rng->rngNextInt(1, 10) === 1)
        {
            $changes = new PetChangesSummary();
            $changes->food = '+';

            PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% nibbled on their ' . InventoryModifierFunctions::getNameWithModifiers($pet->getTool()) . '.')
                ->setIcon('icons/activity-logs/just-the-fork')
                ->setChanges($changes)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Eating' ]))
            ;
        }
        else
            $pet->increaseFood(-1);

        if($pet->getJunk() > 0)
            $pet->increaseJunk(-1);

        if($pet->getPoison() > 0 && $pet->getAlcohol() === 0 && $pet->getCaffeine() === 0 && $pet->getPsychedelic() === 0)
            $pet->increasePoison(-1);

        if($pet->getAlcohol() > 0)
        {
            $pet->increaseAlcohol(-1);

            if($pet->hasMerit(MeritEnum::IRON_STOMACH))
            {
                if($this->rng->rngNextInt(1, 2) === 1)
                    $pet->increasePoison(1);
            }
            else
                $pet->increasePoison(1);
        }

        if($pet->getCaffeine() > 0)
        {
            $pet->increaseCaffeine(-1);

            if($pet->hasMerit(MeritEnum::IRON_STOMACH))
            {
                if($this->rng->rngNextInt(1, 4) === 1)
                    $pet->increasePoison(1);
            }
            else
            {
                if($this->rng->rngNextInt(1, 2) === 1)
                    $pet->increasePoison(1);
            }
        }

        if($pet->getPsychedelic() > 0)
        {
            $pet->increasePsychedelic(-1);

            if($pet->hasMerit(MeritEnum::IRON_STOMACH))
                $pet->increasePoison(1);
            else
                $pet->increasePoison(2);
        }

        $safetyRestingPoint = $pet->hasMerit(MeritEnum::NOTHING_TO_FEAR) ? 8 : 0;

        if($pet->getSafety() > $safetyRestingPoint && $this->rng->rngNextInt(1, 2) === 1)
            $pet->increaseSafety(-1);
        else if($pet->getSafety() < $safetyRestingPoint)
            $pet->increaseSafety(1);

        $loveRestingPoint = $pet->hasMerit(MeritEnum::EVERLASTING_LOVE) ? 8 : 0;

        if($pet->getLove() > $loveRestingPoint && $this->rng->rngNextInt(1, 2) === 1)
            $pet->increaseLove(-1);
        else if($pet->getLove() < $loveRestingPoint && $this->rng->rngNextInt(1, 2) === 1)
            $pet->increaseLove(1);

        $esteemRestingPoint = $pet->hasMerit(MeritEnum::NEVER_EMBARRASSED) ? 8 : 0;

        if($pet->getEsteem() > $esteemRestingPoint)
            $pet->increaseEsteem(-1);
        else if($pet->getEsteem() < $esteemRestingPoint && $this->rng->rngNextInt(1, 2) === 1)
            $pet->increaseEsteem(1);
    }

    private function doHyperchromaticTweak(Pet $pet): void
    {
        if($this->rng->rngNextInt(1, 250) === 1)
        {
            $pet
                ->setColorA(ColorFunctions::RGB2Hex($this->rng->rngNextInt(0, 255), $this->rng->rngNextInt(0, 255), $this->rng->rngNextInt(0, 255)))
                ->setColorB(ColorFunctions::RGB2Hex($this->rng->rngNextInt(0, 255), $this->rng->rngNextInt(0, 255), $this->rng->rngNextInt(0, 255)))
            ;
        }
        else
        {
            $pet
                ->setColorA($this->rng->rngNextTweakedColor($pet->getColorA(), 4))
                ->setColorB($this->rng->rngNextTweakedColor($pet->getColorB(), 4))
            ;
        }
    }

    private function processPoison(Pet $pet): bool
    {
        if($pet->getPoison() <= 0)
            return false;

        if($this->rng->rngNextInt(6, 24) >= $pet->getPoison())
            return false;

        $changes = new PetChanges($pet);

        $safetyVom = (int)ceil($pet->getPoison() / 4);

        $pet->increasePoison(-$this->rng->rngNextInt((int)ceil($pet->getPoison() / 4), (int)ceil($pet->getPoison() * 3 / 4)));
        if($pet->getAlcohol() > 0) $pet->increaseAlcohol(-$this->rng->rngNextInt(1, (int)ceil($pet->getAlcohol() / 2)));
        if($pet->getPsychedelic() > 0) $pet->increasePsychedelic(-$this->rng->rngNextInt(1, (int)ceil($pet->getPsychedelic() / 2)));
        if($pet->getCaffeine() > 0) $pet->increaseFood(-$this->rng->rngNextInt(1, (int)ceil($pet->getCaffeine() / 2)));
        if($pet->getJunk() > 0) $pet->increaseJunk(-$this->rng->rngNextInt(1, (int)ceil($pet->getJunk() / 2)));
        if($pet->getFood() > 0) $pet->increaseFood(-$this->rng->rngNextInt(1, (int)ceil($pet->getFood() / 2)));

        $pet->increaseSafety(-$this->rng->rngNextInt(1, $safetyVom));
        $pet->increaseEsteem(-$this->rng->rngNextInt(1, $safetyVom));

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(15, 30), PetActivityStatEnum::OTHER, null);

        $log = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% threw up :(')
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Sick ]))
            ->setChanges($changes->compare($pet));

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::PoopedShedVommedOrBathed, $log);

        return true;
    }

    private function maybeEatOutOfLunchbox(Pet $pet): void
    {
        $hunger = $this->rng->rngNextInt(0, 4);

        if($pet->getFood() + $pet->getJunk() >= $hunger || count($pet->getLunchboxItems()) <= 0)
            return;

        $petChanges = new PetChanges($pet);

        /** @var LunchboxItem[] $sortedLunchboxItems */
        $sortedLunchboxItems = $pet->getLunchboxItems()->filter(function(LunchboxItem $i) {
            return $i->getInventoryItem()->getItem()->getFood() !== null;
        })->toArray();

        // sorted from most-delicious to least-delicious
        usort($sortedLunchboxItems, function(LunchboxItem $a, LunchboxItem $b) use($pet) {
            $aFood = new FoodWithSpice($a->getInventoryItem()->getItem(), $a->getInventoryItem()->getSpice());
            $bFood = new FoodWithSpice($b->getInventoryItem()->getItem(), $b->getInventoryItem()->getSpice());

            $aValue = EatingService::getFavoriteFlavorStrength($pet, $aFood) + $aFood->love;
            $bValue = EatingService::getFavoriteFlavorStrength($pet, $bFood) + $bFood->love;

            if($aValue === $bValue)
                return $bFood->food <=> $aFood->food;
            else
                return $bValue <=> $aValue;
        });

        $namesOfItemsEaten = [];
        $namesOfItemsSkipped = [];
        $itemsLeftInLunchbox = count($sortedLunchboxItems);

        while($pet->getFood() < $hunger && count($sortedLunchboxItems) > 0)
        {
            $itemToEat = array_shift($sortedLunchboxItems);

            $food = new FoodWithSpice($itemToEat->getInventoryItem()->getItem(), $itemToEat->getInventoryItem()->getSpice());

            $ateIt = $this->eatingService->doEat($pet, $food, null);

            if($ateIt)
            {
                $namesOfItemsEaten[] = $food->name;

                $pet->removeLunchboxItem($itemToEat);

                $this->em->remove($itemToEat);
                $this->em->remove($itemToEat->getInventoryItem());

                $itemsLeftInLunchbox--;
            }
            else
                $namesOfItemsSkipped[] = $food->name;
        }

        if(count($namesOfItemsEaten) > 0)
        {
            $this->responseService->setReloadInventory();

            $message = '%pet:' . $pet->getId() . '.name% ate ' . ArrayFunctions::list_nice($namesOfItemsEaten) . ' out of their lunchbox.';

            if(count($namesOfItemsSkipped) > 0)
                $message .= ' (' . ArrayFunctions::list_nice($namesOfItemsSkipped) . ' really isn\'t appealing right now, though.)';
        }
        else
        {
            // none were eaten, but we know the lunchbox has items in it, therefore items were skipped!
            $message = '%pet:' . $pet->getId() . '.name% looked in their lunchbox for something to eat, but ' . ArrayFunctions::list_nice($namesOfItemsSkipped) . ' really isn\'t appealing right now.';
        }

        if($itemsLeftInLunchbox === 0)
            $message .= ' Their lunchbox is now empty!';

        $lunchboxLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
            ->setIcon('icons/activity-logs/lunchbox')
            ->setChanges($petChanges->compare($pet))
            ->addInterestingness($itemsLeftInLunchbox === 0 ? PetActivityLogInterestingness::LunchboxEmpty : 1)
        ;

        PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::EmptiedTheirLunchbox, $lunchboxLog);
    }

    private function maybeDoWereformTransformation(Pet $pet): void
    {
        if($pet->hasStatusEffect(StatusEffectEnum::Wereform))
        {
            if($this->rng->rngNextInt(1, 10) === 1)
                $pet->removeStatusEffect(StatusEffectEnum::Wereform);
        }
        else
        {
            if(
                $pet->hasStatusEffect(StatusEffectEnum::BittenByAWerecreature) &&
                $this->rng->rngNextInt(1, max(20, 50 + $pet->getFood() + $pet->getSafety() * 2 + $pet->getLove() + $pet->getEsteem())) === 1
            )
            {
                StatusEffectHelpers::applyStatusEffect($this->em, $pet, StatusEffectEnum::Wereform, 1);
            }
        }
    }
}
