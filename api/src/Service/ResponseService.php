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

namespace App\Service;

use App\Entity\Item;
use App\Entity\Pet;
use App\Entity\PetActivityLogItem;
use App\Entity\PetActivityLogTag;
use App\Entity\UnreadPetActivityLog;
use App\Entity\User;
use App\Enum\PetActivityLogInterestingness;
use App\Enum\SerializationGroupEnum;
use App\Functions\ArrayFunctions;
use App\Functions\NormalizerHelpers;
use App\Functions\UserMenuFunctions;
use App\Model\PetChangesSummary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Functions\JsonResponseFactory;

class ResponseService
{
    /** @var FlashMessage[] */ private array $flashMessages = [];
    private bool $reloadInventory = false;
    private bool $reloadPets = false;
    private ?string $sessionId = null;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly NormalizerInterface $normalizer,
        private readonly EntityManagerInterface $em,
        private readonly UserAccessor $userAccessor
    )
    {
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function itemActionSuccess(?string $markdown, array $data = []): JsonResponse
    {
        $data = array_merge($data, [
            'text' => $markdown
        ]);

        return $this->success($data);
    }

    /**
     * @param string[] $groups
     */
    public function success(mixed $data = null, array $groups = []): JsonResponse
    {
        $user = $this->userAccessor->getUser();

        $responseData = [
            'success' => true,
        ];

        if($data !== null)
            $responseData['data'] = $this->normalizer->normalize($data, null, [ 'groups' => $groups ]);

        $activity = $this->getUnreadMessages($user);

        if(count($activity) > 0)
            $responseData['activity'] = $this->normalizer->normalize($activity, null, [ 'groups' => [ SerializationGroupEnum::PET_ACTIVITY_LOGS ] ]);

        if($this->sessionId !== null)
        {
            if($_SERVER['APP_ENV'] === 'dev')
                setcookie('sessionId', $this->sessionId, time() + 60 * 60 * 24 * 7, '/', 'localhost', false, true);
            else
                setcookie('sessionId', $this->sessionId, time() + 60 * 60 * 24 * 7, '/', 'poppyseedpets.com', true, true);
        }

        $this->injectUserData($responseData);

        if($this->reloadInventory) $responseData['reloadInventory'] = true;
        if($this->reloadPets) $responseData['reloadPets'] = true;

        return JsonResponseFactory::create($this->serializer, $responseData, $groups);
    }

    /**
     * @return FlashMessage[]
     */
    private function getUnreadMessages(?User $user): array
    {
        if($user === null)
            return $this->flashMessages;

        $unreadMessages = $this->findUnreadForUser($user);

        // for whatever reason, doing this results in fewer serialization deadlocks
        $query = $this->em->createQuery('
            DELETE FROM App\Entity\UnreadPetActivityLog l
            WHERE l.petActivityLog IN (:messageIds)
        ');

        $messageIds = array_map(
            fn(FlashMessage $l) => $l->id,
            $unreadMessages
        );

        $query->setParameter('messageIds', $messageIds);

        $query->execute();

        $this->em->flush();

        return ArrayFunctions::unique(
            array_merge($this->flashMessages, $unreadMessages),
            fn(FlashMessage $l) => $l->entry,
        );
    }

    public function error(int $httpResponse, array $messages): JsonResponse
    {
        $responseData = [
            'success' => false,
            'errors' => $messages,
        ];

        $this->injectUserData($responseData);

        return JsonResponseFactory::create($this->serializer, $responseData, [], $httpResponse);
    }

    /**
     * @return FlashMessage[]
     */
    public function findUnreadForUser(User $user): array
    {
        $logs = $this->em->getRepository(UnreadPetActivityLog::class)->createQueryBuilder('a')
            ->select('a')
            ->join('a.pet', 'p')
            ->join('a.petActivityLog', 'l')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return array_map(
            function(UnreadPetActivityLog $l) {
                $message = new FlashMessage(
                    $l->getPetActivityLog()->getId(),
                    $l->getPetActivityLog()->getEntry(),
                    $l->getPetActivityLog()->getIcon(),
                    $l->getPetActivityLog()->getChanges(),
                    $l->getPetActivityLog()->getInterestingness()
                );

                $message
                    ->setPet($l->getPet())
                    ->setEquippedItem($l->getPetActivityLog()->getEquippedItem())
                    ->setTags($l->getPetActivityLog()->getTags()->toArray())
                    ->setCreatedItems($l->getPetActivityLog()->getCreatedItems()->toArray());

                return $message;
            },
            $logs
        );
    }

    private function injectUserData(array &$responseData): void
    {
        $user = $this->userAccessor->getUser();

        if($user)
        {
            $responseData['user'] = NormalizerHelpers::normalizeToArray($this->normalizer, $user, null, [ 'groups' => [ SerializationGroupEnum::MY_ACCOUNT ] ]);
            $responseData['user']['menu'] = $this->normalizer->normalize(UserMenuFunctions::getUserMenuItems($this->em, $user), null, [ 'groups' => [ SerializationGroupEnum::MY_MENU ] ]);
        }
    }

    public function setReloadPets(bool $reload = true): self
    {
        $this->reloadPets = $reload;
        return $this;
    }

    public function getReloadPets(): bool
    {
        return $this->reloadPets;
    }

    public function setReloadInventory(bool $reload = true): self
    {
        $this->reloadInventory = $reload;
        return $this;
    }

    public function getReloadInventory(): bool
    {
        return $this->reloadInventory;
    }

    public function addFlashMessage(string $message): self
    {
        $this->flashMessages[] = new FlashMessage(0, $message, '', null, PetActivityLogInterestingness::PlayerActionResponse);

        return $this;
    }
}

class FlashMessage
{
    #[Groups(["petActivityLogs"])]
    public int $id;

    #[Groups(["petActivityLogs"])]
    public string $entry;

    #[Groups(["petActivityLogs"])]
    public ?string $icon;

    #[Groups(["petActivityLogs"])]
    public ?PetChangesSummary $changes;

    #[Groups(["petActivityLogs"])]
    public int $interestingness;

    /**
     * @var PetActivityLogTag[] $tags
     */
    #[Groups(["petActivityLogs"])]
    public array $tags = [];

    #[Groups(["petActivityLogs"])]
    public ?Pet $pet;

    #[Groups(["petActivityLogs"])]
    public ?array $equippedItem;

    #[Groups(["petActivityLogs"])]
    public array $createdItems = [];

    public function __construct(int $id, string $entry, ?string $icon, ?PetChangesSummary $changes, int $interestingness)
    {
        $this->id = $id;
        $this->entry = $entry;
        $this->icon = $icon;
        $this->changes = $changes;
        $this->interestingness = $interestingness;
    }

    public function setPet(?Pet $pet): self
    {
        $this->pet = $pet;
        return $this;
    }

    public function setEquippedItem(?Item $equippedItem): self
    {
        $this->equippedItem = $equippedItem === null ? null : [
            'name' => $equippedItem->getName(),
            'image' => $equippedItem->getImage(),
            'tool' => [
                'gripX' => $equippedItem->getTool()?->getGripX() ?? 0.5,
                'gripY' => $equippedItem->getTool()?->getGripY() ?? 0.5,
                'gripAngle' => $equippedItem->getTool()?->getGripAngle() ?? 0,
                'gripAngleFixed' => $equippedItem->getTool()?->getGripAngleFixed() ?? false,
                'gripScale' => $equippedItem->getTool()?->getGripScale() ?? 1.0,
                'alwaysInFront' => $equippedItem->getTool()?->getAlwaysInFront() ?? false,
            ]
        ];
        return $this;
    }

    /**
     * @param PetActivityLogTag[] $tags
     * @return $this
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @param PetActivityLogItem[] $createdItems
     * @return $this
     */
    public function setCreatedItems(array $createdItems): self
    {
        $this->createdItems = array_map(fn(PetActivityLogItem $logItem) => [
            'item' => [
                'name' => $logItem->getItem()->getName(),
                'image' => $logItem->getItem()->getImage(),
            ]
        ], $createdItems);

        return $this;
    }
}