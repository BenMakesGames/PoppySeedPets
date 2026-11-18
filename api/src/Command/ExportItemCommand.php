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

use App\Command\Traits\GenerateInsertSqlTrait;
use App\Entity\Enchantment;
use App\Entity\HollowEarthTileCard;
use App\Entity\Item;
use App\Entity\ItemFood;
use App\Entity\ItemGrammar;
use App\Entity\ItemGroup;
use App\Entity\ItemHat;
use App\Entity\ItemTool;
use App\Entity\ItemTreasure;
use App\Entity\Plant;
use App\Entity\PlantYield;
use App\Entity\PlantYieldItem;
use App\Entity\Spice;
use App\Functions\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

class ExportItemCommand extends PoppySeedPetsCommand
{
    use GenerateInsertSqlTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:export-item')
            ->setDescription('Generate SQL needed to import item into another database.')
            ->addArgument('item', InputArgument::REQUIRED, 'The name of the Item to export.')
        ;
    }

    protected function doCommand(): int
    {
        self::assertDevEnvironment();

        $name = $this->input->getArgument('item');

        if(!is_string($name))
            throw new \InvalidArgumentException('item must be a string.');

        $item = ItemRepository::findOneByName($this->em, $name);

        echo "\n================================================================================\n";
        echo str_repeat(' ', 40 - (int)(mb_strlen($item->getName()) / 2)) . $item->getName() . "\n";
        echo "================================================================================\n";

        $groups = $item->getItemGroups();
        $treasure = $item->getTreasure();
        $tool = $item->getTool();
        $hat = $item->getHat();
        $food = $item->getFood();
        $enchantment = $item->getEnchants();
        $spice = $item->getSpice();
        $grammar = $item->getGrammar();
        $plant = $item->getPlant();
        $tile = $item->getHollowEarthTileCard();

        $statements = [];

        if($enchantment)
        {
            $enchantmentTool = $enchantment->getEffects();
            $statements[] = $this->generateInsertSql(ItemTool::class, $enchantmentTool, 'enchantment effect');
            $statements[] = $this->generateInsertSql(Enchantment::class, $enchantment, 'enchantment');
        }

        if($spice)
        {
            $spiceFood = $spice->getEffects();
            $statements[] = $this->generateInsertSql(ItemFood::class, $spiceFood, 'spice effect');
            $statements[] = $this->generateInsertSql(Spice::class, $spice, 'spice');
        }

        if($treasure)
            $statements[] = $this->generateInsertSql(ItemTreasure::class, $treasure, 'treasure');

        if($tool)
            $statements[] = $this->generateInsertSql(ItemTool::class, $tool, 'tool effect');

        if($hat)
            $statements[] = $this->generateInsertSql(ItemHat::class, $hat, 'hat');

        if($food)
            $statements[] = $this->generateInsertSql(ItemFood::class, $food, 'food effect');

        if($plant)
        {
            $statements[] = $this->generateInsertSql(Plant::class, $plant, 'plant');

            foreach($plant->getPlantYields() as $yield)
            {
                $statements[] = $this->generateInsertSql(PlantYield::class, $yield, 'plant yield');

                foreach($yield->getItems() as $yieldItem)
                    $statements[] = $this->generateInsertSql(PlantYieldItem::class, $yieldItem, 'plant yield item');
            }
        }

        if($tile)
            $statements[] = $this->generateInsertSql(HollowEarthTileCard::class, $tile, 'hollow earth tile card');

        $statements[] = $this->generateInsertSql(Item::class, $item, 'the item itself!');

        if($grammar)
            $statements[] = $this->generateInsertSql(ItemGrammar::class, $grammar, 'grammar');

        if(count($groups) > 0)
        {
            $sql = "-- item groups\nINSERT IGNORE INTO item_group_item (item_group_id, item_id) VALUES ";

            $valueSqls = array_map(
                fn(ItemGroup $group) => '(' . $group->getId() . ', ' . $item->getId() . ')',
                $groups->toArray()
            );

            $sql .= join(', ', $valueSqls) . ';';

            $statements[] = $sql;
        }

        echo "\n" . implode("\n\n", $statements);

        if($item->getTool()?->getWhenGather()?->getId() == $item->getId() || $item->getTool()?->getWhenGatherAlsoGather()?->getId() == $item->getId())
        {
            echo "\n\n********************************************************************************\nWARNING: There is a circular reference in the tool effect. You will need to manually fix the SQL to handle this.\n********************************************************************************";
        }

        $image = substr($item->getImage(), 0, strrpos($item->getImage(), '/') + 1);

        echo "\n\n" . 'Upload image to: https://s3.console.aws.amazon.com/s3/buckets/poppyseedpets.com?region=us-east-1&prefix=assets/images/items/' . $image . "\n\n";

        echo "================================================================================\n\n";

        return 0;
    }
}
