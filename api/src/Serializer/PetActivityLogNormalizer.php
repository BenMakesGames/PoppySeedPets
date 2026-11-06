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

use App\Entity\PetActivityLog;
use App\Entity\UserActivityLog;
use App\Functions\NormalizerHelpers;
use App\Service\CommentFormatter;
use App\Service\FlashMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PetActivityLogNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,

        private readonly CommentFormatter $commentFormatter
    )
    {
    }

    /**
     * @param PetActivityLog|UserActivityLog|FlashMessage $data
     */
    public function normalize($data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizedData = NormalizerHelpers::normalizeToArray($this->normalizer, $data, $format, $context);

        if(is_string($normalizedData['entry'] ?? null))
            $normalizedData['entry'] = $this->commentFormatter->format($normalizedData['entry']);

        return $normalizedData;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PetActivityLog || $data instanceof UserActivityLog || $data instanceof FlashMessage;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PetActivityLog::class => true,
            UserActivityLog::class => true,
            FlashMessage::class => true
        ];
    }
}
