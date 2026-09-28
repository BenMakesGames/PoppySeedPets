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

namespace App\Service\StarKindred;

use App\Entity\Pet;
use App\Entity\StarKindredCharacter;
use App\Entity\StarKindredDailyPlay;
use App\Entity\User;
use App\Exceptions\PSPFormValidationException;
use App\Exceptions\PSPInvalidOperationException;
use App\Enum\LocationEnum;
use App\Enum\StarKindredClassEnum;
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\StarKindredRaceEnum;
use App\Enum\StarKindredSexEnum;
use App\Enum\StarKindredStatEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Functions\ArrayFunctions;
use App\Functions\GrammarFunctions;
use App\Model\PetShelterPet;
use App\Model\StarKindred\StarKindredAdventure;
use App\Model\StarKindred\StarKindredAdventureResult;
use App\Model\StarKindred\StarKindredAnimalCompanion;
use App\Model\StarKindred\StarKindredCharacterProgress;
use App\Model\StarKindred\StarKindredCheckResult;
use App\Service\Clock;
use App\Service\HattierService;
use App\Service\InventoryService;
use App\Service\IRandom;
use App\Service\UserStatsService;
use Doctrine\ORM\EntityManagerInterface;

class StarKindredAdventureService
{
    public const int MaxPartySize = 3;
    public const int MaxAdventurers = 10;
    public const string RetirementAura = 'StarKindred';
    public const array RetirementRewardsPerAdventurer = [ 'Ruby Chest', 'Cup of Life' ];

    public function __construct(
        private readonly IRandom $rng,
        private readonly Clock $clock,
        private readonly EntityManagerInterface $em,
        private readonly InventoryService $inventoryService,
        private readonly HattierService $hattierService,
        private readonly UserStatsService $userStatsService,
    )
    {
    }

    /**
     * Null if the user hasn't played yet today.
     */
    public function findTodaysPlay(User $user): ?StarKindredDailyPlay
    {
        $play = $this->em->getRepository(StarKindredDailyPlay::class)->findOneBy([ 'user' => $user ]);

        return $play?->isOn($this->clock->now) ? $play : null;
    }

    /**
     * @param string|null $adventureId Null when retiring adventurers, instead of going on an adventure
     * @throws PSPInvalidOperationException
     */
    private function markPlayedToday(User $user, ?string $adventureId): StarKindredDailyPlay
    {
        $play = $this->em->getRepository(StarKindredDailyPlay::class)->findOneBy([ 'user' => $user ]);

        if($play === null)
        {
            $play = new StarKindredDailyPlay($user, $this->clock->now, $adventureId);
            $this->em->persist($play);
            return $play;
        }

        if($play->isOn($this->clock->now))
            throw new PSPInvalidOperationException('There\'s only time for one ★Kindred adventure per day. THEM\'S JUST THE RULES.');

        $play->replay($this->clock->now, $adventureId);

        return $play;
    }

    public static function findActiveCharacter(EntityManagerInterface $em, Pet $pet): ?StarKindredCharacter
    {
        return $em->getRepository(StarKindredCharacter::class)->findOneBy([ 'pet' => $pet, 'retiredOn' => null ]);
    }

