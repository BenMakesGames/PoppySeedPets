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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'fertilizer_idx', columns: ['fertilizer'])]
#[ORM\Index(name: 'fuel_idx', columns: ['fuel'])]
#[ORM\Entity]
class Item
{
    #[Groups(["itemEncyclopedia", "myPet", "marketItem", "myInventory", "itemTypeahead", "museum"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["myPet", "myInventory", "userPublicProfile", "petPublicProfile", "itemEncyclopedia", "museum", "marketItem", "knownRecipe", "mySeeds", "fireplaceMantle", "fireplaceFuel", "myBeehive", "itemTypeahead", "greenhouseFertilizer", "dragonTreasure", "petActivityLogAndPublicPet", "myBids", "myHollowEarthTiles", "myLetters", "zoologistCatalog", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'string', length: 45, unique: true)]
    private string $name = '';

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Groups(["myPet", "myInventory", "userPublicProfile", "petPublicProfile", "itemEncyclopedia", "museum", "marketItem", "knownRecipe", "mySeeds", "hollowEarth", "fireplaceMantle", "fireplaceFuel", "myBeehive", "petGroupDetails", "itemTypeahead", "greenhouseFertilizer", "dragonTreasure", "petActivityLogAndPublicPet", "myBids", "myHollowEarthTiles", "helperPet", "petActivityLogAndPublicPet"])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $image = '';

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $useActions = null;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "itemEncyclopedia", "hollowEarth", "petGroupDetails", "helperPet", "petActivityLogAndPublicPet"])]
    #[ORM\OneToOne(targetEntity: ItemTool::class)]
    private ?ItemTool $tool = null;

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\OneToOne(targetEntity: ItemFood::class, cascade: ['persist', 'remove'])]
    private ?ItemFood $food = null;

    #[ORM\Column(type: 'integer')]
    private int $fertilizer = 0;

    #[ORM\ManyToOne(targetEntity: Plant::class, inversedBy: 'item', cascade: ['persist', 'remove'])]
    private ?Plant $plant = null;

    /**
     * @var Collection<int, MuseumItem>
     */
    #[ORM\OneToMany(targetEntity: MuseumItem::class, mappedBy: 'item', fetch: 'EXTRA_LAZY')]
    private Collection $museumDonations;

    /**
     * @var Collection<int, Inventory>
     */
    #[ORM\OneToMany(targetEntity: Inventory::class, mappedBy: 'item', fetch: 'EXTRA_LAZY')]
    private Collection $inventory;

    #[Groups(["myInventory", "myPet", "userPublicProfile", "petPublicProfile", "itemEncyclopedia", "hollowEarth", "petGroupDetails", "helperPet"])]
    #[ORM\OneToOne(targetEntity: ItemHat::class, inversedBy: 'item', cascade: ['persist', 'remove'])]
    private ?ItemHat $hat = null;

    #[ORM\Column(type: 'integer')]
    private int $fuel = 0;

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\Column(type: 'smallint')]
    private int $recycleValue = 0;

    #[Groups(["myInventory", "marketItem", "itemEncyclopedia"])]
    #[ORM\ManyToOne(targetEntity: Enchantment::class)]
    private ?Enchantment $enchants = null;

    #[ORM\OneToOne(targetEntity: ItemGrammar::class, mappedBy: 'item', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?ItemGrammar $grammar = null;

    #[Groups(["myInventory", "marketItem", "itemEncyclopedia"])]
    #[ORM\ManyToOne(targetEntity: Spice::class)]
    private ?Spice $spice = null;

    #[Groups(["dragonTreasure"])]
    #[ORM\OneToOne(targetEntity: ItemTreasure::class, cascade: ['persist', 'remove'])]
    private ?ItemTreasure $treasure = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isBug = false;

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\ManyToOne(targetEntity: HollowEarthTileCard::class)]
    private ?HollowEarthTileCard $hollowEarthTileCard = null;

    /**
     * @var Collection<int, ItemGroup>
     */
    #[Groups(["itemEncyclopedia", "myInventory"])]
    #[ORM\ManyToMany(targetEntity: ItemGroup::class, mappedBy: 'items')]
    private Collection $itemGroups;

    #[ORM\Column(type: 'boolean')]
    private bool $cannotBeThrownOut = false;

    #[Groups(["myDonatableInventory"])]
    #[ORM\Column(type: 'smallint')]
    private int $museumPoints = 1;

    public function __construct()
    {
        $this->itemGroups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(["myBeehive", "itemEncyclopedia", "myLetters"])]
    public function getNameWithArticle(): string
    {
        if($this->getGrammar() && $this->getGrammar()->getArticle())
            return $this->getGrammar()->getArticle() . ' ' . $this->getName();
        else
            return $this->getName();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getUseActions(): ?array
    {
        return $this->useActions;
    }

    public function setUseActions(?array $useActions): self
    {
        $this->useActions = $useActions;

        return $this;
    }

    public function hasUseAction(string $action): bool
    {
        if($this->useActions === null) return false;

        foreach($this->useActions as $useAction)
        {
            if($useAction[1] === $action)
                return true;
        }

        return false;
    }

    public function getTool(): ?ItemTool
    {
        return $this->tool;
    }

    public function setTool(?ItemTool $tool): self
    {
        $this->tool = $tool;

        return $this;
    }

    public function getFood(): ?ItemFood
    {
        return $this->food;
    }

    public function setFood(?ItemFood $food): self
    {
        $this->food = $food;

        return $this;
    }

    public function getFertilizer(): int
    {
        return $this->fertilizer;
    }

    public function setFertilizer(int $fertilizer): self
    {
        $this->fertilizer = $fertilizer;

        return $this;
    }

    public function getPlant(): ?Plant
    {
        return $this->plant;
    }

    public function setPlant(?Plant $plant): self
    {
        $this->plant = $plant;

        return $this;
    }

    public function getHat(): ?ItemHat
    {
        return $this->hat;
    }

    public function setHat(?ItemHat $hat): self
    {
        $this->hat = $hat;

        return $this;
    }

    public function getFuel(): int
    {
        return $this->fuel;
    }

    public function setFuel(int $fuel): self
    {
        $this->fuel = $fuel;

        return $this;
    }

    #[Groups(["fireplaceFuel"])]
    public function getFuelRating(): int
    {
        return (int)(($this->fuel / 1440) * 10);
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getGreenhouseType(): ?string
    {
        return $this->getPlant()?->getType();
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getIsFlammable(): bool
    {
        return $this->getFuel() > 0;
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getIsFertilizer(): bool
    {
        return $this->getFertilizer() > 0;
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getIsTreasure(): bool
    {
        return $this->getTreasure() != null;
    }

    public function getRecycleValue(): int
    {
        return $this->recycleValue;
    }

    public function setRecycleValue(int $recycleValue): self
    {
        $this->recycleValue = $recycleValue;

        return $this;
    }

    public function getEnchants(): ?Enchantment
    {
        return $this->enchants;
    }

    public function setEnchants(?Enchantment $enchants): self
    {
        $this->enchants = $enchants;

        return $this;
    }

    public function getGrammar(): ?ItemGrammar
    {
        return $this->grammar;
    }

    public function setGrammar(ItemGrammar $grammar): self
    {
        $this->grammar = $grammar;

        // set the owning side of the relation if necessary
        if ($grammar->getItem() !== $this) {
            $grammar->setItem($this);
        }

        return $this;
    }

    public function getSpice(): ?Spice
    {
        return $this->spice;
    }

    public function setSpice(?Spice $spice): self
    {
        $this->spice = $spice;

        return $this;
    }

    public function getTreasure(): ?ItemTreasure
    {
        return $this->treasure;
    }

    public function setTreasure(?ItemTreasure $treasure): self
    {
        $this->treasure = $treasure;

        return $this;
    }

    public function getIsBug(): bool
    {
        return $this->isBug;
    }

    public function setIsBug(bool $isBug): self
    {
        $this->isBug = $isBug;

        return $this;
    }

    public function getHollowEarthTileCard(): ?HollowEarthTileCard
    {
        return $this->hollowEarthTileCard;
    }

    public function setHollowEarthTileCard(?HollowEarthTileCard $hollowEarthTileCard): self
    {
        $this->hollowEarthTileCard = $hollowEarthTileCard;

        return $this;
    }

    /**
     * @return Collection<int, ItemGroup>
     */
    public function getItemGroups(): Collection
    {
        return $this->itemGroups;
    }

    public function addItemGroup(ItemGroup $itemGroup): self
    {
        if (!$this->itemGroups->contains($itemGroup)) {
            $this->itemGroups[] = $itemGroup;
            $itemGroup->addItem($this);
        }

        return $this;
    }

    public function removeItemGroup(ItemGroup $itemGroup): self
    {
        if ($this->itemGroups->removeElement($itemGroup)) {
            $itemGroup->removeItem($this);
        }

        return $this;
    }

    public function hasItemGroup(string $itemGroupName): bool
    {
        foreach($this->itemGroups as $group)
        {
            /** @var ItemGroup $group */
            if($group->getName() === $itemGroupName)
                return true;
        }

        return false;
    }

    public function getCannotBeThrownOut(): bool
    {
        return $this->cannotBeThrownOut;
    }

    public function setCannotBeThrownOut(bool $cannotBeThrownOut): self
    {
        $this->cannotBeThrownOut = $cannotBeThrownOut;

        return $this;
    }

    public function getMuseumPoints(): int
    {
        return $this->museumPoints;
    }

    public function setMuseumPoints(int $museumPoints): self
    {
        $this->museumPoints = $museumPoints;

        return $this;
    }
}
