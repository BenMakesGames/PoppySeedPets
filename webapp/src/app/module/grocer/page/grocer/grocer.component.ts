/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from "rxjs";
import { ApiService } from "../../../shared/service/api.service";
import { NPCStoreInventorySerializationGroup } from "../../../../model/npc-store-inventory.serialization-group";
import { MyAccountSerializationGroup } from "../../../../model/my-account/my-account.serialization-group";
import { UserDataService } from "../../../../service/user-data.service";
import { ItemDetailsDialog } from "../../../../dialog/item-details/item-details.dialog";
import { MatDialog } from "@angular/material/dialog";
import { HasSounds, SoundsService } from "../../../shared/service/sounds.service";
import { WeatherService } from "../../../shared/service/weather.service";

const PerseidsHoliday = 'The Perseids Meteor Shower';

@Component({
    templateUrl: './grocer.component.html',
    styleUrls: ['./grocer.component.scss'],
    standalone: false
})
@HasSounds([ 'chaching' ])
export class GrocerComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'The Grocer' };

  location = 0;
  dialogStep = 'shopping';
  user: MyAccountSerializationGroup;
  userSubscription = Subscription.EMPTY;

  grocerData: GrocerDataModel|undefined;
  grocerDataSubscription = Subscription.EMPTY;
  buySubscription = Subscription.EMPTY;
  weatherSubscription = Subscription.EMPTY;

  isPerseids = false;

  quantity: { [key:string]:any } = {};
  totalCost = 0;
  totalQuantity = 0;
  payWith = 'recycling';
  hotBarItems: string[] = [];

  constructor(
    private api: ApiService, private userData: UserDataService, private matDialog: MatDialog,
    private sounds: SoundsService, private weatherService: WeatherService
  )
  {
    this.payWith = localStorage.getItem('payWith') || 'recycling';
  }

  doPayWith(currency: string)
  {
    this.payWith = currency;
    localStorage.setItem('payWith', currency);
    this.doRecalculateTotalCost();
  }

  ngOnInit(): void {
    this.userSubscription = this.userData.user.subscribe({
      next: u => {
        this.user = u;
      }
    })

    this.grocerDataSubscription = this.api.get<GrocerDataModel>('/grocer').subscribe({
      next: v => {
        this.grocerData = v.data;
        this.hotBarItems = [];

        this.grocerData.inventory.forEach(i => {
          this.quantity[i.item.name] = '';

          if(i.special)
            this.hotBarItems.push(i.item.name);
        });
      }
    })

    this.weatherSubscription = this.weatherService.weather.subscribe({
      next: weather => {
        const today = weather?.find(w => new Date().toISOString().startsWith(w.date));

        this.isPerseids = today ? today.holidays.includes(PerseidsHoliday) : false;
      }
    })
  }

  doViewItem(itemName: string)
  {
    ItemDetailsDialog.open(this.matDialog, itemName);
  }

  doRecalculateTotalCost()
  {
    this.totalCost = this.grocerData.inventory
      .filter(i => parseInt(this.quantity[i.item.name]) >= 0)
      .reduce((acc, current) => acc + parseInt(this.quantity[current.item.name]) * (this.payWith === 'moneys' ? current.moneysCost : current.recyclingCost), 0)
    ;

    this.totalQuantity = this.grocerData.inventory
      .filter(i => parseInt(this.quantity[i.item.name]) >= 0)
      .reduce((acc, current) => acc + parseInt(this.quantity[current.item.name]), 0)
    ;
  }

  doBuy()
  {
    if(!this.buySubscription.closed)
      return;

    let purchasedQuantities = {};

    Object.keys(this.quantity).forEach(itemName => {
      if(parseInt(this.quantity[itemName]) > 0)
        purchasedQuantities[itemName] = this.quantity[itemName];
    });

    const data = {
      ...purchasedQuantities,
      location: this.location,
      payWith: this.payWith,
    }

    this.buySubscription = this.api.post<GrocerLimitUpdate>('/grocer/buy', data).subscribe({
      next: r => {
        this.sounds.playSound('chaching');

        this.grocerData = {
          ...this.grocerData,
          ...r.data
        };

        this.grocerData.inventory.forEach(i => {
          this.quantity[i.item.name] = '';
        });

        this.doRecalculateTotalCost();
      }
    });
  }

  ngOnDestroy() {
    this.userSubscription.unsubscribe();
    this.grocerDataSubscription.unsubscribe();
    this.weatherSubscription.unsubscribe();
  }

}

interface GrocerDataModel {
  inventory: GrocerInventorySerializationGroup[];
  maxPerDay: number;
  maxRemainingToday: number;
}

interface GrocerInventorySerializationGroup extends NPCStoreInventorySerializationGroup
{
  special: boolean;
}

interface GrocerLimitUpdate
{
  maxPerDay: number;
  maxRemainingToday: number;
}