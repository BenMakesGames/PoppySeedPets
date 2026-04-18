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

use App\Enum\FlavorEnum;
use App\Enum\PetSkillEnum;
use App\Enum\StatusEffectEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Table]
#[ORM\Index(name: 'is_candy_idx', columns: ['is_candy'])]
#[ORM\Entity]
class ItemFood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $food = 0;

    #[ORM\Column(type: 'integer')]
    private int $love = 0;

    #[ORM\Column(type: 'integer')]
    private int $junk = 0;

    #[ORM\Column(type: 'integer')]
    private int $alcohol = 0;

    #[ORM\Column(type: 'integer')]
    private int $caffeine = 0;

    #[ORM\Column(type: 'integer')]
    private int $psychedelic = 0;

    #[ORM\Column(type: 'integer')]
    private int $earthy = 0;

    #[ORM\Column(type: 'integer')]
    private int $fruity = 0;

    #[ORM\Column(type: 'integer')]
    private int $tannic = 0;

    #[ORM\Column(type: 'integer')]
    private int $spicy = 0;

    #[ORM\Column(type: 'integer')]
    private int $creamy = 0;

    #[ORM\Column(type: 'integer')]
    private int $meaty = 0;

    #[ORM\Column(type: 'integer')]
    private int $planty = 0;

    #[ORM\Column(type: 'integer')]
    private int $fishy = 0;

    #[ORM\Column(type: 'integer')]
    private int $floral = 0;

    #[ORM\Column(type: 'integer')]
    private int $fatty = 0;

    #[ORM\Column(type: 'integer')]
    private int $oniony = 0;

    #[ORM\Column(type: 'integer')]
    private int $chemically = 0;

    #[Groups(["myInventory", "itemEncyclopedia"])]
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $grantedSkill = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $chanceForBonusItem = null;

    #[ORM\Column(type: 'integer')]
    private int $randomFlavor = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $containsTentacles = false;

    #[ORM\Column(type: 'string', length: 40, nullable: true, enumType: StatusEffectEnum::class)]
    private ?StatusEffectEnum $grantedStatusEffect = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $grantedStatusEffectDuration = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isCandy = false;

    #[ORM\OneToOne(targetEntity: Spice::class, mappedBy: 'effects', cascade: ['persist', 'remove'])]
    private ?Spice $spice = null;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    private ?Item $leftovers = null;

    #[ORM\ManyToOne(targetEntity: ItemGroup::class)]
    private ?ItemGroup $bonusItemGroup = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFood(): int
    {
        return $this->food;
    }

    public function setFood(int $food): self
    {
        $this->food = $food;

        return $this;
    }

    public function getLove(): int
    {
        return $this->love;
    }

    public function setLove(int $love): self
    {
        $this->love = $love;

        return $this;
    }

    public function getJunk(): int
    {
        return $this->junk;
    }

    public function setJunk(int $junk): self
    {
        $this->junk = $junk;

        return $this;
    }

    public function getAlcohol(): int
    {
        return $this->alcohol;
    }

    public function setAlcohol(int $alcohol): self
    {
        $this->alcohol = $alcohol;

        return $this;
    }

    public function getCaffeine(): int
    {
        return $this->caffeine;
    }

    public function setCaffeine(int $caffeine): self
    {
        $this->caffeine = $caffeine;

        return $this;
    }

    public function getPsychedelic(): int
    {
        return $this->psychedelic;
    }

    public function setPsychedelic(int $psychedelic): self
    {
        $this->psychedelic = $psychedelic;

        return $this;
    }

    public function getEarthy(): int
    {
        return $this->earthy;
    }

    public function setEarthy(int $earthy): self
    {
        $this->earthy = $earthy;

        return $this;
    }

    public function getFruity(): int
    {
        return $this->fruity;
    }

    public function setFruity(int $fruity): self
    {
        $this->fruity = $fruity;

        return $this;
    }

    public function getTannic(): int
    {
        return $this->tannic;
    }

    public function setTannic(int $tannic): self
    {
        $this->tannic = $tannic;

        return $this;
    }

    public function getSpicy(): int
    {
        return $this->spicy;
    }

    public function setSpicy(int $spicy): self
    {
        $this->spicy = $spicy;

        return $this;
    }

    public function getCreamy(): int
    {
        return $this->creamy;
    }

    public function setCreamy(int $creamy): self
    {
        $this->creamy = $creamy;

        return $this;
    }

    public function getMeaty(): int
    {
        return $this->meaty;
    }

    public function setMeaty(int $meaty): self
    {
        $this->meaty = $meaty;

        return $this;
    }

    public function getPlanty(): int
    {
        return $this->planty;
    }

    public function setPlanty(int $planty): self
    {
        $this->planty = $planty;

        return $this;
    }

    public function getFishy(): int
    {
        return $this->fishy;
    }

    public function setFishy(int $fishy): self
    {
        $this->fishy = $fishy;

        return $this;
    }

    public function getFloral(): int
    {
        return $this->floral;
    }

    public function setFloral(int $floral): self
    {
        $this->floral = $floral;

        return $this;
    }

    public function getFatty(): int
    {
        return $this->fatty;
    }

    public function setFatty(int $fatty): self
    {
        $this->fatty = $fatty;

        return $this;
    }

    public function getOniony(): int
    {
        return $this->oniony;
    }

    public function setOniony(int $oniony): self
    {
        $this->oniony = $oniony;

        return $this;
    }

    public function getChemically(): int
    {
        return $this->chemically;
    }

    public function setChemically(int $chemically): self
    {
        $this->chemically = $chemically;

        return $this;
    }

    public function add(ItemFood $f): ItemFood
    {
        $added = clone $this;

        $added->food += $f->food;
        $added->love += $f->love;
        $added->junk += $f->junk;
        $added->alcohol += $f->alcohol;
        $added->caffeine += $f->caffeine;
        $added->psychedelic += $f->psychedelic;

        return $added;
    }

    public function multiply(int $f): ItemFood
    {
        $multiplied = clone $this;

        $multiplied->food *= $f;
        $multiplied->love *= $f;
        $multiplied->junk *= $f;
        $multiplied->alcohol *= $f;
        $multiplied->caffeine *= $f;
        $multiplied->psychedelic *= $f;

        return $multiplied;
    }

    #[Groups(["myInventory", "itemEncyclopedia", "marketItem"])]
    public function getModifiers(): array
    {
        $modifiers = [];

        if($this->getGrantsSelfReflection())
            $modifiers[] = 'a pet that eats this will reconcile with another pet at your advice!';

        if($this->food > 9)
            $modifiers[] = 'a huge meal';
        else if($this->food > 6)
            $modifiers[] = 'a meal';
        else if($this->food > 3)
            $modifiers[] = 'a small meal';
        else if($this->food > 1)
            $modifiers[] = 'a snack';
        else if($this->food === 1)
            $modifiers[] = 'a morsel';
        else if($this->getSpice() === null)
            $modifiers[] = 'no food value';

        if($this->love > 2) $modifiers[] = 'delicious!';
        else if($this->love > 0) $modifiers[] = 'tastes good';
        else if($this->love < 0) $modifiers[] = 'acquired taste';

        if($this->junk > 9)
            $modifiers[] = 'very junky';
        else if($this->junk > 5)
            $modifiers[] = 'junky';
        else if($this->junk > 2)
            $modifiers[] = 'somewhat junky';
        else if($this->junk > 0)
            $modifiers[] = 'slightly junky';
        else if($this->junk < 0)
            $modifiers[] = 'alleviates poison!';

        if($this->alcohol > 6)
            $modifiers[] = 'very alcoholic';
        else if($this->alcohol > 3)
            $modifiers[] = 'alcoholic';
        else if($this->alcohol > 0)
            $modifiers[] = 'mildly alcoholic';

        if($this->caffeine > 6)
            $modifiers[] = 'very caffeinated';
        else if($this->caffeine > 3)
            $modifiers[] = 'caffeinated';
        else if($this->caffeine > 0)
            $modifiers[] = 'mildly caffeinated';

        if($this->psychedelic > 6)
            $modifiers[] = 'very trippy';
        else if($this->psychedelic > 3)
            $modifiers[] = 'trippy';
        else if($this->psychedelic > 0)
            $modifiers[] = 'slightly trippy';

        $hasAnyFixedFlavor = false;

        foreach(FlavorEnum::cases() as $flavor)
        {
            if($this->{$flavor->value} > 0)
            {
                if($this->{$flavor->value} > 4)
                    $modifiers[] = 'extremely ' . $flavor->value;
                else if($this->{$flavor->value} > 2)
                    $modifiers[] = 'very ' . $flavor->value;
                else
                    $modifiers[] = $flavor->value;

                $hasAnyFixedFlavor = true;
            }
        }

        if($this->containsTentacles)
            $modifiers[] = 'tentacle-y';

        if($this->randomFlavor > 0)
        {
            if($hasAnyFixedFlavor)
                $modifiers[] = 'also has a' . ($this->randomFlavor > 2 ? ' strong' : '') . ' random flavor';
            else
                $modifiers[] = 'has a' . ($this->randomFlavor > 2 ? ' strong' : '') . ' random flavor';
        }

        return $modifiers;
    }

    public function getGrantedSkill(): ?string
    {
        return $this->grantedSkill;
    }

    public function setGrantedSkill(?string $grantedSkill): self
    {
        if($grantedSkill !== null && !PetSkillEnum::isAValue($grantedSkill))
            throw new \InvalidArgumentException('$grantedSkill must be null, or a PetSkillEnum value.');

        $this->grantedSkill = $grantedSkill;

        return $this;
    }

    public function getChanceForBonusItem(): ?int
    {
        return $this->chanceForBonusItem;
    }

    public function setChanceForBonusItem(?int $chanceForBonusItem): self
    {
        $this->chanceForBonusItem = $chanceForBonusItem;

        return $this;
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getBringsLuck(): ?string
    {
        return $this->bonusItemGroup?->getName();
    }

    public function getRandomFlavor(): int
    {
        return $this->randomFlavor;
    }

    public function setRandomFlavor(int $randomFlavor): self
    {
        $this->randomFlavor = $randomFlavor;

        return $this;
    }

    public function getContainsTentacles(): bool
    {
        return $this->containsTentacles;
    }

    public function setContainsTentacles(bool $containsTentacles): self
    {
        $this->containsTentacles = $containsTentacles;

        return $this;
    }

    #[Groups(["myInventory", "itemEncyclopedia"])]
    public function getGrantedStatusEffect(): ?StatusEffectEnum
    {
        return $this->grantedStatusEffect;
    }

    public function setGrantedStatusEffect(?StatusEffectEnum $grantedStatusEffect): self
    {
        $this->grantedStatusEffect = $grantedStatusEffect;

        return $this;
    }

    public function getGrantedStatusEffectDuration(): ?int
    {
        return $this->grantedStatusEffectDuration;
    }

    public function setGrantedStatusEffectDuration(?int $grantedStatusEffectDuration): self
    {
        $this->grantedStatusEffectDuration = $grantedStatusEffectDuration;

        return $this;
    }

    #[Groups(['myInventory', 'itemEncyclopedia'])]
    #[SerializedName('candy')]
    public function getIsCandy(): bool
    {
        return $this->isCandy;
    }

    public function setIsCandy(): self
    {
        $this->isCandy = $this->love > $this->food - $this->junk / 2;

        return $this;
    }

    public function getSpice(): ?Spice
    {
        return $this->spice;
    }

    public function setSpice(Spice $spice): self
    {
        $this->spice = $spice;

        // set the owning side of the relation if necessary
        if ($spice->getEffects() !== $this) {
            $spice->setEffects($this);
        }

        return $this;
    }

    public function getLeftovers(): ?Item
    {
        return $this->leftovers;
    }

    public function setLeftovers(?Item $leftovers): self
    {
        $this->leftovers = $leftovers;

        return $this;
    }

    #[Groups(['myInventory', 'itemEncyclopedia'])]
    #[SerializedName('leftovers')]
    public function getLeftoversName(): ?string
    {
        return $this->getLeftovers()?->getName();
    }

    public function getBonusItemGroup(): ?ItemGroup
    {
        return $this->bonusItemGroup;
    }

    public function setBonusItemGroup(?ItemGroup $bonusItemGroup): self
    {
        $this->bonusItemGroup = $bonusItemGroup;

        return $this;
    }

    public function getGrantsSelfReflection(): bool
    {
        return $this->id === 390;
    }
}
