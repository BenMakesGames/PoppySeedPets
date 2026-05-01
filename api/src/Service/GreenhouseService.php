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

use App\Entity\Greenhouse;
use App\Entity\GreenhousePlant;
use App\Entity\Inventory;
use App\Entity\Merit;
use App\Entity\PetSpecies;
use App\Entity\User;
use App\Enum\BirdBathBirdEnum;
use App\Enum\FlavorEnum;
use App\Enum\LocationEnum;
use App\Enum\MeritEnum;
use App\Enum\PetLocationEnum;
use App\Enum\PetSpeciesName;
use App\Enum\PollinatorEnum;
use App\Enum\SerializationGroupEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Enum\UserStat;
use App\Functions\ArrayFunctions;
use App\Functions\MeritRepository;
use App\Functions\PetRepository;
use App\Functions\PetSpeciesRepository;
use App\Functions\PlayerLogFactory;
use App\Functions\SpiceRepository;
use App\Functions\UserQuestRepository;
use App\Model\MeritInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class GreenhouseService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PetFactory $petFactory,
        private readonly IRandom $rng,
        private readonly EntityManagerInterface $em,
        private readonly UserStatsService $userStatsRepository,
        private readonly NormalizerInterface $normalizer,
        private readonly Clock $clock
    )
    {
    }

    public function approachBird(Greenhouse $greenhouse): string
    {
        $user = $greenhouse->getOwner();

        switch($greenhouse->getVisitingBird())
        {
            case BirdBathBirdEnum::Owl:
                $scroll = $this->rng->rngNextFromArray([
                    'Behatting Scroll', 'Behatting Scroll', 'Behatting Scroll',
                    'Renaming Scroll', 'Renaming Scroll',
                    'Forgetting Scroll',
                ]);

                $this->inventoryService->receiveItem($scroll, $user, $user, 'Left behind by a huge owl that visited ' . $user->getName() . '\'s Bird Bath.', LocationEnum::Home);
                $message = 'As you approach the owl, it tilts its head at you. You freeze, and stare at each other for a few seconds before the owl flies off, dropping some kind of scroll as it goes!';
                $activityLogMessage = 'You approached an owl in your birdbath! It flew off, leaving behind a ' . $scroll . '!';
                break;

            case BirdBathBirdEnum::Raven:
                $this->inventoryService->receiveItem('Black Feathers', $user, $user, 'Left behind by a huge raven that visited ' . $user->getName() . '\'s Bird Bath.', LocationEnum::Home);
                $this->inventoryService->receiveItem('Black Feathers', $user, $user, 'Left behind by a huge raven that visited ' . $user->getName() . '\'s Bird Bath.', LocationEnum::Home);
                $this->inventoryService->receiveItem('Winged Key', $user, $user, 'Left behind by a huge raven that visited ' . $user->getName() . '\'s Bird Bath.', LocationEnum::Home);
                $message = 'As you approach the raven, it turns to face you. You freeze, and stare at each other for a few seconds before the raven flies off in a flurry of Black Feathers! Also, it apparently left a Winged Key behind? Huh.';
                $activityLogMessage = 'You approached a raven in your birdbath! It flew off, leaving behind some Black Feathers, and a Winged Key!';
                break;

            case BirdBathBirdEnum::Toucan:
                $this->inventoryService->receiveItem('Imperturbable Toucan', $user, $user, 'Found at ' . $user->getName() . '\'s Bird Bath.', LocationEnum::Home);
                $message = 'As you approach the toucan, it turns to face you. You freeze, and stare at each other for a few seconds before it hops right into your arms!';
                $activityLogMessage = 'You approached a toucan in your birdbath, and it hopped into your arms!';
                break;

            default:
                throw new \Exception('Ben has done something wrong, and not accounted for this type of bird in the code! BEN! HOW COULD LET US DOWN LIKE THIS???');
        }

        $greenhouse->setVisitingBird(null);

        $this->userStatsRepository->incrementStat($user, UserStat::LargeBirdsApproached);

        PlayerLogFactory::create($this->em, $user, $activityLogMessage, [ 'Greenhouse', 'Birdbath' ]);

        return $message;
    }

    public function applyPollinatorSpice(Inventory $item, PollinatorEnum $pollinators): void
    {
        if($pollinators === PollinatorEnum::Bees1 || $pollinators === PollinatorEnum::Bees2)
            $spiceName = $this->rng->rngNextInt(1, 20) === 1 ? 'of Queens' : 'Anthophilan';
        else if($pollinators === PollinatorEnum::Butterflies)
            $spiceName = $this->rng->rngNextFromArray([ 'Fortified', 'Nectarous' ]);
        else
            throw new \InvalidArgumentException('Programmer foolishness did not account for all pollinators when applying spices!');

        $item->setSpice(SpiceRepository::findOneByName($this->em, $spiceName));
    }

    public function harvestPlantAsPet(GreenhousePlant $plant, PetSpecies $species, string $colorA, string $colorB, string $name, ?Merit $bonusMerit): string
    {
        $user = $plant->getOwner();

        $message = 'You harvested-- WHOA, WAIT, WHAT?! It\'s a living ' . $species->getName() . '!?';

        $numberOfPetsAtHome = PetRepository::getNumberAtHome($this->em, $user);

        $startingMerits = MeritInfo::POSSIBLE_STARTING_MERITS;

        if($bonusMerit)
        {
            $startingMerits = array_filter($startingMerits, fn($m) =>
                $m !== $bonusMerit->getName()
            );
        }

        $startingMerit = MeritRepository::findOneByName($this->em, $this->rng->rngNextFromArray($startingMerits));

        $harvestedPet = $this->petFactory->createPet(
            $user,
            $name,
            $species,
            $colorA,
            $colorB,
            $this->rng->rngNextFromArray(FlavorEnum::cases()),
            $startingMerit
        );

        if($bonusMerit)
            $harvestedPet->addMerit($bonusMerit);

        $harvestedPet
            ->setFoodAndSafety($this->rng->rngNextInt(10, 12), -9)
            ->setScale($this->rng->rngNextInt(80, 120))
        ;

        $this->em->remove($plant);

        if($numberOfPetsAtHome >= $user->getMaxPets())
        {
            $message .= "\n\n" . 'Seeing no space in your house, the creature wanders off to Daycare.';
            $harvestedPet->setLocation(PetLocationEnum::DAYCARE);
        }
        else
        {
            $message .= "\n\n" . 'The creature wastes no time in setting up residence in your house.';
            $harvestedPet->setLocation(PetLocationEnum::HOME);
        }

        return $message;
    }

    public function maybeAssignPollinators(User $user): void
    {
        $greenhouse = $user->getGreenhouse() ?? throw new \InvalidArgumentException('User has no greenhouse!');

        $twoHoursAgo = $this->clock->now->sub(\DateInterval::createFromDateString('2 hours'));

        if($greenhouse->getButterfliesDismissedOn() <= $twoHoursAgo)
            $this->maybeAssignPollinator($user, PollinatorEnum::Butterflies);

        if($user->hasUnlockedFeature(UnlockableFeatureEnum::Beehive))
        {
            if($greenhouse->getBeesDismissedOn() <= $twoHoursAgo)
                $this->maybeAssignPollinator($user, PollinatorEnum::Bees1);

            if($user->getBeehive() && $user->getBeehive()->getWorkers() >= 500 && $greenhouse->getBees2DismissedOn() <= $twoHoursAgo)
                $this->maybeAssignPollinator($user, PollinatorEnum::Bees2);
        }
    }

    private function maybeAssignPollinator(User $user, PollinatorEnum $pollinator): bool
    {
        // must not already have this pollinator present
        if($user->getGreenhousePlants()->exists(fn($key, GreenhousePlant $p) => $p->getPollinators() == $pollinator))
            return false;

        // must have at least 3 generally-pollinatable plants
        $availablePlants = array_filter($user->getGreenhousePlants()->toArray(), fn(GreenhousePlant $p) => !$p->getPlant()->getNoPollinators());

        if(count($availablePlants) < 3)
            return false;

        // must have at least 1 plant available
        $availablePlants = array_filter($availablePlants, fn(GreenhousePlant $p) => $p->getPollinators() == null);

        if(count($availablePlants) === 0)
            return false;

        /** @var GreenhousePlant $plant */
        $plant = $this->rng->rngNextFromArray($availablePlants);

        $plant->setPollinators($pollinator);

        return true;
    }

    /**
     * @return array{greenhouse: Greenhouse, weeds: string|null, plants: GreenhousePlant[], fertilizer: array}
     */
    public function getGreenhouseResponseData(User $user): array
    {
        $fertilizers = $this->findFertilizers($user);

        return [
            'greenhouse' => $user->getGreenhouse(),
            'weeds' => $this->getWeedText($user),
            'plants' => $this->em->getRepository(GreenhousePlant::class)->findBy([ 'owner' => $user->getId() ]),
            'fertilizer' => $this->normalizer->normalize($fertilizers, null, [ 'groups' => [ SerializationGroupEnum::GREENHOUSE_FERTILIZER ] ]),
        ];
    }

    /**
     * @param int[] $inventoryIds
     * @return Inventory[]
     */
    public function findFertilizers(User $user, ?array $inventoryIds = null): array
    {
        $qb = $this->em->getRepository(Inventory::class)->createQueryBuilder('i')
            ->andWhere('i.owner=:owner')
            ->andWhere('i.location = :home')
            ->leftJoin('i.item', 'item')
            ->leftJoin('i.spice', 'spice')
            ->leftJoin('spice.effects', 'effects')

            // has positive fertilizer - DON'T care about spices or whatever, we definitely want to show it
            // has 0 or negative fertilizer - only show if it has food or love greater than negative fertilizer (food + love exceeds badness of fertilizer)
            ->andWhere('item.fertilizer > 0 OR (effects.food + effects.love > -item.fertilizer)')

            ->addOrderBy('item.name', 'ASC')
            ->setParameter('owner', $user->getId())
            ->setParameter('home', LocationEnum::Home)
        ;

        if($inventoryIds)
        {
            $qb
                ->andWhere('i.id IN (:inventoryIds)')
                ->setParameter('inventoryIds', $inventoryIds)
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function getWeedText(User $user): ?string
    {
        $weeds = UserQuestRepository::findOrCreate($this->em, $user, 'Greenhouse Weeds', $this->clock->now->modify('-1 minutes')->format('Y-m-d H:i:s'));

        $weedTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $weeds->getValue());

        if($weedTime > $this->clock->now)
            $weedText = null;
        else
        {
            $weedText = $this->rng->rngNextFromArray([
                'Don\'t need \'em; don\'t want \'em!',
                'Get outta\' here, weeds!',
                'Weeds can gtfo!',
                'WEEEEEEDS!! *shakes fist*',
                'Exterminate! EXTERMINATE!',
                'Destroy all weeds!',
            ]);
        }

        if(!$weeds->hasId())
            $this->em->flush();

        return $weedText;
    }

    public function makeDapperSwanPet(GreenhousePlant $plant): string
    {
        $species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::DapperSwan);

        $colorA = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            'EEEEEE', 'EEDDCC', 'DDDDBB'
        ]));

        $colorB = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            'bb0000', '33CCFF', '009900', 'CC9933', '333333'
        ]));

        if($this->rng->rngNextInt(1, 3) === 1)
        {
            $temp = $colorA;
            $colorA = $colorB;
            $colorB = $temp;
        }

        $name = $this->rng->rngNextFromArray([
            'Gosling', 'Goose', 'Honks', 'Clamshell', 'Mussel', 'Seafood', 'Nauplius', 'Mr. Beaks',
            'Medli', 'Buff', 'Tuft', 'Tail-feather', 'Anser', 'Cygnus', 'Paisley', 'Bolo', 'Cravat',
            'Ascot', 'Neckerchief'
        ]);

        $bonusMerit = MeritRepository::findOneByName($this->em, MeritEnum::MOON_BOUND);

        return $this->harvestPlantAsPet($plant, $species, $colorA, $colorB, $name, $bonusMerit);
    }

    public function makeMushroomPet(GreenhousePlant $plant): string
    {
        $species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::Mushroom);

        $colorA = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            'e32c2c', 'e5e5d6', 'dd8a09', 'a8443d'
        ]));

        $colorB = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            'd7d38b', 'e5e5d6', '716363'
        ]));

        if($this->rng->rngNextInt(1, 4) === 1)
        {
            $temp = $colorA;
            $colorA = $colorB;
            $colorB = $temp;
        }

        $name = $this->rng->rngNextFromArray([
            'Cremini', 'Button', 'Portobello', 'Oyster', 'Porcini', 'Morel', 'Enoki', 'Shimeji',
            'Shiitake', 'Maitake', 'Reishi', 'Puffball', 'Galerina', 'Milkcap', 'Bolete',
            'Honey', 'Pinewood', 'Horse', 'Périgord', 'Tooth', 'Blewitt', 'Pom Pom', 'Ear', 'Jelly',
            'Chestnut', 'Khumbhi', 'Helvella', 'Amanita'
        ]);

        $bonusMerit = MeritRepository::findOneByName($this->em, MeritEnum::DARKVISION);

        return $this->harvestPlantAsPet($plant, $species, $colorA, $colorB, $name, $bonusMerit);
    }

    public function makeTomatePet(GreenhousePlant $plant): string
    {
        $species = PetSpeciesRepository::findOneByName($this->em, PetSpeciesName::Tomate);

        $colorA = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            'FF6622', 'FFCC22', '77FF22', 'FF2222', '7722FF'
        ]));

        $colorB = $this->rng->rngNextTweakedColor($this->rng->rngNextFromArray([
            '007700', '009922', '00bb44'
        ]));

        $name = $this->rng->rngNextFromArray([
            'Alicante', 'Azoychka', 'Krim', 'Brandywine', 'Campari', 'Canario', 'Tomkin',
            'Flamenco', 'Giulietta', 'Grandero', 'Trifele', 'Jubilee', 'Juliet', 'Kumato',
            'Monterosa', 'Montserrat', 'Plum', 'Raf', 'Roma', 'Rutgers', 'Marzano', 'Cherry',
            'Nebula', 'Santorini', 'Tomaccio', 'Tamatie', 'Tamaatar', 'Matomatisi', 'Yaanyo',
            'Pomidor', 'Utamatisi'
        ]);

        $bonusMerit = MeritRepository::findOneByName($this->em, MeritEnum::MOON_BOUND);

        return $this->harvestPlantAsPet($plant, $species, $colorA, $colorB, $name, $bonusMerit);
    }
}
