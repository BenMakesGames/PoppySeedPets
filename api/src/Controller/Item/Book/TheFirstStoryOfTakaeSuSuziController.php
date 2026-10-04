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

namespace App\Controller\Item\Book;

use App\Controller\Item\ItemControllerHelpers;
use App\Entity\Inventory;
use App\Service\ResponseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\UserAccessor;

#[Route("/item/theFirstStoryOfTakaeSuSuzi")]
class TheFirstStoryOfTakaeSuSuziController
{
    #[Route("/{inventory}/read", methods: ["POST"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function read(Inventory $inventory, ResponseService $responseService,
        UserAccessor $userAccessor
    ): JsonResponse
    {
        ItemControllerHelpers::validateInventoryAllowingLibrary($userAccessor->getUserOrThrow(), $inventory, 'theFirstStoryOfTakaeSuSuzi/#/read');

        return $responseService->itemActionSuccess(<<<EOMD
# The First Story of Takae Su Suzi

This is the story of Takae Su Suzi, who germinated under Gizubi's tree-staff at the dawn of Transformation.

When Takae Su Suzi opened his eyes for the first time, he was distressed with what he saw. Inarguably The All was a beautiful place, indeed almost a paradise, but only almost. "Almost" would not satisfy Takae Su Suzi, and so he went to talk with the daughter of Ki Ri to see what could be done.

"Kaera," Takae Su Suzi said, "you were the first of Ki Ri's offspring, the one to shape the stars and the planets, and even the nothingness that separates them. Each star shines with the brightness of one thousand giamonds, and each planet possesses the subtle beauty of one hundred opals, and so I mean no disrespect - for your handiwork is worthy of many praising words - but I cannot say that there is no room for improvement. Certainly the stars are beautiful, but couldn't they be more beautiful still? And certainly the planets are intriguing in their complexity, but couldn't they be more puzzling still? Why have you, when so close to the mark, fallen short just so?"

"Takae," Kaera Ki Ri Kashu replied, "you are new to this world, and so I will forgive your ignorance, but be warned that the next time you insult my craftsmanship it will not be so easy for you."

Takae Su Suzi was red as much with embarrassment as with rage, but stayed silent.

"Takae," Kaera Ki Ri Kashu continued, "if the stars were not so dim, they would blind you at a glance, if the planets were not so dull, they would cut your mind in two. The All is as it is, because it is perfection."

Takae Su Suzi nodded and left, but was alight with anger, and so when no one was looking he found the dullest planet and dimmest star he could, and said to himself, "Just because I am new to the world does not mean my eyes and mind are not as keen as anyone else's! I will take these plain things - Planet and Star - and make them the most wonderful of All!"

Takae Su Suzi stood upon Planet, looked up to the sky, and said: "I may not be able to make Star brighter, but if I can bring it closer it will certainly outshine all of the others!" And so Takae Su Suzi went to Star, and pushed it with all his might, moving it closer and closer, until, looking up from Planet's surface, Star was the only thing visible.

Then Takae Su Suzi stood upon Planet, looked around, and said: "I may not be able to make Planet more interesting, but perhaps if I wrap it in some interesting things, no one will know the difference!" And so Takae Su Suzi sneaked into Gizubi's garden and took a sample of all the most interesting things he could find to wrap around Planet.

First he poured water all over its surface, at first a bit too much so that he had to scoop some up, leaving behind tall mountains and deep valleys. The dirt that clung to him he rolled into a ball and placed to the side, where it began to circle Planet.

"It wasn't intended," Takae Su Suzi thought to himself, "but it certainly is interesting." And so he left it there, where it dried with his hand-print on its surface.

Next he put creatures in the sea, but it wasn't long before they all ate each other until only one was left: a monster as large as an ocean who called himself Liku-Liku. Liku-Liku, having nothing left to eat, appealed to Takae Su Suzi.

"Takae," Liku-Liku said, "while I have grown big thanks to the animals you provided, there are now none left. If I do not have more food I will certainly die."

But Takae Su Suzi was upset. "I am trying to fill this planet with wonder, but you have eaten it all, and if I put more animals into the sea you will certainly eat them as well! I would rather let you die, so that the animals I put into the sea afterwards can eat you instead of one another!"

Liku-Liku was terrified at this, but had an idea. He said, "but Takae, large though I may be I am only as big as the animals you put here. If you put as many animals in again, it will not be long before I am consumed, and they will resort to eating each other once again, until a single monster twice my size remains! It will become troublesome for you to go on replenishing the sea with animals for such a large monster!"

"What do you suggest?" asked Takae Su Suzi, perturbed.

"Bring instead some plants to put into the sea. If you let me live, I promise to tend to the plants so that there will always be enough for the animals, and I will eat only enough animals to keep me alive."

Takae Su Suzi was pleased with this arrangement, and so he filled up the seas with plants, which Liku-Liku took care of as promised, and then he added animals, which ate the plants accordingly. With everything worked out in the sea, Takae Su Suzi began to devise a plan for putting animals on the land.

"If I am not careful," Takae Su Suzi thought to himself, "the animals will all eat each other up. Like the sea, I will first need plants, and a monster to take care of them."

So Takae Su Suzi found some plants in Gizubi's garden suitable for the land, and scattered them around, and then he found the largest monster he could, and put it on the planet. The monster was so large, however, that it sunk the land it sat on and drowned, crushing many of Liku-Liku's plants.

"Takae!" Liku-Liku said, "Many of my plants have died! How can I maintain the seas if you carry on with disasters of this kind?"

"I am trying to put plants on the land, and a monster to tend to them, but it hasn't worked at all as you can see! What do you suggest?" asked Takae Su Suzi, holding back tears of frustration.

"Instead of such a large monster, why not several small ones?"

Takae Su Suzi thought this to be an excellent idea, and so he went to find some small monsters, but was unable to find any in all of Gizubi's gardens that could do the job.

"Liku-Liku," Takae Su Suzi said, "there are no monsters suitable to the task."

"Then make your own. The water here is full of life, the earth strong, and the wind, carrying our words, intelligent. Make monsters out of these things to tend to the land."

Takae Su Suzi set out to work immediately, sculpting figures from the earth. When he was done sculpting, he poured water into them through their mouths, and when he was done pouring, he whispered wind into them through their ears, and when he was done whispering they all stood up at once and looked around, and knew what they were to do. Takae Su Suzi then put animals on the land, and watched, and seeing that everything was in order, went to Liku-Liku to tell him the news.

"Liku-Liku," Takae Su Suzi said, "I have done it! I have created monsters to take care of the land!"

"What do they call themselves?" Liku-Liku asked.

Takae Su Suzi stopped a moment and listened to them. "Man! And they are quite contented."

"Good," replied Liku-Liku, "but you'll forgive me if I don't like the looks of them myself. I think from now on I will keep them out of my sight, and me out of theirs."

Takae Su Suzi nearly exploded with anger, having his creation so insulted, but then remembered his conversation with Kaera. His anger subsided, and he thanked Liku-Liku for his help before taking to the skies.

"Sun, Earth, and Moon," Takae Su Suzi said, looking at Star, Planet, and the small ball of dirt circling it, "but perhaps I had better keep it a secret for a while yet," and left.
EOMD);
    }
}
