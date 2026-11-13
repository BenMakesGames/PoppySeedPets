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

use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Entity\SurveyQuestionAnswer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SurveyService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function getActiveSurvey(string $guid, \DateTimeImmutable $dateTime): ?Survey
    {
        $qb = $this->em->getRepository(Survey::class)->createQueryBuilder('s');

        return $qb
            ->andWhere('s.guid=:guid')
            ->andWhere('s.startDate <= :dateTime')
            ->andWhere('s.endDate >= :dateTime')
            ->setParameter('guid', $guid)
            ->setParameter('dateTime', $dateTime)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return SurveyQuestion[]|null
     */
    public function getSurveyQuestions(string $guid, \DateTimeImmutable $dateTime): ?array
    {
        $survey = $this->getActiveSurvey($guid, $dateTime);

        if(!$survey)
            return null;

        return $survey->getQuestions()->toArray();
    }

    public function getSurveyQuestionAnswers(string $guid, User $user): ?array
    {
        $survey = $this->getActiveSurvey($guid, new \DateTimeImmutable());

        if(!$survey)
            return null;

        $qb = $this->em->getRepository(SurveyQuestionAnswer::class)->createQueryBuilder('a');

        return $qb
            ->join('a.question', 'q')
            ->andWhere('a.user=:user')
            ->andWhere('q.survey=:survey')
            ->setParameter('user', $user->getId())
            ->setParameter('survey', $survey->getId())
            ->orderBy('q.id', 'ASC')
            ->getQuery()
            ->execute()
        ;
    }

    public function upsertAnswer(SurveyQuestion $question, User $user, string $answerText, ?string $ipAddress): SurveyQuestionAnswer
    {
        $answer = $this->em->getRepository(SurveyQuestionAnswer::class)->findOneBy([
            'user' => $user,
            'question' => $question
        ]);

        if($answer == null)
        {
            $answer = new SurveyQuestionAnswer(
                user: $user,
                question: $question,
                answer: $answerText
            );

            $this->em->persist($answer);
        }
        else
            $answer->setAnswer($answerText);

        $answer->addIpAddress($ipAddress);

        return $answer;
    }

    public function deleteAnswer(SurveyQuestion $question, User $user): void
    {
        $answer = $this->em->getRepository(SurveyQuestionAnswer::class)->findOneBy([
            'user' => $user,
            'question' => $question
        ]);

        if($answer)
            $this->em->remove($answer);
    }
}