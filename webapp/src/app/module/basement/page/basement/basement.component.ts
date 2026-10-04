/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Component, OnDestroy, OnInit} from '@angular/core';
import { InventoryModeEnum, isSelectionMode } from "../../../../model/inventory-mode.enum";
import {MyInventorySerializationGroup} from "../../../../model/my-inventory/my-inventory.serialization-group";
import {Observable, Subscription} from "rxjs";
import {ApiResponseModel} from "../../../../model/api-response.model";
import {ApiService} from "../../../shared/service/api.service";
import {LocationEnum} from "../../../../model/location.enum";
import {InventoryDetailsDialog} from "../../../../dialog/inventory-details/inventory-details.dialog";
import {FilterResultsSerializationGroup} from "../../../../model/filter-results.serialization-group";
import {ActivatedRoute, Params} from "@angular/router";
import {
  CreateItemSearchModel,
  CreateRequestDtoFromItemSearchModel,
  ItemSearchModel
} from "../../../../model/search/item-search.model";
import {UserDataService} from "../../../../service/user-data.service";
import { ThemeService } from "../../../shared/service/theme.service";
import { InventoryHelperService } from "../../../../service/inventory-helper.service";
import { MatDialog } from "@angular/material/dialog";
import { MyAccountSerializationGroup } from "../../../../model/my-account/my-account.serialization-group";

@Component({
    templateUrl: './basement.component.html',
    styleUrls: ['./basement.component.scss'],
    standalone: false
})
export class BasementComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'Basement' };

  querySubscription: Subscription;

  lastClicked = [];
  InventoryModeEnum = InventoryModeEnum;
  LocationEnum = LocationEnum;
  inventorySelected = 0;

  search: ItemSearchModel = CreateItemSearchModel();

  waitingOnAjax = false;

  mode: InventoryModeEnum = InventoryModeEnum.Browsing;

  inventory: MyInventorySerializationGroup[] = null;
  results: FilterResultsSerializationGroup<MyInventorySerializationGroup> = null;

  inventoryAjax = Subscription.EMPTY;
  inventoryChangedSubscription = Subscription.EMPTY;

  user: MyAccountSerializationGroup;

  constructor(
    private api: ApiService, private matDialog: MatDialog, private route: ActivatedRoute,
    private userData: UserDataService, private themeService: ThemeService,
    private inventoryHelper: InventoryHelperService
  ) {
    this.user = userData.user.getValue();
  }

  ngOnInit() {
    this.inventoryChangedSubscription = this.userData.userInventoryChanged.subscribe({
      next: () => this.doInventoryChanged(),
    });

    this.querySubscription = this.route.queryParams.subscribe({
      next: (p: Params) => {
        if('filter.name' in p)
          this.search.name = p['filter.name'];

        if('filter.itemGroup' in p)
          this.search.itemGroup = p['filter.itemGroup'];
        
        this.inventoryAjax = this.loadInventory(0).subscribe({
          next: r => this.processInventoryResponse(r),
          error: () => {},
        });
      }
    });
  }

  ngOnDestroy(): void {
    this.inventoryAjax.unsubscribe();
    this.inventoryChangedSubscription.unsubscribe();
  }

  doSearch()
  {
    this.results = null;
    this.inventory = [];

    this.inventoryAjax.unsubscribe();
    this.inventoryAjax = this.loadInventory(0).subscribe({
      next: r => this.processInventoryResponse(r),
      error: () => {},
    });
  }

  doChangePage(page: number)
  {
    this.inventoryAjax.unsubscribe();
    this.inventoryAjax = this.loadInventory(page).subscribe({
      next: r => this.processInventoryResponse(r),
      error: () => {},
    });
  }

  doInventoryChanged()
  {
    if(!this.results)
      return;

    this.inventoryAjax.unsubscribe();
    this.inventoryAjax = this.loadInventory(this.results.page).subscribe({
      next: r => this.processInventoryResponse(r),
      error: () => {},
    });
  }

  private loadInventory(page: number): Observable<ApiResponseModel<FilterResultsSerializationGroup<MyInventorySerializationGroup>>>
  {
    this.inventorySelected = 0;

    let data: { page: number, filter: ItemSearchModel } = {
      page: page,
      filter: CreateRequestDtoFromItemSearchModel(this.search),
    };

    return this.api.get<FilterResultsSerializationGroup<MyInventorySerializationGroup>>('/inventory/my/' + LocationEnum.Basement, data);
  }

  private processInventoryResponse(response: ApiResponseModel<FilterResultsSerializationGroup<MyInventorySerializationGroup>>)
  {
    if(response.data)
    {
      this.inventory = response.data.results;
      this.results = response.data;
    }
    else
      this.inventory = [];
  }

  recordLastClicked(inventory)
  {
    this.lastClicked.push(inventory);

    if(this.lastClicked.length > 2)
      this.lastClicked.shift();
  }

  doLongPress(inventory: MyInventorySerializationGroup)
  {
    if(this.themeService.multiSelectWith.getValue() !== 'longPress')
      return;

    this.multiSelect(inventory);
  }

  doDoubleClickItem(inventory: MyInventorySerializationGroup)
  {
    if(this.themeService.multiSelectWith.getValue() !== 'doubleClick')
      return;

    if(!this.lastClicked.every(c => c === inventory))
      return;

    this.multiSelect(inventory);
  }

  private multiSelect(inventory: MyInventorySerializationGroup)
  {
    if(this.waitingOnAjax || !this.inventoryAjax.closed) return;

    if(isSelectionMode(this.mode))
    {
      this.inventoryHelper.multiSelectInventory(this.inventory, inventory.item.id, !inventory.selected, this.mode);

      this.inventorySelected = this.inventory.reduce((total, i) => total + (i.selected ? 1 : 0), 0);
    }
  }

  doClickItem(inventory: MyInventorySerializationGroup)
  {
    if(this.waitingOnAjax || !this.inventoryAjax.closed) return;

    this.recordLastClicked(inventory);

    if(this.mode === InventoryModeEnum.Browsing)
    {
      const inventoryId = inventory.id;
      const itemDetailsDialog = InventoryDetailsDialog.open(this.matDialog, inventory);

      itemDetailsDialog.afterClosed().subscribe({
        next: () => {
          if(!itemDetailsDialog.componentInstance.sellPriceChanged)
            return;

          this.waitingOnAjax = true;

          this.inventoryHelper.findAndUpdateInventorySellPrice(this.inventory, inventoryId, itemDetailsDialog.componentInstance.newSellPrice).subscribe({
            complete: () => {
              this.waitingOnAjax = false;
            },
            error: () => {
              this.waitingOnAjax = false;
            }
          });
        }
      });

      const itemDeletedSubscription = itemDetailsDialog.componentInstance.itemDeleted.subscribe(() => {
        this.inventoryAjax = this.loadInventory(this.results.page).subscribe({
          next: r => this.processInventoryResponse(r),
          error: () => {},
        });
      });

      itemDetailsDialog.afterClosed().subscribe(() => {
        itemDeletedSubscription.unsubscribe();
      });
    }
    else if(isSelectionMode(this.mode))
    {
      inventory.selected = !inventory.selected;

      if(inventory.selected)
        this.inventorySelected++;
      else
        this.inventorySelected--;
    }
  }
}
