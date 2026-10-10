/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {HomeRoutingModule} from "./home-routing.module";
import {BehattingScrollComponent} from "./page/behatting-scroll/behatting-scroll.component";
import {ChoosePetComponent} from "./page/choose-pet/choose-pet.component";
import {FeedBugComponent} from "./page/feed-bug/feed-bug.component";
import {ForgettingScrollComponent} from "./page/forgetting-scroll/forgetting-scroll.component";
import {IridescentHandCannonComponent} from "./page/iridescent-hand-cannon/iridescent-hand-cannon.component";
import {PolymorphSpiritScrollComponent} from "./page/polymorph-spirit-scroll/polymorph-spirit-scroll.component";
import {RenamingScrollComponent} from "./page/renaming-scroll/renaming-scroll.component";
import {TransmigrationSerumComponent} from "./page/transmigration-serum/transmigration-serum.component";
import {HomeComponent} from "./page/home/home.component";
import {FormsModule} from "@angular/forms";
import {MarkdownModule} from "ngx-markdown";
import { RijndaelComponent } from './page/rijndael/rijndael.component';
import { DragonVaseComponent } from './page/dragon-vase/dragon-vase.component';
import { LengthyScrollOfSkillComponent } from './page/lengthy-scroll-of-skill/lengthy-scroll-of-skill.component';
import {WunderbussComponent} from "./page/wunderbuss/wunderbuss.component";
import { SummaryComponent } from './page/summary/summary.component';
import { PhilosophersStoneComponent } from "./page/philosophers-stone/philosophers-stone.component";
import { RenameYourselfComponent } from "./page/rename-yourself/rename-yourself.component";
import { HotKeysModule } from "../hot-keys/hot-keys.module";
import { ReleaseMothsComponent } from "./page/release-moths/release-moths.component";
import { BetaBugComponent } from "./page/beta-bug/beta-bug.component";
import { TakePictureComponent } from "./page/take-picture/take-picture.component";
import { RenameSpiritCompanionComponent } from "./page/rename-spirit-companion/rename-spirit-companion.component";
import { LunchboxPaintComponent } from "./page/lunchbox-paint/lunchbox-paint.component";
import { HotPotComponent } from "./page/hot-pot/hot-pot.component";
import { ScrollOfIllusionsComponent } from "./page/scroll-of-illusions/scroll-of-illusions.component";
import { LoadingThrobberComponent } from "../shared/component/loading-throbber/loading-throbber.component";
import { HasUnlockedFeaturePipe } from "../shared/pipe/has-unlocked-feature.pipe";
import { InventoryControlComponent } from "../shared/component/inventory-control/inventory-control.component";
import { PetComponent } from "../shared/component/pet/pet.component";
import { InventoryItemComponent } from "../shared/component/inventory-item/inventory-item.component";
import { FloorPipe } from "../shared/pipe/floor.pipe";
import { NpcDialogComponent } from "../shared/component/npc-dialog/npc-dialog.component";
import { FindItemByNameComponent } from "../shared/component/find-item-by-name/find-item-by-name.component";
import { ColorNamePipe } from "../shared/pipe/color-name.pipe";
import { SelectPetComponent } from "../shared/component/select-pet/select-pet.component";
import { PetAppearanceComponent } from "../shared/component/pet-appearance/pet-appearance.component";
import { PetActivityLogTableComponent } from "../shared/component/pet-activity-log-table/pet-activity-log-table.component";
import { HelpLinkComponent } from "../shared/component/help-link/help-link.component";
import { SkillsTableComponent } from "../shared/component/skills-table/skills-table.component";
import { SkillBonusesComponent } from "../shared/component/skill-bonuses/skill-bonuses.component";
import { AttributesTableComponent } from "../shared/component/attributes-table/attributes-table.component";
import { PetMeritsComponent } from "../shared/component/pet-merits/pet-merits.component";
import { PetNotesComponent } from "../shared/component/pet-notes/pet-notes.component";
import { PetFriendsComponent } from "../shared/component/pet-friends/pet-friends.component";
import { PaginatorComponent } from "../shared/component/paginator/paginator.component";
import { DragonTongueComponent } from "./page/dragon-tongue/dragon-tongue.component";
import { FormFieldComponent } from "../shared/component/form-field/form-field.component";
import { MagicCrystalBallComponent } from "./page/magic-crystal-ball/magic-crystal-ball.component";
import { ImageComponent } from "../shared/component/image/image.component";
import { SmilingWandComponent } from "./page/smiling-wand/smiling-wand.component";
import { InArrayPipe } from "../shared/pipe/in-array.pipe";
import {PetBadgeTableComponent} from "../shared/pet-badge-table/pet-badge-table.component";
import { PetLogsLinksComponent } from "../shared/component/pet-logs-links/pet-logs-links.component";
import { ChanceOfChangesLovePipe } from "./pipe/chance-of-chang-e-s-love.pipe";
import { SummaryLocationComponent } from "./component/summary-location/summary-location.component";
import { InteractWithPetDialog } from "./dialog/interact-with-pet/interact-with-pet.dialog";
import { ResonatingBowComponent } from "./page/resonating-bow/resonating-bow.component";
import { TelephoneComponent } from "./page/telephone/telephone.component";
import { MoneysComponent } from "../shared/component/moneys/moneys.component";

@NgModule({
  declarations: [
    BehattingScrollComponent,
    ChoosePetComponent,
    FeedBugComponent,
    ForgettingScrollComponent,
    IridescentHandCannonComponent,
    PolymorphSpiritScrollComponent,
    RenamingScrollComponent,
    TransmigrationSerumComponent,
    HomeComponent,
    RijndaelComponent,
    DragonVaseComponent,
    LengthyScrollOfSkillComponent,
    WunderbussComponent,
    SummaryComponent,
    PhilosophersStoneComponent,
    RenameYourselfComponent,
    ReleaseMothsComponent,
    BetaBugComponent,
    TakePictureComponent,
    RenameSpiritCompanionComponent,
    LunchboxPaintComponent,
    HotPotComponent,
    ScrollOfIllusionsComponent,
    DragonTongueComponent,
    MagicCrystalBallComponent,
    SmilingWandComponent,
    ResonatingBowComponent,
    TelephoneComponent,
  ],
  imports: [
    CommonModule,
    HomeRoutingModule,
    FormsModule,
    MarkdownModule.forChild(),
    HotKeysModule,
    LoadingThrobberComponent,
    HasUnlockedFeaturePipe,
    InventoryControlComponent,
    PetComponent,
    InventoryItemComponent,
    FloorPipe,
    NpcDialogComponent,
    FindItemByNameComponent,
    ColorNamePipe,
    SelectPetComponent,
    PetAppearanceComponent,
    PetActivityLogTableComponent,
    HelpLinkComponent,
    SkillsTableComponent,
    SkillBonusesComponent,
    AttributesTableComponent,
    PetMeritsComponent,
    PetNotesComponent,
    PetFriendsComponent,
    PaginatorComponent,
    FormFieldComponent,
    ImageComponent,
    InArrayPipe,
    PetBadgeTableComponent,
    PetLogsLinksComponent,
    ChanceOfChangesLovePipe,
    SummaryLocationComponent,
    InteractWithPetDialog,
    MoneysComponent,
  ]
})
export class HomeModule { }
