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

namespace Entity;

use App\Entity\PhoneNumber;
use App\Entity\PhoneNumberLootPick;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function testToDigits(): void
    {
        $this->assertEquals('180074992', PhoneNumber::toDigits('1800PIZZA'));
        $this->assertEquals('180074992', PhoneNumber::toDigits('1-800----PI-Z-ZA'));
        $this->assertEquals('180074992', PhoneNumber::toDigits('180074992'));
        $this->assertEquals('180074992', PhoneNumber::toDigits('1 (800) pizza'));
        $this->assertEquals('22233344455566677778889999', PhoneNumber::toDigits('abcdefghijklmnopqrstuvwxyz'));
        $this->assertEquals('', PhoneNumber::toDigits('-()- '));
    }

    public function testNumberComesFromLabel(): void
    {
        $phoneNumber = new PhoneNumber('1-800-PIZZA', 45, '', []);

        $this->assertEquals('180074992', $phoneNumber->getNumber());
        $this->assertEquals('1-800-PIZZA', $phoneNumber->getLabel());
    }

    public function testLabelMustContainANumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PhoneNumber('-()-', 45, '', []);
    }

    public function testLootRoundTrips(): void
    {
        $loot = new PhoneNumber('1', 0, '', [ new PhoneNumberLootPick(2, [ 'A', 'B', 'C' ]) ])->getLoot();

        $this->assertCount(1, $loot);
        $this->assertEquals(2, $loot[0]->pick);
        $this->assertEquals([ 'A', 'B', 'C' ], $loot[0]->from);
    }

    public function testLootCannotPickMoreThanAvailable(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        new PhoneNumberLootPick(4, [ 'A', 'B', 'C' ]);
    }
}
