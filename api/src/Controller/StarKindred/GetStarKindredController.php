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

namespace App\Controller\StarKindred;

use App\Entity\Enchantment;
use App\Entity\Item;
use App\Entity\StarKindredCharacter;
use App\Entity\User;
use App\Entity\UserUnlockedAura;
use App\Enum\SerializationGroupEnum;
use App\Enum\StarKindredDifficultyEnum;
use App\Enum\UnlockableFeatureEnum;
use App\Exceptions\PSPNotUnlockedException;
use App\Model\StarKindred\StarKindredAdventure;
use App\Model\StarKindred\StarKindredEncounter;
use App\Model\StarKindred\StarKindredReward;
use App\Service\Clock;
use App\Service\ResponseService;
use App\Service\StarKindred\StarKindredAdventureService;
use App\Service\StarKindred\StarKindredCharacterSheet;
use App\Service\StarKindred\StarKindredDailyAdventures;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/starKindred")]
class GetStarKindredController
{
    #[Route("", methods: ["GET"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function handle(
        ResponseService $responseService, EntityManagerInterface $em, UserAccessor $userAccessor,
        StarKindredAdventureService $starKindred, Clock $clock
    ): JsonResponse
    {
        $user = $userAccessor->getUserOrThrow();

        if(!$user->hasUnlockedFeature(UnlockableFeatureEnum::StarKindred))
            throw new PSPNotUnlockedException('★Kindred');

        /** @var StarKindredCharacter[] $characters */
        $characters = $em->getRepository(StarKindredCharacter::class)->createQueryBuilder('c')
            ->join('c.pet', 'p')
            ->andWhere('p.owner = :user')
            ->andWhere('c.retiredOn IS NULL')
            ->setParameter('user', $user)
            ->addOrderBy('c.level', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->execute();

        $adventures = StarKindredDailyAdventures::forDate($clock->now);

        $rewards = [
            ...array_merge(...array_map(fn(StarKindredAdventure $a) => $a->rewards, $adventures)),
            ...array_map(
                fn(string $item) => StarKindredReward::item(StarKindredDifficultyEnum::Novice, $item, 1),
                StarKindredAdventureService::RetirementRewardsPerAdventurer
            ),
            StarKindredReward::aura(StarKindredDifficultyEnum::Novice, StarKindredAdventureService::RetirementAura),
        ];

        $rewardMapper = new RewardMapper($em, $user, $rewards);

        $todaysPlay = $starKindred->findTodaysPlay($user);

        return $responseService->success(
            [
                'todaysPlay' => $todaysPlay === null ? null : [
                    'adventureId' => $todaysPlay->getAdventureId(),
                    'rewardsWon' => $todaysPlay->getRewardsWon(),
                ],
                'maxPartySize' => StarKindredAdventureService::MaxPartySize,
                'maxAdventurers' => StarKindredAdventureService::MaxAdventurers,
                'maxLevel' => StarKindredCharacter::MaxLevel,
                'adventures' => array_map(
                    fn(StarKindredAdventure $a) => [
                        'id' => $a->id,
                        'theme' => $a->theme->value,
                        'title' => $a->title,
                        'summary' => $a->summary,
                        'encounters' => array_map(fn(StarKindredEncounter $e) => [ 'title' => $e->title, 'skill' => $e->skill->value, 'againstUndead' => $e->againstUndead ], $a->encounters),
                        'skillsTested' => array_map(fn($s) => $s->value, $a->getSkillsTested()),
                        'hasUndeadEncounters' => $a->hasUndeadEncounters(),
                        'rewards' => array_map($rewardMapper->map(...), $a->rewards),
                    ],
                    $adventures
                ),
                'retirementRewards' => [
                    'perAdventurer' => array_map(
                        fn(string $item) => $rewardMapper->map(StarKindredReward::item(StarKindredDifficultyEnum::Novice, $item, 1)),
                        StarKindredAdventureService::RetirementRewardsPerAdventurer
                    ),
                    'aura' => $rewardMapper->map(StarKindredReward::aura(StarKindredDifficultyEnum::Novice, StarKindredAdventureService::RetirementAura)),
                ],
                'difficulties' => array_map(
                    fn(StarKindredDifficultyEnum $d) => [
                        'name' => $d->value,
                        'baseTarget' => $d->baseTarget(),
                        'targetPerAdventurer' => $d->targetPerCharacter(),
                        'victoryExperience' => $d->victoryExperience(),
                    ],
                    StarKindredDifficultyEnum::cases()
                ),
                'characters' => array_map(StarKindredCharacterSheet::map(...), $characters),
            ],
            [ SerializationGroupEnum::PET_PUBLIC_PROFILE ]
        );
    }
}

/**
 * Looks up every reward's item/hat-styling in one query each, so the page can show images - and whether
 * the player already has a hat styling, since collecting one twice gets them nothing.
 */
class RewardMapper
{
    /** @var array<string, Item> */
    private array $items = [];

    /** @var array<string, Enchantment> */
    private array $auras = [];

    /** @var int[] */
    private array $unlockedAuraIds;

    /**
     * @param StarKindredReward[] $rewards
     */
    public function __construct(EntityManagerInterface $em, User $user, array $rewards)
    {
        $itemNames = array_values(array_unique(array_filter(array_map(fn(StarKindredReward $r) => $r->item, $rewards))));
        $auraNames = array_values(array_unique(array_filter(array_map(fn(StarKindredReward $r) => $r->aura, $rewards))));

        /** @var Item[] $items */
        $items = count($itemNames) === 0 ? [] : $em->getRepository(Item::class)->findBy([ 'name' => $itemNames ]);

        foreach($items as $item)
            $this->items[$item->getName()] = $item;

        /** @var Enchantment[] $auras */
        $auras = count($auraNames) === 0 ? [] : $em->getRepository(Enchantment::class)->findBy([ 'name' => $auraNames ]);

        foreach($auras as $aura)
            $this->auras[$aura->getName()] = $aura;

        $this->unlockedAuraIds = array_map(
            fn(UserUnlockedAura $u) => $u->getAura()->getId(),
            $user->getUnlockedAuras()->toArray()
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function map(StarKindredReward $reward): array
    {
        if($reward->aura)
        {
            $enchantment = $this->auras[$reward->aura] ?? throw new \Exception("★Kindred hat styling \"{$reward->aura}\" does not exist!");

            return [
                'difficulty' => $reward->difficulty->value,
                'item' => null,
                'aura' => [
                    'name' => $enchantment->getName(),
                    'image' => $enchantment->getAura()?->getImage(),
                    'alreadyUnlocked' => in_array($enchantment->getId(), $this->unlockedAuraIds, true),
                ],
            ];
        }

        $item = $this->items[$reward->item] ?? throw new \Exception("★Kindred reward item \"{$reward->item}\" does not exist!");

        return [
            'difficulty' => $reward->difficulty->value,
            'item' => [
                'name' => $item->getName(),
                'image' => $item->getImage(),
                'quantity' => $reward->quantity,
            ],
            'aura' => null,
        ];
    }
}
