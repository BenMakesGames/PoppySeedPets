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

use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Entity\SurveyQuestionAnswer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportSurveyAnswersCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:export-survey-answers')
            ->addArgument('guid', InputArgument::REQUIRED, 'The GUID of the survey to export.')
            ->setDescription('Export survey answers to CSV. Pipe to a file to save.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $guid = $input->getArgument('guid');

        $survey = $this->em->getRepository(Survey::class)->findOneBy([ 'guid' => $guid ]);

        if(!$survey)
        {
            $output->writeln('Survey not found.');
            return 1;
        }

        $qb = $this->em->getRepository(SurveyQuestionAnswer::class)->createQueryBuilder('a');
        $results = $qb
            ->select('DISTINCT IDENTITY(a.user) AS id')
            ->where('a.question IN (:questionIds)')
            ->setParameter('questionIds', $survey->getQuestions()->map(fn (SurveyQuestion $q) => $q->getId())->toArray())
            ->getQuery()
            ->getResult()
        ;

        $ids = array_map(fn($r) => (int)$r['id'], $results);

        $column = 0;
        $questionColumns = [];
        $questionText = [];

        foreach($survey->getQuestions() as $question)
        {
            $questionText[] = $question->getTitle();
            $questionColumns[$question->getId()] = $column;
            $column++;
        }

        $output->write('id,name,registered on,moneys,museum points,');
        $output->writeln(ExportSurveyAnswersCommand::arrayToCSVLine($questionText));

        for($i = 0; $i < count($ids); $i += 50)
        {
            $this->exportUsers(array_slice($ids, $i, 50), $questionColumns, $output);
        }

        return 0;
    }

    private function exportUsers(array $ids, array $questionColumns, OutputInterface $output): void
    {
        $users = $this->em->getRepository(User::class)->findBy([ 'id' => $ids ], [ 'id' => 'ASC' ]);

        foreach($users as $user)
        {
            $row = [];

            for($i = 0; $i < count($questionColumns); $i++)
                $row[$i] = '';

            $answers = $this->em->getRepository(SurveyQuestionAnswer::class)->findBy([
                'user' => $user,
                'question' => array_keys($questionColumns)
            ]);

            foreach($answers as $answer)
                $row[$questionColumns[$answer->getQuestion()->getId()]] = $answer->getAnswer();

            $this->exportAnswers($user, $row, $output);
        }
    }

    private function exportAnswers(User $user, array $row, OutputInterface $output): void
    {
        $data = [
            $user->getId(),
            $user->getName(),
            $user->getRegisteredOn()->format('Y-m-d H:i:s'),
            $user->getMoneys(),
            $user->getMuseumPoints()
        ];

        foreach($row as $r)
            $data[] = $r;

        $output->writeln(ExportSurveyAnswersCommand::arrayToCSVLine($data));
    }

    private static function arrayToCSVLine(array $values): string
    {
        return implode(',', array_map(function ($v) {
            return '"' . str_replace('"', '""', $v) . '"';
        }, $values));
    }
}
