<?php
declare(strict_types = 1);

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

use App\Enum\UserStat;
use App\Model\ComputedPetSkills;
use App\Service\IRandom;
use App\Service\PetActivity\SpecialLocations\Caerbannog;
use App\Service\PetActivity\SpecialLocations\ChocolateMansion;
use App\Service\PetActivity\SpecialLocations\FructalPlaneService;
use App\Service\PetActivity\SpecialLocations\HeartDimensionService;
use App\Service\PetActivity\SpecialLocations\LostInTownService;
use App\Service\UserStatsService;

class ToolAdventures
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly UserStatsService $userStatsRepository,
        private readonly TreasureMapService $treasureMapService,
        private readonly Caerbannog $caerbannog,
        private readonly PhilosophersStoneService $philosophersStoneService,
        private readonly ChocolateMansion $chocolateMansion,
        private readonly KappaService $kappaService,
        private readonly SportsBallActivityService $sportsBallActivityService,
        private readonly HeartDimensionService $heartDimensionService,
        private readonly JumpRopeService $jumpRopeService,
        private readonly DokiDokiService $dokiDokiService,
        private readonly MortarOrPestleService $mortarOrPestleService,
        private readonly LostInTownService $lostInTownService,
        private readonly FructalPlaneService $fructalPlaneService
    )
    {
    }

    public function maybeDoToolAdventure(ComputedPetSkills $petWithSkills): bool
    {
        $pet = $petWithSkills->getPet();
        $tool = $pet->getTool();

        if(!$tool)
            return false;

        switch($tool->getItem()->getName())
        {
            case '"Gold" Idol':
                $this->treasureMapService->doGoldIdol($pet);
                return true;

            case '5-leaf Clover':
                $this->treasureMapService->doLeprechaun($petWithSkills);
                return true;

            case 'Aubergine Commander':
                if($this->rng->rngNextInt(1, 80) === 1)
                {
                    $this->treasureMapService->doEggplantCurse($pet);
                    return true;
                }
                break;

            case 'Carrot Key':
                $this->caerbannog->adventure($petWithSkills);
                return true;

            case 'Ceremony of Fire':
                if($this->rng->rngNextInt(1, 20) == 1)
                {
                    $this->philosophersStoneService->seekVesicaHydrargyrum($petWithSkills);
                    return true;
                }
                break;

            case 'Cetgueli\'s Treasure Map':
                $this->treasureMapService->doCetguelisTreasureMap($petWithSkills);
                return true;

            case 'Chocolate Key':
                $this->chocolateMansion->adventure($petWithSkills);
                return true;

            case 'Cucumber':
                $this->kappaService->doHuntKappa($petWithSkills);
                return true;

            case 'Diffie-H Key':
                $this->treasureMapService->doUseDiffieHKey($pet);
                return true;

            case 'Faded Treasure Map':
                $this->treasureMapService->doFadedTreasureMap($petWithSkills);
                return true;

            case 'Fimbulvetr':
                if($this->rng->rngNextInt(1, 20) == 1)
                {
                    $this->philosophersStoneService->seekMetatronsFire($petWithSkills);
                    return true;
                }
                break;

            case 'Fruit Fly on a String':
                $this->treasureMapService->doFruitHunting($pet);
                return true;

            case 'Green Sportsball Ball':
                $this->sportsBallActivityService->doGreenSportsballBall($petWithSkills);
                return true;

            case 'Heartstone':
                if(!$this->heartDimensionService->canAdventure($pet))
                {
                    $this->heartDimensionService->notEnoughAffectionAdventure($pet);
                    return true;
                }
                else if($this->rng->rngNextInt(1, 100) <= $this->heartDimensionService->chanceOfHeartDimensionAdventure($pet))
                {
                    $this->heartDimensionService->adventure($petWithSkills);
                    return true;
                }

                break;

            case 'Jump Rope':
                if($this->rng->rngNextInt(1, 4) == 1)
                {
                    $this->jumpRopeService->adventure($petWithSkills);
                    return true;
                }
                break;

            case 'Large Radish':
                $this->dokiDokiService->adventure($petWithSkills);
                return true;

            case 'Mortar or Pestle':
                if($this->mortarOrPestleService->findTheOtherBit($petWithSkills->getPet()))
                    return true;
                break;

            case 'Orange Sportsball Ball':
                $this->sportsBallActivityService->doOrangeSportsballBall($petWithSkills);
                return true;

            case 'Saucepan':
                if($this->rng->rngNextInt(1, 10) === 1)
                {
                    $this->treasureMapService->doCookSomething($pet);
                    return true;
                }

                break;

            case 'Shirikodama':
                $this->kappaService->doReturnShirikodama($petWithSkills);
                return true;

            case 'Skewered Marshmallow':
                if($this->rng->rngNextInt(1, 10) == 1)
                {
                    $this->treasureMapService->doToastSkeweredMarshmallow($pet);
                    return true;
                }
                break;

            case 'Snickerblade':
                if($this->rng->rngNextInt(1, 20) == 1)
                {
                    $this->philosophersStoneService->seekEarthsEgg($petWithSkills);
                    return true;
                }
                break;

            case 'Sportsball Oar':
                $this->sportsBallActivityService->doSportsballOar($petWithSkills);
                return true;

            case 'Sportsball Pin':
                $this->sportsBallActivityService->doSportsballPin($petWithSkills);
                return true;

            case 'Winged Key':
                $this->treasureMapService->doAbundantiasVault($pet);
                return true;

            case 'Woher Cuán Nani-nani':
                $this->lostInTownService->adventure($petWithSkills);
                return true;

            case 'Silver Keyblade':
            case 'Gold Keyblade':
                if($pet->getFood() > 0 && $this->rng->rngNextInt(1, 10) === 1)
                {
                    $this->treasureMapService->doKeybladeTower($petWithSkills);
                    return true;
                }

                break;

            case 'Rainbow Dolphin Plushy':
            case 'Sneqo Plushy':
            case 'Bulbun Plushy':
            case 'Peacock Plushy':
            case 'Phoenix Plushy':
            case '"Roy" Plushy':
                if($this->rng->rngNextInt(1, 6) === 1 || $this->userStatsRepository->getStatValue($pet->getOwner(), UserStat::TradedWithTheFluffmonger) === 0)
                {
                    $this->treasureMapService->doFluffmongerTrade($pet);
                    return true;
                }

                break;
        }

        if($tool->getEnchantment())
        {
            switch($tool->getEnchantment()->getName())
            {
                case 'Searing':
                    if($this->rng->rngNextInt(1, 20) == 1)
                    {
                        if($this->philosophersStoneService->seekMerkabaOfAir($petWithSkills))
                            return true;
                    }
                    break;

                case 'Gooder':
                    $this->fructalPlaneService->adventure($petWithSkills);
                    return true;
            }
        }

        return false;
    }
}