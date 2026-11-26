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

namespace App\Controller\Item\Blueprint;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Fireplace;
use App\Entity\Inventory;
use App\Entity\Pet;
use App\Enum\MeritEnum;
use App\Enum\PetLocationEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Functions\ArrayFunctions;
use App\Functions\InventoryHelpers;
use App\Functions\ItemRepository;
use App\Functions\PetColorFunctions;
use App\Functions\UserQuestRepository;
use App\Functions\UserUnlockedFeatureHelpers;
use App\Service\IRandom;
use App\Service\ResponseService;
use App\Service\UserAccessor;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/item/fairy")]
class HouseFairyController
{
    public const array FairyNames = [
        'Ævintýri', 'Alfrigg', 'Ant', 'Ao', 'Aphid', 'Apricot', 'Arethusa', 'Ariel',
        'Basil', 'Beeswax', 'Bitterweed', 'Blueberry', 'Bromine',
        'Cardamom', 'Celadon', 'Celeste', 'Cobweb', 'Coriander', 'Cornsilk', 'Cottonweed', 'Cottonwood', 'Crysta', 'Curium', 'Cyclamen',
        'Donella', 'Drake', 'Durin',
        'Ecru', 'Elwood', 'Erline',
        'Faye', 'Faylinn', 'Fée', 'Fern', 'Fishbone', 'Fluorine', 'Fran', 'Frostbite',
        'Gallium', 'Ginger', 'Goldenrod', 'Granite', 'Gumweed',
        'Harp', 'Holly', 'Hollywood',
        'Inchworm', 'Incus', 'Iolanthe', 'Iris',
        'Kailen', 'Klabautermann',
        'Lance', 'Lilac', 'Lily', 'Lumina',
        'Malachite', 'Marigold', 'Marrowfat', 'Melon', 'Milkweed', 'Mint', 'Moss', 'Moth', 'Mulberry', 'Mustardseed',
        'Navi', 'Neráida', 'Nissa',
        'Odelina', 'Onyx', 'Orin', 'Oxblood',
        'Paprika', 'Peaseblossom', 'Peri', 'Plum', 'Potato', 'Pudding', 'Pumpkin',
        'Rhythm', 'Ribbon', 'Riverweed', 'Robin', 'Rockweed', 'Roosevelt', 'Rosewood', 'Rust',
        'Sage', 'Saria', 'Scarlet', 'Seashell', 'Seaweed', 'Sebille', 'Sinopia', 'Sunset',
        'Tania', 'Tapioca', 'Tatiana', 'Tetra', 'Thistle', 'Tibia', 'Timberwolf', 'Tin', 'Tumbleweed',
        'Uranium',
        'Volt',
        'Warren', 'Waxweed', 'Whalebone', 'Wintersnap',
        'Yak',
        'Zanna', 'Zoe',
    ];

    private static function fairyName(Inventory $i): string
    {
        return self::FairyNames[$i->getId() % count(self::FairyNames)];
    }

    #[Route("/{inventory}/hello", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function sayHello(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'fairy/#/hello');

        $saidHello = UserQuestRepository::findOrCreate($em, $user, 'Said Hello to House Fairy', false);

