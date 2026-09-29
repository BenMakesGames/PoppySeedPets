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
import { MarkdownModule } from "ngx-markdown";
import { StarKindredRoutingModule } from "./star-kindred-routing.module";
import { StarKindredComponent } from "./page/star-kindred/star-kindred.component";
import { CharacterSheetComponent } from "./page/character-sheet/character-sheet.component";
import { RetiredComponent } from "./page/retired/retired.component";
import { CharacterCardComponent } from "./component/character-card/character-card.component";
import { RewardComponent } from "./component/reward/reward.component";
import { AssemblePartyDialog } from "./dialog/assemble-party/assemble-party.dialog";
import { RollCharacterDialog } from "./dialog/roll-character/roll-character.dialog";
import { AdventureResultsDialog } from "./dialog/adventure-results/adventure-results.dialog";
import { HelpLinkComponent } from "../shared/component/help-link/help-link.component";
import { LoadingThrobberComponent } from "../shared/component/loading-throbber/loading-throbber.component";
import { PaginatorComponent } from "../shared/component/paginator/paginator.component";
import { PetAppearanceComponent } from "../shared/component/pet-appearance/pet-appearance.component";

@NgModule({
  declarations: [
    StarKindredComponent,
    CharacterSheetComponent,
    RetiredComponent,
    CharacterCardComponent,
    RewardComponent,
    AssemblePartyDialog,
    RollCharacterDialog,
    AdventureResultsDialog,
  ],
  imports: [
    CommonModule,
    StarKindredRoutingModule,
    MarkdownModule,
    HelpLinkComponent,
    LoadingThrobberComponent,
    PaginatorComponent,
    PetAppearanceComponent,
  ]
})
export class StarKindredModule { }
