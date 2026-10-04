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

namespace App\Controller\Item;

use App\Entity\Inventory;
use App\Enum\FlavorEnum;
use App\Enum\PetLocationEnum;
use App\Enum\PetSpeciesName;
use App\Enum\SerializationGroupEnum;
use App\Enum\StoryEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotFoundException;
use App\Functions\ColorFunctions;
use App\Functions\ItemRepository;
use App\Functions\MeritRepository;
use App\Functions\PetRepository;
use App\Functions\PetSpeciesRepository;
use App\Functions\UserQuestRepository;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetFactory;
use App\Service\ResponseService;
use App\Service\StoryService;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/bug")]
class BugController
{
    #[Route("/{inventory}/squish", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function squishBug(
        Inventory $inventory, ResponseService $responseService, UserStatsService $userStatsRepository,
        EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'bug/#/squish');

        $promised = UserQuestRepository::findOrCreate($em, $user, 'Promised to Not Squish Bugs', 0);

        if($promised->getValue())
            return $responseService->itemActionSuccess('You\'ve promised not to squish any more bugs...');

        $em->remove($inventory);

        $userStatsRepository->incrementStat($user, UserStat::BugsSquished);

        $em->flush();

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }

    #[Route("/{inventory}/putOutside", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function putBugOutside(
        Inventory $inventory, ResponseService $responseService, UserStatsService $userStatsRepository,
        EntityManagerInterface $em, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'bug/#/putOutside');

        $em->remove($inventory);

        $userStatsRepository->incrementStat($user, UserStat::BugsPutOutside);
        $userStatsRepository->incrementStat($user, UserStat::ItemsRecycled);

        $em->flush();

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }

    #[Route("/{inventory}/feed", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function feedBug(
        Inventory $inventory, ResponseService $responseService, UserStatsService $userStatsRepository,
        EntityManagerInterface $em, Request $request, InventoryService $inventoryService, IRandom $rng,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'feedBug');

        $item = $em->getRepository(Inventory::class)->find($request->request->getInt('food'));

        if(!$item || $item->getOwner()->getId() !== $user->getId())
            throw new PSPNotFoundException('Must select an item to feed.');

        if(!$item->getItem()->getFood())
            throw new PSPInvalidOperationException('Bugs won\'t eat that item. (Bugs are bougie like that, I guess.)');

        switch($inventory->getItem()->getName())
        {
            case 'Centipede':
                $userStatsRepository->incrementStat($user, UserStat::EvolvedACentipede);
                $inventory
                    ->changeItem(ItemRepository::findOneByName($em, 'Moth'))
                    ->addComment($user->getName() . ' fed this Centipede, allowing it to grow up into a beautiful... Moth.')
                    ->setModifiedOn()
                ;
                $message = "What? Centipede is evolving!\n\nCongratulations! Your Centipede evolved into... a Moth??";
                break;

            case 'Cockroach':
                $inventoryService->receiveItem('Cockroach', $user, $user, $user->getName() . ' fed a Cockroach; as a result, _this_ Cockroach showed up. (Is this a good thing?)', $inventory->getLocation());
                $message = 'Oh. You\'ve attracted another Cockroach!';
                break;

            case 'Line of Ants':
                $userStatsRepository->incrementStat($user, UserStat::FedALineOfAnts);

                if($item->getItem()->getName() === 'Ants on a Log')
                {
                    if($rng->rngNextInt(1, 6) === 6)
                    {
                        $inventoryService->receiveItem('Ant Queen', $user, $user, $user->getName() . ' fed a Line of Ants; as a result, this Queen Ant showed up! (Is this a good thing?)', $inventory->getLocation());
                        $message = 'As part of a study on cannibalism in other species, you feed the Line of Ants some Ants on a Log. And oh: you\'ve attracted the attention of an Ant Queen! (What a surprising result! What could it mean!?)';
                    }
                    else
                    {
                        $inventoryService->receiveItem('Line of Ants', $user, $user, $user->getName() . ' fed a Line of Ants; as a result, _these_ ants showed up. (Is this a good thing?)', $inventory->getLocation());
                        $message = 'As part of a study on cannibalism in other species, you feed the Line of Ants some Ants on a Log. And oh: you\'ve attracted more ants! Interesting... interesting...';
                    }
                }
                else
                {
                    if($rng->rngNextInt(1, 6) === 6)
                    {
                        $inventoryService->receiveItem('Ant Queen', $user, $user, $user->getName() . ' fed a Line of Ants; as a result, this Queen Ant showed up! (Is this a good thing?)', $inventory->getLocation());
                        $message = 'Oh? You\'ve attracted an Ant Queen!';
                    }
                    else
                    {
                        $inventoryService->receiveItem('Line of Ants', $user, $user, $user->getName() . ' fed a Line of Ants; as a result, _these_ ants showed up. (Is this a good thing?)', $inventory->getLocation());
                        $message = 'Oh. You\'ve attracted more ants! (You were hoping for an Ant Queen, but oh well... maybe next time...)';
                    }
                }

                break;

            case 'Ant Queen':
                $inventoryService->receiveItem('Line of Ants', $user, $user, $user->getName() . ' fed an Ant Queen; as a result, _these_ ants showed up. (Is this a good thing?)', $inventory->getLocation());
                $message = 'Oh. You\'ve attracted more ants!';
                break;

            case 'Fruit Fly':
                $inventoryService->receiveItem('Fruit Fly', $user, $user, $user->getName() . ' fed a Fruit Fly; as a result, _this_ Fruit Fly showed up. (Is this a good thing?)', $inventory->getLocation());
                $message = 'Oh. You\'ve attracted another Fruit Fly!';
                break;

            case 'Heart Beetle':
                $inventoryService->receiveItem('Heart Beetle', $user, $user, $user->getName() . ' fed a Heart Beetle; as a result, _this_ Heart Beetle showed up. (Is this a good thing?)', $inventory->getLocation());
                $message = 'Oh. You\'ve attracted another Heart Beetle!';
                break;

            default:
                throw new \Exception($inventory->getItem()->getName() . ' cannot be fed! This is totally a programmer\'s error, and should be fixed!');
        }

        $em->remove($item);

        $userStatsRepository->incrementStat($user, UserStat::BugsFed);

        $em->flush();

        $responseService->addFlashMessage($message);

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }

    #[Route("/{inventory}/adopt", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function adopt(
        Inventory $inventory, EntityManagerInterface $em, ResponseService $responseService, PetFactory $petFactory,
        IRandom $rng, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'bug/#/adopt');

        $petName = $rng->rngNextFromArray([
            'Afrolixa', 'Alcimus', 'Antocha', 'Argyra', 'Asiola', 'Atarba', 'Atissa',
            'Beskia', 'Bothria', 'Bremia',
            'Cadrema', 'Chlorops', 'Cirrula', 'Cladura', 'Conosia', 'Cremmus',
            'Dagus', 'Dicarca', 'Diostracus', 'Dytomyia',
            'Elliptera', 'Enlinia', 'Eothalassius',
            'Filatopus',
            'Garifuna', 'Gaurax',
            'Harmandia', 'Hurleyella', 'Hyadina',
            'Iteomyia',
            'Janetiella',
            'Lecania', 'Libnotes', 'Lipara',
            'Maietta', 'Mberu', 'Melanderia', 'Meromyza',
            'Nanomyina', 'Narrabeenia', 'Naufraga', 'Neossos',
            'Odus', 'Ormosia', 'Orzihincus',
            'Paraclius', 'Peodes', 'Pilbara', 'Pinyonia', 'Porasilus',
            'Rhaphium', 'Risa',
            'Saphaea', 'Semudobia', 'Shamshevia', 'Silvestrina', 'Stilpnogaster', 'Strobliola', 'Syntormon',
            'Teneriffa', 'Tolmerus', 'Tricimba', 'Trotteria',
            'Vitisiella',
            'Wyliea',
            'Xena',
            'Yumbera',
            'Zeros', 'Zoticus',
        ]);

        // RANDOM!
        $h1 = $rng->rngNextInt(0, 1000) / 1000.0;
        $s1 = $rng->rngNextInt($rng->rngNextInt(0, 500), 1000) / 1000.0;
        $l1 = $rng->rngNextInt($rng->rngNextInt(0, 500), $rng->rngNextInt(750, 1000)) / 1000.0;

        $h2 = $rng->rngNextInt(0, 1000) / 1000.0;
        $s2 = $rng->rngNextInt($rng->rngNextInt(0, 500), 1000) / 1000.0;
        $l2 = $rng->rngNextInt($rng->rngNextInt(0, 500), $rng->rngNextInt(750, 1000)) / 1000.0;

        $colorA = ColorFunctions::HSL2Hex($h1, $s1, $l1);
        $colorB = ColorFunctions::HSL2Hex($h2, $s2, $l2);

        $newPet = $petFactory->createPet(
            $user,
            $petName,
            PetSpeciesRepository::findOneByName($em, PetSpeciesName::SentientBeetle),
            $colorA,
            $colorB,
            $rng->rngNextFromArray(FlavorEnum::cases()),
            MeritRepository::getRandomAdoptedPetStartingMerit($em, $rng)
        );

        $newPet
            ->setFoodAndSafety($rng->rngNextInt(10, 12), -9)
            ->setScale($rng->rngNextInt(80, 120))
        ;

        $numberOfPetsAtHome = PetRepository::getNumberAtHome($em, $user);

        if($numberOfPetsAtHome >= $user->getMaxPets())
        {
            $newPet->setLocation(PetLocationEnum::DAYCARE);
            $message = 'The beetle trundles happily into the daycare...';
            $reloadPets = false;
        }
        else
        {
            $message = 'The beetle finds a nice corner in your house, and settles in...';
            $reloadPets = true;
        }

        $responseService->addFlashMessage($message);

        $em->persist($newPet);
        $em->remove($inventory);
        $em->flush();

        $responseService->setReloadPets($reloadPets);

        return $responseService->itemActionSuccess(null, [ 'itemDeleted' => true ]);
    }

    /**
     * @throws \Exception
     */
    #[Route("/{inventory}/talkToQueen", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function talkToQueen(
        Inventory $inventory, StoryService $storyService, Request $request,
        ResponseService $responseService, UserAccessor $userAccessor
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        ItemControllerHelpers::validateInventory($user, $inventory, 'bug/#/squish');

        $response = $storyService->doStory($user, StoryEnum::StolenPlans, $request->request, $inventory);

        return $responseService->success($response, [ SerializationGroupEnum::STORY ]);
    }
}
