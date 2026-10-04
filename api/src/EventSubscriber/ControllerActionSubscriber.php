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

namespace App\EventSubscriber;

use App\Attributes\DoesNotRequireHouseHours;
use App\Entity\User;
use App\Exceptions\PSPHoursMustBeRun;
use App\Service\HouseService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class ControllerActionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'beforeFilter',
            KernelEvents::RESPONSE => 'finalizeResponse'
        ];
    }

    public function __construct(
        private readonly Security $security,
        private readonly HouseService $houseService,
        private readonly RateLimiterFactoryInterface $pspDefaultLimiter,
        private readonly ParameterBagInterface $parameterBag
    )
    {
    }

    public function beforeFilter(ControllerEvent $event): void
    {
        if(is_array($event->getController()))
        {
            $this->checkRateLimiters($event);
            $this->checkHouseHours($event);
        }

        $this->convertJsonStringToArray($event);
    }

    private function checkRateLimiters(ControllerEvent $event): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if(!$user)
            return;

        $defaultLimiter = $this->pspDefaultLimiter->create((string)$user->getId());

        $defaultLimiter->reserve(1, 15)->wait();
    }

    private function checkHouseHours(ControllerEvent $event): void
    {
        $controllerAction = $event->getController();
        $method = new \ReflectionMethod(get_class($controllerAction[0]), $controllerAction[1]);
        $doesNotRequireHouseHours = $method->getAttributes(DoesNotRequireHouseHours::class);

        if($doesNotRequireHouseHours)
            return;

        /** @var User|null $user */
        $user = $this->security->getUser();

        if(!$user)
            return;

        $item = $this->houseService->getHouseRunLock($user);

        if($item->isHit())
            return;

        if(!$this->houseService->needsToBeRun($user))
            return;

        throw new PSPHoursMustBeRun();
    }

    private function convertJsonStringToArray(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getContentTypeFormat() != 'json' || !$request->getContent())
            return;

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE)
            throw new BadRequestHttpException('Invalid JSON body: ' . json_last_error_msg());

        $request->request->replace(is_array($data) ? $data : []);
    }

    public function finalizeResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Powered-By', 'PSYC-101');

        // changes on every deploy (cache:clear rebuilds the container); the webapp watches it to learn it is out of date
        $buildId = $this->parameterBag->get('container.build_id');

        if(!is_string($buildId))
            throw new \LogicException('container.build_id should be a string.');

        $event->getResponse()->headers->set('X-PSP-Build', $buildId);
    }
}
