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

use App\Enum\UnlockableFeatureEnum;
use App\Functions\ArrayFunctions;
use App\Service\TransactionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table]
#[ORM\Index(name: 'name_idx', columns: ['name'])]
#[ORM\Index(name: 'last_activity_idx', columns: ['last_activity'])]
#[ORM\Entity]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const int MaxHouseInventory = 100;
    public const int MaxBasementSize = 10000;
    public const int MinPassphraseLength = 12;
    public const int MaxPassphraseLength = 128; // Reasonable maximum to prevent DoS attacks

    #[Groups(["myAccount", "myInventory", "userPublicProfile", "article", "petPublicProfile", "museum", "parkEvent", "userTypeahead", "publicStyle", "myFollowers"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['myAccount'])]
    private string $email;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    #[Groups(['myAccount'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[Groups(["myAccount", "myInventory", "userPublicProfile", "article", "petPublicProfile", "museum", "parkEvent", "userTypeahead", "publicStyle", "myFollowers"])]
    #[ORM\Column(type: 'string', length: 40)]
    private string $name;

    #[Groups(["userPublicProfile", "myFollowers"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastActivity;

    /** @var Collection<int, Pet> */
    #[ORM\OneToMany(targetEntity: Pet::class, mappedBy: 'owner', fetch: 'EXTRA_LAZY')]
    private Collection $pets;

    #[Groups(["userPublicProfile"])]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $registeredOn;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $lastAllowanceCollected;

    #[ORM\Column(type: 'boolean')]
    private bool $isLocked = false;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'integer')]
    private int $moneys = 0;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'integer')]
    private int $maxPets = 2;

    /** @var Collection<int, UserFollowing> */
    #[ORM\OneToMany(targetEntity: UserFollowing::class, mappedBy: 'user', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private Collection $following;

    /** @var Collection<int, UserFollowing> */
    #[ORM\OneToMany(targetEntity: UserFollowing::class, mappedBy: 'following', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private Collection $followedBy;

    /** @var Collection<int, UserStats> */
    #[ORM\OneToMany(targetEntity: UserStats::class, mappedBy: 'user', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private Collection $stats;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'integer')]
    private int $defaultSessionLengthInHours = 72;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'integer')]
    private int $maxSellPrice = 10;

    #[ORM\OneToOne(targetEntity: PassphraseResetRequest::class, mappedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private ?PassphraseResetRequest $passphraseResetRequest = null;

    /** @var Collection<int, GreenhousePlant> */
    #[ORM\OneToMany(targetEntity: GreenhousePlant::class, mappedBy: 'owner', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    private Collection $greenhousePlants;

    /** @var Collection<int, UserSession> */
    #[ORM\OneToMany(targetEntity: UserSession::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $userSessions;

    #[ORM\OneToOne(targetEntity: HollowEarthPlayer::class, mappedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private ?HollowEarthPlayer $hollowEarthPlayer = null;

    #[ORM\OneToOne(targetEntity: Fireplace::class, mappedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private ?Fireplace $fireplace = null;

    #[ORM\OneToOne(targetEntity: Beehive::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Beehive $beehive = null;

    #[ORM\OneToOne(targetEntity: Vault::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Vault $vault = null;

    #[ORM\OneToOne(targetEntity: Greenhouse::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?Greenhouse $greenhouse = null;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'integer')]
    private int $recyclePoints = 0;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'smallint')]
    private int $unreadNews = 0;

    #[Groups(["myAccount", "userPublicProfile", "petPublicProfile", "museum", "parkEvent", "publicStyle", "myFollowers"])]
    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $icon = null;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'smallint')]
    private int $maxMarketBids = 5;

    #[ORM\OneToOne(targetEntity: UserMenuOrder::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private UserMenuOrder $menuOrder;

    /** @var Collection<int, UserUnlockedAura>  */
    #[ORM\OneToMany(targetEntity: UserUnlockedAura::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $unlockedAuras;

    #[ORM\Column(type: 'integer')]
    private int $museumPoints = 0;

    #[ORM\Column(type: 'integer')]
    private int $museumPointsSpent = 0;

    #[Groups(["myAccount"])]
    #[ORM\Column(type: 'boolean')]
    private bool $canAssignHelpers = false;

    #[ORM\Column(type: 'integer')]
    private int $fate;

    /** @var Collection<int, UserUnlockedFeature>  */
    #[Groups(["myAccount"])]
    #[ORM\OneToMany(targetEntity: UserUnlockedFeature::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $unlockedFeatures;

    /** @var Collection<int, UserBadge>  */
    #[ORM\OneToMany(targetEntity: UserBadge::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $badges;

    #[Groups(["myAccount"])]
    #[ORM\OneToOne(targetEntity: UserSubscription::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserSubscription $subscription = null;

    /** @var Collection<int, UserFieldGuideEntry>  */
    #[ORM\OneToMany(targetEntity: UserFieldGuideEntry::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $fieldGuideEntries;

    #[ORM\OneToOne(mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private ?CookingBuddy $cookingBuddy = null;

    #[ORM\Column]
    private \DateTimeImmutable $lastPerformedQualityTime;

    #[Groups(["myAccount"])]
    #[ORM\Column]
    private int $basementSize = 1000;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;

        $this->pets = new ArrayCollection();
        $this->registeredOn = new \DateTimeImmutable();
        $this->lastAllowanceCollected = new \DateTimeImmutable()->modify('-7 days');
        $this->following = new ArrayCollection();
        $this->stats = new ArrayCollection();
        $this->greenhousePlants = new ArrayCollection();
        $this->userSessions = new ArrayCollection();
        $this->unlockedAuras = new ArrayCollection();
        $this->fate = mt_rand(0, 2147483647);
        $this->unlockedFeatures = new ArrayCollection();
        $this->badges = new ArrayCollection();
        $this->fieldGuideEntries = new ArrayCollection();

        // 3 hours & 58 minutes - players can do this action every 4 hours; this makes it appear
        // for brand new players after 2 minutes of poking around the site
        $this->lastPerformedQualityTime = new \DateTimeImmutable()->modify('-238 minutes');
    }

    public function getId(): int
    {
        return $this->id ?? throw new \LogicException('This entity has not been persisted.');
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getLastActivity(): ?\DateTimeImmutable
    {
        return $this->lastActivity;
    }

    public function setLastActivity(): self
    {
        $this->lastActivity = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Pet>
     */
    public function getPets(): Collection
    {
        return $this->pets;
    }

    public function getRegisteredOn(): \DateTimeImmutable
    {
        return $this->registeredOn;
    }

    public function getLastAllowanceCollected(): \DateTimeImmutable
    {
        return $this->lastAllowanceCollected;
    }

    public function setLastAllowanceCollected(\DateTimeImmutable $lastAllowanceCollected): self
    {
        $this->lastAllowanceCollected = $lastAllowanceCollected;

        return $this;
    }

    public function getIsLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    public function getMoneys(): int
    {
        return $this->moneys;
    }

    /**
     * @deprecated use {@see TransactionService::getMoney} and {@see TransactionService::spendMoney} instead
     */
    public function increaseMoneys(int $amount): self
    {
        $this->moneys += $amount;

        return $this;
    }

    public function getMaxPets(): int
    {
        return $this->maxPets;
    }

    public function increaseMaxPets(int $amount): self
    {
        $this->maxPets += $amount;

        return $this;
    }

    /**
     * @return Collection<int, UserFollowing>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(UserFollowing $following): self
    {
        if (!$this->following->contains($following)) {
            $this->following[] = $following;
            $following->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, UserStats>
     */
    public function getStats(): Collection
    {
        return $this->stats;
    }

    public function addStat(UserStats $stat): self
    {
        if (!$this->stats->contains($stat)) {
            $this->stats[] = $stat;
            $stat->setUser($this);
        }

        return $this;
    }

    public function getDefaultSessionLengthInHours(): int
    {
        return $this->defaultSessionLengthInHours;
    }

    public function setDefaultSessionLengthInHours(int $defaultSessionLengthInHours): self
    {
        $this->defaultSessionLengthInHours = $defaultSessionLengthInHours;

        return $this;
    }

    public function getMaxSellPrice(): int
    {
        return $this->maxSellPrice;
    }

    public function setMaxSellPrice(int $maxSellPrice): self
    {
        $this->maxSellPrice = $maxSellPrice;

        return $this;
    }

    public function getPassphraseResetRequest(): ?PassphraseResetRequest
    {
        return $this->passphraseResetRequest;
    }

    public function setPassphraseResetRequest(PassphraseResetRequest $passphraseResetRequest): self
    {
        $this->passphraseResetRequest = $passphraseResetRequest;

        // set the owning side of the relation if necessary
        if ($this !== $passphraseResetRequest->getUser()) {
            $passphraseResetRequest->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, GreenhousePlant>
     */
    public function getGreenhousePlants(): Collection
    {
        return $this->greenhousePlants;
    }

    public function addGreenhousePlant(GreenhousePlant $greenhousePlant): self
    {
        if (!$this->greenhousePlants->contains($greenhousePlant)) {
            $this->greenhousePlants[] = $greenhousePlant;
            $greenhousePlant->setOwner($this);
        }

        return $this;
    }

    public function removeGreenhousePlant(GreenhousePlant $greenhousePlant): self
    {
        if ($this->greenhousePlants->contains($greenhousePlant)) {
            $this->greenhousePlants->removeElement($greenhousePlant);
            // set the owning side to null (unless already changed)
            if ($greenhousePlant->getOwner() === $this) {
                $greenhousePlant->setOwner(null);
            }
        }

        return $this;
    }

    #[Groups(["myAccount"])]
    public function getMaxPlants(): int
    {
        return $this->getGreenhouse() ? $this->getGreenhouse()->getMaxPlants() : 0;
    }

    #[Groups(["myAccount"])]
    public function getIsVaultOpen(): bool
    {
        return $this->vault?->isOpen() ?? false;
    }

    #[Groups(["myAccount"])]
    public function getVaultOpenUntil(): ?string
    {
        return $this->vault?->getOpenUntil()?->format('c');
    }

    /**
     * @return Collection<int, UserSession>
     */
    public function getUserSessions(): Collection
    {
        return $this->userSessions;
    }

    public function getHollowEarthPlayer(): ?HollowEarthPlayer
    {
        return $this->hollowEarthPlayer;
    }

    public function setHollowEarthPlayer(HollowEarthPlayer $hollowEarthPlayer): self
    {
        $this->hollowEarthPlayer = $hollowEarthPlayer;

        // set the owning side of the relation if necessary
        if ($this !== $hollowEarthPlayer->getUser()) {
            $hollowEarthPlayer->setUser($this);
        }

        return $this;
    }

    public function getFireplace(): ?Fireplace
    {
        return $this->fireplace;
    }

    public function setFireplace(Fireplace $fireplace): self
    {
        $this->fireplace = $fireplace;

        // set the owning side of the relation if necessary
        if ($this !== $fireplace->getUser()) {
            $fireplace->setUser($this);
        }

        return $this;
    }

    public function getBeehive(): ?Beehive
    {
        return $this->beehive;
    }

    public function setBeehive(Beehive $beehive): self
    {
        $this->beehive = $beehive;

        // set the owning side of the relation if necessary
        if ($this !== $beehive->getUser()) {
            $beehive->setUser($this);
        }

        return $this;
    }

    public function getVault(): ?Vault
    {
        return $this->vault;
    }

    public function setVault(Vault $vault): self
    {
        $this->vault = $vault;

        // set the owning side of the relation if necessary
        if ($this !== $vault->getUser()) {
            $vault->setUser($this);
        }

        return $this;
    }

    public function getGreenhouse(): ?Greenhouse
    {
        return $this->greenhouse;
    }

    public function setGreenhouse(Greenhouse $greenhouse): self
    {
        $this->greenhouse = $greenhouse;

        // set the owning side of the relation if necessary
        if ($this !== $greenhouse->getOwner()) {
            $greenhouse->setOwner($this);
        }

        return $this;
    }

    public function getRecyclePoints(): int
    {
        return $this->recyclePoints;
    }

    /**
     * @deprecated use {@see TransactionService::getRecyclingPoints} and {@see TransactionService::spendRecyclingPoints} instead
     */
    public function increaseRecyclePoints(int $recyclePoints): self
    {
        $this->recyclePoints += $recyclePoints;

        return $this;
    }

    public function getUnreadNews(): int
    {
        return $this->unreadNews;
    }

    public function setUnreadNews(int $unreadNews): self
    {
        $this->unreadNews = $unreadNews;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getMaxMarketBids(): int
    {
        return $this->maxMarketBids;
    }

    public function increaseMaxMarketBids(int $amount): self
    {
        $this->maxMarketBids += $amount;

        return $this;
    }

    public function getMenuOrder(): ?UserMenuOrder
    {
        return $this->menuOrder;
    }

    public function setMenuOrder(UserMenuOrder $menuOrder): self
    {
        // set the owning side of the relation if necessary
        if ($menuOrder->getUser() !== $this) {
            $menuOrder->setUser($this);
        }

        $this->menuOrder = $menuOrder;

        return $this;
    }

    /**
     * @return Collection<int, UserUnlockedAura>
     */
    public function getUnlockedAuras(): Collection
    {
        return $this->unlockedAuras;
    }

    public function getMuseumPoints(): int
    {
        return $this->museumPoints;
    }

    /**
     * @deprecated use {@see TransactionService::getMuseumFavor} instead
     */
    public function addMuseumPoints(int $museumPoints): self
    {
        $this->museumPoints += $museumPoints;

        return $this;
    }

    public function getMuseumPointsSpent(): int
    {
        return $this->museumPointsSpent;
    }

    /**
     * @deprecated use {@see TransactionService::spendMuseumFavor} instead
     */
    public function addMuseumPointsSpent(int $museumPointsSpent): self
    {
        $this->museumPointsSpent += $museumPointsSpent;

        return $this;
    }

    public function getCanAssignHelpers(): bool
    {
        return $this->canAssignHelpers;
    }

    public function setCanAssignHelpers(bool $canAssignHelpers): self
    {
        $this->canAssignHelpers = $canAssignHelpers;

        return $this;
    }

    public function getFate(): int
    {
        return $this->fate;
    }

    public function setFate(): self
    {
        $this->fate = mt_rand(0, 2147483647);

        return $this;
    }

    public function getDailySeed(): int
    {
        return (($this->fate * date('N')) % (date('nd') * 53)) + (int)date('Yj');
    }

    /**
     * @return Collection<int, UserUnlockedFeature>
     */
    public function getUnlockedFeatures(): Collection
    {
        return $this->unlockedFeatures;
    }

    public function addUnlockedFeature(UserUnlockedFeature $unlockedFeature): self
    {
        if (!$this->unlockedFeatures->contains($unlockedFeature)) {
            $this->unlockedFeatures[] = $unlockedFeature;
            $unlockedFeature->setUser($this);
        }

        return $this;
    }

    public function hasUnlockedFeature(UnlockableFeatureEnum $feature): bool
    {
        return $this->unlockedFeatures->exists(
            fn($key, UserUnlockedFeature $unlockedFeature) => $unlockedFeature->getFeature() === $feature
        );
    }

    public function getUnlockedFeatureDate(UnlockableFeatureEnum $feature): ?\DateTimeImmutable
    {
        $unlockedFeature = $this->unlockedFeatures->findFirst(
            fn($key, UserUnlockedFeature $unlockedFeature) => $unlockedFeature->getFeature() === $feature
        );

        if(!$unlockedFeature)
            return null;

        return $unlockedFeature->getUnlockedOn();
    }

    /**
     * @return Collection<int, UserBadge>
     */
    public function getBadges(): Collection
    {
        return $this->badges;
    }

    public function getSubscription(): ?UserSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(?UserSubscription $subscription): self
    {
        // unset the owning side of the relation if necessary
        if ($subscription === null && $this->subscription !== null) {
            $this->subscription->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($subscription !== null && $subscription->getUser() !== $this) {
            $subscription->setUser($this);
        }

        $this->subscription = $subscription;

        return $this;
    }

    /**
     * @return Collection<int, UserFieldGuideEntry>
     */
    public function getFieldGuideEntries(): Collection
    {
        return $this->fieldGuideEntries;
    }

    public function getCookingBuddy(): ?CookingBuddy
    {
        return $this->cookingBuddy;
    }

    public function setCookingBuddy(CookingBuddy $cookingBuddy): static
    {
        // set the owning side of the relation if necessary
        if ($cookingBuddy->getOwner() !== $this) {
            $cookingBuddy->setOwner($this);
        }

        $this->cookingBuddy = $cookingBuddy;

        return $this;
    }

    #[Groups(['myAccount'])]
    public function getLastPerformedQualityTime(): \DateTimeImmutable
    {
        return $this->lastPerformedQualityTime;
    }

    public function setLastPerformedQualityTime(): static
    {
        $this->lastPerformedQualityTime = new \DateTimeImmutable();

        return $this;
    }

    public function hasFieldGuideEntry(string $entryName): bool
    {
        return $this->fieldGuideEntries->exists(
            fn(int $key, UserFieldGuideEntry $entry) => $entry->getEntry()->getName() === $entryName
        );
    }

    public function getBasementSize(): int
    {
        return $this->basementSize;
    }

    public function increaseBasementSize(int $growth): static
    {
        $this->basementSize = min(self::MaxBasementSize, $this->basementSize + $growth);

        return $this;
    }
}
