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

namespace App\Service\PetActivity;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Enum\EnumInvalidValueException;
use App\Enum\FlavorEnum;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPInvalidOperationException;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\GrammarFunctions;
use App\Functions\ItemRepository;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\StatusEffectHelpers;
use App\Model\FoodWithSpice;
use App\Model\FortuneCookie;
use App\Model\PetChanges;
use App\Service\CravingService;
use App\Service\PetActivity\FeedResult;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use App\Service\ResponseService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;

class EatingService
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly CravingService $cravingService,
        private readonly InventoryService $inventoryService,
        private readonly ResponseService $responseService,
        private readonly EntityManagerInterface $em,
        private readonly PetExperienceService $petExperienceService,
        private readonly UserStatsService $userStatsRepository
    )
    {
    }

    /**
     * @throws EnumInvalidValueException
     * @return bool
     */
    public function doEat(Pet $pet, FoodWithSpice $food, ?PetActivityLog $activityLog): bool
    {
        // pets will not eat if their stomach is already full
        if($pet->getJunk() + $pet->getFood() >= $pet->getStomachSize())
            return false;

        if($pet->wantsSobriety() && ($food->alcohol || $food->caffeine > 0 || $food->psychedelic > 0))
            return false;

        $this->applyFoodEffects($pet, $food);

        // consider favorite flavor:
        $randomFlavor = $food->randomFlavor > 0 ? $this->rng->rngNextFromArray(FlavorEnum::cases()) : null;

        $esteemGain = self::getFavoriteFlavorStrength($pet, $food, $randomFlavor) + $food->love;

        $pet->increaseEsteem($esteemGain);

        if($activityLog)
        {
            if($randomFlavor)
                $activityLog->appendEntry(ActivityHelpers::PetName($pet) . ' immediately ate the ' . $food->name . '. (Ooh! ' . ucwords($randomFlavor->value) . '!');
            else
                $activityLog->appendEntry(ActivityHelpers::PetName($pet) . ' immediately ate the ' . $food->name . '.');

            $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Eating' ]));
        }

        return true;
    }

    public static function getFavoriteFlavorStrength(Pet $pet, FoodWithSpice $food, ?FlavorEnum $randomFlavor = null): int
    {
        if($pet->hasMerit(MeritEnum::AFFECTIONLESS))
            return 0;

        $favoriteFlavorStrength = $food->{$pet->getFavoriteFlavor()->value};

        if($randomFlavor === $pet->getFavoriteFlavor())
            $favoriteFlavorStrength += $food->randomFlavor;

        if($pet->hasMerit(MeritEnum::LOLLIGOVORE))
            $favoriteFlavorStrength += $food->containsTentacles;

        if($pet->hasStatusEffect(StatusEffectEnum::BittenByAVampire)
            && $food->baseItem->getItemGroups()->exists(fn($key, ItemGroup $group) => $group->getName() === 'Bloody'))
        {
            // Cancel out any acquired taste from bloody foods
            if($food->love < 0)
                $favoriteFlavorStrength += abs($food->love);

            // And add a bonus
            $favoriteFlavorStrength += 2;
        }

        return $favoriteFlavorStrength;
    }

    public function applyFoodEffects(Pet $pet, FoodWithSpice $food): void
    {
        $pet->increaseAlcohol($food->alcohol);

        $caffeine = $food->caffeine;

        if($caffeine > 0)
        {
            $pet->increaseCaffeine($caffeine);
            StatusEffectHelpers::applyStatusEffect($this->em, $pet, StatusEffectEnum::Caffeinated, $caffeine * 60);
        }
        else if($caffeine < 0)
            $pet->increaseCaffeine($caffeine);

        $pet->increasePsychedelic($food->psychedelic);
        $pet->increaseFood($food->food);

        if($food->junk > 0)
            $pet->increaseJunk($food->junk);
        else if($food->junk < 0)
            $pet->increasePoison($food->junk);

        foreach($food->grantedStatusEffects as $statusEffect)
        {
            StatusEffectHelpers::applyStatusEffect($this->em, $pet, $statusEffect['effect'], $statusEffect['duration']);
        }

        if($food->grantsSelfReflection)
            $pet->increaseSelfReflectionPoint(1);

        if(CravingService::foodMeetsCraving($pet, $food->baseItem))
        {
            $this->cravingService->satisfyCraving($pet, $food->baseItem);
        }

        if($food->leftovers)
        {
            $leftoverNames = array_map(fn(Item $item) => $item->getNameWithArticle(), $food->leftovers);

            $wasOrWere = count($food->leftovers) === 1 ? 'was' : 'were';

            $changes = new PetChanges($pet);

            $activityLog = PetActivityLogFactory::createUnreadLog(
                $this->em,
                $pet,
                'After ' . $pet->getName() . ' ate the ' . $food->name . ', ' . ArrayFunctions::list_nice($leftoverNames) . ' ' . $wasOrWere . ' left over.'
            );

            foreach($food->leftovers as $leftoverItem)
                $this->inventoryService->petCollectsItem($leftoverItem, $pet, $pet->getName() . ' ate ' . GrammarFunctions::indefiniteArticle($food->name) . ' ' . $food->name . '; this was left over.', $activityLog);

            $activityLog
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [
                    PetActivityLogTagEnum::Eating,
                    PetActivityLogTagEnum::Leftovers,
                ]))
                ->setChanges($changes->compare($pet));
        }

        $bonusItems = [];

        foreach($food->bonusItems as $bonusItem)
        {
            if($this->rng->rngNextInt(1, 1000) <= $bonusItem->chance)
                $bonusItems[] = InventoryService::getRandomItemFromItemGroup($this->rng, $bonusItem->itemGroup);
        }

        if(count($bonusItems) > 0)
        {
            $exclamations = [ 'Convenient!', 'How serendipitous!', 'What are the odds!' ];

            $bonusItemNamesWithArticles = array_map(fn(Item $item) => $item->getNameWithArticle(), $bonusItems);

            if(count($bonusItems) === 1)
                $exclamations[] = 'Where\'d that come from??';
            else
                $exclamations[] = 'Where\'d those come from??';

            $naniNani = $this->rng->rngNextFromArray($exclamations);

            $activityLogText = 'While eating the ' . $food->name . ', ' . $pet->getName() . ' spotted ' . ArrayFunctions::list_nice($bonusItemNamesWithArticles) . '! (' . $naniNani . ')';

            $changes = new PetChanges($pet);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $activityLogText);

            foreach($bonusItems as $item)
            {
                $comment =
                    'While eating ' . $food->name . ', ' . $pet->getName() . ' happened to spot this! ' .
                    $this->rng->rngNextFromArray([
                        '', '... Sure!', '... Why not?', 'As you do!', 'A happy coincidence!', 'Weird!',
                        'Inexplicable, but not unwelcome!', '(Where was it up until this point, I wonder??)',
                        'These things happen. Apparently.', '👍', 'Wild!', 'How\'s _that_ work?',
                    ])
                ;

                $this->inventoryService->petCollectsItem($item, $pet, $comment, $activityLog);
            }

            $activityLog
                ->setChanges($changes->compare($pet))
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::Lucky_Food ]))
            ;
        }

        if($pet->hasMerit(MeritEnum::BURPS_MOTHS) && $this->rng->rngNextInt(1, 200) < $food->food + $food->junk)
        {
            $inventory = new Inventory(owner: $pet->getOwner(), item: ItemRepository::findOneByName($this->em, 'Moth'))
                ->setLocation(LocationEnum::Home)
                ->setCreatedBy($pet->getOwner())
                ->addComment('After eating ' . $food->name . ', ' . $pet->getName() . ' burped this up!')
            ;
            $this->em->persist($inventory);

            $this->responseService->addFlashMessage('After eating ' . $food->name . ', ' . $pet->getName() . ' burped up a Moth!');
        }

        foreach($food->grantedSkills as $skill)
        {
            if($pet->getSkills()->getStat($skill) < 1)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'Such skillful food!');
                $this->petExperienceService->forceIncreaseSkill($pet, $skill, 1, $activityLog);
            }
        }
    }

    /**
     * @param list<Inventory> $inventory
     */
    public function doFeed(User $feeder, Pet $pet, array $inventory): FeedResult
    {
        if(!$pet->isAtHome())
            throw new PSPInvalidOperationException('Pets that aren\'t home cannot be interacted with.');

        if(array_any($inventory, fn(Inventory $i) => $i->getItem()->getFood() === null))
            throw new PSPInvalidOperationException('One or more of the selected items is not edible! (Yuck!)');

        $this->rng->rngNextShuffle($inventory);

        $isThirsty = $pet->hasStatusEffect(StatusEffectEnum::Thirsty);
        $isJaune = $pet->hasStatusEffect(StatusEffectEnum::Jaune);
        $gotAColdDrink = null;
        $gotButter = null;

        $petChanges = new PetChanges($pet);
        $foodsEaten = [];
        /** @var FoodWithSpice[] $favorites */
        $favorites = [];
        $tooPoisonous = [];
        $ateAFortuneCookie = false;
        $ateFavFood = false;

        foreach($inventory as $i)
        {
            $food = new FoodWithSpice($i->getItem(), $i->getSpice());

            $itemName = $food->name;

            if($pet->getJunk() + $pet->getFood() >= $pet->getStomachSize())
                continue;

            if($pet->wantsSobriety() && ($food->alcohol > 0 || $food->caffeine > 0 || $food->psychedelic > 0))
            {
                $tooPoisonous[] = $itemName;
                continue;
            }

            $this->applyFoodEffects($pet, $food);

            // consider favorite flavor:
            $randomFlavor = $food->randomFlavor > 0 ? $this->rng->rngNextFromArray(FlavorEnum::cases()) : null;

            $favoriteFlavorStrength = self::getFavoriteFlavorStrength($pet, $food, $randomFlavor);

            $loveAndEsteemGain = $favoriteFlavorStrength + $food->love;

            if($isThirsty && !$gotAColdDrink && $i->getItem()->getItemGroups()->exists(fn($key, ItemGroup $ig) => $ig->getName() === 'Cold Drink'))
                $gotAColdDrink = $i->getItem();

            if($isJaune && !$gotButter && str_contains(strtolower($i->getItem()->getName()), 'butter'))
                $gotButter = $i->getItem();

            $pet
                ->increaseLove($loveAndEsteemGain)
                ->increaseEsteem($loveAndEsteemGain)
            ;

            if($favoriteFlavorStrength > 0)
            {
                $this->petExperienceService->gainAffection($pet, $favoriteFlavorStrength);

                $favorites[] = $food;
            }

            $this->em->remove($i);

            if($randomFlavor)
                $foodsEaten[] = $itemName . ' (ooh! ' . $randomFlavor->value . '!)';
            else
                $foodsEaten[] = $itemName;

            if($itemName === 'Fortune Cookie')
                $ateAFortuneCookie = true;
        }

        // gain safety & affection equal to 1/8 food gained, when hand-fed
        $foodGained = $pet->getFood() - $petChanges->food;

        if($foodGained > 0)
        {
            $remainder = $foodGained % 8;
            $gain = $foodGained >> 3; // ">> 3" === "/ 8"

            if($remainder > 0 && $this->rng->rngNextInt(1, 8) <= $remainder)
                $gain++;

            $pet->increaseSafety($gain);
            $this->petExperienceService->gainAffection($pet, $gain);

            if($pet->getPregnancy())
                $pet->getPregnancy()->increaseAffection($gain);

            $this->userStatsRepository->incrementStat($feeder, UserStat::FoodHoursFedToPets, $foodGained);

            $this->cravingService->maybeAddCraving($pet);
        }

        if(count($foodsEaten) > 0)
        {
            $message = '%user:' . $feeder->getId() . '.Name% fed ' . $pet->getName() . ' ' . ArrayFunctions::list_nice($foodsEaten) . '.';
            $icon = 'icons/activity-logs/mangia';

            if(count($favorites) > 0)
            {
                $icon = 'ui/affection';
                $message .= ' ' . $pet->getName() . ' really liked the ' . $this->rng->rngNextFromArray($favorites)->name . '!';
                $ateFavFood = true;
            }

            if($isThirsty && $gotAColdDrink)
            {
                $statusEffect = $this->satisfiedCravingStatusEffect($pet, StatusEffectEnum::Thirsty);
                $message .= ' The ' . $gotAColdDrink->getName() . ' satisfied their Thirst! They\'re feeling ' . $statusEffect->value . '!';
            }

            if($isJaune && $gotButter)
            {
                $statusEffect = $this->satisfiedCravingStatusEffect($pet, StatusEffectEnum::Jaune);
                $message .= ' The ' . $gotButter->getName() . ' satisfied their desire to eat Butter! They\'re feeling ' . $statusEffect->value . '!';
            }

            if($ateAFortuneCookie)
            {
                $message .= ' "' . $this->rng->rngNextFromArray(FortuneCookie::Fortunes) . '"';
                if($this->rng->rngNextInt(1, 20) === 1 && $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Greenhouse))
                {
                    $message .= ' ... in bed!';

                    if($pet->hasMerit(MeritEnum::AFFECTIONLESS))
                        $message .= ' (' . $pet->getName() . ' seems completely unamused by this joke.)';
                    else if($this->rng->rngNextInt(1, 5) === 1)
                        $message .= ' XD';
                }
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, $message)
                ->setIcon($icon)
                ->setChanges($petChanges->compare($pet))
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Eating' ]))
            ;

            return new FeedResult($activityLog, $ateFavFood);
        }
        else
        {
            if(count($tooPoisonous) > 0)
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%user:' . $pet->getOwner()->getId() . '.Name% tried to feed %pet:' . $pet->getId() . '.name%, but ' . $this->rng->rngNextFromArray($tooPoisonous) . ' really isn\'t appealing right now.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Eating' ]));

                return new FeedResult($activityLog, false);
            }
            else
            {
                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, '%user:' . $pet->getOwner()->getId() . '.Name% tried to feed %pet:' . $pet->getId() . '.name%, but they\'re too full to eat anymore.')
                    ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Eating' ]));

                return new FeedResult($activityLog, false);
            }
        }
    }

    private function satisfiedCravingStatusEffect(Pet $pet, StatusEffectEnum $cravingStatusEffect): StatusEffectEnum
    {
        $pet->removeStatusEffect($cravingStatusEffect);

        $this->petExperienceService->gainAffection($pet, 2);

        $statusEffect = $this->rng->rngNextFromArray([
            StatusEffectEnum::Inspired,
            StatusEffectEnum::Oneiric,
            StatusEffectEnum::Vivacious,
        ]);

        StatusEffectHelpers::applyStatusEffect($this->em, $pet, $statusEffect, 8 * 60);

        return $statusEffect;
    }
}