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
use App\Entity\StatusEffect;
use App\Enum\EnumInvalidValueException;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\PetBadgeEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPInvalidOperationException;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\CalendarFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Functions\PetBadgeHelpers;
use App\Functions\UserQuestRepository;
use App\Functions\UserUnlockedFeatureHelpers;
use Doctrine\ORM\EntityManagerInterface;

class PetExperienceService
{
    public const int SocialEnergyPerHangOut = 576; // 2.5 hangouts per day (for average pets)

    public function __construct(
        private readonly IRandom $rng,
        private readonly InventoryService $inventoryService,
        private readonly UserStatsService $userStatsRepository,
        private readonly HattierService $hattierService,
        private readonly EntityManagerInterface $em,
        private readonly Clock $clock
    )
    {
    }

    /**
     * @param string[] $stats
     * @throws EnumInvalidValueException
     */
    public function gainExp(Pet $pet, int $exp, array $stats, PetActivityLog $activityLog): bool
    {
        if(count($stats) == 0) return false;

        if($pet->hasStatusEffect(StatusEffectEnum::Inspired))
            $exp++;

        if($exp < 0) return false;

        if($pet->getTool() && array_any($stats, fn(string $stat) => $pet->getTool()->focusesSkill($stat)))
        {
            $exp++;
        }

        $divideBy = 1;

        if($pet->getFood() + $pet->getAlcohol() < 0) $divideBy++;
        if($pet->getSafety() + $pet->getAlcohol() < 0) $divideBy++;
        if($pet->getLove() + $pet->getAlcohol() < 0) $divideBy++;
        if($pet->getEsteem() + $pet->getAlcohol() < 0) $divideBy++;

        $divideBy += 2 * $pet->getAlcohol() / $pet->getStomachSize();

        $exp = (int)round($exp / $divideBy);

        if($exp == 0) return false;

        $pet->increaseExperience($exp);

        $levelUp = $pet->getExperience() >= $pet->getExperienceToLevel();

        $focusStatusEffect = $levelUp ? PetExperienceService::getPetFocusingStatusEffect($pet) : null;

        $oldLevel = $pet->getLevel();

        while($pet->getExperience() >= $pet->getExperienceToLevel())
        {
            $pet->decreaseExperience($pet->getExperienceToLevel());

            if($focusStatusEffect && $pet->getSkills()->getStat($focusStatusEffect->skill) >= 10)
                $statToLevel = $focusStatusEffect->skill;
            else
                $statToLevel = $this->rng->rngNextFromArray($stats);

            // only remove a Focused status effect if the focused stat was leveled-up
            if($focusStatusEffect && $focusStatusEffect->skill == $statToLevel)
            {
                $pet->removeStatusEffect($focusStatusEffect->statusEffect);
                $focusStatusEffect = null;
            }

            if($pet->getSkills()->getStat($statToLevel) >= 20)
            {
                $pet->getSkills()->increaseScrollLevels();
                $this->userStatsRepository->incrementStat($pet->getOwner(), 'Skill Scrolls Made by Pets');

                $newItem = $this->inventoryService->petCollectsItem('Skill Scroll: ' . $statToLevel, $pet, $pet->getName() . ', a ' . $statToLevel . '-master, produced this scroll.', $activityLog);
                $newItem->setLockedToOwner(true);

                PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::ProducedASkillScroll, $activityLog);
            }
            else
            {
                $pet->getSkills()->increaseStat($statToLevel);

                $activityLog
                    ->appendEntry(ActivityHelpers::PetName($pet) . ' leveled up! +1 ' . ucfirst($statToLevel) . '!')
                    ->addInterestingness(PetActivityLogInterestingness::LevelUp)
                ;
            }
        }

        if($levelUp)
            $activityLog->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Level-up' ]));

        $this->checkForLevelUpBadges($pet, $oldLevel, $activityLog);

