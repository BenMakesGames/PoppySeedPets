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

namespace App\Controller;

use App\Attributes\DoesNotRequireHouseHours;
use App\Service\ResponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route("")]
class IndexController
{
    #[DoesNotRequireHouseHours]
    #[Route("/about")]
    public function about(ResponseService $responseService): JsonResponse
    {
        return $responseService->success([
            'lead developer & game designer' => [
                'Ben Hendel-Doying'
            ],
            'developers' => [
                'chiknluvr',
                'erinmclaughlin',
                'GrimmestSnarl',
                'nibkind',
                'Vermidia',
            ],
            'artists' => [
                'Aileen MacKay',
                'Ben Hendel-Doying',
                'Sabrina Silli',
                'Hae-Rhee',
                'TBNRskye',
                'Moopyloots',
                'Mothnox',
                'Vermidia',
            ],
            'thanks' => [
                'Hector Lee',
                'Katie Stanonik',
                'Mothnox',
                'Verdale',
                'pericarditis',
                'Shirley Farrow',
                'All my friends in college',
                'Tomi',
                'Vicious Ruff',
                'Onyx',
            ],
            'inspirations' => [
                'PsyPets',
                'The Sims',
                'Dwarf Fortress',
                'Dofus',
                'Kingdom of Loathing',
            ],
            'madeWith' => [
                'Symfony', 'PHPStorm', 'existential nihilism', 'absurdism', 'humanism', 'candy'
            ]
        ]);
    }
}
