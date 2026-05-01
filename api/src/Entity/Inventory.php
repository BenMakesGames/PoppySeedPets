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

use App\Enum\EnumInvalidValueException;
use App\Enum\LocationEnum;
use App\Functions\InventoryModifierFunctions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'modified_on_idx', columns: ['modified_on'])]
#[ORM\Index(name: 'full_item_name_idx', columns: ['full_item_name'])]
#[ORM\Index(name: 'owner_location_item_idx', columns: ['owner_id', 'location', 'item_id'])]
#[ORM\Entity]
class Inventory
{
    /** @var int[] */
    public const array ConsumableLocations = [
        LocationEnum::Home,
        LocationEnum::Basement
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["myPet", "myInventory", "greenhouseFertilizer", "mySeeds", "fireplaceFuel", "dragonTreasure", "myHollowEarthTiles"])]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'inventory')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["myPet", "myInventory", "userPublicProfile", "petPublicProfile", "marketItem", "greenhouseFertilizer", "mySeeds", "hollowEarth", "fireplaceMantle", "fireplaceFuel", "petGroupDetails", "dragonTreasure", "myHollowEarthTiles", "helperPet"])]
    private Item $item;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["myInventory"])]
    private \DateTimeImmutable $createdOn;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["myInventory"])]
    private \DateTimeImmutable $modifiedOn;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    #[Groups(["myInventory", "fireplaceMantle"])]
    private array $comments = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(["myInventory"])]
    private ?User $createdBy;

    #[ORM\OneToOne(targetEntity: Pet::class, mappedBy: 'tool')]
    private ?Pet $holder = null;

    #[ORM\Column(type: 'smallint')]
    private int $location = LocationEnum::Home;

    #[ORM\OneToOne(targetEntity: Pet::class, mappedBy: 'hat')]
    private ?Pet $wearer = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(["myInventory"])]
    private bool $lockedToOwner = false;

    #[ORM\OneToOne(targetEntity: LunchboxItem::class, mappedBy: 'inventoryItem', cascade: ['remove'])]
    private ?LunchboxItem $lunchboxItem = null;

    #[ORM\OneToOne(targetEntity: InventoryEnchantment::class, mappedBy: 'inventory', cascade: ['persist', 'remove'])]
    private ?InventoryEnchantment $enchantmentData = null;

    #[ORM\ManyToOne(targetEntity: Spice::class)]
    #[Groups(["myInventory", "itemEncyclopedia", "marketItem", "fireplaceFuel", "greenhouseFertilizer", "myPet", "fireplaceMantle", "dragonTreasure"])]
    private ?Spice $spice = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $fullItemName;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[Groups(["myInventory", "myPet", "fireplaceMantle", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails", "helperPet", "fireplaceFuel", "dragonTreasure"])]
    private ?Item $illusion = null;

    #[ORM\OneToOne(mappedBy: 'inventory', cascade: ['remove'])]
    private ?InventoryForSale $forSale = null;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unused */
    private int $version;

    public function __construct(User $owner, Item $item)
    {
        $this->owner = $owner;
        $this->item = $item;
        $this->fullItemName = InventoryModifierFunctions::getNameWithModifiers($this);

        $this->createdOn = new \DateTimeImmutable();
        $this->modifiedOn = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function hasId(): bool { return $this->id !== null; }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function changeItem(Item $item): self
    {
        $this->item = $item;

        $this->fullItemName = InventoryModifierFunctions::getNameWithModifiers($this);

        // if the item changes, we need to make sure it can still be worn/held, and unequip it if not
        if($this->getWearer() && !$item->getHat())
        {
            $this
                ->setLocation(LocationEnum::Home)
                ->getWearer()->setHat(null)
            ;
        }

        if($this->getHolder() && !$item->getTool())
        {
            $this
                ->setLocation(LocationEnum::Home)
                ->getHolder()->setTool(null)
            ;
        }

        $this->setModifiedOn();

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function changeOwner(User $owner, string $comment, EntityManagerInterface $em): self
    {
        $this->addComment($comment);

        $this->owner = $owner;

        $this->setModifiedOn();

        if($this->getForSale())
        {
            $em->remove($this->getForSale());
            $this->forSale = null;
        }

        if($this->getLunchboxItem())
        {
            $em->remove($this->getLunchboxItem());
            $this->lunchboxItem = null;
        }

        if($this->getHolder()) $this->getHolder()->setTool(null);
        if($this->getWearer()) $this->getWearer()->setHat(null);

        return $this;
    }

    public function forceSetOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreatedOn(): \DateTimeImmutable
    {
        return $this->createdOn;
    }

    public function getModifiedOn(): \DateTimeImmutable
    {
        return $this->modifiedOn;
    }

    public function setModifiedOn(): self
    {
        $this->modifiedOn = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(string $comment): self
    {
        $this->comments[] = mb_trim($comment);

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getHolder(): ?Pet
    {
        return $this->holder;
    }

    public function setHolder(?Pet $pet): self
    {
        $this->holder = $pet;

        // set (or unset) the owning side of the relation if necessary
        $newTool = $pet === null ? null : $this;
        if ($newTool !== $pet->getTool()) {
            $pet->setTool($newTool);
        }

        return $this;
    }

    public function getLocation(): int
    {
        return $this->location;
    }

    public function setLocation(int $location): self
    {
        if(!LocationEnum::isAValue($location))
            throw new EnumInvalidValueException(LocationEnum::class, $location);

        $this->location = $location;

        $this->setModifiedOn();

        return $this;
    }

    public function getWearer(): ?Pet
    {
        return $this->wearer;
    }

    public function setWearer(?Pet $wearer): self
    {
        $this->wearer = $wearer;

        // set (or unset) the owning side of the relation if necessary
        $newHat = $wearer === null ? null : $this;
        if ($newHat !== $wearer->getHat()) {
            $wearer->setHat($newHat);
        }

        return $this;
    }

    public function getLockedToOwner(): bool
    {
        return $this->lockedToOwner;
    }

    public function setLockedToOwner(bool $lockedToOwner): self
    {
        $this->lockedToOwner = $lockedToOwner;

        return $this;
    }

    public function getLunchboxItem(): ?LunchboxItem
    {
        return $this->lunchboxItem;
    }

    public function setLunchboxItem(LunchboxItem $lunchboxItem): self
    {
        $this->lunchboxItem = $lunchboxItem;

        // set the owning side of the relation if necessary
        if ($lunchboxItem->getInventoryItem() !== $this) {
            $lunchboxItem->setInventoryItem($this);
        }

        return $this;
    }

    #[Groups(["myInventory", "itemEncyclopedia", "marketItem", "fireplaceFuel", "greenhouseFertilizer", "myPet", "fireplaceMantle", "dragonTreasure", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    public function getEnchantment(): ?Enchantment
    {
        return $this->enchantmentData?->getEnchantment();
    }

    public function setEnchantment(?Enchantment $enchantment, int $hue = 0): self
    {
        if ($enchantment === null) {
            $this->enchantmentData = null;
        } else {
            if ($this->enchantmentData === null) {
                $this->enchantmentData = new InventoryEnchantment($this, $enchantment, $hue);
            } else {
                $this->enchantmentData->setEnchantment($enchantment);
                $this->enchantmentData->setHue($hue);
            }
        }

        $this->fullItemName = InventoryModifierFunctions::getNameWithModifiers($this);

        return $this;
    }

    public function getEnchantmentData(): ?InventoryEnchantment
    {
        return $this->enchantmentData;
    }

    #[Groups(["myInventory", "itemEncyclopedia", "marketItem", "fireplaceFuel", "greenhouseFertilizer", "myPet", "fireplaceMantle", "dragonTreasure", "userPublicProfile", "petPublicProfile", "hollowEarth", "petGroupDetails"])]
    public function getEnchantmentHue(): ?int
    {
        return $this->enchantmentData?->getHue();
    }

    public function providesLight(): bool
    {
        return
            $this->getItem()->getTool()?->getProvidesLight() ||
            $this->getEnchantment()?->getEffects()->getProvidesLight()
        ;
    }

    public function protectsFromHeat(): bool
    {
        return
            $this->getItem()->getTool()?->getProtectionFromHeat() ||
            $this->getEnchantment()?->getEffects()->getProtectionFromHeat()
        ;
    }

    public function sexDriveBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getSexDrive() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getSexDrive() ?? 0)
        ;
    }

    public function natureBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getNature() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getNature() ?? 0)
        ;
    }

    public function stealthBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getStealth() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getStealth() ?? 0)
        ;
    }

    public function rangedOnly(): bool
    {
        return
            $this->getItem()->getTool()?->getIsRanged() ||
            $this->getEnchantment()?->getEffects()->getIsRanged()
        ;
    }

    public function brawlBonus(bool $allowRanged = true): int
    {
        if(!$allowRanged && $this->rangedOnly())
            return 0;

        return
            ($this->getItem()->getTool()?->getBrawl() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getBrawl() ?? 0)
        ;
    }

    public function craftsBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getCrafts() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getCrafts() ?? 0)
        ;
    }

    public function arcanaBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getArcana() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getArcana() ?? 0)
        ;
    }

    public function fishingBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getFishing() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getFishing() ?? 0)
        ;
    }

    public function musicBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getMusic() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getMusic() ?? 0)
        ;
    }

    public function smithingBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getSmithing() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getSmithing() ?? 0)
        ;
    }

    public function gatheringBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getGathering() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getGathering() ?? 0)
        ;
    }

    public function scienceBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getScience() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getScience() ?? 0)
        ;
    }

    public function climbingBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getClimbing() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getClimbing() ?? 0)
        ;
    }

    public function physicsBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getPhysics() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getPhysics() ?? 0)
        ;
    }

    public function electronicsBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getElectronics() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getElectronics() ?? 0)
        ;
    }

    public function hackingBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getHacking() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getHacking() ?? 0)
        ;
    }

    public function umbraBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getUmbra() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getUmbra() ?? 0)
        ;
    }

    public function magicBindingBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getMagicBinding() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getMagicBinding() ?? 0)
        ;
    }

    public function miningBonus(): int
    {
        return
            ($this->getItem()->getTool()?->getMining() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getMining() ?? 0)
        ;
    }

    public function focusesSkill(string $skill): bool
    {
        return
            ($this->getItem()->getTool()?->getFocusSkill() === $skill) ||
            ($this->getEnchantment()?->getEffects()->getFocusSkill() === $skill)
        ;
    }

    public function increasesPooping(): int
    {
        return
            ($this->getItem()->getTool()?->getIncreasesPooping() ? 1 : 0) +
            ($this->getEnchantment()?->getEffects()->getIncreasesPooping() ? 1 : 0)
        ;
    }

    public function canBeNibbled(): bool
    {
        return
            $this->getItem()->getTool()?->getCanBeNibbled() ||
            $this->getEnchantment()?->getEffects()->getCanBeNibbled()
        ;
    }

    public function isDreamcatcher(): bool
    {
        return
            $this->getItem()->getTool()?->getDreamcatcher() ||
            $this->getEnchantment()?->getEffects()->getDreamcatcher()
        ;
    }

    public function isGrayscaling(): bool
    {
        return
            $this->getItem()->getTool()?->getIsGrayscaling() ||
            $this->getEnchantment()?->getEffects()->getIsGrayscaling()
        ;
    }

    public function isGreenifying(): bool
    {
        return $this->getItem()->getName() === '5-leaf Clover';
    }

    public function socialEnergyModifier(): int
    {
        return
            ($this->getItem()->getTool()?->getSocialEnergyModifier() ?? 0) +
            ($this->getEnchantment()?->getEffects()->getSocialEnergyModifier() ?? 0)
        ;
    }

    public function getSpice(): ?Spice
    {
        return $this->spice;
    }

    public function setSpice(?Spice $spice): self
    {
        if($spice && $this->getItem()->getSpice())
            return $this;

        $this->spice = $spice;

        $this->fullItemName = InventoryModifierFunctions::getNameWithModifiers($this);

        return $this;
    }

    public function getTotalFertilizerValue(): int
    {
        $value = $this->getItem()->getFertilizer();

        if($this->getSpice()?->getEffects())
        {
            $value += $this->getSpice()->getEffects()->getFood();
            $value += $this->getSpice()->getEffects()->getLove();
        }

        return $value;
    }

    #[Groups(["greenhouseFertilizer"])]
    public function getFertilizerRating(): int
    {
        $totalFertilizerValue = $this->getTotalFertilizerValue();

        if($totalFertilizerValue >= 200)
            return 10;
        else if($totalFertilizerValue >= 100)
            return 9;
        else if($totalFertilizerValue >= 50)
            return 8;
        else if($totalFertilizerValue >= 40)
            return 7;
        else if($totalFertilizerValue >= 30)
            return 6;
        else if($totalFertilizerValue >= 20)
            return 5;
        else if($totalFertilizerValue >= 14)
            return 4;
        else if($totalFertilizerValue >= 8)
            return 3;
        else if($totalFertilizerValue >= 4)
            return 2;
        else if($totalFertilizerValue > 0)
            return 1;
        else
            return 0;
    }

    public function getFullItemName(): string
    {
        return $this->fullItemName;
    }

    public function getIllusion(): ?Item
    {
        return $this->illusion;
    }

    public function setIllusion(?Item $illusion): self
    {
        $this->illusion = $illusion;

        return $this;
    }

    public function getForSale(): ?InventoryForSale
    {
        return $this->forSale;
    }

    public function setForSale(?InventoryForSale $forSale): static
    {
        // set the owning side of the relation if necessary
        if ($forSale && $forSale->getInventory() !== $this) {
            $forSale->setInventory($this);
        }

        $this->forSale = $forSale;

        return $this;
    }

    #[Groups(["myInventory", "fireplaceFuel", "myGreenhouse", "myPet", "dragonTreasure", "myHollowEarthTiles"])]
    public function getSellPrice(): ?int
    {
        return $this->getForSale()?->getSellPrice();
    }
}
