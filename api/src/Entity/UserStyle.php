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
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'user_id_name_idx', columns: ['user_id', 'name'])]
#[ORM\Entity]
class UserStyle
{
    public const string Current = 'Current';

    /**
     * @var string[]
     */
    const array Properties = [
        'backgroundColor',
        'speechBubbleBackgroundColor',
        'textColor',
        'primaryColor',
        'textOnPrimaryColor',
        'tabBarBackgroundColor',
        'linkAndButtonColor',
        'buttonTextColor',
        'dialogLinkColor',
        'warningColor',
        'gainColor',
        'bonusAndSpiceColor',
        'bonusAndSpiceSelectedColor',
        'inputBackgroundColor',
        'inputTextColor'
    ];

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[Groups(["publicStyle"])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Groups(["myStyle"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $backgroundColor = 'EEEEEE';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $speechBubbleBackgroundColor = 'FFFFFF';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $textColor = '333333';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $primaryColor = '225588';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $textOnPrimaryColor = 'FFFFFF';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $tabBarBackgroundColor = 'BBBBBB';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $linkAndButtonColor = '4477AA';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $buttonTextColor = 'FFFFFF';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $dialogLinkColor = '4477AA';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $warningColor = 'CC4422';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $gainColor = '228844';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $bonusAndSpiceColor = '009999';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $bonusAndSpiceSelectedColor = '00CCCC';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $inputBackgroundColor = 'FFFFFF';

    #[Groups(["myStyle", "publicStyle"])]
    #[ORM\Column(type: 'string', length: 6)]
    private string $inputTextColor = '333333';

    public function __construct(User $user, string $name)
    {
        $this->user = $user;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getUser(): User
    {
        return $this->user;
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

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getSpeechBubbleBackgroundColor(): ?string
    {
        return $this->speechBubbleBackgroundColor;
    }

    public function setSpeechBubbleBackgroundColor(string $speechBubbleBackgroundColor): self
    {
        $this->speechBubbleBackgroundColor = $speechBubbleBackgroundColor;

        return $this;
    }

    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    public function setTextColor(string $textColor): self
    {
        $this->textColor = $textColor;

        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(string $primaryColor): self
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function getTextOnPrimaryColor(): ?string
    {
        return $this->textOnPrimaryColor;
    }

    public function setTextOnPrimaryColor(string $textOnPrimaryColor): self
    {
        $this->textOnPrimaryColor = $textOnPrimaryColor;

        return $this;
    }

    public function getTabBarBackgroundColor(): ?string
    {
        return $this->tabBarBackgroundColor;
    }

    public function setTabBarBackgroundColor(string $tabBarBackgroundColor): self
    {
        $this->tabBarBackgroundColor = $tabBarBackgroundColor;

        return $this;
    }

    public function getLinkAndButtonColor(): ?string
    {
        return $this->linkAndButtonColor;
    }

    public function setLinkAndButtonColor(string $linkAndButtonColor): self
    {
        $this->linkAndButtonColor = $linkAndButtonColor;

        return $this;
    }

    public function getButtonTextColor(): ?string
    {
        return $this->buttonTextColor;
    }

    public function setButtonTextColor(string $buttonTextColor): self
    {
        $this->buttonTextColor = $buttonTextColor;

        return $this;
    }

    public function getDialogLinkColor(): ?string
    {
        return $this->dialogLinkColor;
    }

    public function setDialogLinkColor(string $dialogLinkColor): self
    {
        $this->dialogLinkColor = $dialogLinkColor;

        return $this;
    }

    public function getWarningColor(): ?string
    {
        return $this->warningColor;
    }

    public function setWarningColor(string $warningColor): self
    {
        $this->warningColor = $warningColor;

        return $this;
    }

    public function getGainColor(): ?string
    {
        return $this->gainColor;
    }

    public function setGainColor(string $gainColor): self
    {
        $this->gainColor = $gainColor;

        return $this;
    }

    public function getBonusAndSpiceColor(): ?string
    {
        return $this->bonusAndSpiceColor;
    }

    public function setBonusAndSpiceColor(string $bonusAndSpiceColor): self
    {
        $this->bonusAndSpiceColor = $bonusAndSpiceColor;

        return $this;
    }

    public function getBonusAndSpiceSelectedColor(): ?string
    {
        return $this->bonusAndSpiceSelectedColor;
    }

    public function setBonusAndSpiceSelectedColor(string $bonusAndSpiceSelectedColor): self
    {
        $this->bonusAndSpiceSelectedColor = $bonusAndSpiceSelectedColor;

        return $this;
    }

    public function getInputBackgroundColor(): ?string
    {
        return $this->inputBackgroundColor;
    }

    public function setInputBackgroundColor(string $inputBackgroundColor): self
    {
        $this->inputBackgroundColor = $inputBackgroundColor;

        return $this;
    }

    public function getInputTextColor(): ?string
    {
        return $this->inputTextColor;
    }

    public function setInputTextColor(string $inputTextColor): self
    {
        $this->inputTextColor = $inputTextColor;

        return $this;
    }
}
