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

use App\Entity\Pet;
use App\Entity\User;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetLocationEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPInvalidOperationException;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\PetChanges;
use App\Service\QualityTime\QualityTimeEvent;
use App\Service\QualityTime\QualityTimeResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class QualityTimeService
{
    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly CravingService $cravingService,
        private readonly EntityManagerInterface $em,
        private readonly UserStatsService $userStatsRepository,
        private readonly IRandom $rng,
        /**
         * @var iterable<QualityTimeEvent>
         */
        #[AutowireIterator('app.qualityTimeEvent')]
        private readonly iterable $qualityTimeEvents,
    )
    {
    }

    public function doQualityTime(User $user): string
    {
        $pets = $this->em->getRepository(Pet::class)->findBy([
            'owner' => $user,
            'location' => [
                PetLocationEnum::HOME,
                PetLocationEnum::BEEHIVE,
                PetLocationEnum::FIREPLACE,
                PetLocationEnum::GREENHOUSE,
                PetLocationEnum::DRAGON_DEN,
            ]
        ]);

        if(count($pets) === 0)
            throw new PSPInvalidOperationException('You have no pets to spend time with.');

        $now = new \DateTimeImmutable();

        $qualityTime = $this->pickQualityTimeEvent($user, $pets);

        foreach($pets as $pet)
        {
            $changes = new PetChanges($pet);

            $diff = $now->diff($pet->getLastInteracted());
            $hours = min(48, $diff->h + $diff->days * 24);

            if($qualityTime->foodBased)
                $pet->increaseFood((int)($hours / 4));

            $affection = (int)($hours / 4);
            $gain = (int)ceil($hours / 2.5) + 3;

            $safetyBonus = 0;
            $esteemBonus = 0;

            if($pet->getSafety() > $pet->getEsteem())
            {
                $safetyBonus -= (int)floor($gain / 4);
                $esteemBonus += (int)floor($gain / 4);
            }
            else if($pet->getEsteem() > $pet->getSafety())
            {
                $safetyBonus += (int)floor($gain / 4);
                $esteemBonus -= (int)floor($gain / 4);
            }

            $pet->increaseSafety($gain + $safetyBonus);
            $pet->increaseLove($gain);
            $pet->increaseEsteem($gain + $esteemBonus);
            $this->petExperienceService->gainAffection($pet, $affection);

            $pet->setLastInteracted($now);

            $this->cravingService->maybeAddCraving($pet);

            PetActivityLogFactory::createReadLog($this->em, $pet, $qualityTime->message)
                ->setIcon('ui/affection')
                ->setChanges($changes->compare($pet))
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ PetActivityLogTagEnum::QualityTime ]))
            ;
        }

        $this->userStatsRepository->incrementStat($user, UserStat::PettedAPet, count($pets));

        $user->setLastPerformedQualityTime();

        return $qualityTime->message;
    }

    /**
     * @param Pet[] $pets
     */
    private function pickQualityTimeEvent(User $user, array $pets): QualityTimeResult
    {
        $availableEvents = array_filter(
            [...$this->qualityTimeEvents],
            fn(QualityTimeEvent $event) => $event->isAvailable($user)
        );

        $picked = $this->rng->rngNextFromArray($availableEvents);

        return $picked->generate($user, $pets);
    }
}
