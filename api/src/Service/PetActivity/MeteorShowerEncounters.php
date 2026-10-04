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

use App\Entity\Pet;
use App\Entity\PetActivityLog;
use App\Enum\PetActivityStatEnum;
use App\Enum\PetSkillEnum;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\SpiceRepository;
use App\Model\ComputedPetSkills;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Encounters shared by every meteor shower (currently {@see LeonidsService} and {@see PerseidsService}). The shower's
 * display name is passed in, so a single scene can be reached from any of them.
 *
 * NOTE: callers are responsible for the shower's own tags and interestingness; the encounters here only add whatever
 * tags are particular to the scene itself.
 */
class MeteorShowerEncounters
{
    public function __construct(
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly EntityManagerInterface $em
    )
    {
    }

    /**
     * the opening line every meteor shower encounter starts with; $showerName is the plural name of the shower's
     * meteors (ex: "Leonids").
     */
    public static function getActivityLogPrefix(Pet $pet, string $showerName): string
    {
        return '%pet:' . $pet->getId() . '.name% went into the Umbra, and followed the ' . $showerName . ' to where they were falling!';
    }

    public function encounterFairies(ComputedPetSkills $petWithSkills, string $showerName): PetActivityLog
    {
        $pet = $petWithSkills->getPet();

        $gatheringRoll = $this->rng->rngSkillRoll($petWithSkills->getPerception()->getTotal() + $petWithSkills->getDexterity()->getTotal() + $petWithSkills->getArcana()->getTotal() + $petWithSkills->getGatheringBonus()->getTotal());

        if($gatheringRoll >= 10)
        {
            if($gatheringRoll >= 20)
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(60, 75), PetActivityStatEnum::UMBRA, true);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, self::getActivityLogPrefix($pet, $showerName) . ' There, they ran into some fairies. They helped the fairies gather a ton of Stardust, for which they received lunch and Quintessence as way of thanks!');

                $this->inventoryService->petCollectsItem('Quintessence', $pet, $pet->getName() . ' received this from some fairies after helping them gather tons of Stardust in the Umbra!', $activityLog);

                $this->petExperienceService->gainExp($pet, 2, [ PetSkillEnum::Arcana ], $activityLog);
            }
            else
            {
                $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

                $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, self::getActivityLogPrefix($pet, $showerName) . ' There, they ran into some fairies. After working at it for a while, they all took a break, and the fairies shared some of their food!');

                $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
            }

            $spice = SpiceRepository::findOneByName($this->em, $this->rng->rngNextFromArray([
                'Rain-scented',
                'Juniper',
                'with Rosemary',
                'with Toad Jelly',
            ]));

            $foodItem = $this->rng->rngNextFromArray([
                'Pumpkin Bread',
                'Slice of Naner Bread',
                'World\'s Best Sugar Cookie',
                'Shortbread Cookies',
                'Cheese',
            ]);

            $this->inventoryService->petCollectsEnhancedItem($foodItem, null, $spice, $pet, $pet->getName() . ' received this from some fairies after helping them gather Stardust in the Umbra!', $activityLog);
        }
        else
        {
            $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(45, 60), PetActivityStatEnum::UMBRA, true);

            $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, self::getActivityLogPrefix($pet, $showerName) . ' There, they ran into some fairies. They all hung out and kept each other company while gathering Stardust for a while...');

            $this->petExperienceService->gainExp($pet, 1, [ PetSkillEnum::Arcana ], $activityLog);
        }

        $this->inventoryService->petCollectsItem('Stardust', $pet, $pet->getName() . ' gathered this with some fairies they met in the Umbra!', $activityLog);

        return $activityLog
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Fae-kind' ]))
        ;
    }
}
