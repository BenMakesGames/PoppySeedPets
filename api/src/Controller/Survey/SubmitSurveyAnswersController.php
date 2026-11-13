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

namespace App\Controller\Survey;

use App\Exceptions\PSPNotFoundException;
use App\Service\ResponseService;
use App\Service\SurveyService;
use App\Service\UserAccessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/survey")]
class SubmitSurveyAnswersController
{
    #[Route("/{guid}", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function submitSurvey(
        string $guid, SurveyService $surveyService, Request $request, ResponseService $responseService,
        EntityManagerInterface $em,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        $now = new \DateTimeImmutable();

        $questions = $surveyService->getSurveyQuestions($guid, $now);

        if(!$questions)
            throw new PSPNotFoundException('Survey not found.');

        $user = $userAccessor->getUserOrThrow();

        foreach($questions as $question)
        {
            $answer = mb_trim($request->request->getString("{$question->getId()}"));

            if($answer)
                $surveyService->upsertAnswer($question, $user, $answer, $request->getClientIp());
            else
                $surveyService->deleteAnswer($question, $user);
        }

        $em->flush();

        return $responseService->success();
    }
}