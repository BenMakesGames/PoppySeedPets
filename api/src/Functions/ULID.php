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

namespace App\Functions;

use App\Exceptions\PSPFormValidationException;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class ULID
{
    /**
     * Validates that a request-supplied string is a ULID, returning the parsed Ulid or throwing a
     * client-facing PSPFormValidationException (422) that names the offending field. Use this instead of
     * Symfony\Component\Uid\Ulid::fromString() on user input, which throws a raw \InvalidArgumentException (500).
     */
    public static function fromUserInput(string $value, string $fieldLabel): SymfonyUlid
    {
        if(!SymfonyUlid::isValid($value))
            throw new PSPFormValidationException('"' . $fieldLabel . '" is not a valid ID.');

        return SymfonyUlid::fromString($value);
    }

    public static function generateUUID(?int $timeInMs = null): string
    {
        $uuidHex = self::generateHex($timeInMs);

        // format as UUID
        return sprintf('%s-%s-%s-%s-%s',
            substr($uuidHex, 0, 8),
            substr($uuidHex, 8, 4),
            substr($uuidHex, 12, 4),
            substr($uuidHex, 16, 4),
            substr($uuidHex, 20, 12)
        );
    }

    public static function generateHex(?int $timeInMs = null): string
    {
        $timeInMs ??= (int)(microtime(true) * 1000);

        // store time in 6 bytes (48 bits)
        $timeInMs = $timeInMs & ((1 << 48) - 1);

        // generate 10 random bytes (80 bits)
        $randomBytes = random_bytes(10);

        // convert to a hex string
        return sprintf('%012x', $timeInMs) . bin2hex($randomBytes);
    }

    public static function generateBinary(?int $timeInMs = null): string
    {
        $timeInMs ??= (int)(microtime(true) * 1000);

        // get 48 bits of time:
        $timeInMs = $timeInMs & ((1 << 48) - 1);

        // convert timeInMs to a string of 6 bytes
        $timeBytes = substr(pack('J', $timeInMs), 2);

        // concatenate 10 random bytes (80 bits)
        return $timeBytes . random_bytes(10);
    }
}