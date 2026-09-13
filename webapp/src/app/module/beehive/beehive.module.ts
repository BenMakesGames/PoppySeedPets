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
import { BeehiveComponent } from './page/beehive/beehive.component';
import {BeehiveRoutingModule} from "./beehive-routing.module";
import { MatDialogModule } from "@angular/material/dialog";
import { LoadingThrobberComponent } from "../shared/component/loading-throbber/loading-throbber.component";
import { InventoryItemComponent } from "../shared/component/inventory-item/inventory-item.component";
import { NpcDialogComponent } from "../shared/component/npc-dialog/npc-dialog.component";
import { PetAppearanceComponent } from "../shared/component/pet-appearance/pet-appearance.component";
import { ProgressBarComponent } from "../shared/component/progress-bar/progress-bar.component";
import { DialogTitleWithIconsComponent } from "../shared/component/dialog-title-with-icons/dialog-title-with-icons.component";
import {HelpLinkComponent} from "../shared/component/help-link/help-link.component";
import { BeehiveSpacesComponent } from "./component/beehive-spaces/beehive-spaces.component";

@NgModule({
  declarations: [
    BeehiveComponent,
  ],
  imports: [
    CommonModule,
    BeehiveRoutingModule,
    MatDialogModule,
    LoadingThrobberComponent,
    InventoryItemComponent,
    NpcDialogComponent,
    PetAppearanceComponent,
    ProgressBarComponent,
    DialogTitleWithIconsComponent,
    HelpLinkComponent,
    BeehiveSpacesComponent,
  ],
  exports: [
    BeehiveRoutingModule
  ]
})
export class BeehiveModule { }
