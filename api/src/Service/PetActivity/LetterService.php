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

use App\Entity\Letter;
use App\Entity\PetActivityLog;
use App\Entity\User;
use App\Entity\UserLetter;
use App\Enum\LetterSenderEnum;
use App\Enum\LocationEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetActivityStatEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\UserQuestRepository;
use App\Functions\UserUnlockedFeatureHelpers;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\MuseumService;
use App\Service\PetExperienceService;
use Doctrine\ORM\EntityManagerInterface;

class LetterService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetExperienceService $petExperienceService,
        private readonly MuseumService $museumService,
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng
    )
    {
    }

    public function adventure(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $log = $this->doAnniversary($petWithSkills);
        if($log) return $log;

        $log = $this->doSharuminyinka($petWithSkills);
        if($log) return $log;

        $log = $this->doKatica($petWithSkills);
        if($log) return $log;

        $log = $this->doHyssop($petWithSkills);
        if($log) return $log;

        return null;
    }

    private function doAnniversary(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $owner = $petWithSkills->getPet()->getOwner();

        $accountAgeInYears = new \DateTimeImmutable()->diff($owner->getRegisteredOn())->y;

        if($accountAgeInYears === 0)
            return null;

        $anniversaryLettersDelivered = UserQuestRepository::find(
            $this->em,
            $owner,
            'Anniversary Letters Delivered'
        );

        $lettersDelivered = $anniversaryLettersDelivered ? $anniversaryLettersDelivered->getValue() : 0;

        if($lettersDelivered >= $accountAgeInYears)
            return null;

        $response = $this->doDeliverLetter($petWithSkills, LetterSenderEnum::Mia, 1);

        if(!$response)
            return null;

        if($response->letter->getLetter()->getAttachment())
            $this->museumService->forceDonateItem($owner, $response->letter->getLetter()->getAttachment(), $response->letter->getLetter()->getSender() . ' donated this to the Museum on your behalf.');

        return $response->activityLog;
    }

    private function doHyssop(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $owner = $petWithSkills->getPet()->getOwner();

        $canReceiveLettersFromFairies = UserQuestRepository::find(
            $this->em,
            $owner,
            'Can Receive Letters from Fairies'
        );

        if(!$canReceiveLettersFromFairies)
            return null;

        $hyssopLettersDelivered = UserQuestRepository::find(
            $this->em,
            $owner,
            'Hyssop Letters Delivered'
        );

        if($hyssopLettersDelivered && $hyssopLettersDelivered->getValue() >= $canReceiveLettersFromFairies->getValue())
            return null;

        $response = $this->doDeliverLetter($petWithSkills, LetterSenderEnum::Hyssop, 4);

        return $response ? $response->activityLog : null;
    }

    private function doKatica(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $owner = $petWithSkills->getPet()->getOwner();

        if($owner->getBeehive() && $owner->getBeehive()->getWorkers() >= 5000)
        {
            $response = $this->doDeliverLetter($petWithSkills, LetterSenderEnum::Katica, 19);

            return $response ? $response->activityLog : null;
        }

        return null;
    }

    private function doSharuminyinka(ComputedPetSkills $petWithSkills): ?PetActivityLog
    {
        $owner = $petWithSkills->getPet()->getOwner();

        $sharuminyinkaQuestStep = UserQuestRepository::find(
            $this->em,
            $owner,
            'Sharuminyinka\'s Despair - Step'
        );

        if($sharuminyinkaQuestStep && $sharuminyinkaQuestStep->getValue() === 40)
        {
            $thirtyDaysAgo = new \DateTimeImmutable()->modify('-30 days');

            if($sharuminyinkaQuestStep->getLastUpdated() < $thirtyDaysAgo)
            {
                $response = $this->doDeliverLetter($petWithSkills, LetterSenderEnum::Sharuminyinka, 14);

                return $response ? $response->activityLog : null;
            }
        }

        return null;
    }

    private function doDeliverLetter(ComputedPetSkills $petWithSkills, LetterSenderEnum $sender, int $minDaysBetweenDelivery): ?LetterResponse
    {
        $pet = $petWithSkills->getPet();
        $owner = $pet->getOwner();
        $deliveryIntervalAgo = new \DateTimeImmutable()->modify('-' . $minDaysBetweenDelivery . ' days');

        $lettersDelivered = UserQuestRepository::findOrCreate($this->em, $owner, $sender->value . ' Letters Delivered', 0);

        // for letters beyond the first, we must wait at least $minDaysBetweenDelivery to deliver another message:
        if($lettersDelivered->getValue() > 0 && $lettersDelivered->getLastUpdated() >= $deliveryIntervalAgo)
            return null;

        // if all the letters have already been delivered, get outta' here:
        if($lettersDelivered->getValue() >= self::getNumberOfLettersFromSender($this->em, $sender))
            return null;

        $petChanges = new PetChanges($pet);

        $descriptionForPet = 'some pet they didn\'t recognize.';

        $this->petExperienceService->spendTime($pet, $this->rng->rngNextInt(30, 60), PetActivityStatEnum::OTHER, null);

        $activityLog = PetActivityLogFactory::createUnreadLog($this->em, $pet, 'While %pet:' . $pet->getId() . '.name% was thinking about what to do, a courier delivered them a Letter from ' . $sender->value . '! The courier was ' . $descriptionForPet)
            ->setIcon('icons/activity-logs/letter')
            ->addInterestingness(PetActivityLogInterestingness::RareActivity)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Mail' ]))
        ;

        $activityLog->setChanges($petChanges->compare($pet));

        $letterDescription = '%pet:' . $pet->getId() . '.name% was delivered this letter by a courier: ' . $descriptionForPet;

        $letter = $this->giveNextLetter($owner, $sender, $letterDescription);

        $lettersDelivered->setValue($lettersDelivered->getValue() + 1);

        $response = new LetterResponse();
        $response->letter = $letter;
        $response->activityLog = $activityLog;

        return $response;
    }

    private static function getNumberOfLettersFromSender(EntityManagerInterface $em, LetterSenderEnum $sender): int
    {
        return (int)$em->getRepository(Letter::class)->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.sender = :sender')
            ->setParameter('sender', $sender)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private static function getNumberOfLettersToUserFromSender(EntityManagerInterface $em, User $user, LetterSenderEnum $sender): int
    {
        return (int)$em->getRepository(UserLetter::class)->createQueryBuilder('ul')
            ->select('COUNT(ul.id)')
            ->leftJoin('ul.letter', 'l')
            ->andWhere('ul.user = :userId')
            ->andWhere('l.sender = :sender')
            ->setParameter('userId', $user->getId())
            ->setParameter('sender', $sender)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function giveNextLetter(User $user, LetterSenderEnum $sender, string $comment): UserLetter
    {
        $existingLetters = self::getNumberOfLettersToUserFromSender($this->em, $user, $sender);

        $nextLetter = $this->findBySenderIndex($sender, $existingLetters)
            ?? throw new \InvalidArgumentException('The user already has every letter from that sender!');

        $newLetter = new UserLetter(
            user: $user,
            letter: $nextLetter,
            comment: $comment
        );

        if($nextLetter->getAttachment())
        {
            $item = $this->inventoryService->receiveItem($nextLetter->getAttachment(), $user, null, 'This item was sent by ' . $sender->value . ', along with a letter.', LocationEnum::Home, true);

            if($nextLetter->getBonus()) $item->setEnchantment($nextLetter->getBonus());
            if($nextLetter->getSpice()) $item->setSpice($nextLetter->getSpice());
        }

        $this->em->persist($newLetter);

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::Mailbox))
            UserUnlockedFeatureHelpers::create($this->em, $user, UnlockableFeatureEnum::Mailbox);

        return $newLetter;
    }

    private function findBySenderIndex(LetterSenderEnum $sender, int $index): ?Letter
    {
        $results = $this->em->getRepository(Letter::class)->findBy(
            [ 'sender' => $sender ],
            [ 'id' => 'ASC' ],
            1,
            $index
        );

        return count($results) === 0 ? null : $results[0];
    }
}

class LetterResponse
{
    public UserLetter $letter;
    public PetActivityLog $activityLog;
}
