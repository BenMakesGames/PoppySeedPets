/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {RouterModule, Routes} from "@angular/router";
import {NgModule} from "@angular/core";
import {HomeComponent} from "./page/home/home.component";
import {RenamingScrollComponent} from "./page/renaming-scroll/renaming-scroll.component";
import {FeedBugComponent} from "./page/feed-bug/feed-bug.component";
import {BehattingScrollComponent} from "./page/behatting-scroll/behatting-scroll.component";
import {IridescentHandCannonComponent} from "./page/iridescent-hand-cannon/iridescent-hand-cannon.component";
import {PolymorphSpiritScrollComponent} from "./page/polymorph-spirit-scroll/polymorph-spirit-scroll.component";
import {TransmigrationSerumComponent} from "./page/transmigration-serum/transmigration-serum.component";
import {ForgettingScrollComponent} from "./page/forgetting-scroll/forgetting-scroll.component";
import {ChoosePetComponent} from "./page/choose-pet/choose-pet.component";
import {RijndaelComponent} from "./page/rijndael/rijndael.component";
import {DragonVaseComponent} from "./page/dragon-vase/dragon-vase.component";
import {LengthyScrollOfSkillComponent} from "./page/lengthy-scroll-of-skill/lengthy-scroll-of-skill.component";
import {WunderbussComponent} from "./page/wunderbuss/wunderbuss.component";
import { SummaryComponent } from "./page/summary/summary.component";
import { PhilosophersStoneComponent } from "./page/philosophers-stone/philosophers-stone.component";
import { RenameYourselfComponent } from "./page/rename-yourself/rename-yourself.component";
import { ReleaseMothsComponent } from "./page/release-moths/release-moths.component";
import { BetaBugComponent } from "./page/beta-bug/beta-bug.component";
import { TakePictureComponent } from "./page/take-picture/take-picture.component";
import { RenameSpiritCompanionComponent } from "./page/rename-spirit-companion/rename-spirit-companion.component";
import { LunchboxPaintComponent } from "./page/lunchbox-paint/lunchbox-paint.component";
import { HotPotComponent } from "./page/hot-pot/hot-pot.component";
import { ScrollOfIllusionsComponent } from "./page/scroll-of-illusions/scroll-of-illusions.component";
import { DragonTongueComponent } from "./page/dragon-tongue/dragon-tongue.component";
import { MagicCrystalBallComponent } from "./page/magic-crystal-ball/magic-crystal-ball.component";
import { SmilingWandComponent } from "./page/smiling-wand/smiling-wand.component";
import { ResonatingBowComponent } from "./page/resonating-bow/resonating-bow.component";
import { TelephoneComponent } from "./page/telephone/telephone.component";

const routes: Routes = [
  { path: '', component: HomeComponent },
  { path: 'summary', component: SummaryComponent },
  { path: 'renamingScroll/:id', component: RenamingScrollComponent },
  { path: 'renameYourself/:id', component: RenameYourselfComponent },
  { path: 'renameSpiritCompanion/:id', component: RenameSpiritCompanionComponent },
  { path: 'feedBug/:id', component: FeedBugComponent },
  { path: 'behattingScroll/:id', component: BehattingScrollComponent },
  { path: 'iridescentHandCannon/:id', component: IridescentHandCannonComponent },
  { path: 'spiritPolymorphPotion/:id', component: PolymorphSpiritScrollComponent },
  { path: 'transmigrationSerum/:id', component: TransmigrationSerumComponent },
  { path: 'forgettingScroll/:id', component: ForgettingScrollComponent },
  { path: 'lengthyScrollOfSkill/:id', component: LengthyScrollOfSkillComponent },
  { path: 'choosePet/:route/:id', component: ChoosePetComponent },
  { path: 'rijndael/:id', component: RijndaelComponent },
  { path: 'wunderbuss/:id', component: WunderbussComponent },
  { path: 'dragonVase/:id', component: DragonVaseComponent },
  { path: 'hotPot/:id', component: HotPotComponent },
  { path: 'philosophersStone/:id', component: PhilosophersStoneComponent },
  { path: 'releaseMoths/:id', component: ReleaseMothsComponent },
  { path: 'betaBug/:id', component: BetaBugComponent },
  { path: 'takePicture/:id', component: TakePictureComponent },
  { path: 'lunchboxPaint/:id', component: LunchboxPaintComponent },
  { path: 'scrollOfIllusions/:id', component: ScrollOfIllusionsComponent },
  { path: 'dragonTongue/:id', component: DragonTongueComponent },
  { path: 'magicCrystalBall/:id', component: MagicCrystalBallComponent },
  { path: 'smilingWand/:id', component: SmilingWandComponent },
  { path: 'resonatingBow/:id', component: ResonatingBowComponent },
  { path: 'telephone/:id', component: TelephoneComponent },
];

@NgModule({
  imports: [
    RouterModule.forChild(routes),
  ],
  exports: [RouterModule],
})
export class HomeRoutingModule { }