    /**
     * Counts the active (un-retired) characters played by the user's pets.
     */
    public static function countActiveCharacters(EntityManagerInterface $em, User $user): int
    {
        return (int)$em->getRepository(StarKindredCharacter::class)->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.pet', 'p')
            ->andWhere('p.owner = :user')
            ->andWhere('c.retiredOn IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Loads, and validates, a party of the user's pets' active characters.
     *
     * @param int[] $characterIds
     * @return StarKindredCharacter[]
     * @throws PSPFormValidationException
     */
    public function findParty(User $user, array $characterIds): array
    {
        $characterIds = array_values(array_unique(array_map(intval(...), $characterIds)));

        if(count($characterIds) < 1 || count($characterIds) > self::MaxPartySize)
            throw new PSPFormValidationException('A party must have between 1 and ' . self::MaxPartySize . ' adventurers.');

        /** @var StarKindredCharacter[] $party */
        $party = $this->em->getRepository(StarKindredCharacter::class)->createQueryBuilder('c')
            ->join('c.pet', 'p')
            ->andWhere('c.id IN (:ids)')
            ->andWhere('p.owner = :user')
            ->andWhere('c.retiredOn IS NULL')
            ->setParameter('ids', $characterIds)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        if(count($party) !== count($characterIds))
            throw new PSPFormValidationException('One or more of those adventurers could not be found. (Maybe reload and try again?)');

        return $party;
    }

    public function rollCharacter(Pet $pet): StarKindredCharacter
    {
        if(self::findActiveCharacter($this->em, $pet))
            throw new PSPInvalidOperationException($pet->getName() . ' already has a ★Kindred character!');

        if(self::countActiveCharacters($this->em, $pet->getOwner()) >= self::MaxAdventurers)
            throw new PSPInvalidOperationException('Your pets already have ' . self::MaxAdventurers . ' ★Kindred characters! Retire some before rolling up more.');

        // ease new players in: their first character is a race they probably already know; their second
        // is guaranteed to show them something new; after that, anything goes
        $charactersRolled = $this->userStatsService->getStatValue($pet->getOwner(), UserStat::RolledAStarKindredCharacter);

        $race = $this->rng->rngNextFromArray(match($charactersRolled) {
            0 => StarKindredRaceEnum::familiar(),
            1 => StarKindredRaceEnum::unfamiliar(),
            default => StarKindredRaceEnum::cases(),
        });

        $this->userStatsService->incrementStat($pet->getOwner(), UserStat::RolledAStarKindredCharacter);

        $class = $this->rng->rngNextFromArray(StarKindredClassEnum::cases());

        $stats = [];

        foreach(StarKindredStatEnum::cases() as $stat)
        {
            // 4d6, drop the lowest
            $dice = [ $this->rng->rngNextInt(1, 6), $this->rng->rngNextInt(1, 6), $this->rng->rngNextInt(1, 6), $this->rng->rngNextInt(1, 6) ];
            sort($dice);

            $stats[$stat->value] = max(3, $dice[1] + $dice[2] + $dice[3] + ($race->statModifiers()[$stat->value] ?? 0));
        }

        // sex only picks the name & portrait; it isn't stored
        $sex = $race->isAndrogynous() ? null : $this->rng->rngNextFromArray(StarKindredSexEnum::cases());

        $name = StarKindredNames::roll($this->rng, $race, $sex);
        $portrait = StarKindredPortraits::roll($this->rng, $race, $sex);

        $character = new StarKindredCharacter($pet, $name, $race, $class, $portrait, $stats, $this->clock->now);

        $this->em->persist($character);

        return $character;
    }

    /**
     * @param StarKindredCharacter[] $party
     */
    public function goOnAdventure(User $user, StarKindredAdventure $adventure, StarKindredDifficultyEnum $difficulty, array $party): StarKindredAdventureResult
    {
        if(array_any($party, fn(StarKindredCharacter $c) => $c->isMaxLevel()))
            throw new PSPInvalidOperationException('Level ' . StarKindredCharacter::MaxLevel . ' adventurers have nothing left to prove! The only adventure left for them is retirement.');

        $play = $this->markPlayedToday($user, $adventure->id);

        $target = $difficulty->targetPerCharacter() * count($party);
        $encountersWon = 0;

        $checks = [];
        $text = '';

        foreach($adventure->encounters as $encounter)
        {
            $total = ArrayFunctions::sum(
                $party,
                fn(StarKindredCharacter $c) => $this->rng->rngNextInt(1, 20) + $c->getSkill($encounter->skill)
            );

            $won = $total >= $target;

            $checks[] = new StarKindredCheckResult(
                $won,
                "**{$encounter->title}** ({$encounter->skill->value}: rolled {$total} vs. {$target})\\\n" .
                ($won ? $encounter->success : $encounter->failure)
            );

            if($won)
                $encountersWon++;
        }

        $victory = $encountersWon * 2 > count($adventure->encounters);
        $loot = [];
        $extraMessages = [];

        if($victory)
        {
            $rewards = $adventure->getRewardsFor($difficulty);

            $play->setRewardsWon(count($rewards));

            foreach($rewards as $reward)
            {
                if($reward->item)
                {
                    for($i = 0; $i < $reward->quantity; $i++)
                        $loot[] = $reward->item;
                }

                if($reward->aura)
                    $extraMessages[] = $this->maybeUnlockAura($this->rng->rngNextFromArray($party)->getPet(), $reward->aura);
            }

            $this->userStatsService->incrementStat($user, UserStat::WonAStarKindredAdventure);

            if($difficulty === StarKindredDifficultyEnum::Demigod)
                $this->userStatsService->incrementStat($user, UserStat::WonADemigodStarKindredAdventure);
        }
        else
        {
            $text .= "The party retreats to regroup, a little wiser for the experience.\n\n";
        }

        $this->userStatsService->incrementStat($user, UserStat::WentOnAStarKindredAdventure);

        $experience = $victory
            ? $difficulty->victoryExperience()
            : $difficulty->defeatExperience($encountersWon);

        $progress = [];
        $milestones = [];

        foreach($party as $character)
        {
            $character->recordAdventure($victory);
            $levelsGained = $character->gainExperience($experience);

            $progress[] = new StarKindredCharacterProgress(
                $character->getId(), $character->getName(), $character->getPet()->getName(),
                $experience, $levelsGained, $character->getLevel(), false
            );

            if($levelsGained > 0)
            {
                $milestone = $this->maybeGainAnimalCompanion($user, $character);

                if($milestone !== null)
                    $milestones[] = $milestone;
            }
        }

        if(count($loot) > 0)
            $text .= '(You award your pets ' . self::describeLoot($loot) . '.)';

        foreach($extraMessages as $message)
            $text .= "\n\n" . $message;

        $this->receiveLoot($user, $loot);

        return new StarKindredAdventureResult($victory, $adventure->title, $checks, $text, $loot, $progress, $milestones);
    }

    /**
     * @return string|null A message for the player (Markdown), if the character gained an animal companion
     */
    private function maybeGainAnimalCompanion(User $user, StarKindredCharacter $character): ?string
    {
        $class = $character->getCharacterClass();

        if(
            !$class->hasAnimalCompanion() ||
            $character->getLevel() < StarKindredAnimalCompanion::GainedAtLevel ||
            $character->getAnimalCompanion() !== null
        )
            return null;

        $figurine = $this->rng->rngNextFromArray(array_keys(self::AnimalCompanionFigurines));
        $species = self::AnimalCompanionFigurines[$figurine];
        $companion = new StarKindredAnimalCompanion($this->rng->rngNextFromArray(PetShelterPet::PetNames), $species);

        $character->gainAnimalCompanion($companion);

        $this->inventoryService->receiveItem(
            $figurine, $user, $user,
            "This {$species} represents {$companion->name}, the animal companion of {$character->getName()}, {$character->getPet()->getName()}'s ★Kindred {$class->value}.",
            LocationEnum::Home
        );

        return
            "{$character->getName()}, as a level-" . StarKindredAnimalCompanion::GainedAtLevel . " {$class->value}, gets an animal companion! " .
            'They chose ' . GrammarFunctions::indefiniteArticle($species) . " {$species} named {$companion->name}. " .
            '(You award your pets ' . GrammarFunctions::indefiniteArticle($figurine) . " {$figurine}, to commemorate the event!)"
        ;
    }

    /**
     * @param StarKindredCharacter[] $party
     */
    public function retire(User $user, array $party): StarKindredAdventureResult
    {
        if(array_any($party, fn(StarKindredCharacter $c) => !$c->isMaxLevel()))
            throw new PSPInvalidOperationException('Only level ' . StarKindredCharacter::MaxLevel . ' adventurers may retire, and they must all retire together.');

        $this->markPlayedToday($user, null);

        $loot = [];
        $text = '';

        foreach($party as $character)
        {
            $epilogue = $this->generateEpilogue($character);
            $character->retire($this->clock->now, $epilogue);

            $loot = [ ...$loot, ...self::RetirementRewardsPerAdventurer ];

            $text .= "**{$character->getName()}**\\\n{$epilogue}\n\n";

            $this->userStatsService->incrementStat($user, UserStat::RetiredAStarKindredAdventurer);
        }

        $text .= '(You award your pets ' . self::describeLoot($loot) . ', to remember their adventurers by.)';

        $text .= "\n\n" . $this->maybeUnlockAura($this->rng->rngNextFromArray($party)->getPet(), self::RetirementAura);

        $this->receiveLoot($user, $loot);

        $progress = array_map(
            fn(StarKindredCharacter $c) => new StarKindredCharacterProgress(
                $c->getId(), $c->getName(), $c->getPet()->getName(), 0, 0, $c->getLevel(), true
            ),
            $party
        );

        return new StarKindredAdventureResult(true, 'The Final Adventure', [], $text, $loot, $progress, []);
    }

    private function generateEpilogue(StarKindredCharacter $character): string
    {
        $fate = $this->rng->rngNextFromArray([
            'opened a cozy tavern in a quiet village, where the stew is always hot and the stories are always tall',
            'became a teacher at the Adventurers\' Academy, where students still whisper about their exploits',
            'were crowned ruler of a small, but very happy, kingdom',
            'set sail for lands unknown, and were last seen waving from the deck',
            'wrote a best-selling memoir (which only slightly exaggerates things)',
            'took up gardening, and now grow the finest pumpkins in the realm',
            'ascended to the heavens, and now shine as a new star in the night sky',
            'founded a guild for young heroes, and give every new member a copy of their old map',
            'retired to a mountain cabin, and answer every letter from adoring fans',
            'became the keeper of a great library, guarding the stories of heroes yet to come',
        ]);

        $adventures = $character->getAdventuresCompleted();
        $victories = $character->getAdventuresWon();

        return
            $character->getName() . ', ' . $character->getRace()->value . ' ' . $character->getCharacterClass()->value . ', ' .
            'hung up their ' . $character->getCharacterClass()->signatureGear() . ' after ' .
            $adventures . ' ' . ($adventures === 1 ? 'adventure' : 'adventures') . ' (' .
            $victories . ' ' . ($victories === 1 ? 'victory' : 'victories') . '). ' .
            'They ' . $fate . '.'
        ;
    }

    /**
     * @return string A message for the player, whether or not the styling was new to them
     */
    private function maybeUnlockAura(Pet $pet, string $auraName): string
    {
        $message = '★Kindred inspired ' . $pet->getName() . ' to create a new hat style!';

        $unlocked = $this->hattierService->petMaybeUnlockAura($pet, $auraName, $message, $message, $message);

        if(!$unlocked)
            return "(The adventure reminds {$pet->getName()} of the \"{$auraName}\" hat styling... but you've already got that one!)";

        if($pet->getOwner()->hasUnlockedFeature(UnlockableFeatureEnum::Hattier))
            return "(Inspired by the adventure, {$pet->getName()} created a new hat styling: \"{$auraName}\"! Find it at the Hattier!)";
        else
            return "(Inspired by the adventure, {$pet->getName()} created a new hat styling?! What!? (The Hattier has been unlocked! Check it out in the menu!))";
    }

    /**
     * @param string[] $loot
     */
    private static function describeLoot(array $loot): string
    {
        $quantities = array_count_values($loot);
        ksort($quantities);

        return ArrayFunctions::list_nice_quantities($quantities);
    }

    /**
     * @param string[] $loot
     */
    private function receiveLoot(User $user, array $loot): void
    {
        foreach($loot as $item)
            $this->inventoryService->receiveItem($item, $user, $user, $user->getName() . ' gave this to their pets during a game of ★Kindred.', LocationEnum::Home);
    }

    /**
     * Item name => companion species. ("Roy" Plushy is a special event item; Phoenix Plushy is a quest item.)
     */
    public const array AnimalCompanionFigurines = [
        'Bulbun Plushy' => 'Bulbun',
        'Peacock Plushy' => 'Peacock',
        'Rainbow Dolphin Plushy' => 'Rainbow Dolphin',
        'Sneqo Plushy' => 'Sneqo',
        'Catmouse Figurine' => 'Catmouse',
        'Tentacat Figurine' => 'Tentacat',
    ];
}
