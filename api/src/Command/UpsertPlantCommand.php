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

use App\Command\Traits\AskItemTrait;
use App\Entity\Plant;
use App\Entity\PlantYield;
use App\Entity\PlantYieldItem;
use App\Enum\PlantTypeEnum;
use App\Functions\ArrayFunctions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

class UpsertPlantCommand extends PoppySeedPetsCommand
{
    use AskItemTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:upsert-plant')
            ->setDescription('Creates a new Plant, or updates an existing one.')
            ->addArgument('plant', InputArgument::REQUIRED, 'The name of the Plant to upsert.')
        ;
    }

    protected function doCommand(): int
    {
        if(strtolower($_SERVER['APP_ENV']) !== 'dev')
            throw new \Exception('Can only be run in dev environments.');

        $name = $this->input->getArgument('plant');
        $plant = $this->em->getRepository(Plant::class)->findOneBy(['name' => $name]);

        if($plant)
            $this->output->writeln('Updating "' . $plant->getName() . '"');
        else
        {
            $this->output->writeln('Creating "' . $name . '"');

            $plant = new Plant()
                ->setName($name)
                ->setNoPollinators(false)
            ;

            $this->em->persist($plant);

            $this->editType($plant);
            $this->editImages($plant);
            $this->editGrowingTimes($plant);
        }

        do
        {
            $this->output->writeln('');
            $this->output->writeln('Edit Plant');
            $this->output->writeln('----------');

            if(count($plant->getPlantYields()) > 0)
            {
                $this->output->writeln('Current yields:');

                foreach($plant->getPlantYields() as $yield)
                {
                    $length = strlen('  ' . $yield->getMin() . '-' . $yield->getMax() . ': ');
                    $this->output->write('  ' . $yield->getMin() . '-' . $yield->getMax() . ': ');

                    $first = true;
                    foreach($yield->getItems() as $item)
                    {
                        if($first)
                            $first = false;
                        else
                            $this->output->write(str_repeat(' ', $length));

                        $this->output->writeln($item->getPercentChance() . '% ' . $item->getItem()->getName());
                    }
                }
            }

            $this->output->writeln('Choose an action!');
            $this->output->writeln('1. Edit a yield');
            $this->output->writeln('2. Delete a yield');
            $this->output->writeln('3. Add a yield');
            $this->output->writeln('4. Edit plant type');
            $this->output->writeln('5. Edit images');
            $this->output->writeln('6. Edit growing times');
            $this->output->writeln('7. Flush changes, and quit');

            $choice = $this->askInt('', 7, fn(int $n) => $n >= 1 && $n <= 7);

            switch($choice)
            {
                case 1:
                    $this->selectYieldToEdit($plant);
                    break;
                case 2:
                    $this->deleteYield($plant);
                    break;
                case 3:
                    $this->addYield($plant);
                    break;
                case 4:
                    $this->editType($plant);
                    break;
                case 5:
                    $this->editImages($plant);
                    break;
                case 6:
                    $this->editGrowingTimes($plant);
                    break;
            }
        }
        while($choice !== 7);

        $this->em->flush();

        return self::SUCCESS;
    }

    private function editType(Plant $plant): void
    {
        $type = $this->askString(ArrayFunctions::list_nice(PlantTypeEnum::getValues(), ', ', ', or ') . '?', $plant->getType(), function(string $s) {
            return in_array(strtolower($s), PlantTypeEnum::getValues());
        });

        $plant->setType(strtolower($type));
    }

    private function editGrowingTimes(Plant $plant): void
    {
        $timeToAdult = $this->askInt('Time to adult', (int)$plant->getTimeToAdult());
        $timeToFruit = $this->askInt('Time to fruit', (int)$plant->getTimeToFruit());

        $plant->setTimeToAdult($timeToAdult);
        $plant->setTimeToFruit($timeToFruit);
    }

    private function editImages(Plant $plant): void
    {
        $sprout = $this->askString('Sprout', $plant->getSproutImage());
        $medium = $this->askString('Medium', $plant->getMediumImage());
        $adult = $this->askString('Adult', $plant->getAdultImage());
        $fruiting = $this->askString('Fruiting', $plant->getHarvestableImage());

        $plant->setSproutImage($sprout);
        $plant->setMediumImage($medium);
        $plant->setAdultImage($adult);
        $plant->setHarvestableImage($fruiting);
    }

    private function selectYieldToEdit(Plant $plant): void
    {
        $yieldIndex = $this->askInt('Which yield?', 1, fn($n) => $n >= 1 && $n <= count($plant->getPlantYields()));
        $yield = $plant->getPlantYields()[$yieldIndex - 1];

        $this->editYield($yield);
    }

    private function editYield(PlantYield $yield): void
    {
        while(true)
        {
            $this->output->writeln('');
            $this->output->writeln('Edit Yield');
            $this->output->writeln('----------');

            $index = 1;
            foreach($yield->getItems() as $item)
            {
                $this->output->writeln($index . '. ' . $item->getPercentChance() . '% ' . $item->getItem()->getName());
                $index++;
            }

            $totalPercent = ArrayFunctions::sum(
                $yield->getItems()->toArray(),
                function(PlantYieldItem $item) { return $item->getPercentChance(); }
            );

            $this->output->writeln($index . '. Add a new item');
            $this->output->writeln(($index + 1) . '. Current total: ' . $totalPercent . '%; auto-balance to 100%');
            $this->output->writeln(($index + 2) . '. Change yield quantity');
            $this->output->writeln(($index + 3) . '. Done');

            $yieldIndex = $this->askInt('', $index + 3, fn($n) => $n >= 1 && $n <= $index + 3);

            if($yieldIndex === $index + 3)
            {
                if($totalPercent === 100)
                    return;

                $this->output->writeln('Can\'t leave until the item drops total to 100% exactly!');
            }
            else if($yieldIndex === $index + 2)
            {
                $this->editYieldQuantities($yield);
            }
            else if($yieldIndex === $index + 1)
            {
                $fraction = $totalPercent / 100;

                foreach($yield->getItems() as $item)
                    $item->setPercentChance((int)round($item->getPercentChance() / $fraction));
            }
            else if($yieldIndex === $index)
            {
                $this->addYieldItem($yield);
            }
            else
            {
                $yieldItem = $yield->getItems()[$yieldIndex - 1];

                $this->editYieldItem($yieldItem);
            }
        }
    }

    private function addYieldItem(PlantYield $yield): void
    {
        $item = $this->askItem('Item', null);
        $percent = $this->askInt('% Chance', 100, function($n) { return $n > 0 && $n <= 100; });

        $yieldItem = new PlantYieldItem($item, $percent);

        $yield->addItem($yieldItem);

        $this->em->persist($yieldItem);
    }

    private function editYieldItem(PlantYieldItem $yieldItem): void
    {
        $item = $this->askItem('Item', $yieldItem->getItem());
        $percent = $this->askInt('% Chance', $yieldItem->getPercentChance(), function($n) { return $n > 0 && $n <= 100; });

        $yieldItem
            ->setItem($item)
            ->setPercentChance($percent)
        ;
    }

    private function deleteYield(Plant $plant): void
    {
        $yieldIndex = $this->askInt('Which yield?', 1, fn(int $n) => $n >= 1 && $n <= count($plant->getPlantYields()));

        $yieldToDelete = $plant->getPlantYields()[$yieldIndex - 1];

        $plant->removePlantYield($yieldToDelete);
        $this->em->remove($yieldToDelete);
    }

    private function editYieldQuantities(PlantYield $yield): void
    {
        $min = $this->askInt('Minimum yield:', $yield->getMin(), function(int $n) { return $n >= 1; });
        $max = $this->askInt('Maximum yield:', max($min, $yield->getMax()), fn(int $n) => $n >= $min);

        $yield
            ->setMin($min)
            ->setMax($max)
        ;
    }

    private function addYield(Plant $plant): void
    {
        $newYield = new PlantYield(1, 1);

        $this->editYieldQuantities($newYield);

        $plant->addPlantYield($newYield);

        $this->em->persist($newYield);

        $this->editYield($newYield);
    }
}
