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

namespace Controller\Inventory;

use App\Controller\Inventory\CookAndCombineController;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemFood;
use App\Entity\Spice;
use App\Entity\User;
use App\Functions\InventoryModifierFunctions;
use App\Service\CookingService;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CookAndCombineControllerTest extends TestCase
{
    private static function makeFoodItem(string $name): Item
    {
        $item = new Item();
        $item->setName($name);
        $item->setFood(new ItemFood());

        return $item;
    }

    private static function makeSpiceItem(string $name): Item
    {
        $item = new Item();
        $item->setName($name);
        $item->setSpice(new Spice($name, new ItemFood()));

        return $item;
    }

    private static function makeInventory(User $owner, Item $item): Inventory
    {
        return new Inventory($owner, $item);
    }

    public function testPartialMixedSpiceBatchReportsTheCountOfPlainFoods(): void
    {
        $owner = new User('Tester', 'tester@example.com');
        $egg = self::makeFoodItem('Egg');
        $tomatoKetchup = self::makeSpiceItem('Tomato Ketchup');
        $onion = self::makeSpiceItem('Onion');

        $inventory = [
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $egg),
            self::makeInventory($owner, $tomatoKetchup),
            self::makeInventory($owner, $onion),
        ];

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn($inventory);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Inventory::class)->willReturn($repository);
        $entityManager->expects($this->exactly(2))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $responseService = $this->getMockBuilder(ResponseService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'addFlashMessage', 'success' ])
            ->getMock();
        $responseService->expects($this->once())
            ->method('addFlashMessage')
            ->with('2× Egg are now seasoned: Tomato Ketchup, and Onion - but there wasn\'t enough for the last 2× Egg so they\'re plain for now.')
            ->willReturnSelf();
        $responseService->method('success')->willReturn(new JsonResponse());

        $userAccessor = $this->createMock(UserAccessor::class);
        $userAccessor->method('getUserOrThrow')->willReturn($owner);

        (new CookAndCombineController())->prepareRecipe(
            new Request([], [ 'inventory' => [ 1, 2, 3, 4, 5, 6 ] ]),
            $responseService,
            $entityManager,
            $this->createMock(CookingService::class),
            $this->createMock(IRandom::class),
            $userAccessor
        );
    }
}