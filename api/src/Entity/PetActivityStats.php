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

#[ORM\Entity]
class PetActivityStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Pet::class, inversedBy: 'petActivityStats', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Pet $pet;

    #[ORM\Column(type: 'integer')]
    private int $craftSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $craftFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $craftTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $magicBindSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $magicBindFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $magicBindTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $smithSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $smithFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $smithTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $plasticPrintSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $plasticPrintFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $plasticPrintTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $fishSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $fishFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $fishTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $gatherSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $gatherFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $gatherTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $huntSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $huntFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $huntTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $protocol7Success = 0;

    #[ORM\Column(type: 'integer')]
    private int $protocol7Failure = 0;

    #[ORM\Column(type: 'integer')]
    private int $protocol7Time = 0;

    #[ORM\Column(type: 'integer')]
    private int $programSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $programFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $programTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $umbraSuccess = 0;

    #[ORM\Column(type: 'integer')]
    private int $umbraFailure = 0;

    #[ORM\Column(type: 'integer')]
    private int $umbraTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $parkEvent = 0;

    #[ORM\Column(type: 'integer')]
    private int $parkEventTime = 0;

    #[ORM\Column(type: 'integer')]
    private int $other = 0;

    #[ORM\Column(type: 'integer')]
    private int $otherTime = 0;

    public function __construct(Pet $pet)
    {
        $this->pet = $pet;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(Pet $pet): self
    {
        $this->pet = $pet;

        return $this;
    }

    public function getCraftSuccess(): int
    {
        return $this->craftSuccess;
    }

    public function increaseCraftSuccess(int $amount = 1): self
    {
        $this->craftSuccess += $amount;

        return $this;
    }

    public function getCraftFailure(): int
    {
        return $this->craftFailure;
    }

    public function increaseCraftFailure(int $amount = 1): self
    {
        $this->craftFailure += $amount;

        return $this;
    }

    public function getMagicBindSuccess(): int
    {
        return $this->magicBindSuccess;
    }

    public function increaseMagicBindSuccess(int $amount = 1): self
    {
        $this->magicBindSuccess += $amount;

        return $this;
    }

    public function getMagicBindFailure(): int
    {
        return $this->magicBindFailure;
    }

    public function increaseMagicBindFailure(int $amount = 1): self
    {
        $this->magicBindFailure += $amount;

        return $this;
    }

    public function getSmithSuccess(): int
    {
        return $this->smithSuccess;
    }

    public function increaseSmithSuccess(int $amount = 1): self
    {
        $this->smithSuccess += $amount;

        return $this;
    }

    public function getSmithFailure(): int
    {
        return $this->smithFailure;
    }

    public function increaseSmithFailure(int $amount = 1): self
    {
        $this->smithFailure += $amount;

        return $this;
    }

    public function getPlasticPrintSuccess(): int
    {
        return $this->plasticPrintSuccess;
    }

    public function increasePlasticPrintSuccess(int $amount = 1): self
    {
        $this->plasticPrintSuccess += $amount;

        return $this;
    }

    public function getPlasticPrintFailure(): int
    {
        return $this->plasticPrintFailure;
    }

    public function increasePlasticPrintFailure(int $amount = 1): self
    {
        $this->plasticPrintFailure += $amount;

        return $this;
    }

    public function getFishSuccess(): int
    {
        return $this->fishSuccess;
    }

    public function increaseFishSuccess(int $amount = 1): self
    {
        $this->fishSuccess += $amount;

        return $this;
    }

    public function getFishFailure(): int
    {
        return $this->fishFailure;
    }

    public function increaseFishFailure(int $amount = 1): self
    {
        $this->fishFailure += $amount;

        return $this;
    }

    public function getGatherSuccess(): int
    {
        return $this->gatherSuccess;
    }

    public function increaseGatherSuccess(int $amount = 1): self
    {
        $this->gatherSuccess += $amount;

        return $this;
    }

    public function getGatherFailure(): int
    {
        return $this->gatherFailure;
    }

    public function increaseGatherFailure(int $amount = 1): self
    {
        $this->gatherFailure += $amount;

        return $this;
    }

    public function getHuntSuccess(): int
    {
        return $this->huntSuccess;
    }

    public function increaseHuntSuccess(int $amount = 1): self
    {
        $this->huntSuccess += $amount;

        return $this;
    }

    public function getHuntFailure(): int
    {
        return $this->huntFailure;
    }

    public function increaseHuntFailure(int $amount = 1): self
    {
        $this->huntFailure += $amount;

        return $this;
    }

    public function getProtocol7Success(): int
    {
        return $this->protocol7Success;
    }

    public function increaseProtocol7Success(int $amount = 1): self
    {
        $this->protocol7Success += $amount;

        return $this;
    }

    public function getProtocol7Failure(): int
    {
        return $this->protocol7Failure;
    }

    public function increaseProtocol7Failure(int $amount = 1): self
    {
        $this->protocol7Failure += $amount;

        return $this;
    }

    public function getProgramSuccess(): int
    {
        return $this->programSuccess;
    }

    public function increaseProgramSuccess(int $amount = 1): self
    {
        $this->programSuccess += $amount;

        return $this;
    }

    public function getProgramFailure(): int
    {
        return $this->programFailure;
    }

    public function increaseProgramFailure(int $amount = 1): self
    {
        $this->programFailure += $amount;

        return $this;
    }

    /**
     * Represents number of times the pet successfully explored the Umbra.
     */
    public function getUmbraSuccess(): int
    {
        return $this->umbraSuccess;
    }

    /**
     * Represents number of times the pet successfully explored the Umbra.
     */
    public function increaseUmbraSuccess(int $amount = 1): self
    {
        $this->umbraSuccess += $amount;

        return $this;
    }

    /**
     * Represents number of times the pet FAILED while exploring the Umbra.
     */
    public function getUmbraFailure(): int
    {
        return $this->umbraFailure;
    }

    /**
     * Represents number of times the pet FAILED while exploring the Umbra.
     */
    public function increaseUmbraFailure(int $amount = 1): self
    {
        $this->umbraFailure += $amount;

        return $this;
    }

    public function getParkEvent(): ?int
    {
        return $this->parkEvent;
    }

    public function increaseParkEvent(int $amount = 1): self
    {
        $this->parkEvent += $amount;

        return $this;
    }

    public function getOther(): int
    {
        return $this->other;
    }

    public function increaseOther(int $amount = 1): self
    {
        $this->other += $amount;

        return $this;
    }

    public function getCraftTime(): int
    {
        return $this->craftTime;
    }

    public function increaseCraftTime(int $amount): self
    {
        $this->craftTime += $amount;

        return $this;
    }

    public function getMagicBindTime(): int
    {
        return $this->magicBindTime;
    }

    public function increaseMagicBindTime(int $amount): self
    {
        $this->magicBindTime += $amount;

        return $this;
    }

    public function getSmithTime(): int
    {
        return $this->smithTime;
    }

    public function increaseSmithTime(int $amount): self
    {
        $this->smithTime += $amount;

        return $this;
    }

    public function getPlasticPrintTime(): int
    {
        return $this->plasticPrintTime;
    }

    public function increasePlasticPrintTime(int $amount): self
    {
        $this->plasticPrintTime += $amount;

        return $this;
    }

    public function getFishTime(): int
    {
        return $this->fishTime;
    }

    public function increaseFishTime(int $amount): self
    {
        $this->fishTime += $amount;

        return $this;
    }

    public function getGatherTime(): int
    {
        return $this->gatherTime;
    }

    public function increaseGatherTime(int $amount): self
    {
        $this->gatherTime += $amount;

        return $this;
    }

    public function getHuntTime(): int
    {
        return $this->huntTime;
    }

    public function increaseHuntTime(int $amount): self
    {
        $this->huntTime += $amount;

        return $this;
    }

    public function getProtocol7Time(): int
    {
        return $this->protocol7Time;
    }

    public function increaseProtocol7Time(int $amount): self
    {
        $this->protocol7Time += $amount;

        return $this;
    }

    public function getProgramTime(): int
    {
        return $this->programTime;
    }

    public function increaseProgramTime(int $amount): self
    {
        $this->programTime += $amount;

        return $this;
    }

    public function getUmbraTime(): int
    {
        return $this->umbraTime;
    }

    public function increaseUmbraTime(int $amount): self
    {
        $this->umbraTime += $amount;

        return $this;
    }

    public function getParkEventTime(): int
    {
        return $this->parkEventTime;
    }

    public function increaseParkEventTime(int $amount): self
    {
        $this->parkEventTime += $amount;

        return $this;
    }

    public function getOtherTime(): int
    {
        return $this->otherTime;
    }

    public function increaseOtherTime(int $amount): self
    {
        $this->otherTime += $amount;

        return $this;
    }
}
