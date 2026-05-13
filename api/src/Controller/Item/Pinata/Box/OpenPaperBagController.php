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

namespace App\Controller\Item\Pinata\Box;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Functions\DateFunctions;
use App\Functions\ItemRepository;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OpenPaperBagController
{
    #[Route("/item/box/paperBag/{inventory}/open", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function openPaperBag(
        Inventory $inventory, ResponseService $responseService, InventoryService $inventoryService, IRandom $rng,
        UserStatsService $userStatsRepository, EntityManagerInterface $em, Clock $clock,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'box/paperBag/#/open');

        $wheatFlourOrCorn = DateFunctions::isCornMoon($clock->now) ? 'Corn' : 'Wheat Flour';

        $item = ItemRepository::findOneByName($em, $rng->rngNextFromArray([
            'Apricot', 'Baking Soda', 'Beans', 'Blackberry Lassi', 'Blueberries', 'Butter', 'Canned Food', 'Celery',
            'Cockroach', 'Corn', 'Cream of Tartar', 'Creamy Milk', 'Egg', 'Fish', 'Fluff', 'Glowing Four-sided Die',
            'Grandparoot', 'Honeydont', 'Hot Dog', 'Iron Ore', 'Jelly-filled Donut', 'Kombucha', 'Melon Bun', 'Mint',
            'Mixed Nuts', 'Naner', 'Oil', 'Onion', 'Orange', 'Pamplemousse', 'Plain Yogurt', 'Quintessence', 'Red',
            'Red Clover', 'Rice', 'Seaweed', 'Secret Seashell', 'Silica Grounds', 'Smallish Pumpkin', 'Sugar',
            'Toad Legs', 'Tomato', $wheatFlourOrCorn, 'World\'s Best Sugar Cookie', 'Yeast', 'Yellowy Lime', 'Ponzu',
            'Plastic Bottle',
        ]));

        $openedStat = $userStatsRepository->incrementStat($user, 'Opened ' . $inventory->getItem()->getNameWithArticle());

        if($item->getName() === 'Cockroach' && $rng->rngNextInt(1, 3) === 1)
        {
            $numRoaches = $rng->rngNextInt(6, 8);

            for($i = 0; $i < $numRoaches; $i++)
            {
                $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $inventory->getLocation(), $inventory->getLockedToOwner())
                    ->setSpice($inventory->getSpice())
                ;
            }

            $message = 'You open the bag... agh! It\'s swarming with roaches!!';
        }
        else
        {
            $inventoryService->receiveItem($item, $user, $user, $user->getName() . ' got this from ' . $inventory->getItem()->getNameWithArticle() . '.', $inventory->getLocation(), $inventory->getLockedToOwner())
                ->setSpice($inventory->getSpice())
            ;

            if(
                $item->getName() !== 'Cockroach' && (
                    $openedStat->getValue() == 2 || (
                        $openedStat->getValue() >= 5 &&
                        $rng->rngNextInt(1, 15) === 1
                    )
                )
            )
            {
                $message = $rng->rngNextFromArray([
                    'You open the bag... WHAT THE FU-- oh, wait, nm: it\'s just ' . $item->getNameWithArticle() . '.',
                    'Da, da, da, DAAAAAAAA: ' . $item->getNameWithArticle() . '!',
                    'You open the bag... just some ordinary, run-of-the-mill ' . $item->getName() . '.',
                    'You open the bag... there\'s another Paper Bag inside, so you open _that_... ah! ' . ucfirst($item->getNameWithArticle()) . '! (Finally!)',
                    'You open the bag... ah! It\'s the ' . $item->getName() . ' you\'ve always dreamed about; the ' . $item->getName() . ' you _so richly deserve_.',
                    'You open the bag... ah! ' . ucfirst($item->getNameWithArticle()) . ' fit for a queen! Or a king! Or whatever! You do you!',
                    'You open the bag... it\'s empty??? Wait, no, here it is, stuck under a flap in the deepest recesses of the bag: ' . $item->getNameWithArticle() . '.',
                    'You open the bag... you pray it\'s not ' . $item->getNameWithArticle() . ', but - and I hate to break it to you - that\'s _exactly_ what it is.',
                    "You open the-- aw, shit! The bag tore!\n\nSomething tumbles out, and makes a very uncomfortable noise when it hit the ground. Well, at least it didn't hit you on its way there.\n\nYou look past the bag, to the floor, and at the source of your consternation. Hmph: all that trouble for " . $item->getNameWithArticle() . "...",
                    'You open the bag... iiiiiiiiiiit\'s ' . $item->getNameWithArticle() . '.',
                    "If I tell you there's " . $item->getNameWithArticle() . " in this bag, will you believe me?\n\nThere's " . $item->getNameWithArticle() . " in this bag.",
                    "You open the bag... but it's one of those Mimic Paper Bags! OH NO! IT CHOMPS DOWN HARD ON YOUR-- oh. Wait, it... it doesn't have any teeth.\n\nWell, it's a bit more work - and a bit wetter - than you\'d like, but with a little work you manage to extract " . $item->getNameWithArticle() . ".",
                    'You open the bag... but it\'s one of those Mimic Paper Bags! OH NO! It wriggles free, drops to the ground, and scurries off, ' . $item->getNameWithArticle() . ' tumbling out of its... mouth (???) as it goes.',
                    "You open the bag... for some reason it's got that insulation lining on the inside? " . $rng->rngNextFromArray([ 'Cold', 'Warm' ]) . " air cascades out of the bag as you rummage around inside...\n\nAh, here it is: " . $item->getNameWithArticle() . "!",
                    "You try to open the bag, but it's glued shut!\n\nFoolish bag! Do you really think you're a match for " . $user->getName() . "'s RIPPLING, _SEXY_ MUSCLES!?!\n\nRAWR!!\n\nThe bag is torn in two, sending " . $item->getNameWithArticle() . " tumbling to the ground.",
                ]);
            }
            else
                $message = 'You open the bag... ah! ' . ucfirst($item->getNameWithArticle()) . '!';
        }

        $em->remove($inventory);

        $em->flush();

        return $responseService->itemActionSuccess($message, [ 'itemDeleted' => true ]);
    }
}
