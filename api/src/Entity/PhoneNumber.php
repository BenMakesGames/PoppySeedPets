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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A number that can be dialed with a telephone. Calling it costs moneys and gives items.
 */
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'number_idx', columns: ['number'])]
#[ORM\Entity]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    /**
     * Digits only (no letters, hyphens, etc).
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $number;

    /**
     * How the number is shown to players; for example "1-800-PIZZA".
     */
    #[ORM\Column(type: 'string', length: 40)]
    private string $label;

    #[ORM\Column(type: 'integer')]
    private int $cost;

    /**
     * Message shown to the player after calling.
     */
    #[ORM\Column(type: 'text')]
    private string $message;

    /**
     * A list of picks; see {@see PhoneNumberLootPick}.
     *
     * @var list<array{pick: int, from: list<string>}>
     */
    #[ORM\Column(type: 'json')]
    private array $loot;

    /**
     * @param PhoneNumberLootPick[] $loot
     */
    public function __construct(string $label, int $cost, string $message, array $loot)
    {
        $number = self::toDigits($label);

        if($number === '')
            throw new \InvalidArgumentException('$label must contain at least one letter or digit.');

        if($cost < 0)
            throw new \InvalidArgumentException('$cost must be 0 or greater.');

        $this->number = $number;
        $this->label = $label;
        $this->cost = $cost;
        $this->message = $message;
        $this->loot = array_values(array_map(fn(PhoneNumberLootPick $pick) => [ 'pick' => $pick->pick, 'from' => $pick->from ], $loot));
    }

    /**
     * Turns letters into their telephone keypad digits, and drops everything else that isn't a digit
     * (hyphens, parentheses, spaces...)
     */
    public static function toDigits(string $number): string
    {
        $keypad = strtr(
            strtoupper($number),
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '22233344455566677778889999'
        );

        return preg_replace('/[^0-9]/', '', $keypad) ?? '';
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return PhoneNumberLootPick[]
     */
    public function getLoot(): array
    {
        return array_map(fn(array $pick) => new PhoneNumberLootPick($pick['pick'], $pick['from']), $this->loot);
    }
}
