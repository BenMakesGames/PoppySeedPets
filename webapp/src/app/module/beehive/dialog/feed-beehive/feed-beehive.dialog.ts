/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Component, OnDestroy} from '@angular/core';
import {ApiService} from "../../../shared/service/api.service";
import {ApiResponseModel} from "../../../../model/api-response.model";
import {Subscription} from "rxjs";
import { ThemeService } from "../../../shared/service/theme.service";
import { MatDialog, MatDialogRef } from "@angular/material/dialog";
import { ItemOtherPropertiesIcons } from "../../../../model/item-other-properties-icons";
import { MyBeehiveResponse } from "../../../../model/my-beehive.serialization-group";
import { DialogTitleWithIconsComponent } from "../../../shared/component/dialog-title-with-icons/dialog-title-with-icons.component";
import { LoadingThrobberComponent } from "../../../shared/component/loading-throbber/loading-throbber.component";
import { InventoryItemComponent } from "../../../shared/component/inventory-item/inventory-item.component";

@Component({
  templateUrl: './feed-beehive.dialog.html',
  imports: [
    DialogTitleWithIconsComponent,
    LoadingThrobberComponent,
    InventoryItemComponent
  ],
  styleUrls: [ './feed-beehive.dialog.scss' ]
})
export class FeedBeehiveDialog implements OnDestroy {

  lastClicked: BeehiveFlowerModel[] = [];
  loading = false;
  flowers: BeehiveFlowerModel[]|null = null;
  selected: any = {};
  numSelected = 0;

  getFlowersAjax: Subscription;

  readonly itemOtherPropertiesIcons = ItemOtherPropertiesIcons;

  constructor(
    private dialogRef: MatDialogRef<FeedBeehiveDialog>,
    private api: ApiService, private themeService: ThemeService,
  )
  {
    this.getFlowersAjax = this.api.get<BeehiveFlowerModel[]>('/beehive/flowers').subscribe({
      next: (r: ApiResponseModel<BeehiveFlowerModel[]>) => {
        this.flowers = r.data;
      }
    })
  }

  ngOnDestroy(): void {
    this.getFlowersAjax.unsubscribe();
  }

  recordLastClicked(inventory: BeehiveFlowerModel)
  {
    this.lastClicked.push(inventory);

    if(this.lastClicked.length > 2)
      this.lastClicked.shift();
  }

  doLongPress(inventory: BeehiveFlowerModel)
  {
    if(this.themeService.multiSelectWith.getValue() !== 'longPress')
      return;

    this.multiSelect(inventory);
  }

  doDoubleClickItem(inventory: BeehiveFlowerModel)
  {
    if(this.themeService.multiSelectWith.getValue() !== 'doubleClick')
      return;

    if(!this.lastClicked.every(c => c === inventory))
      return;

    this.multiSelect(inventory);
  }

  private multiSelect(flower: BeehiveFlowerModel)
  {
    if(!this.flowers) return;

    const select = !this.selected.hasOwnProperty(flower.id);

    if(select)
    {
      this.flowers.filter(f => f.item.name === flower.item.name).forEach(f => {
        if(!this.selected.hasOwnProperty(f.id))
        {
          this.selected[f.id] = true;
          this.numSelected++;
        }
      });
    }
    else
    {
      this.flowers.filter(f => f.item.name === flower.item.name).forEach(f => {
        if(this.selected.hasOwnProperty(f.id)) {
          delete this.selected[f.id];
          this.numSelected--;
        }
      });
    }
  }

  doSelectFlowers(flower: BeehiveFlowerModel)
  {
    this.recordLastClicked(flower);

    if(this.selected.hasOwnProperty(flower.id)) {
      delete this.selected[flower.id];
      this.numSelected--;
    }
    else
    {
      this.selected[flower.id] = true;
      this.numSelected++;
    }
  }

  doFeedBeehive()
  {
    if(this.loading || this.numSelected === 0) return;

    this.loading = true;

    const data = {
      flowers: Object.keys(this.selected)
    };

    this.api.post<MyBeehiveResponse>('/beehive/feed', data).subscribe({
      next: (r: ApiResponseModel<MyBeehiveResponse>) => {
        this.dialogRef.close(r.data);
      },
      error: () => {
        this.loading = false;
      }
    })

  }

  public static open(matDialog: MatDialog): MatDialogRef<FeedBeehiveDialog>
  {
    return matDialog.open(FeedBeehiveDialog)
  }
}

interface BeehiveFlowerModel
{
  id: number;
  item: { name: string, image: string };
  spice: { name: string, isSuffix: string }|null;
  illusion: { image: string, name: string }|null;
  flowerPower: number;
}