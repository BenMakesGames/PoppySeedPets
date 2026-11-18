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
use App\Entity\PetSpecies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

class ExportSpeciesCommand extends PoppySeedPetsCommand
{
    use GenerateInsertSqlTrait;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:export-species')
            ->setDescription('Generate SQL needed to import pet species into another database.')
            ->addArgument('species', InputArgument::REQUIRED, 'The name of the PetSpecies to export.')
        ;
    }

    protected function doCommand(): int
    {
        self::assertDevEnvironment();

        $name = $this->input->getArgument('species');
        $species = $this->em->getRepository(PetSpecies::class)->findOneBy([ 'name' => $name ]);

        if(!$species)
        {
            $this->output->writeln('<error>Species not found.</error>');
            return self::FAILURE;
        }

        echo "\n================================================================================\n";
        echo str_repeat(' ', 40 - (int)(mb_strlen($species->getName()) / 2)) . $species->getName() . "\n";
        echo "================================================================================\n";

        echo "\n" . $this->generateInsertSql(PetSpecies::class, $species, 'the species itself!');

        echo "\n\nNOTE: This species sheds \"" . $species->getSheds()->getName() . '" (item #' . $species->getSheds()->getId() . '). Make sure that item exists in the target database first (see app:export-item).';

        echo "\n\n" . 'Upload image to: https://s3.console.aws.amazon.com/s3/buckets/poppyseedpets.com?region=us-east-1&prefix=assets/images/pets/' . self::getDirectory($species->getImage());

        if($species->getEggImage())
            echo "\n" . 'Upload egg image to: https://s3.console.aws.amazon.com/s3/buckets/poppyseedpets.com?region=us-east-1&prefix=assets/images/eggs/' . self::getDirectory($species->getEggImage());

        echo "\n\n================================================================================\n\n";

        return self::SUCCESS;
    }

    private static function getDirectory(string $image): string
    {
        return str_contains($image, '/') ? substr($image, 0, strrpos($image, '/') + 1) : '';
    }
}