        if(!$saidHello->getValue())
        {
            $saidHello->setValue(true);
            $em->flush();

            return $responseService->itemActionSuccess(
                '"Hi! Thanks for saving me!" It says. "The human world is _crazy!_ Your place is kind of nice, though. Mind if I stick around for a little while? Maybe I can help you out. I am a House Fairy, after all. By the way, my name\'s ' . self::fairyName($inventory) . '!"'
            );
        }
        else
        {
            $pet = $inventory->getHolder();

            if($pet === null)
            {
                return $responseService->itemActionSuccess(
                    '"Oh, hi!" says ' . self::fairyName($inventory) . '.'
                );
            }

            $adjectives = [
                'is pretty cute', 'is really friendly', 'actually smells really nice',
                'seems to like listening to my stories', 'is really funny',
                'has a charming aura', 'makes sure I don\'t get hurt', 'reminds me of Hahanu'
            ];

            $adjective = $adjectives[($inventory->getId() + $pet->getId()) % count($adjectives)];

            if($adjective === 'reminds me of Hahanu' && strtolower(str_replace(' ', '', $pet->getName())) === 'hahanu')
                $adjective = 'reminds me of the real Hahanu';

            return $responseService->itemActionSuccess(
                '"I mean, I wasn\'t exactly expecting to get carried around like this," says ' . self::fairyName($inventory) . '. "But ' . $pet->getName() . ' ' . $adjective . ', so I guess it\'s fine?? Better than being carried around by a raccoon, anyway!"'
            );
        }
    }

    #[Route("/{inventory}/buildFireplace", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function buildBasement(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        UserStatsService $userStatsRepository, IRandom $rng, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'fairy/#/buildFireplace');

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Fireplace) && $user->getFireplace())
        {
            return $responseService->itemActionSuccess(
                '"You already have a Fireplace, and it\'s already as fireplacey as a fireplace can be!" says ' . self::fairyName($inventory) . '.' . "\n\n". 'Fairy nough-- er: fair enough.'
            );
        }

        $petsAtHome = $em->getRepository(Pet::class)->findBy([
            'owner' => $user,
            'location' => PetLocationEnum::HOME
        ]);

        $petWithFairyGodmother = array_find($petsAtHome, fn(Pet $p) => $p->hasMerit(MeritEnum::FAIRY_GODMOTHER));

        if($petWithFairyGodmother)
        {
            $message = '"Usually I\'d ask for Quintessence, buuuuut..." ' . self::fairyName($inventory) . ' looks at ' . $petWithFairyGodmother->getName() . ' "I think ' . $petWithFairyGodmother->getName() . '\'s godmother would say I should do it for free, so... let me just... do a little thing here..."' . "\n\n" . self::fairyName($inventory) . ' wriggles a little, as if something is crawling around inside their... dress? Tunic? Whatever it is.' . "\n\n" . '"Alright! It\'s done!" announces the fairy with a grin.' . "\n\n" . 'And indeed: there\'s now a fireplace in the living room! (But you, dear Poppy Seed Pets player, can find it in the game menu.)';
        }
        else
        {
            $quint = InventoryHelpers::findOneToConsume($em, $user, 'Quintessence');

            if($quint === null)
            {
                return $responseService->itemActionSuccess(
                    '"I\'d like to repay you for saving me, but it\'s gonna take... some _doing_. Can you get me a Quintessence?"'
                );
            }

            $message = '"Thanks! Oh, but actually: do you also have any Bricks?" asks ' . self::fairyName($inventory) . '. "I\'m going to need about 20 exactly...' . "\n\n" . '"Hehehe! Your face! Humans are so cute! I\'m going to make the bricks - and everything else - out of a Quintessence! Obviously! Now, let me just... do a little thing here..."' . "\n\n" . self::fairyName($inventory) . ' wriggles a little, as if something is crawling around inside their... dress? Tunic? Whatever it is.' . "\n\n" . '"Alright! It\'s done!" announces the fairy with a grin.' . "\n\n" . 'And indeed: there\'s now a fireplace in the living room! (But you, dear Poppy Seed Pets player, can find it in the game menu.)';

            $em->remove($quint);
        }

        UserUnlockedFeatureHelpers::create($em, $user, UnlockableFeatureEnum::Fireplace);


        $fireplace = new Fireplace(user: $user, rng: $rng);

        if($userStatsRepository->getStatValue($user, UserStat::ItemsDonatedToMuseum) >= 400)
            $fireplace->setMantleSize(24);

        $em->persist($fireplace);

        $em->flush();

        $responseService->setReloadInventory();

        return $responseService->itemActionSuccess(
            $message
        );
    }

    #[Route("/{inventory}/quintessence", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function askAboutQuintessence(
        Inventory $inventory, ResponseService $responseService, UserAccessor $userAccessor
    ): JsonResponse
    {
        ItemControllerHelpers::validateInventory($userAccessor->getUserOrThrow(), $inventory, 'fairy/#/quintessence');

        return $responseService->itemActionSuccess(
            '"I mean, it\'s kind of like a currency in the Umbra. And a food," ' . self::fairyName($inventory) . ' explains. "All magical creatures want it. Need it. If you\'re looking to get some, honestly, trading with a magical creature is one of the most reliable ways. Or you could beat up a ghost, werewolf, vampire, or some other awful creature like that."'
        );
    }

    #[Route("/{inventory}/makeFairyFloss", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function makeFairyFloss(
        Inventory $inventory, ResponseService $responseService, EntityManagerInterface $em,
        IRandom $rng, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'fairy/#/makeFairyFloss');

        $sugarItem = ItemRepository::findOneByName($em, 'Sugar');

        $sugarToTransform = $em->getRepository(Inventory::class)->findOneBy([
            'item' => $sugarItem,
            'owner' => $user->getId(),
            'location' => $inventory->getLocation()
        ]);

        if(!$sugarToTransform)
        {
            return $responseService->itemActionSuccess(
                '"If you bring me some Sugar, I\'d be happy to spin it into some Fairy Floss for you! It\'s kind of fun to do! Gets me all... dizzy!"'
            );
        }

        $fairyFloss = $rng->rngNextFromArray([ 'Pink Fairy Floss', 'Blue Fairy Floss' ]);

        $message = '"Thanks! Give me a second here!"' . "\n\n" . self::fairyName($inventory) . ' proceeds to spin around, stretching the sugar out, then wrapping it around a bit of Paper that... seems to have come from nowhere???' . "\n\nAfter doing this, they fall over, holding the newly-spun candy aloft.\n\n" . '"Whoo! And here you go! Some ' . $fairyFloss . '!"';

        $fairyFlossItem = ItemRepository::findOneByName($em, $fairyFloss);

        $sugarToTransform
            ->changeItem($fairyFlossItem)
            ->addComment(self::fairyName($inventory) . ' spun a bit of Sugar into this ' . $fairyFloss . '!');

        $em->flush();

        $responseService->setReloadInventory();

        return $responseService->itemActionSuccess(
            $message
        );
    }
}
