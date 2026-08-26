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

namespace App\Controller\Inventory;

use App\Entity\Inventory;
use App\Enum\SerializationGroupEnum;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Functions\ArrayFunctions;
use App\Functions\GrammarFunctions;
use App\Functions\InventoryModifierFunctions;
use App\Service\CookingService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/inventory")]
class CookAndCombineController
{
    #[Route("/prepare", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function prepareRecipe(
        Request $request, ResponseService $responseService, EntityManagerInterface $em,
        CookingService $cookingService, IRandom $rng, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        $inventoryIds = $request->request->all('inventory');

        if(count($inventoryIds) > 100)
            throw new PSPFormValidationException('Oh, goodness, please don\'t try to Cook or Combine more than 100 items at a time! (Sorry for the inconvenience...)');

        $inventory = $em->getRepository(Inventory::class)->findBy([
            'owner' => $user,
            'id' => $inventoryIds
        ]);

        if(count($inventory) !== count($inventoryIds))
            throw new PSPNotFoundException('Some of the items could not be found??');

        if(count($inventory) === 0)
            throw new PSPFormValidationException('You gotta\' select at least ONE item!');

        if(!InventoryService::inventoryInSameLocation($inventory))
            throw new PSPFormValidationException('All of the items must be in the same location.');

        if(count($inventory) === 2)
        {
            /** @var Inventory $baseItem */
            $baseItem = array_find($inventory, fn(Inventory $i) => $i->getId() === $inventoryIds[0]);

            /** @var Inventory $addOn */
            $addOn = array_find($inventory, fn(Inventory $i) => $i->getId() === $inventoryIds[1]);

            // try enchanting
            $enchanted = null;

            if($baseItem->getItem()->getTool() && $addOn->getItem()->getEnchants())
            {
                InventoryModifierFunctions::enchant($em, $baseItem, $addOn);
                $enchanted = $baseItem;
            }
            else if($addOn->getItem()->getTool() && $baseItem->getItem()->getEnchants())
            {
                InventoryModifierFunctions::enchant($em, $addOn, $baseItem);
                $enchanted = $addOn;
            }

            if($enchanted)
            {
                $newName = InventoryModifierFunctions::getNameWithModifiers($enchanted);

                $responseService->addFlashMessage('The ' . $enchanted->getItem()->getName() . ' is now ' . GrammarFunctions::indefiniteArticle($newName) . ' ' . $newName . '!');

                $em->flush();

                $responseService->setReloadInventory();

                return $responseService->success($enchanted, [ SerializationGroupEnum::MY_INVENTORY ]);
            }

            // try spicing up
            $spiced = null;

            if($baseItem->getItem()->getFood() && $addOn->getItem()->getSpice())
            {
                InventoryModifierFunctions::spiceUp($em, $baseItem, $addOn);
                $spiced = $baseItem;
            }
            else if($addOn->getItem()->getFood() && $baseItem->getItem()->getSpice())
            {
                InventoryModifierFunctions::spiceUp($em, $addOn, $baseItem);
                $spiced = $addOn;
            }

            if($spiced)
            {
                $newName = InventoryModifierFunctions::getNameWithModifiers($spiced);

                $responseService->addFlashMessage('The ' . $spiced->getItem()->getName() . ' is now ' . GrammarFunctions::indefiniteArticle($newName) . ' ' . $newName . '!');

                $em->flush();

                $responseService->setReloadInventory();

                return $responseService->success($spiced, [ SerializationGroupEnum::MY_INVENTORY ]);
            }
        }

        $bulkSpicingPlan = InventoryModifierFunctions::planBulkSpicing($inventory);

        if($bulkSpicingPlan !== null)
        {
            foreach($bulkSpicingPlan->pairs as [$food, $spice])
                InventoryModifierFunctions::spiceUp($em, $food, $spice);

            $count = count($bulkSpicingPlan->pairs);
            $foodName = $bulkSpicingPlan->pairs[0][0]->getItem()->getName();
            $spiceName = $bulkSpicingPlan->pairs[0][1]->getItems()->getSpice()?->getName()
                ?? throw new PSPInvalidOperationException('There is no spice to be found!');

            if($bulkSpicingPlan->leftoverFoodCount > 0)
                $responseService->addFlashMessage($count . ' of those ' . $foodName . ' now have the ' . $spiceName . ' spice - but there wasn\'t enough for the last ' . $bulkSpicingPlan->leftoverFoodCount . ' ' . $foodName . ' so they\'re plain for now.');
            else if($bulkSpicingPlan->leftoverSpiceCount > 0)
                $responseService->addFlashMessage('All ' . $count . ' of those ' . $foodName . ' now have the ' . $spiceName . ' spice! You have ' . $bulkSpicingPlan->leftoverSpiceCount . ' ' . $spiceName . ' spices leftover.');
            else
                $responseService->addFlashMessage('All ' . $count . ' of those ' . $foodName . ' now have the ' . $spiceName . ' spice! Batch-prepping FTW!');

            $em->flush();

            $responseService->setReloadInventory();

            return $responseService->success(array_map(fn(array $pair) => $pair[0], $bulkSpicingPlan->pairs), [ SerializationGroupEnum::MY_INVENTORY ]);
        }

        if(InventoryModifierFunctions::isAmbiguousBulkSpicingAttempt($inventory))
            throw new PSPInvalidOperationException('Hmm, this is some complicated seasoning you\'re requesting. Let\'s not.');

        $results = $cookingService->prepareRecipeByHand($user, $user, $inventory);

        // do this before checking if anything was made
        // because if NOTHING was made, a record in "RecipeAttempted" was made :P
        $em->flush();

        if($results === null)
            throw new PSPInvalidOperationException('You can\'t make anything with those ingredients.');

        $qList = [];
        $totalQuantity = 0;

        foreach($results->quantities as $q)
        {
            $qList[$q->item->getName()] = $q->quantity;
            $totalQuantity += $q->quantity;
        }

        if($totalQuantity < 4)
            $exclaim = '.';
        else if($totalQuantity < 10 || strpos($results->recipe->ingredients, ',') === false)
            $exclaim = '!';
        else
            $exclaim = '! (' . $rng->rngNextFromArray([ 'Dang!', 'Wow!', 'Wowie-zowie!', 'Incredible...', 'So cook! Very meal!', 'A veritable feast!', 'Such skill! Such panache!', 'When did you become so powerful?!' ]) . ')';

        $responseService->addFlashMessage('You prepared ' . ArrayFunctions::list_nice_quantities($qList) . $exclaim);

        return $responseService->success($results->inventory, [ SerializationGroupEnum::MY_INVENTORY ]);
    }
}
