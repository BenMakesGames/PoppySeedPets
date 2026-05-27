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

namespace App\Serializer;

use App\Entity\Pet;
use App\Entity\PetSpecies;
use App\Enum\SerializationGroupEnum;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PetSpeciesNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private readonly EntityManagerInterface $em,
    )
    {
    }

    /**
     * @param PetSpecies $data
     */
    public function normalize($data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = $this->normalizer->normalize($data, $format, $context);

        if(in_array(SerializationGroupEnum::PET_ENCYCLOPEDIA, $context['groups']))
        {
            $normalizedData['numberOfPets'] = self::getNumberHavingSpecies($this->em, $data);
        }

        return $normalizedData;
    }

    public static function getNumberHavingSpecies(EntityManagerInterface $em, PetSpecies $petSpecies): int
    {
        return (int)$em->getRepository(Pet::class)->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.species=:species')
            ->setParameter('species', $petSpecies->getId()->toBinary(), ParameterType::BINARY)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PetSpecies;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ PetSpecies::class => true ];
    }
}
