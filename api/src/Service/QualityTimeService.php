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

use App\Entity\FieldGuideEntry;
use App\Entity\Pet;
use App\Entity\User;
use App\Enum\PetActivityLogTagEnum;
use App\Enum\PetLocationEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Exceptions\PSPInvalidOperationException;
use App\Functions\ActivityHelpers;
use App\Functions\ArrayFunctions;
use App\Functions\CalendarFunctions;
use App\Functions\PetActivityLogFactory;
use App\Functions\PetActivityLogTagHelpers;
use App\Model\PetChanges;
use App\Model\WeatherSky;
use Doctrine\ORM\EntityManagerInterface;

class QualityTimeService
{
    public function __construct(
        private readonly PetExperienceService $petExperienceService,
        private readonly CravingService $cravingService,
        private readonly EntityManagerInterface $em,
        private readonly UserStatsService $userStatsRepository,
        private readonly IRandom $rng,
        private readonly Clock $clock,
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

        $qualityTime = $this->getRandomQualityTimeDescription($user, $pets);

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
    private function getRandomQualityTimeDescription(User $user, array $pets): QualityTimeResult
    {
        $sky = WeatherService::getSky($this->clock->now);

        $possibleMessages = [
            $this->playHideAndSeek($user, $pets),
            $this->playTag($user, $pets),
            $this->readAStory($user, $pets),
            $this->practiceTricks($user, $pets),
            $this->bakeCookies($user, $pets),
            $this->playCharades($user, $pets),
            $this->stretchTogether($user, $pets),
        ];

        if($sky !== WeatherSky::Stormy)
        {
            $possibleMessages[] = $this->goFishing($user, $pets);
        }

        if($sky === WeatherSky::Clear || $sky === WeatherSky::Cloudy)
        {
            $possibleMessages[] = $this->exploreTidePools($user, $pets);
        }

        if($sky === WeatherSky::Clear)
        {
            $possibleMessages[] = $this->stargaze($user, $pets);
            $possibleMessages[] = $this->goOnAWalk($user, $pets);
        }

        if(CalendarFunctions::isApricotFestival($this->clock->now))
        {
            $possibleMessages[] = $this->makeApricotPies($user, $pets);
        }

        if(CalendarFunctions::isHalloweenCrafting($this->clock->now))
        {
            if($this->rng->rngNextInt(1, 7) === 1)
                $possibleMessages[] = $this->carveGourds($user, $pets);
        }

        if(CalendarFunctions::isStockingStuffingSeason($this->clock->now))
        {
            if($this->rng->rngNextInt(1, 6) === 1)
                $possibleMessages[] = $this->decorateTheHouseForStockingStuffingSeason($user, $pets);
        }

        return $this->rng->rngNextFromArray($possibleMessages);
    }

    /**
     * @param Pet[] $pets
     */
    private function carveGourds(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $petNames = ArrayFunctions::list_nice($petNamesList);

        $message = ActivityHelpers::UserName($user, true) . " carved gourds with $petNames.";

        $forWho = count($pets) === 1 ? 'the both of you' : 'for everyone';

        if($user->getCookingBuddy())
            $message .= ' ' . ActivityHelpers::UserName($user, true) . ' and ' . $user->getCookingBuddy()->getName() . ' roasted the seeds for ' . $forWho . ' to eat!';
        else
            $message .= ' ' . ActivityHelpers::UserName($user, true) . ' roasted the seeds to for ' . $forWho . ' eat!';

        return new QualityTimeResult($message, foodBased: true);
    }

    /**
     * @param Pet[] $pets
     */
    private function decorateTheHouseForStockingStuffingSeason(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $petNames = ArrayFunctions::list_nice($petNamesList);

        $possibleLocations = [
            'the kitchen',
            'the living room',
        ];

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Fireplace))
            $possibleLocations[] = 'the Fireplace mantle';

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::DragonDen))
            $possibleLocations[] = 'the Dragon Den';

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Greenhouse))
            $possibleLocations[] = 'the Greenhouse';

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::HollowEarth))
            $possibleLocations[] = 'the door to the Hollow Earth';

        if($user->getBeehive())
            $possibleLocations[] = 'the Beehive while Queen ' . $user->getBeehive()->getQueenName() . ' looked on with curiosity';

        $location = $this->rng->rngNextFromArray($possibleLocations);

        $message = ActivityHelpers::UserName($user, true) . " made festive decorations with $petNames for $location.";

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function goFishing(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $petNames = ArrayFunctions::list_nice($petNamesList);

        $message = ActivityHelpers::UserName($user, true) . " went fishing with $petNames.";

        $randomPet = $this->rng->rngNextFromArray($pets);

        $message .= ' ' . ActivityHelpers::PetName($randomPet) . " caught a huge fish, ";

        if(count($petNamesList) === 1)
            $message .= "which you cooked for the both of you.";
        else
            $message .= "which you cooked for the group.";

        return new QualityTimeResult($message, foodBased: true);
    }

    /**
     * @param Pet[] $pets
     */
    private function makeApricotPies(User $user, array $pets): QualityTimeResult
    {
        $petNames = ArrayFunctions::list_nice(
            array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets)
        );

        return new QualityTimeResult(
            ActivityHelpers::UserName($user) . " made little apricot pies with $petNames.",
            foodBased: true
        );
    }

    /**
     * @param Pet[] $pets
     */
    private function playHideAndSeek(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames played hide-and-seek in the house.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $possibleHidingDescriptions = [
            "$randomPet was an exceptional seeker."
        ];

        if($user->getFireplace() && $user->getFireplace()->getHeat() === 0)
            $possibleHidingDescriptions[] = "$randomPet hid themselves inside the fireplace chimney!";
        else if($user->hasUnlockedFeature(UnlockableFeatureEnum::Basement))
            $possibleHidingDescriptions[] = "It took forever for anyone to find $randomPet, who had hid themselves behind some boxes in the basement.";
        else if($user->hasUnlockedFeature(UnlockableFeatureEnum::DragonDen))
            $possibleHidingDescriptions[] = "$randomPet buried themselves in some gold coins at the foot of your dragon and hid there successfully for some time!";
        else if($user->getCookingBuddy()?->getAppearance() === 'robot/mega-cooking')
        {
            if(count($petNamesList) === 1)
                $possibleHidingDescriptions[] = "It didn't occur to you to check behind your giant Cooking Buddy for forever, which is exactly where $randomPet was hiding!";
            else
                $possibleHidingDescriptions[] = "It didn't occur to anyone to check behind the giant Cooking Buddy for forever, which is exactly where $randomPet was hiding!";
        }

        $message .= "\n\n" . $this->rng->rngNextFromArray($possibleHidingDescriptions);

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    public function playTag(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames played tag for a while.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        if($this->rng->rngNextBool())
            $message .= " $randomPet avoided being \"it\" the WHOLE time!";
        else
            $message .= " $randomPet ended up being \"it\" for most of the game!";

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function readAStory(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames curled up and read a story together.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $endings = [
            "$randomPet kept asking for just one more chapter.",
            "$randomPet fell asleep halfway through, snoring softly.",
            "($randomPet provided sound effects!)",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($endings);

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function bakeCookies(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);

        $cookingBuddy = $user->getCookingBuddy();

        if($cookingBuddy)
        {
            $petNames = ArrayFunctions::list_nice([
                $cookingBuddy->getName(),
                ...$petNamesList
            ]);

            $message = ActivityHelpers::UserName($user, true) . " baked cookies with $petNames.";
        }
        else
        {
            $petNames = ArrayFunctions::list_nice($petNamesList);

            $message = ActivityHelpers::UserName($user, true) . " baked cookies with $petNames.";
        }

        if(count($petNamesList) === 1)
            $message .= " You both enjoyed them warm from the oven.";
        else
            $message .= " Everyone enjoyed them warm from the oven.";

        return new QualityTimeResult($message, foodBased: true);
    }

    /**
     * @param Pet[] $pets
     */
    private function practiceTricks(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $tricks = [
            "$randomPet finally mastered a perfect twirl!",
            "$randomPet almost did a backflip, then burst into proud wiggles instead.",
            "$randomPet learned to boop on command.",
            "$randomPet showed everyone a new dance step.",
        ];

        $message = "$everyonesNames practiced tricks in the living room. " . $this->rng->rngNextFromArray($tricks);

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function stargaze(User $user, array $pets): QualityTimeResult
    {
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets)
        ]);

        $constellations = [
            'a teacup constellation',
            'a sleepy dragon constellation',
            'a cookie-shaped constellation',
            'a heart made of stars',
        ];

        $message = "$everyonesNames went outside to stargaze.";
        $message .= ' You all spotted ' . $this->rng->rngNextFromArray($constellations) . '.';

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function playCharades(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);

        $petNames = ArrayFunctions::list_nice($petNamesList);

        $message = ActivityHelpers::UserName($user, true) . " played charades with $petNames. ";

        $pet = $this->rng->rngNextFromArray($pets);

        $possibleObjects = [
            'a tasseled wobbegong',
            'a spiny lumpsucker',
            'a doux-rêve ouvre-boîte',
            'a pleasing fungus beetle',
            'a satanic leaf-tailed gecko',
            'a bone-eating snot flower worm',
            'a sparklemuffin peacock spider',
            'a magical liopleurodon',
        ];

        if($user->hasFieldGuideEntry('Cosmic Goat'))
            $possibleObjects[] = 'the Cosmic Goat';

        if($user->hasFieldGuideEntry('Huge Toad'))
            $possibleObjects[] = 'a Huge Toad';

        if($user->hasFieldGuideEntry('Nang Tani'))
            $possibleObjects[] = 'Nang Tani';

        if($user->hasFieldGuideEntry('Infinity Imp'))
            $possibleObjects[] = 'an Infinity Imp';

        if($user->hasFieldGuideEntry('Drizzly Bear'))
            $possibleObjects[] = 'a Drizzly Bear';

        $object = $this->rng->rngNextFromArray($possibleObjects);

        $message .= ActivityHelpers::PetName($pet) . " tried miming $object, but ";

        if(count($pets) === 1)
            $message .= ActivityHelpers::UserName($user) . ' were completely unable to figure it out!';
        else
            $message .= 'no one was able to figure it out!';

        if($object === 'an Infinity Imp' && $pet->getSpecies()->getName() === 'Infinity Imp')
            $message .= ' (How ironic!)';

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function stretchTogether(User $user, array $pets): QualityTimeResult
    {
        if(count($pets) === 1)
        {
            return new QualityTimeResult(
                ActivityHelpers::UserName($user, true) . ' and ' . ActivityHelpers::PetName($pets[0]) . ' did some stretching exercises together.',
                foodBased: false
            );
        }

        $leader = $this->rng->rngNextFromArray($pets);

        $nonLeaderPets = array_filter($pets, fn(Pet $pet) => $pet !== $leader);

        $everyoneElse = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $nonLeaderPets)
        ]);

        return new QualityTimeResult(
            ActivityHelpers::PetName($leader) . " led $everyoneElse in some stretching exercises together.",
            foodBased: false
        );
    }

    /**
     * @param Pet[] $pets
     */
    private function goOnAWalk(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames went on a walk around the neighborhood.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $endings = [
            "$randomPet saw a HUGE bug on a rock. Neat!",
            "$randomPet got too tired to walk and had to be carried home.",
            "$randomPet saw a colorful snake basking on a rock. How cool!",
            "$randomPet found the BIGGEST STICK EVER but it was too heavy to carry, so they had to leave it behind.",
            "$randomPet saw a colorful lizard basking on a rock. How cute!",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($endings);

        return new QualityTimeResult($message, foodBased: false);
    }

    /**
     * @param Pet[] $pets
     */
    private function exploreTidePools(User $user, array $pets): QualityTimeResult
    {
        $petNamesList = array_map(fn(Pet $pet) => ActivityHelpers::PetName($pet), $pets);
        $everyonesNames = ArrayFunctions::list_nice([
            ActivityHelpers::UserName($user, true),
            ...$petNamesList
        ]);

        $message = "$everyonesNames crouched at the tide pools to see what the sea had left behind.";

        $randomPet = $this->rng->rngNextFromArray($petNamesList);

        $tidePoolMoments = [
            "$randomPet poked a sea anemone and got gently booped back.",
            "$randomPet counted eleven different kinds of snail (allegedly).",
            "$randomPet valiantly shooed off a particularly suspicious-looking seagull that had been eyeing the group.",
            "$randomPet stared down a crab in an epic, dignified standoff.",
        ];

        $message .= ' ' . $this->rng->rngNextFromArray($tidePoolMoments);

        return new QualityTimeResult($message, foodBased: false);
    }
}

class QualityTimeResult
{
    public function __construct(public readonly string $message, public readonly bool $foodBased)
    {
    }
}
