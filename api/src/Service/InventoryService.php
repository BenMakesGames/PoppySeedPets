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

use App\Entity\Enchantment;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Entity\Spice;
use App\Entity\User;
use App\Enum\EnumInvalidValueException;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\MoonNameEnum;
use App\Exceptions\PSPNotFoundException;
use App\Functions\ArrayFunctions;
use App\Functions\DateFunctions;
use App\Functions\ItemRepository;
use App\Functions\SpiceRepository;
use App\Functions\StatusEffectHelpers;
use App\Model\FoodWithSpice;
use App\Model\ItemQuantity;
use App\Service\PetActivity\EatingService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class InventoryService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResponseService $responseService,
        private readonly IRandom $rng,
        private readonly EatingService $eatingService,
        private readonly HouseSimService $houseSimService,
        private readonly Clock $clock
    )
    {
    }

    /**
     * @throws EnumInvalidValueException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public static function countInventory(EntityManagerInterface $em, int $userId, int $itemId, int $location): int
    {
        if(!LocationEnum::isAValue($location))
            throw new EnumInvalidValueException(LocationEnum::class, $location);

        return (int)$em->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(Inventory::class, 'i')
            ->andWhere('i.owner=:owner')
            ->andWhere('i.item=:item')
            ->andWhere('i.location=:location')
            ->setParameter('owner', $userId)
            ->setParameter('item', $itemId)
            ->setParameter('location', $location)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @throws EnumInvalidValueException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public static function countTotalInventory(EntityManagerInterface $em, User $user, int $location): int
    {
        if(!LocationEnum::isAValue($location))
            throw new EnumInvalidValueException(LocationEnum::class, $location);

        return (int)$em->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(Inventory::class, 'i')
            ->andWhere('i.owner=:owner')
            ->andWhere('i.location=:location')
            ->setParameter('owner', $user->getId())
            ->setParameter('location', $location)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param ItemQuantity[] $requirements
     * @param ItemQuantity[] $inventory
     */
    public static function hasRequiredItems(array $requirements, array $inventory): bool
    {
        foreach($requirements as $requirement)
        {
            if(!array_key_exists($requirement->item->getName(), $inventory) || $inventory[$requirement->item->getName()]->quantity < $requirement->quantity)
                return false;
        }

        return true;
    }

    /**
     * @return ItemQuantity[]
     * @throws PSPNotFoundException
     */
    public static function deserializeItemList(EntityManagerInterface $em, string $list): array
    {
        if($list === '') return [];

        $quantities = [];

        $items = \explode(',', $list);
        foreach($items as $item)
        {
            [$itemId, $quantity] = \explode(':', $item);

            $quantities[] = new ItemQuantity(
                ItemRepository::findOneById($em, (int)$itemId),
                (int)$quantity
            );
        }

        return $quantities;
    }

    /**
     * @param ItemQuantity[] $quantities
     */
    public static function serializeItemList(array $quantities): string
    {
        if(count($quantities) === 0) return '';

        usort($quantities, fn(ItemQuantity $a, ItemQuantity $b) => $a->item->getId() <=> $b->item->getId());

        $items = [];

        foreach($quantities as $itemQuantity)
            $items[] = $itemQuantity->item->getId() . ':' . $itemQuantity->quantity;

        return \implode(',', $items);
    }

    /**
     * @param ItemQuantity|ItemQuantity[] $quantities
     * @return list<Inventory>
     * @throws EnumInvalidValueException|PSPNotFoundException
     */
    public function giveInventoryQuantities(ItemQuantity|array $quantities, User $owner, User $creator, string $comment, int $location, bool $lockedToOwner = false): array
    {
        if(!is_array($quantities)) $quantities = [ $quantities ];

        $inventory = [];

        foreach($quantities as $itemQuantity)
        {
            for($q = 0; $q < $itemQuantity->quantity; $q++)
                $inventory[] = $this->receiveItem($itemQuantity->item, $owner, $creator, $comment, $location, $lockedToOwner);
        }

        $this->responseService->setReloadInventory();

        return $inventory;
    }

    /**
     * @throws PSPNotFoundException
     * @throws EnumInvalidValueException
     */
    public function petCollectsEnhancedItem(string|Item $item, ?Enchantment $bonus, ?Spice $spice, Pet $pet, string $comment, PetActivityLog $activityLog): ?Inventory
    {
        $item = $this->getItemWithChanceForLuckyTransformation($item);

        if($pet->hasStatusEffect(StatusEffectEnum::HotToTheTouch))
            $spice = (!$spice || $this->rng->rngNextInt(1, 4) == 4) ? SpiceRepository::findOneByName($this->em, 'Spicy') : $spice;

        if(!$spice && $item->getName() === 'Fish' && $pet->hasMerit(MeritEnum::ICHTHYASTRA) && $this->rng->rngNextInt(1, 3) === 1)
        {
            $spice = SpiceRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
                'Cosmic',
                'Feseekh',
                'Lunar',
                'Nectarous',
                'of Deathly Heat',
                'Starry',
                'with Flavors Unknown',
            ]));
        }

        $cancelGather = false;
        $replacementItemNames = [];
        $extraItemSpice = null;

        if($pet->hasMerit(MeritEnum::RUMPELSTILTSKINS_CURSE))
        {
            if($item->getName() === 'Gold Bar' || $item->getName() === 'Gold Ore')
            {
                if(DateFunctions::isCornMoon($this->clock->now))
                {
                    $activityLog
                        ->appendEntry('The ' . $item->getName() . ' was transformed into... Corn??? (That\'s not how the curse is supposed to work!)')
                        ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ;

                    $item = ItemRepository::findOneByName($this->em, 'Corn');
                }
                else
                {
                    $activityLog
                        ->appendEntry('The ' . $item->getName() . ' was transformed into Wheat by their curse!')
                        ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                    ;

                    $item = ItemRepository::findOneByName($this->em, 'Wheat');
                }
            }
            else if($item->getName() === 'Wheat' || $item->getName() === 'Wheat Flower')
            {
                $activityLog
                    ->appendEntry('The ' . $item->getName() . ' was transformed into a Gold Bar by their curse!')
                    ->addInterestingness(PetActivityLogInterestingness::UncommonActivity)
                ;

                $item = ItemRepository::findOneByName($this->em, 'Gold Bar');
            }
        }

        $petTool = $pet->getTool();

        if($petTool)
        {
            if($petTool->getSpice())
                $extraItemSpice = (!$spice || $this->rng->rngNextBool()) ? $petTool->getSpice() : $spice;
            else
                $extraItemSpice = $spice;

            if($petTool->getItem()->getTool())
            {
                $toolTool = $petTool->getItem()->getTool();

                // bonus gather from equipment
                if($toolTool->getWhenGather() && $item->getName() === $toolTool->getWhenGather()->getName())
                {
                    if($toolTool->getWhenGatherApplyStatusEffect() && $toolTool->getWhenGatherApplyStatusEffectDuration())
                        StatusEffectHelpers::applyStatusEffect($this->em, $pet, $toolTool->getWhenGatherApplyStatusEffect(), $toolTool->getWhenGatherApplyStatusEffectDuration());

                    if($toolTool->getWhenGatherPreventGather())
                        $cancelGather = true;

                    if($toolTool->getWhenGatherAlsoGather())
                    {
                        $extraItemItem = $this->getItemWithChanceForLuckyTransformation($toolTool->getWhenGatherAlsoGather());

                        $extraItem = new Inventory(owner: $pet->getOwner(), item: $extraItemItem)
                            ->setCreatedBy($pet->getOwner())
                            ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' with their ' . $petTool->getItem()->getName() . '.')
                            ->setLocation(LocationEnum::Home)
                            ->setSpice($extraItemSpice)
                            ->setEnchantment($bonus)
                        ;

                        $this->applySeasonalSpiceToNewItem($extraItem);

                        if(!$this->houseSimService->getState()->addInventory($extraItem))
                            $this->em->persist($extraItem);

                        $this->responseService->setReloadInventory();

                        if($toolTool->getWhenGatherPreventGather())
                            $replacementItemNames[] = $extraItem->getItem()->getNameWithArticle();
                    }
                }
                else if($toolTool->getAttractsBugs() && $item->getIsBug())
                {
                    $extraItem = new Inventory(owner: $pet->getOwner(), item: $item)
                        ->setCreatedBy($pet->getOwner())
                        ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' with their ' . $petTool->getItem()->getName() . '.')
                        ->setLocation(LocationEnum::Home)
                        ->setSpice($extraItemSpice)
                        ->setEnchantment($bonus)
                    ;

                    $this->applySeasonalSpiceToNewItem($extraItem);

                    if(!$this->houseSimService->getState()->addInventory($extraItem))
                        $this->em->persist($extraItem);

                    $this->responseService->setReloadInventory();
                }
            }

            $enchantment = $petTool->getEnchantment();

            // bonus gather from equipment enchantment effects
            if($enchantment)
            {
                $bonusEffects = $enchantment->getEffects();

                if($bonusEffects->getWhenGather() && $item->getName() === $bonusEffects->getWhenGather()->getName())
                {
                    if($bonusEffects->getWhenGatherApplyStatusEffect() && $bonusEffects->getWhenGatherApplyStatusEffectDuration())
                        StatusEffectHelpers::applyStatusEffect($this->em, $pet, $bonusEffects->getWhenGatherApplyStatusEffect(), $bonusEffects->getWhenGatherApplyStatusEffectDuration());

                    if($bonusEffects->getWhenGatherPreventGather())
                        $cancelGather = true;

                    if($bonusEffects->getWhenGatherAlsoGather())
                    {
                        $extraItemItem = $this->getItemWithChanceForLuckyTransformation(
                            $bonusEffects->getWhenGatherAlsoGather()
                        );

                        $extraItem = new Inventory(owner: $pet->getOwner(), item: $extraItemItem)
                            ->setCreatedBy($pet->getOwner())
                            ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' with their ' . $petTool->getItem()->getName() . '.')
                            ->setLocation(LocationEnum::Home)
                            ->setSpice($extraItemSpice)
                            ->setEnchantment($bonus)
                        ;

                        $this->applySeasonalSpiceToNewItem($extraItem);

                        if(!$this->houseSimService->getState()->addInventory($extraItem))
                            $this->em->persist($extraItem);

                        $this->responseService->setReloadInventory();

                        if($bonusEffects->getWhenGatherPreventGather())
                            $replacementItemNames[] = $extraItem->getItem()->getNameWithArticle();
                    }
                }
                else if($bonusEffects->getAttractsBugs() && $item->getIsBug())
                {
                    $extraItem = new Inventory(owner: $pet->getOwner(), item: $item)
                        ->setCreatedBy($pet->getOwner())
                        ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' with their ' . $pet->getTool()->getItem()->getName() . '.')
                        ->setLocation(LocationEnum::Home)
                        ->setSpice($extraItemSpice)
                        ->setEnchantment($bonus)
                    ;

                    $this->applySeasonalSpiceToNewItem($extraItem);

                    if(!$this->houseSimService->getState()->addInventory($extraItem))
                        $this->em->persist($extraItem);

                    $this->responseService->setReloadInventory();
                }
            }
        }

        if($pet->hasMerit(MeritEnum::CELESTIAL_CHORUSER) && $item->hasItemGroup('Outer Space'))
        {
            $musicNote = ItemRepository::findOneByName($this->em, 'Music Note');

            $extraItem = new Inventory(owner: $pet->getOwner(), item: $musicNote)
                ->setCreatedBy($pet->getOwner())
                ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' as a Celestial Choruser.')
                ->setLocation(LocationEnum::Home)
                ->setSpice($extraItemSpice)
                ->setEnchantment($bonus)
            ;

            $activityLog->addInterestingness(PetActivityLogInterestingness::ActivityUsingMerit);

            $this->applySeasonalSpiceToNewItem($extraItem);

            if(!$this->houseSimService->getState()->addInventory($extraItem))
                $this->em->persist($extraItem);

            $this->responseService->setReloadInventory();
        }

        if($pet->hasStatusEffect(StatusEffectEnum::FruitClobbering) && $item->hasItemGroup('Fresh Fruit'))
        {
            $pectin = ItemRepository::findOneByName($this->em, 'Pectin');

            $extraItem = new Inventory(owner: $pet->getOwner(), item: $pectin)
                ->setCreatedBy($pet->getOwner())
                ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' while ' . StatusEffectEnum::FruitClobbering->value . '.')
                ->setLocation(LocationEnum::Home)
                ->setSpice($extraItemSpice)
                ->setEnchantment($bonus)
            ;

            $this->applySeasonalSpiceToNewItem($extraItem);

            if(!$this->houseSimService->getState()->addInventory($extraItem))
                $this->em->persist($extraItem);

            $this->responseService->setReloadInventory();
        }

        if($pet->hasMerit(MeritEnum::LIGHTNING_REINS) && $item->getName() === 'Lightning in a Bottle')
        {
            $pectin = ItemRepository::findOneByName($this->em, 'Quintessence');

            $extraItem = new Inventory(owner: $pet->getOwner(), item: $pectin)
                ->setCreatedBy($pet->getOwner())
                ->addComment($pet->getName() . ' got this by obtaining ' . $item->getName() . ' with its Lightning Reins.')
                ->setLocation(LocationEnum::Home)
                ->setEnchantment($bonus)
            ;

            if(!$this->houseSimService->getState()->addInventory($extraItem))
                $this->em->persist($extraItem);

            $this->responseService->setReloadInventory();
        }

        if($pet->hasStatusEffect(StatusEffectEnum::Spiced) && $item->getSpice())
        {
            $extraItem = new Inventory(owner: $pet->getOwner(), item: $item)
                ->setCreatedBy($pet->getOwner())
                ->addComment($pet->getName() . ' got this extra ' . $item->getName() . ' thanks to being ' . StatusEffectEnum::Spiced->value . '.')
                ->setLocation(LocationEnum::Home)
                ->setEnchantment($bonus)
            ;

            if(!$this->houseSimService->getState()->addInventory($extraItem))
                $this->em->persist($extraItem);

            $this->responseService->setReloadInventory();
        }

        if($pet->hasStatusEffect(StatusEffectEnum::Hoppin) && str_ends_with($item->getName(), 'Toad Legs'))
        {
            $extraItem = new Inventory(owner: $pet->getOwner(), item: $item)
                ->setCreatedBy($pet->getOwner())
                ->addComment($pet->getName() . ' got this extra ' . $item->getName() . ' thanks to being ' . StatusEffectEnum::Hoppin->value . '.')
                ->setLocation(LocationEnum::Home)
                ->setSpice($extraItemSpice)
                ->setEnchantment($bonus)
            ;

            $this->applySeasonalSpiceToNewItem($extraItem);

            if(!$this->houseSimService->getState()->addInventory($extraItem))
                $this->em->persist($extraItem);

            $this->responseService->setReloadInventory();
        }

        if($cancelGather)
        {
            if(count($replacementItemNames) > 0)
                $activityLog->appendEntry('And the ' . $item->getName() . ' transformed into ' . ArrayFunctions::list_nice($replacementItemNames) . '!');
            else
                $activityLog->appendEntry('However, the ' . $item->getName() . ' melted away instantly!');

            return null;
        }

        if($item->getFood() !== null && count($pet->getLunchboxItems()) === 0 && $this->rng->rngNextInt(1, 20) < 10 - $pet->getFood() - $pet->getJunk() / 2)
        {
            if($this->eatingService->doEat($pet, new FoodWithSpice($item, $spice), $activityLog))
                return null;
        }

        $i = new Inventory(owner: $pet->getOwner(), item: $item)
            ->setCreatedBy($pet->getOwner())
            ->addComment($comment)
            ->setLocation(LocationEnum::Home)
            ->setSpice($spice)
            ->setEnchantment($bonus)
        ;

        $this->applySeasonalSpiceToNewItem($i);

        if(!$this->houseSimService->getState()->addInventory($i))
            $this->em->persist($i);

        $activityLog->addCreatedItem($item);

        $this->responseService->setReloadInventory();

        return $i;
    }

    private function applySeasonalSpiceToNewItem(Inventory $i): Inventory
    {
        if($i->getSpice() && $this->rng->rngNextBool())
            return $i;

        if($i->getItem()->getName() === 'Cellular Peptide Cake')
            return $i->setSpice(SpiceRepository::findOneByName($this->em, 'with Mint Frosting'));

        if($i->getItem()->getName() === 'Worms' && DateFunctions::isSpecificMoon($this->clock->now, MoonNameEnum::WormMoon))
            return $i->setSpice(SpiceRepository::findOneByName($this->em, 'with Butts'));

        return $i;
    }

    public function petCollectsItem(Item|string $item, Pet $pet, string $comment, PetActivityLog $activityLog): ?Inventory
    {
        return $this->petCollectsEnhancedItem($item, null, null, $pet, $comment, $activityLog);
    }

    public function petAttractsRandomBug(Pet $pet, ?string $bugName = null): ?Inventory
    {
        $bugs = 1;
        $toolAttractsBugs = false;

        if($pet->getTool())
        {
            if($pet->getTool()->getItem()->getTool())
            {
                if($pet->getTool()->getItem()->getTool()->getAttractsBugs())
                {
                    $toolAttractsBugs = true;
                    $bugs++;
                }

                if($pet->getTool()->getItem()->getTool()->getPreventsBugs())
                    $bugs--;
            }

            if($pet->getTool()->getEnchantment())
            {
                if($pet->getTool()->getEnchantment()->getEffects()->getAttractsBugs())
                {
                    $toolAttractsBugs = true;
                    $bugs++;
                }

                if($pet->getTool()->getEnchantment()->getEffects()->getPreventsBugs())
                    $bugs--;
            }
        }

        if($bugs <= 0)
            return null;

        if($bugName === null)
            $bugName = $this->rng->rngNextFromArray([ 'Spider', 'Centipede', 'Cockroach', 'Line of Ants', 'Fruit Fly', 'Stink Bug', 'Moth', 'Mosquito' ]);

        $bug = ItemRepository::findOneByName($this->em, $bugName);

        $comment = $toolAttractsBugs ? $pet->getName() . ' caught this in their ' . $pet->getTool()->getItem()->getName() . '!' : 'Ah! How\'d this get inside?!';
        $inventory = null;

        for($i = 0; $i < $bugs; $i++)
        {
            $location = (!$toolAttractsBugs && $pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Basement) && $this->rng->rngNextInt(1, 4) === 1)
                ? LocationEnum::Basement
                : LocationEnum::Home
            ;

            $inventory = $this->receiveItem($bug, $pet->getOwner(), null, $comment, $location);

            if($bugName === 'Spider' && $i === 0)
                $this->receiveItem('Cobweb', $pet->getOwner(), null, 'Cobwebs?! Some Spider must have made this...', $location);
        }

        return $inventory;
    }

    /**
     * @throws PSPNotFoundException
     */
    private function getItemWithChanceForLuckyTransformation(string|Item $item): Item
    {
        $itemIsString = is_string($item);

        if($this->rng->rngNextInt(1, 200) === 1)
        {
            $itemName = $itemIsString ? $item : $item->getName();

            if($itemName === 'Butter')
                return ItemRepository::findOneByName($this->em, 'Butterknife');
            else if($itemName === 'Beans')
                return ItemRepository::findOneByName($this->em, 'Magic Beans');
            else if($itemName === 'Feathers')
                return ItemRepository::findOneByName($this->em, 'Ruby Feather');
            else if($itemName === 'Toad Legs')
                return ItemRepository::findOneByName($this->em, 'Rainbow Toad Legs');
            else if($itemName === 'Stink Bug')
                return ItemRepository::findOneByName($this->em, 'Stinkier Bug');
            else if($itemName === 'Naner')
                return ItemRepository::findOneByName($this->em, 'Bunch of Naners');
        }

        return $itemIsString ? ItemRepository::findOneByName($this->em, $item) : $item;
    }

    /**
     * @throws EnumInvalidValueException
     * @throws PSPNotFoundException
     */
    public function receiveItem(Item|string $item, User $owner, ?User $creator, string $comment, int $location, bool $lockedToOwner = false): Inventory
    {
        $item = $this->getItemWithChanceForLuckyTransformation($item);

        $i = new Inventory(owner: $owner, item: $item)
            ->setCreatedBy($creator)
            ->addComment($comment)
            ->setLocation($location)
            ->setLockedToOwner($lockedToOwner)
        ;

        $this->applySeasonalSpiceToNewItem($i);

        if($location !== LocationEnum::Home || !$this->houseSimService->getState()->addInventory($i))
            $this->em->persist($i);

        $this->responseService->setReloadInventory();

        return $i;
    }

    /**
     * @param int|int[] $location
     */
    public function loseItem(User $owner, int $itemId, int|array $location, int $quantity = 1): int
    {
        $inventory = $this->em->getRepository(Inventory::class)->findBy(
            [
                'owner' => $owner->getId(),
                'item' => $itemId,
                'location' => $location
            ],
            null,
            $quantity
        );

        if(count($inventory) < $quantity)
            return 0;

        foreach($inventory as $i)
        {
            if($i->getHolder()) $i->getHolder()->setTool(null);
            if($i->getWearer()) $i->getWearer()->setHat(null);

            $this->em->remove($i);
        }

        $this->responseService->setReloadInventory();

        return count($inventory);
    }

    /**
     * @param Inventory[] $inventory
     */
    public static function inventoryInSameLocation(array $inventory): bool
    {
        if(count($inventory) === 0)
            throw new \InvalidArgumentException('$inventory must contain at least 1 element.');

        if(count($inventory) === 1)
            return true;

        $locationOfFirstItem = $inventory[0]->getLocation();

        return array_all($inventory, fn(Inventory $i) => $i->getLocation() === $locationOfFirstItem);
    }

    public static function getRandomItemFromItemGroup(IRandom $rng, ItemGroup $itemGroup): Item
    {
        return $rng->rngNextFromArray($itemGroup->getItems()->toArray());
    }

    /**
     * @return ItemQuantity[]
     */
    public function getInventoryQuantities(User $user, int $location, ?string $indexBy = null): array
    {
        $query = $this->em->createQueryBuilder()
            ->from(Inventory::class, 'inventory')
            ->select('item,COUNT(inventory.id) AS quantity')
            ->leftJoin(Item::class, 'item', 'WITH', 'inventory.item = item.id')
            ->andWhere('inventory.owner=:user')
            ->andwhere('inventory.location=:location')
            ->groupBy('item.id')
            ->setParameter('user', $user->getId())
            ->setParameter('location', $location)
        ;

        $results = $query->getQuery()->execute();

        $quantities = [];

        foreach($results as $result)
        {
            $quantity = new ItemQuantity(
                $result[0],
                (int)$result['quantity'],
            );

            if($indexBy)
            {
                $getter = 'get' . $indexBy;
                $quantities[$quantity->item->$getter()] = $quantity;
            }
            else
                $quantities[] = $quantity;
        }

        return $quantities;
    }

    /**
     * @throws EnumInvalidValueException
     */
    public function countItemsInLocation(User $user, int $location): int
    {
        if(!LocationEnum::isAValue($location))
            throw new EnumInvalidValueException(LocationEnum::class, $location);

        return (int)$this->em->createQueryBuilder()
            ->select('COUNT(i.id)')->from(Inventory::class, 'i')
            ->andWhere('i.owner=:user')
            ->andWhere('i.location=:location')
            ->setParameter('user', $user)
            ->setParameter('location', $location)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
