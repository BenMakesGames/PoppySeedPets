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

namespace App\Controller\Beehive;

use App\Entity\Inventory;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\BeehiveBarEnum;
use App\Enum\BeehiveSpaceTypeEnum;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetBadgeEnum;
use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Exceptions\PSPNotUnlockedException;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\PlayerLogFactory;
use App\Functions\SpiceRepository;
use App\Model\PetChanges;
use App\Service\BeehiveService;
use App\Service\Clock;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetAssistantService;
use App\Service\ResponseService;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/beehive")]
class HarvestController
{
    #[Route("/harvest", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function harvest(
        ResponseService $responseService, EntityManagerInterface $em, InventoryService $inventoryService, IRandom $rng,
        UserAccessor $userAccessor, BeehiveService $beehiveService, Clock $clock,

        #[MapRequestPayload]
        HarvestRequest $request
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Beehive) || !$user->getBeehive())
            throw new PSPNotUnlockedException('Beehive');

        $beehive = $user->getBeehive();

        $barIsFull = match($request->bar)
        {
            BeehiveBarEnum::RoyalJelly => $beehive->getRoyalJellyPercent() >= 1,
            BeehiveBarEnum::Honeycomb => $beehive->getHoneycombPercent() >= 1,
            BeehiveBarEnum::Misc => $beehive->getMiscPercent() >= 1,
            BeehiveBarEnum::Helper => $beehive->getHelperPercent() >= 1,
        };

        if(!$barIsFull)
            throw new PSPInvalidOperationException('The bees aren\'t ready to head out yet!');

        $helper = $beehive->getHelper();

        if($request->bar === BeehiveBarEnum::Helper && !$helper)
            throw new PSPInvalidOperationException('No pet is helping the bees right now.');

        $space = $beehive->getSpace($request->space);

        if(!$space)
            throw new PSPInvalidOperationException('There\'s no such space in the beehive. Reload and try again?');

        if($space->harvested)
            throw new PSPInvalidOperationException('That space has already been picked clean!');

        $oldBasementSize = $user->getBasementSize();
        $comment = $user->getName() . ' took this from their Beehive.';

        /** @var Inventory[] $baseRewards */
        $baseRewards = [];
        $itemNames = [];

        switch($request->bar)
        {
            case BeehiveBarEnum::RoyalJelly:
                $beehive->setRoyalJellyProgress(0);
                $inventoryService->receiveItem('Royal Jelly', $user, $user, $comment, LocationEnum::Home);
                $itemNames[] = 'Royal Jelly';
                break;

            case BeehiveBarEnum::Honeycomb:
                $beehive->setHoneycombProgress(0);
                $goods = $beehiveService->getGoodsForTerrain($space->type);
                $baseRewards[] = $inventoryService->receiveItem($rng->rngNextFromArray($goods), $user, $user, $comment, LocationEnum::Home);
                $baseRewards[] = $inventoryService->receiveItem($rng->rngNextFromArray($goods), $user, $user, $comment, LocationEnum::Home);
                break;

            case BeehiveBarEnum::Misc:
                $beehive->setMiscProgress(0);
                $goods = $beehiveService->getGoodsForTerrain($space->type);
                $baseRewards[] = $inventoryService->receiveItem($rng->rngNextFromArray($goods), $user, $user, $comment, LocationEnum::Home);
                break;

            case BeehiveBarEnum::Helper:
                $beehive->setHelperProgress(0);
                self::helperHuntsOrGathers($em, $inventoryService, $rng, $beehiveService, $helper, $user, $space->type);
                break;
        }

        $beehive->markSpaceHarvested($request->space);

        if(count($baseRewards) > 0)
        {
            $isRaining = WeatherService::getWeather($clock->now)->isRaining();

            foreach($baseRewards as $newItem)
            {
                if($newItem->getItem()->getName() === 'Crooked Stick' || $newItem->getItem()->getFood())
                {
                    if($isRaining && $rng->rngNextInt(1, 3) === 1)
                        $newItem->setSpice(SpiceRepository::findOneByName($em, 'Rain-scented'));
                    else if($rng->rngNextInt(1, 20) === 1)
                        $newItem->setSpice(SpiceRepository::findOneByName($em, 'of Queens'));
                    else
                        $newItem->setSpice(SpiceRepository::findOneByName($em, 'Anthophilan'));
                }

                $itemNames[] = $newItem->getFullItemName();
            }
        }

        $clearedTheGrid = $beehive->countUnharvestedSpaces() === 0;

        if($clearedTheGrid)
        {
            $beehive->resetHarvestedSpaces();

            if($user->hasUnlockedFeature(UnlockableFeatureEnum::Basement))
                $user->increaseBasementSize(60);
        }

        $logEntry = 'You sent your Beehive\'s ' . self::describeBar($request->bar) . ' out to a ' . $space->type->value . ' space'
            . (count($itemNames) > 0 ? ', and received ' . ArrayFunctions::list_nice($itemNames) : '')
            . '.'
            . ($clearedTheGrid ? ' That was the last space; the bees rearranged their hive!' : '');

        PlayerLogFactory::create($em, $user, $logEntry, [ 'Beehive' ]);

        $em->flush();

        $basementGrew = $user->getBasementSize() > $oldBasementSize;

        if(count($itemNames) > 0)
        {
            $itemList = ArrayFunctions::list_nice($itemNames);

            if($basementGrew)
            {
                $howNice = $rng->rngNextFromArray([
                    '(The power of hexagons!)',
                    '(That was nice of them!)',
                    '(Such skill! Such panache!)',
                    '(Bee power!)'
                ]);

                $responseService->addFlashMessage("The bees bring you $itemList, AND some friendly bees increased your Basement - it can now hold {$user->getBasementSize()} items! $howNice");
            }
            else
                $responseService->addFlashMessage("The bees bring you $itemList.");
        }
        else if($basementGrew)
        {
            $responseService->addFlashMessage("Some friendly bees increased your Basement - it can now hold {$user->getBasementSize()} items!");
        }

        return $responseService->success($beehiveService->getResponseData($user), [ SerializationGroupEnum::MY_BEEHIVE, SerializationGroupEnum::HELPER_PET ]);
    }

    private static function describeBar(BeehiveBarEnum $bar): string
    {
        return match($bar)
        {
            BeehiveBarEnum::RoyalJelly => 'Royal Jelly bees',
            BeehiveBarEnum::Honeycomb => 'Honeycomb bees',
            BeehiveBarEnum::Misc => 'Normal Bee Stuff bees',
            BeehiveBarEnum::Helper => 'helper',
        };
    }

    /**
     * The helper pet's own hunt/gather reward (no base reward from the space's terrain, but the terrain decides which
     * tier tables are in play).
     */
    private static function helperHuntsOrGathers(EntityManagerInterface $em, InventoryService $inventoryService, IRandom $rng, BeehiveService $beehiveService, Pet $helper, User $user, BeehiveSpaceTypeEnum $terrain): void
    {
        $petWithSkills = $helper->getComputedSkills();

        $changes = new PetChanges($helper);

        if($helper->hasMerit(MeritEnum::GREEN_THUMB))
        {
            $gathering = $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal();

            $gatherTiers = $beehiveService->getHelperGatherTiers($terrain);

            // two independent draws from the same tables; Naner is used for badge, below
            $extraItem1 = PetAssistantService::getExtraItemFromTiers($rng, $gathering, $gatherTiers);
            $extraItem2 = PetAssistantService::getExtraItemFromTiers($rng, $gathering, $gatherTiers);

            $activityLog = PetActivityLogFactory::createUnreadLog($em, $helper, ActivityHelpers::PetName($helper) . ' helped ' . $user->getName() . '\'s bees while they were out gathering, and collected ' . $extraItem1 . ' AND ' . $extraItem2 . '.');

            $inventoryService->petCollectsItem($extraItem1, $helper, $helper->getName() . ' helped ' . $user->getName() . '\'s bees gathered this.', $activityLog);
            $inventoryService->petCollectsItem($extraItem2, $helper, $helper->getName() . ' helped ' . $user->getName() . '\'s bees gathered this.', $activityLog);

            if($extraItem1 === 'Naner' || $extraItem2 === 'Naner')
                PetBadgeHelpers::awardBadge($em, $helper, PetBadgeEnum::BeeNana, $activityLog);
        }
        else
        {
            $gathering = $petWithSkills->getPerception()->getTotal() + $petWithSkills->getNature()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal();
            $hunting = $petWithSkills->getStrength()->getTotal() + $petWithSkills->getBrawl()->getTotal();

            $total = $gathering + $hunting;

            if($total < 2)
                $doGatherAction = $rng->rngNextBool();
            else
                $doGatherAction = $rng->rngNextInt(1, $total) <= $gathering;

            if($doGatherAction)
            {
                // Naner is used for badge, below
                $extraItem = PetAssistantService::getExtraItemFromTiers($rng, $gathering, $beehiveService->getHelperGatherTiers($terrain));

                $verb = 'gather';
            }
            else
            {
                $extraItem = PetAssistantService::getExtraItemFromTiers($rng, $hunting, $beehiveService->getHelperHuntTiers($terrain));

                $verb = 'hunt';
            }

            $activityLog = PetActivityLogFactory::createUnreadLog($em, $helper, ActivityHelpers::PetName($helper) . ' helped ' . $user->getName() . '\'s bees while they were out ' . $verb . 'ing, and collected ' . $extraItem . '.');

            $inventoryService->petCollectsItem($extraItem, $helper, $helper->getName() . ' helped ' . $user->getName() . '\'s bees ' . $verb . ' this.', $activityLog);

            if($extraItem === 'Naner')
                PetBadgeHelpers::awardBadge($em, $helper, PetBadgeEnum::BeeNana, $activityLog);
        }

        $activityLog
            ->addInterestingness(PetActivityLogInterestingness::PlayerActionResponse)
            ->setChanges($changes->compare($helper))
            ->addTags(PetActivityLogTagHelpers::findByNames($em, [ PetActivityLogTagEnum::Add_on_Assistance, PetActivityLogTagEnum::Beehive ]))
        ;
    }
}

class HarvestRequest
{
    public function __construct(
        public readonly BeehiveBarEnum $bar,
        public readonly int $space,
    )
    {
    }
}
