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

use App\Entity\Inventory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * If the inventory is a hat whose wearer has been fitted by the Hattier, the fit replaces the hat
 * item's default positioning. Clients see ordinary item.hat data, and need not know fits exist.
 */
class InventoryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    )
    {
    }

    /**
     * @param Inventory $data
     * @param array<string, mixed> $context
     * @return array<array-key, mixed>|string|int|float|bool|\ArrayObject<array-key, mixed>|null
     */
    public function normalize($data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = $this->normalizer->normalize($data, $format, $context);

        $hatFit = $data->getWearer()?->getHatFit();

        if(
            $hatFit &&
            is_array($normalizedData) &&
            is_array($normalizedData['item'] ?? null) &&
            is_array($normalizedData['item']['hat'] ?? null)
        )
        {
            $normalizedData['item']['hat']['headX'] = $hatFit->getHeadX();
            $normalizedData['item']['hat']['headY'] = $hatFit->getHeadY();
            $normalizedData['item']['hat']['headAngle'] = $hatFit->getHeadAngle();
            $normalizedData['item']['hat']['headScale'] = $hatFit->getHeadScale();
        }

        return $normalizedData;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Inventory;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ Inventory::class => true ];
    }
}
