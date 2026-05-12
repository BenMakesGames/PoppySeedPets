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

use App\Entity\Merit;
use App\Entity\Pet;
use App\Entity\PetHouseTime;
use App\Entity\PetSkills;
use App\Entity\PetSpecies;
use App\Entity\User;
use App\Enum\FlavorEnum;
use App\Enum\MeritEnum;
use App\Functions\PetColorFunctions;
use App\Functions\MeritRepository;
use App\Model\PetShelterPet;
use Doctrine\ORM\EntityManagerInterface;

class PetFactory
{
    /**
     * @var string[]
     */
    private const array SentinelNames = [
        'Sentinel',
        'Homunculus',
        'Golem',
        'Puppet',
        'Guardian',
        'Marionette',
        'Familiar',
        'Summon',
        'Shield',
        'Sentry',
        'Substitute',
        'Ersatz',
        'Proxy',
        'Placeholder',
        'Surrogate',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IRandom $rng
    )
    {
    }

    public function createPet(
        User $owner, string $name, PetSpecies $species,
        string $colorA, string $colorB,
        FlavorEnum $favoriteFlavor, Merit $startingMerit
    ): Pet
    {
        $petSkills = new PetSkills();

        $this->em->persist($petSkills);

        $pet = new Pet(
            name: $name,
            species: $species,
            owner: $owner,
            skills: $petSkills,
            colorA: $colorA,
            colorB: $colorB
        )
            ->setFavoriteFlavor($favoriteFlavor)
            ->addMerit($startingMerit)
        ;

        $petHouseTime = new PetHouseTime($pet)
            ->setSocialEnergy((int)ceil(PetExperienceService::SocialEnergyPerHangOut * (4 + $pet->getExtroverted()) / 4))
        ;

        $pet->setHouseTime($petHouseTime);

        $this->em->persist($petHouseTime);
        $this->em->persist($pet);

        return $pet;
    }

    public function createRandomPetOfSpecies(User $owner, PetSpecies $petSpecies): Pet
    {
        $now = new \DateTimeImmutable();

        $petCount = (int)$this->em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.birthDate<:today')
            ->setParameter('today', $now)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if($petCount == 0)
        {
            $colors = PetColorFunctions::generateRandomPetColors($this->rng);
            $colorA = $colors->colorA;
            $colorB = $colors->colorB;
        }
        else
        {
            /** @var Pet $basePet */
            $basePet = $this->em->getRepository(Pet::class)->createQueryBuilder('p')
                ->andWhere('p.birthDate<:today')
                ->setParameter('today', $now)
                ->setMaxResults(1)
                ->setFirstResult($this->rng->rngNextInt(0, $petCount - 1))
                ->getQuery()
                ->getSingleResult()
            ;

            $colorA = $this->rng->rngNextTweakedColor($basePet->getColorA());
            $colorB = $this->rng->rngNextTweakedColor($basePet->getColorB());
        }

        $isSagaJelling = $petSpecies->getName() === 'Sága Jelling';

        $startingMerit = $isSagaJelling
            ? MeritRepository::findOneByName($this->em, MeritEnum::SAGA_SAGA)
            : MeritRepository::getRandomStartingMerit($this->em, $this->rng)
        ;

        $name = $petSpecies->getName() === 'Sentinel'
            ? $this->rng->rngNextFromArray(self::SentinelNames)
            : $this->rng->rngNextFromArray(PetShelterPet::PetNames)
        ;

        $pet = $this->createPet(
            $owner,
            $name,
            $petSpecies,
            $colorA,
            $colorB,
            $this->rng->rngNextFromArray(FlavorEnum::cases()),
            $startingMerit
        );

        $pet
            ->setFoodAndSafety($this->rng->rngNextInt(10, 12), -9)
            ->setScale($this->rng->rngNextInt(80, 120))
        ;

        if($isSagaJelling)
            $pet->addMerit(MeritRepository::findOneByName($this->em, MeritEnum::AFFECTIONLESS));

        return $pet;
    }
}
