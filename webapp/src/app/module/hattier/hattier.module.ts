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
import { CommonModule, NgOptimizedImage } from '@angular/common';
import { SelectionComponent } from './page/selection/selection.component';
import { DressingRoomComponent } from './page/dressing-room/dressing-room.component';
import { FittingRoomComponent } from './page/fitting-room/fitting-room.component';
import { HattierRoutingModule } from "./hattier-routing.module";
import { PetCustomerComponent } from './component/pet-customer/pet-customer.component';
import { FormsModule } from "@angular/forms";
import { MarkdownModule } from "ngx-markdown";
import { CollectionComponent } from './page/collection/collection.component';
import { IllusionistComponent } from './page/illusionist/illusionist.component';
import { IllusionistCostComponent } from './components/illusionist-cost/illusionist-cost.component';
import { LoadingThrobberComponent } from "../shared/component/loading-throbber/loading-throbber.component";
import { NpcDialogComponent } from "../shared/component/npc-dialog/npc-dialog.component";
import { DateOnlyComponent } from "../shared/component/date-only/date-only.component";
import { MoneysComponent } from "../shared/component/moneys/moneys.component";
import { RecyclingPointsComponent } from "../shared/component/recycling-points/recycling-points.component";
import { PetAppearanceComponent } from "../shared/component/pet-appearance/pet-appearance.component";
import { ItemNameWithBonusComponent } from "../shared/component/item-name-with-bonus/item-name-with-bonus.component";



@NgModule({
  declarations: [
    SelectionComponent,
    DressingRoomComponent,
    FittingRoomComponent,
    PetCustomerComponent,
    CollectionComponent,
    IllusionistComponent,
    IllusionistCostComponent
  ],
  imports: [
    CommonModule,
    HattierRoutingModule,
    FormsModule,
    MarkdownModule,
    NgOptimizedImage,
    LoadingThrobberComponent,
    NpcDialogComponent,
    DateOnlyComponent,
    MoneysComponent,
    RecyclingPointsComponent,
    PetAppearanceComponent,
    ItemNameWithBonusComponent,
  ]
})
export class HattierModule { }
