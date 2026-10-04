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

namespace App\Security;

/**
 * Class for cryptographic operations.
 * All methods in this class should be cryptographically secure.
 */
final class CryptographicFunctions
{
    /**
     * Generates a cryptographically secure random string of specified length.
     *
     * @param int $length The desired length of the random string
     * @param string $allowedCharacters The set of characters to use (default: alphanumeric)
     * @return string A random string of the specified length
     */
    public static function generateSecureRandomString(int $length, string $allowedCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string
    {
        $allowedCharactersCount = strlen($allowedCharacters);

        if($allowedCharactersCount < 1)
            throw new \InvalidArgumentException('$allowedCharacters must not be empty.');

        $result = '';

        for ($i = 0; $i < $length; $i++)
            $result .= $allowedCharacters[random_int(0, $allowedCharactersCount - 1)];

        return $result;
    }
} 