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

use App\Enum\StarKindredSkillEnum;
use App\Model\StarKindred\StarKindredReward;
use App\Service\Clock;
use App\Service\StarKindred\StarKindredDailyAdventures;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowStarKindredAdventuresCommand extends Command
{
    private const int Days = 100;

    public function __construct(private readonly Clock $clock)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:show-star-kindred-adventures')
            ->setDescription('Show the daily ★Kindred adventures for the next ' . self::Days . ' days.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = $this->clock->now->setTime(0, 0);

        for($day = 0; $day < self::Days; $day++)
        {
            $date = $today->modify('+' . $day . ' days');

            $output->writeln('<info>=== ' . $date->format('Y-m-d (D)') . ' ===</info>');

            foreach(StarKindredDailyAdventures::forDate($date) as $adventure)
            {
                $output->writeln('');
                $output->writeln('<comment>' . $adventure->title . '</comment>');
                $output->writeln($adventure->summary);
                $output->writeln('Skills: ' . implode(', ', array_map(fn(StarKindredSkillEnum $s) => $s->value, $adventure->getSkillsTested())));
                $output->writeln('Rewards:');

                foreach($adventure->rewards as $reward)
                    $output->writeln('  ' . $reward->difficulty->value . ': ' . self::describeReward($reward));
            }

            $output->writeln('');
        }

        return self::SUCCESS;
    }

    private static function describeReward(StarKindredReward $reward): string
    {
        if($reward->item !== null)
            return ($reward->quantity > 1 ? $reward->quantity . 'x ' : '') . $reward->item;

        return '"' . $reward->aura . '" hat styling';
    }
}
