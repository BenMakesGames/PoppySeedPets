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

use App\Entity\PetBadge;
use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Entity\SurveyQuestionAnswer;
use App\Entity\User;
use App\Entity\UserBadge;
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
            ->getSingleColumnResult()
        ;

        $ids = [];

        foreach($results as $id)
        {
            if(!is_numeric($id))
                throw new \UnexpectedValueException('Expected numeric user ID; got ' . get_debug_type($id) . '.');

            $ids[] = (int)$id;
        }

        $column = 0;
        $questionColumns = [];
        $questionText = [];

        foreach($survey->getQuestions() as $question)
        {
            $questionText[] = $question->getTitle();
            $questionColumns[$question->getId()] = $column;
            $column++;
        }

        $output->write('id,name,registered on,moneys,recycling points,museum points,achievements claimed,pet badges,');
        $output->writeln(ExportSurveyAnswersCommand::arrayToCSVLine($questionText));

        for($i = 0; $i < count($ids); $i += 50)
        {
            $this->exportUsers(array_slice($ids, $i, 50), $questionColumns, $output);
        }

        return 0;
    }

    /**
     * @param int[] $ids
     * @param array<int, int> $questionColumns question ID => column index
     */
    private function exportUsers(array $ids, array $questionColumns, OutputInterface $output): void
    {
        $users = $this->em->getRepository(User::class)->findBy([ 'id' => $ids ], [ 'id' => 'ASC' ]);

        $achievementCounts = $this->countByUser(
            'SELECT IDENTITY(b.user) AS userId, COUNT(b.id) AS total FROM ' . UserBadge::class . ' b
            WHERE b.user IN (:ids) GROUP BY b.user',
            $ids
        );

        // counts badges on the pets each player currently owns, including badges earned under a previous owner
        $petBadgeCounts = $this->countByUser(
            'SELECT IDENTITY(p.owner) AS userId, COUNT(b.id) AS total FROM ' . PetBadge::class . ' b
            JOIN b.pet p
            WHERE p.owner IN (:ids) GROUP BY p.owner',
            $ids
        );

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

            $this->exportAnswers(
                $user,
                $achievementCounts[$user->getId()] ?? 0,
                $petBadgeCounts[$user->getId()] ?? 0,
                $row,
                $output
            );
        }
    }

    /**
     * @param int[] $userIds
     * @return array<int, int> user ID => count
     */
    private function countByUser(string $dql, array $userIds): array
    {
        $results = $this->em->createQuery($dql)
            ->setParameter('ids', $userIds)
            ->getArrayResult()
        ;

        $counts = [];

        foreach($results as $r)
        {
            if(!is_array($r) || !is_numeric($r['userId'] ?? null) || !is_numeric($r['total'] ?? null))
                throw new \UnexpectedValueException('countByUser query must select numeric "userId" and "total" columns.');

            $counts[(int)$r['userId']] = (int)$r['total'];
        }

        return $counts;
    }

    /**
     * @param string[] $row
     */
    private function exportAnswers(User $user, int $achievementsClaimed, int $petBadges, array $row, OutputInterface $output): void
    {
        $data = [
            $user->getId(),
            $user->getName(),
            $user->getRegisteredOn()->format('Y-m-d H:i:s'),
            $user->getMoneys(),
            $user->getRecyclePoints(),
            $user->getMuseumPoints(),
            $achievementsClaimed,
            $petBadges,
        ];

        foreach($row as $r)
            $data[] = $r;

        $output->writeln(ExportSurveyAnswersCommand::arrayToCSVLine($data));
    }

    /**
     * @param array<string|int|null> $values
     */
    private static function arrayToCSVLine(array $values): string
    {
        return implode(',', array_map(function ($v) {
            return '"' . str_replace('"', '""', (string)$v) . '"';
        }, $values));
    }
}
