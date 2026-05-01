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
use App\Entity\PetActivityLog;
use App\Entity\PetGroup;
use App\Entity\PetRelationship;
use App\Entity\PetSkills;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetGroupTypeEnum;
use App\Enum\RelationshipEnum;
use App\Functions\ArrayFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\ComputedPetSkills;
use App\Model\PetChanges;
use App\Service\PetActivity\Group\AstronomyClubService;
use App\Service\PetActivity\Group\BandService;
use App\Service\PetActivity\Group\GamingGroupService;
use App\Service\PetActivity\Group\GardeningClubService;
use App\Service\PetActivity\Group\SportsBallService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class PetGroupService
{
    public const int SocialEnergyPerMeet = 60 * 12;

    public const array GroupTypeNames = [
        PetGroupTypeEnum::Band->value => 'band',
        PetGroupTypeEnum::Astronomy->value => 'astronomy lab',
        PetGroupTypeEnum::Gaming->value => 'gaming group',
        PetGroupTypeEnum::Sportsball->value => 'sportsball team',
        PetGroupTypeEnum::Gardening->value => 'gardening club',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PetExperienceService $petExperienceService,
        private readonly BandService $bandService,
        private readonly AstronomyClubService $astronomyClubService,
        private readonly IRandom $rng,
        private readonly GamingGroupService $gamingGroupService,
        private readonly SportsBallService $sportsBallService,
        private readonly GardeningClubService $gardeningClubService
    )
    {
    }

    public function doGroupActivity(PetGroup $group): void
    {
        $group->spendSocialEnergy(PetGroupService::SocialEnergyPerMeet);

        if($this->checkForSplitUp($group))
            return;

        if($this->checkForRecruitment($group))
            return;

        switch ($group->getType())
        {
            case PetGroupTypeEnum::Band:
                $this->bandService->meet($group);
                break;

            case PetGroupTypeEnum::Astronomy:
                $this->astronomyClubService->meet($group);
                break;

            case PetGroupTypeEnum::Gaming:
                $this->gamingGroupService->meet($group);
                break;

            case PetGroupTypeEnum::Sportsball:
                $this->sportsBallService->meet($group);
                break;

            case PetGroupTypeEnum::Gardening:
                $this->gardeningClubService->meet($group);
                break;

            default:
                throw new \Exception('Unhandled group type "' . $group->getType()->name . '"');
        }
    }

    public function getMemberHappiness(PetGroup $group, Pet $pet): int
    {
        // array_reduce is NOT easier to read (and doesn't seem more CPU-efficient, especially since we have to convert toArray())
        $happiness = $pet->getEsteem();

        foreach($group->getMembers() as $member)
        {
            if($member->getId() === $pet->getId()) continue;

            $relationship = $pet->getRelationshipWith($member);

            if($relationship === null) continue;

            $happiness += $relationship->getHappiness();
        }

        return $happiness;
    }

    private function checkForSplitUp(PetGroup $group): bool
    {
        $unhappyMembers = [];

        foreach($group->getMembers() as $member)
        {
            $happiness = $this->getMemberHappiness($group, $member) + $this->rng->rngNextInt(-500, 500) / 100;

            if($happiness < 0)
            {
                $unhappyMembers[] = [
                    'pet' => $member,
                    'happiness' => $happiness
                ];
            }
        }

        if(count($unhappyMembers) === 0)
            return false;

        // sort by happiness, ascending
        if(count($unhappyMembers) > 1)
            usort($unhappyMembers, fn($a, $b) => $a['happiness'] <=> $b['happiness']);

        /** @var Pet $unhappiestPet */
        $unhappiestPet = $unhappyMembers[0]['pet'];

        $userIdsMessaged = [];

        foreach($group->getMembers() as $member)
        {
            $changes = new PetChanges($member);

            if($member->getId() === $unhappiestPet->getId())
            {
                $member->increaseEsteem(-$this->rng->rngNextInt(2, 4));
            }
            else
            {
                $r = $member->getRelationshipWith($unhappiestPet);

                if($r && $r->getHappiness() < 0)
                    $member->increaseSafety($this->rng->rngNextInt(2, 4));
                else
                    $member->increaseLove(-$this->rng->rngNextInt(2, 4));
            }

            $message = count($group->getMembers()) === 1
                ? ($unhappiestPet->getName() . ' abandoned ' . $group->getName() . '...')
                : ($unhappiestPet->getName() . ' left ' . $group->getName() . '...')
            ;

            // if the group has many pets from the same house, we should mark subsequent messages
            // as viewed, so we don't spam the player.
            $alreadyMessagedThisPlayer = in_array($member->getOwner()->getId(), $userIdsMessaged);

            if(!$alreadyMessagedThisPlayer)
                $userIdsMessaged[] = $member->getOwner()->getId();

            $log = $alreadyMessagedThisPlayer
                ? PetActivityLogFactory::createReadLog($this->em, $member, $message)
                : PetActivityLogFactory::createUnreadLog($this->em, $member, $message);

            $log
                ->setChanges($changes->compare($member))
                ->addInterestingness(PetActivityLogInterestingness::RelationshipDiscussion)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout' ]))
            ;
        }

        $unhappiestPet->removeGroup($group);

        if(count($group->getMembers()) === 0)
            $this->em->remove($group);

        return true;
    }

    private function checkForRecruitment(PetGroup $group): bool
    {
        $numMembers = count($group->getMembers());

        // if the group is too big, DEFINITELY don't recruit
        if($numMembers >= $group->getMaximumSize())
            return false;

        // if the group is not in danger of disbanding, there's a large chance of NOT recruiting
        if($numMembers >= $group->getMinimumSize() && $this->rng->rngNextInt(1, $numMembers * 20) > 1)
            return false;

        /** @var Pet[] $recruits */
        $recruits = $this->em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('p2')
            ->distinct(true)
            ->leftJoin(PetRelationship::class, 'r', Join::WITH, 'r.pet = p.id')
            ->leftJoin(Pet::class, 'p2', Join::WITH, 'r.relationship = p2.id AND p2.id NOT IN (:groupMembers)')
            ->leftJoin(PetRelationship::class, 'r2', Join::WITH, 'r2.pet = p2.id AND r2.relationship IN (:groupMembers)')
            ->leftJoin(PetSkills::class, 'p2s', Join::WITH, 'p2.skills = p2s.id')
            ->andWhere('r.currentRelationship NOT IN (:unhappyRelationships)')
            ->andWhere('p.id IN (:groupMembers)')
            ->andWhere('r2.currentRelationship NOT IN (:unhappyRelationships)')
            ->orderBy('p2s.music', 'DESC')
            ->setParameter('groupMembers', $group->getMembers()->map(fn(Pet $p) => $p->getId()))
            ->setParameter('unhappyRelationships', [ RelationshipEnum::BrokeUp, RelationshipEnum::Dislike ])
            ->getQuery()
            ->execute()
        ;

        $recruits = array_filter($recruits, function(Pet $p) {
            return count($p->getGroups()) < $p->getMaximumGroups();
        });

        if(count($recruits) > 0)
        {
            $this->recruitMember($group, $recruits[array_key_first($recruits)]);

            return true;
        }

        // if you failed to recruit, and you don't have enough members, the group might disband
        if(count($group->getMembers()) === 1 || (count($group->getMembers()) < $group->getMinimumSize() && $this->rng->rngNextInt(1, 2) === 1))
        {
            $this->disbandGroup($group);

            return true;
        }

        $usersAlerted = [];

        foreach($group->getMembers() as $member)
        {
            $message = $group->getName() . ' tried to recruit another member, but couldn\'t find anyone.';

            if(count($group->getMembers()) < $group->getMinimumSize())
                $message .= ' They decided to try again, later...';

            // if the group has many pets from the same house, we should mark subsequent messages
            // as viewed, so we don't spam the player.
            $alreadyMessagedThisPlayer = in_array($member->getOwner()->getId(), $usersAlerted);

            if(!$alreadyMessagedThisPlayer)
                $usersAlerted[] = $member->getOwner()->getId();

            $log = $alreadyMessagedThisPlayer
                ? PetActivityLogFactory::createReadLog($this->em, $member, $message)
                : PetActivityLogFactory::createUnreadLog($this->em, $member, $message);

            $log
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout' ]))
            ;
        }

        return true;
    }

    private function disbandGroup(PetGroup $group): void
    {
        foreach($group->getMembers() as $member)
        {
            $changes = new PetChanges($member);

            $member
                ->removeGroup($group)
                ->increaseEsteem(-$this->rng->rngNextInt(4, 8))
                ->increaseLove(-$this->rng->rngNextInt(2, 4))
            ;

            PetActivityLogFactory::createUnreadLog($this->em, $member, $group->getName() . ' tried to recruit another member, but couldn\'t find anyone. They decided to disband :(')
                ->setChanges($changes->compare($member))
                ->addInterestingness(PetActivityLogInterestingness::RelationshipDiscussion)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout' ]))
            ;
        }

        $this->em->remove($group);
    }

    private function recruitMember(PetGroup $group, Pet $recruit): void
    {
        $usersAlerted = [];

        $recruit->addGroup($group);

        foreach($group->getMembers() as $member)
        {
            $changes = new PetChanges($member);

            $member
                ->increaseLove($this->rng->rngNextInt(2, 4))
                ->increaseEsteem($this->rng->rngNextInt(2, 4))
            ;

            if($member->getId() === $recruit->getId())
            {
                $message = $member->getName() . ' was invited to join ' . $group->getName() . '! They accepted!';
            }
            else
            {
                $message = $group->getName() . ' invited ' . $recruit->getName() . ' to join; they accepted!';

                // if the group was at risk of disbanding, a special message, and the pets feel extra good about it
                if(count($group->getMembers()) === $group->getMinimumSize())
                {
                    $message .= ' ' . $group->getName() . ' is saved!';

                    $member
                        ->increaseEsteem($this->rng->rngNextInt(2, 4))
                        ->increaseSafety($this->rng->rngNextInt(2, 4))
                    ;
                }
            }

            $alreadyMessagedThisPlayer = in_array($member->getOwner()->getId(), $usersAlerted);

            if(!$alreadyMessagedThisPlayer)
                $usersAlerted[] = $member->getOwner()->getId();

            $log = $alreadyMessagedThisPlayer
                ? PetActivityLogFactory::createReadLog($this->em, $member, $message)
                : PetActivityLogFactory::createUnreadLog($this->em, $member, $message);

            $log
                ->setChanges($changes->compare($member))
                ->addInterestingness(PetActivityLogInterestingness::RareActivity)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout' ]))
            ;
        }
    }

    private static function weightSkill(int $skill): int
    {
        if($skill < 5)
            return 0;
        else if($skill < 10)
            return 1;
        else if($skill < 17)
            return 2;
        else
            return 3;
    }

    private const array FriendlyRelationships = [
        RelationshipEnum::Friend,
        RelationshipEnum::BFF,
        RelationshipEnum::FWB,
        RelationshipEnum::Mate
    ];

    /**
     * @return Pet[]
     */
    private static function findFriendsWithFewGroups(Pet $pet): array
    {
        $relationshipsWithFewGroups = array_filter(
            $pet->getPetRelationships()->toArray(),
            function(PetRelationship $r) use($pet)
            {
                $otherSide = $r->getRelationship()->getRelationshipWith($pet);

                return
                    //
                    $r->getCurrentRelationship() !== RelationshipEnum::BrokeUp &&

                    // as long as both pets WANT a friendly relationship, they'll do this
                    $otherSide &&
                    in_array($otherSide->getRelationshipGoal(), self::FriendlyRelationships) &&
                    in_array($r->getRelationshipGoal(), self::FriendlyRelationships) &&

                    // the pets involved must not already have too many group commitments
                    $r->getRelationship()->getGroups()->count() < $r->getRelationship()->getMaximumGroups()
                ;
            }
        );

        return array_map(function(PetRelationship $p) { return $p->getRelationship(); }, $relationshipsWithFewGroups);
    }

    public function createGroup(Pet $pet): ?PetGroup
    {
        /** @var list<ComputedPetSkills> $availableFriends */
        $availableFriends = array_values(array_map(function(Pet $pet) {
            return $pet->getComputedSkills();
        }, self::findFriendsWithFewGroups($pet)));

        // the more groups you're in, the more friends you need to start another group
        // (reduces the chances of having duplicate-member groups)
        if(count($availableFriends) < 2 + count($pet->getGroups()) * 2)
            return null;

        $groupTypePreferences = [
            [
                'type' => PetGroupTypeEnum::Band,
                'description' => self::GroupTypeNames[PetGroupTypeEnum::Band->value],
                'icon' => 'groups/band',
                'preference' => 2 + PetGroupService::weightSkill($pet->getSkills()->getMusic()),
            ],
            [
                'type' => PetGroupTypeEnum::Astronomy,
                'description' => self::GroupTypeNames[PetGroupTypeEnum::Astronomy->value],
                'icon' => 'groups/astronomy',
                'preference' => 2 + PetGroupService::weightSkill($pet->getSkills()->getScience()),
            ],
            [
                'type' => PetGroupTypeEnum::Gaming,
                'description' => self::GroupTypeNames[PetGroupTypeEnum::Gaming->value],
                'icon' => 'groups/gaming',
                'preference' => 1 + ($pet->getExtroverted() + 1) * 2,
            ],
            [
                'type' => PetGroupTypeEnum::Sportsball,
                'description' => self::GroupTypeNames[PetGroupTypeEnum::Sportsball->value],
                'icon' => 'groups/sportsball',
                'preference' => 2 + PetGroupService::weightSkill($pet->getSkills()->getBrawl()),
            ],
            [
                'type' => PetGroupTypeEnum::Gardening,
                'description' => self::GroupTypeNames[PetGroupTypeEnum::Gardening->value],
                'icon' => 'groups/gardening',
                'preference' => 2 + PetGroupService::weightSkill($pet->getSkills()->getNature()),
            ],
        ];

        $groupType = ArrayFunctions::pick_one_weighted($groupTypePreferences, fn($t) => $t['preference']);
        $type = $groupType['type'];

        $group = new PetGroup(type: $type, name: $this->generateName($type));

        $this->em->persist($group);

        $pet->addGroup($group);

        switch($type)
        {
            case PetGroupTypeEnum::Band:
                usort($availableFriends, function (ComputedPetSkills $a, ComputedPetSkills $b) {
                    return $b->getMusic()->getTotal() <=> $a->getMusic()->getTotal();
                });
                break;

            case PetGroupTypeEnum::Astronomy:
                usort($availableFriends, function (ComputedPetSkills $a, ComputedPetSkills $b) {
                    return $b->getScience()->getTotal() <=> $a->getScience()->getTotal();
                });
                break;

            case PetGroupTypeEnum::Sportsball:
                usort($availableFriends, function (ComputedPetSkills $a, ComputedPetSkills $b) {
                    return $b->getBrawl()->getTotal() + $b->getStealth()->getTotal() / 2 <=> $a->getBrawl()->getTotal() + $b->getStealth()->getTotal() / 2;
                });
                break;

            case PetGroupTypeEnum::Gardening:
                usort($availableFriends, function (ComputedPetSkills $a, ComputedPetSkills $b) {
                    return $b->getNature()->getTotal() <=> $a->getNature()->getTotal();
                });
                break;

            // gaming groups are totally random

            default:
                $this->rng->rngNextShuffle($availableFriends);
        }

        $friendsToInvite = array_slice($availableFriends, 0, min(count($availableFriends), $this->rng->rngNextInt(2, $this->rng->rngNextInt(3, 4))));
        $friendNames = array_map(fn(ComputedPetSkills $p) => $p->getPet()->getName(), $friendsToInvite);

        foreach($friendsToInvite as $friend)
        {
            $friendPet = $friend->getPet();
            $friendPet->addGroup($group);

            PetActivityLogFactory::createUnreadLog($this->em, $friendPet, $friendPet->getName() . ' was invited to join ' . $pet->getName() . '\'s new ' . self::GroupTypeNames[$type->value] . ', ' . $group->getName() . '!')
                ->addInterestingness(PetActivityLogInterestingness::NewRelationship)
                ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Group Hangout' ]))
            ;
        }

        $this->petExperienceService->spendSocialEnergy($pet, PetExperienceService::SocialEnergyPerHangOut);

        PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% started a new ' . $groupType['description'] . ' with ' . ArrayFunctions::list_nice($friendNames) . '.')
            ->setIcon($groupType['icon']);

        return $group;
    }

    public function generateName(PetGroupTypeEnum $type): string
    {
        return match ($type)
        {
            PetGroupTypeEnum::Band => $this->bandService->generateGroupName(),
            PetGroupTypeEnum::Astronomy => $this->astronomyClubService->generateGroupName(),
            PetGroupTypeEnum::Gaming => $this->gamingGroupService->generateGroupName(),
            PetGroupTypeEnum::Sportsball => $this->sportsBallService->generateGroupName(),
            PetGroupTypeEnum::Gardening => $this->gardeningClubService->generateGroupName(),
        };
    }
}
