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

namespace App\Command;

use App\Entity\HollowEarthTile;
use App\Entity\HollowEarthTileCard;
use App\Entity\Item;
use App\Enum\HollowEarthActionTypeEnum;
use App\Enum\PetSkillEnum;
use App\Exceptions\PSPNotFoundException;
use App\Functions\HollowEarthTileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

class UpdateHollowEarthTileEventCommand extends PoppySeedPetsCommand
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-hollow-earth-tile-event')
            ->setDescription('Create a basic Hollow Earth tile event.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the tile to update.')
        ;
    }

    protected function doCommand(): int
    {
        $tileName = $this->input->getArgument('name');

        $tile = $this->em->getRepository(HollowEarthTileCard::class)->findOneBy([ 'name' => $tileName ])
            ?? throw new \Exception('There is no tile named "' . $tileName . '".');

        $existingEvent = $tile->getEvent();

        if(is_array($existingEvent) && count($existingEvent) > 0)
            throw new \Exception('Tile already has an event. If you really want to run this command, clear the event data, first.');

        $newEvent = [];

        $this->createEvent($newEvent);

        $tile->setEvent($newEvent);

        $json = json_encode($newEvent, JSON_PRETTY_PRINT);

        echo $json;

        $answer = $this->askBool('Does this look right?', true);

        if($answer)
            $this->em->flush();

        return self::SUCCESS;
    }

    private function createEvent(array &$event): void
    {
        $type = $this->askChoice('What kind of event do you want to make?', array_merge(HollowEarthActionTypeEnum::getValues()), null);

        switch($type)
        {
            case HollowEarthActionTypeEnum::Onward:
                $this->createBasicEvent($event);
                return;

            case HollowEarthActionTypeEnum::PetChallenge:
                $this->createPetChallengeEvent($event);
                return;

            case HollowEarthActionTypeEnum::MoveTo:
                $this->createMoveToEvent($event);
                return;

            case HollowEarthActionTypeEnum::PayItem:
                $this->createPayItemEvent($event);
                return;

            case HollowEarthActionTypeEnum::PayMoneys:
                $this->createPayMoneysEvent($event);
                return;

            case HollowEarthActionTypeEnum::PayItemAndMoneys:
                $this->createPayItemAndMoneysEvent($event);
                return;

            case HollowEarthActionTypeEnum::ChooseOne:
                $this->createChooseOneEvent($event);
                return;

            default:
                throw new \Exception('Unsupported event type: ' . $type);
        }
    }

    private function createBasicEvent(array &$event): void
    {
        $description = $this->askString('Description', null);

        if($description)
            $event['description'] = $description;

        $buttonText = $this->askString('Button text', 'Onward!');

        if($buttonText && $buttonText !== 'Onward!')
            $event['buttonText'] = $buttonText;

        $this->createNeedAdjustments($event);
        $this->createExpGain($event);
        $this->createItemsReceived($event);
    }

    private function createNeedAdjustments(array &$event): void
    {
        foreach([ 'food', 'safety', 'love', 'esteem' ] as $need)
        {
            $amount = $this->askInt(ucfirst($need) . ' change', 0);

            if($amount != 0)
                $event[$need] = $amount;
        }
    }

    private function createExpGain(array &$event): void
    {
        $exp = $this->askInt('Exp gain', 0);

        if($exp === 0)
            return;

        /** @var list<string> $stats */
        $stats = [];

        $allSkills = PetSkillEnum::getValues();

        while(true)
        {
            // if you have no skills selected, you must select one
            $skillList = count($stats) > 0 ? array_merge($allSkills, [ 'NULL' ]) : $allSkills;

            $this->output->writeln('Skills trained: ' . implode(', ', $stats));

            $stat = $this->askChoice('Toggle skill to train', $skillList, null);

            if($stat == 'NULL' && count($stats) > 0)
                break;

            $skillIndex = array_search($stat, $stats, true);

            if($skillIndex !== false)
                $stats = array_values(array_filter($stats, fn($s) => $s !== $stat));
            else
                $stats[] = $stat;
        }

        $event['exp'] = [
            'amount' => $exp,
            'stats' => $stats,
        ];
    }

    private function createPetChallengeEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::PetChallenge;
        $event['description'] = $this->askString('Description', null);
        $event['buttonText'] = $this->askString('Button text', 'OK');
        $event['baseRoll'] = 20;

        $this->createTestedSkills($event);

        $event['requiredRoll'] = $this->askInt('20 + stats >= ??? ', 15);

        $event['ifSucceed'] = [];
        $this->output->writeln('If the pet SUCCEEDS this challenge, what happens?');

        $this->createEvent($event['ifSucceed']);

        $event['ifFail'] = [];
        $this->output->writeln($event['description']);
        $this->output->writeln('If the pet FAILS this challenge, what happens?');

        $this->createEvent($event['ifFail']);
    }

    private function createTestedSkills(array &$event): void
    {
        $event['stats'] = [];

        $allStatsAndSkills = array_merge(
            [
                'strength', 'stamina', 'dexterity', 'perception', 'intelligence'
            ],
            PetSkillEnum::getValues()
        );

        while(true)
        {
            // if you have no stats or skills selected, you must select one
            $skillList = count($event['stats']) > 0 ? array_merge($allStatsAndSkills, [ 'NULL' ]) : $allStatsAndSkills;

            $this->output->writeln('Stats tested: ' . implode(', ', $event['stats']));

            $stat = $this->askChoice('Toggle stats to test', $skillList, null);

            if($stat == 'NULL' && count($event['stats']) > 0)
                break;

            $skillIndex = array_search($stat, $event['stats']);

            if($skillIndex !== false)
                unset($event['stats'][$skillIndex]);
            else
                $event['stats'][] = $stat;
        }
    }

    private function createItemsReceived(array &$event): void
    {
        $items = [];

        while(true)
        {
            $this->output->writeln('Items received: ' . implode(', ', $items));

            // if you have no stats or skills selected, you must select one
            $itemName = $this->askString('Item to toggle, or nothing to stop', null);

            if($itemName === '')
                break;

            $item = $this->em->getRepository(Item::class)->findOneBy([ 'name' => $itemName ]);

            if(!$item)
            {
                $this->output->writeln('There is no item called "' . $itemName . '".');
                continue;
            }

            $itemIndex = array_search($item->getName(), $items);

            if($itemIndex !== false)
                unset($items[$itemIndex]);
            else
                $items[] = $item->getName();
        }

        $event['receiveItems'] = $items;
    }

    private function createChooseOneEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::ChooseOne;
        $event['description'] = $this->askString('Description', null);

        $event['buttons'] = [
            $this->askString('Option 1 button text', null),
            $this->askString('Option 2 button text', null)
        ];
        $event['outcomes'] = [ [], [] ];

        $this->output->writeln('If the player chooses "' . $event['buttons'][0] . '", what happens?');
        $this->createEvent($event['outcomes'][0]);

        $this->output->writeln($event['description']);
        $this->output->writeln('If the player chooses "' . $event['buttons'][1] . '", what happens?');
        $this->createEvent($event['outcomes'][1]);
    }

    private function createMoveToEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::MoveTo;
        $event['description'] = $this->askString('Description', null);
        $event['buttonText'] = $this->askString('Button text', 'OK');

        /** @var HollowEarthTile|null $tile */
        $tile = null;

        while(!$tile)
        {
            $tileId = $this->askInt('Destination tile ID', 0);

            try
            {
                $tile = HollowEarthTileRepository::findOneById($this->em, $tileId);
            }
            catch(PSPNotFoundException)
            {
                $this->output->writeln('There is no tile with ID ' . $tileId . '.');
            }
        }

        $event['id'] = $tile->getId();
    }

    private function createPayMoneysEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::PayMoneys;
        $event['description'] = $this->askString('Description', null);

        do
        {
            $event['amount'] = $this->askInt('Amount to pay', 1);
        } while($event['amount'] < 1);

        $event['ifPaid'] = [];
        $this->output->writeln('If the player pays ' . $event['amount'] . ' moneys, what happens?');
        $this->createEvent($event['ifPaid']);
    }

    private function createPayItemEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::PayItem;
        $event['description'] = $this->askString('Description', null);

        while(true)
        {
            $itemName = $this->askString('Item to pay', null);

            /** @var Item|null $item */
            $item = $this->em->getRepository(Item::class)->findOneBy([ 'name' => $itemName ]);

            if($item)
            {
                $event['item'] = $item->getName();
                break;
            }

            $this->output->writeln('There is no item called "' . $itemName . '".');
        }

        $event['ifPaid'] = [];
        $this->output->writeln('If the player pays ' . $item->getNameWithArticle() . ', what happens?');
        $this->createEvent($event['ifPaid']);
    }

    private function createPayItemAndMoneysEvent(array &$event): void
    {
        $event['type'] = HollowEarthActionTypeEnum::PayItemAndMoneys;
        $event['description'] = $this->askString('Description', null);

        do
        {
            $event['amount'] = $this->askInt('Amount to pay', 1);
        } while($event['amount'] < 1);

        while(true)
        {
            $itemName = $this->askString('Item to pay', null);

            $item = $this->em->getRepository(Item::class)->findOneBy([ 'name' => $itemName ]);

            if($item)
            {
                $event['item'] = $item->getName();
                break;
            }

            $this->output->writeln('There is no item called "' . $itemName . '".');
        }

        $event['ifPaid'] = [];
        $this->output->writeln('If the player pays ' . $event['amount'] . ' moneys and ' . $item->getNameWithArticle() . ', what happens?');
        $this->createEvent($event['ifPaid']);
    }
}