        return $levelUp;
    }

    public function forceIncreaseSkill(Pet $pet, string $skill, int $amount, PetActivityLog $activityLog): void
    {
        if(!PetSkillEnum::isAValue($skill))
            throw new EnumInvalidValueException(PetSkillEnum::class, $skill);

        if($pet->getSkills()->getStat($skill) >= 20)
            throw new PSPInvalidOperationException('Pet skill ' . $skill . ' is already at max level.');

        $oldLevel = $pet->getLevel();

        for($i = 0; $i < $amount; $i++)
            $pet->getSkills()->increaseStat($skill);

        $activityLog
            ->appendEntry(ActivityHelpers::PetName($pet) . '\'s ' . ucfirst($skill) . ' has increased to ' . $pet->getSkills()->getStat($skill) . '!')
            ->addInterestingness(PetActivityLogInterestingness::LevelUp)
            ->addTags([ PetActivityLogTagHelpers::findOneByName($this->em, 'Level-up') ])
        ;

        $this->checkForLevelUpBadges($pet, $oldLevel, $activityLog);
    }

    private function checkForLevelUpBadges(Pet $pet, int $oldLevel, PetActivityLog $activityLog): void
    {
        if($oldLevel < 20 && $pet->getLevel() >= 20) PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::Level20, $activityLog);
        if($oldLevel < 40 && $pet->getLevel() >= 40) PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::Level40, $activityLog);
        if($oldLevel < 50 && $pet->getLevel() >= 50) $this->unlockLevel50Style($pet);
        if($oldLevel < 60 && $pet->getLevel() >= 60) PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::Level60, $activityLog);
        if($oldLevel < 80 && $pet->getLevel() >= 80) PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::Level80, $activityLog);
        if($oldLevel < 100 && $pet->getLevel() >= 100) PetBadgeHelpers::awardBadge($this->em, $pet, PetBadgeEnum::Level100, $activityLog);
    }

    private static function getPetFocusingStatusEffect(Pet $pet): ?FocusingStatusEffect
    {
        $possibleEffects = [
            [ PetSkillEnum::Brawl, StatusEffectEnum::FocusedBrawl ],
            [ PetSkillEnum::Nature, StatusEffectEnum::FocusedNature ],
            [ PetSkillEnum::Crafts, StatusEffectEnum::FocusedCrafts ],
            [ PetSkillEnum::Stealth, StatusEffectEnum::FocusedStealth ],
            [ PetSkillEnum::Science, StatusEffectEnum::FocusedScience ],
            [ PetSkillEnum::Music, StatusEffectEnum::FocusedMusic ],
            [ PetSkillEnum::Arcana, StatusEffectEnum::FocusedArcana ],
        ];

        foreach($possibleEffects as $effect)
        {
            $statusEffect = $pet->getStatusEffect($effect[1]);

            if($statusEffect)
                return new FocusingStatusEffect($effect[0], $effect[1]);
        }

        return null;
    }

    private function unlockLevel50Style(Pet $pet): void
    {
        $this->hattierService->petMaybeUnlockAura(
            $pet,
            'Impactful',
            '%pet:' . $pet->getId() . '.name% has reached level 50?! Incredible. Truly _Wow!_',
            '%pet:' . $pet->getId() . '.name% has reached level 50?! Incredible. Truly _Wow!_',
            ActivityHelpers::PetName($pet) . ' has reached level 50! Incredible. Truly _Wow!_ (And I\'m sure the Hattier would agree!)'
        );
    }

    public function spendSocialEnergy(Pet $pet, int $energy): void
    {
        if($pet->hasStatusEffect(StatusEffectEnum::ExtraExtroverted) || $pet->hasStatusEffect(StatusEffectEnum::Moonstruck))
            $energy = (int)ceil($energy / 2);

        if($this->rng->rngNextInt(1, 10) === 1)
        {
            // smallish chance to consume WAY less energy. this was added to help jiggle pets out of a situation where
            // two pets owned by the same account are always offset in social energy such that they're never able to hang
            // out with each other.
            $energy = $this->rng->rngNextInt((int)ceil($energy / 4), (int)ceil($energy * 3 / 4));
        }
        else
        {
            // always add a LITTLE random jiggle, though:
            $energy = $this->rng->rngNextInt((int)ceil($energy * 8 / 10), (int)ceil($energy * 12 / 10));
        }

        // tool modifiers (if any)
        $socialEnergyModifier = $pet->getTool() ? $pet->getTool()->socialEnergyModifier() : 0;

        // a small drift based on number of extra friends a pet can have; more friends = less energy cost
        $socialEnergyModifier -= $pet->getBonusMaximumFriends() * 5;

        // introverted pets spend more social energy; extroverted pets spend less
        if($pet->getExtroverted() < 0)
            $socialEnergyModifier += 20;
        else if($pet->getExtroverted() > 0)
            $socialEnergyModifier -= 20;

        $energy = (int)round($energy * (100 + $socialEnergyModifier) / 100);

        if($energy < 0)
            throw new \Exception('Somehow, the game tried to spend negative social energy. This is bad, and Ben should fix it.');

        $pet->getHouseTime()->spendSocialEnergy($energy);
    }

    /**
     * spendTime should be called AFTER gainExp
     * @throws EnumInvalidValueException
     */
    public function spendTime(Pet $pet, int $time, string $activityStat, ?bool $success): void
    {
        $pet->getHouseTime()->spendActivityTime($time);
        PetActivityStatsService::logStat($this->em, $pet, $activityStat, $success, $time);

        if($pet->getPregnancy())
            $pet->getPregnancy()->increaseGrowth($time);

        self::spendTimeOnStatusEffects($pet, $time);
    }

    public static function spendTimeOnStatusEffects(Pet $pet, int $time): void
    {
        /** @var StatusEffect[] $statusEffects */
        $statusEffects = array_values($pet->getStatusEffects()->toArray());

        for($i = count($statusEffects) - 1; $i >= 0; $i--)
        {
            $statusEffects[$i]->spendTime($time);

            // some status effects TRANSFORM when they run out (like caffeinated -> tired)
            if($statusEffects[$i]->getTimeRemaining() <= 0)
            {
                if($statusEffects[$i]->getStatus() === StatusEffectEnum::Caffeinated)
                {
                    $newTotal = (int)ceil($statusEffects[$i]->getTotalDuration() / 2);
                    $statusEffects[$i]
                        ->setStatus(StatusEffectEnum::Tired)
                        ->setTimeRemaining($statusEffects[$i]->getTimeRemaining() + $newTotal)
                        ->setTotalDuration($newTotal)
                    ;
                }
            }

            // if the status effect didn't transform (or was still out of time AFTER transforming), then delete it
            if($statusEffects[$i]->getTimeRemaining() <= 0)
                $pet->removeStatusEffect($statusEffects[$i]);
        }
    }

    /**
     * @throws EnumInvalidValueException
     */
    public function gainAffection(Pet $pet, int $points): void
    {
        if($points === 0 || $pet->hasMerit(MeritEnum::AFFECTIONLESS))
            return;

        $divideBy = 1;

        if($pet->getFood() + $pet->getAlcohol() < 0) $divideBy++;
        if($pet->getSafety() + $pet->getAlcohol() < 0) $divideBy++;

        $points = (int)ceil($points / $divideBy);

        if($points == 0) return;

        $previousAffectionLevel = $pet->getAffectionLevel();

        $pet->increaseAffectionPoints($points);

        // if a pet's affection level increased, and you haven't unlocked the park, now you get the park!
        if($pet->getAffectionLevel() > $previousAffectionLevel && !$pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Park))
            UserUnlockedFeatureHelpers::create($this->em, $pet->getOwner(), UnlockableFeatureEnum::Park);

        if(CalendarFunctions::isValentinesOrAdjacent($this->clock->now))
            $this->maybeGivePlayerTwuWuv($pet);
    }

    private function maybeGivePlayerTwuWuv(Pet $pet): bool
    {
        $alreadyReceived = UserQuestRepository::findOrCreate($this->em, $pet->getOwner(), 'Valentines ' . date('Y-m-d'), false);

        if($alreadyReceived->getValue())
            return false;

        $this->inventoryService->receiveItem('Twu Wuv', $pet->getOwner(), $pet->getOwner(), $pet->getOwner()->getName() . ' received this from ' . $pet->getName() . ' for Valentine\'s Day!', LocationEnum::Home, true);
        $this->inventoryService->receiveItem('Twu Wuv', $pet->getOwner(), $pet->getOwner(), $pet->getOwner()->getName() . ' received this from ' . $pet->getName() . ' for Valentine\'s Day!', LocationEnum::Home, true);

        PetActivityLogFactory::createUnreadLog($this->em, $pet, '%pet:' . $pet->getId() . '.name% gave Twu Wuv to %user:' . $pet->getOwner()->getId() . '.Name% for Valentine\'s Day! (Two of them! Two Twu Wuvs!)')
            ->setIcon('items/resource/twu-wuv')
            ->addInterestingness(PetActivityLogInterestingness::HolidayOrSpecialEvent)
            ->addTags(PetActivityLogTagHelpers::findByNames($this->em, [ 'Special Event', 'Valentine\'s' ]))
        ;

        $alreadyReceived->setValue(true);

        return true;
    }
}

class FocusingStatusEffect
{
    function __construct(
        public readonly string $skill,
        public readonly StatusEffectEnum $statusEffect
    )
    {
    }
}