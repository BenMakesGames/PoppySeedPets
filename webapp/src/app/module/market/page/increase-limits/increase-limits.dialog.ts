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
import {UserDataService} from "../../../../service/user-data.service";
import {MyAccountSerializationGroup} from "../../../../model/my-account/my-account.serialization-group";
import {ApiService} from "../../../shared/service/api.service";
import {Subscription} from "rxjs";

@Component({
    templateUrl: './increase-limits.dialog.html',
    styleUrls: ['./increase-limits.dialog.scss'],
    standalone: false
})
export class IncreaseLimitsDialog implements OnInit, OnDestroy {

  state = 'intro';
  dialogText = 'Just a moment, please...';
  canAsk = false;
  canGetHint = false;
  canIncreaseLimit = false;
  givingItem = false;
  dialogChoicesPaddingBottom = 0;

  user: MyAccountSerializationGroup;
  limits: UserLimitsModel;
  marketLimitsAjax = Subscription.EMPTY;
  suggestBulkSelling = false;
  suggestCurries = false;

  constructor(
    private userData: UserDataService,
    private api: ApiService
  ) {
    this.user = this.userData.user.getValue();

    this.canAsk = localStorage.getItem('tried to ask Market Manager about her size') === null;
  }

  ngOnInit() {
    this.user = this.userData.user.getValue();

    this.marketLimitsAjax = this.api.get<MarketStateResponse>('/market/limits').subscribe({
      next: r => {
        this.limits = r.data.limits;
        this.suggestBulkSelling = r.data.offeringBulkSellUpgrade;
        this.suggestCurries = !r.data.knows1800Curries;
        this.dialogText = 'Can I help you with something?';
      }
    })
  }

  doDontAsk()
  {
    this.canAsk = false;
    localStorage.setItem('tried to ask Market Manager about her size', 'how rude!');
    this.dialogText = 'CAN I help you?';
    this.dialogChoicesPaddingBottom = 1.5;
  }

  doAskAboutDelivery()
  {
    this.state = 'delivery';
  }

  doDoneAskingAboutDelivery()
  {
    this.state = 'intro';
    this.dialogText = 'Mm-hm.';
  }

  doGetTips()
  {
    this.state = 'faq';
  }

  doNoMoreTips()
  {
    this.state = 'intro';
    this.dialogText = 'No problem.';
  }

  doPurchaseBulkSelling()
  {
    this.suggestBulkSelling = false;
    this.state = 'bulkSelling';

    this.api.post('/market/getWingedKey').subscribe();
  }

  doIncreaseLimits()
  {
    this.canAsk = false;
    this.state = 'increaseLimits';

    this.dialogText =
      [
        this.user.name + ', right? Let\'s see...',
        'And you are?\n\nOh, yes, ' + this.user.name + '...',
        '\\*sigh\\* let me look you up... ' + this.user.name + ', right?',
      ][Math.floor(Math.random() * 3)] +
      '\n\n...\n\nYour current limit is ' + this.limits.moneysLimit + '~~m~~'
    ;

    this.appendItemRequiredDialog();
  }

  private appendItemRequiredDialog()
  {
    if(this.limits.itemRequired)
    {
      this.canGetHint = true;
      this.canIncreaseLimit = true;

      if(this.limits.moneysLimit === 10 || this.limits.moneysLimit === 50)
        this.dialogText += '. I can increase the limit by 10~~m~~, in exchange for ' + this.limits.itemRequired.itemName + '.';
      else
        this.dialogText += ', so next would be... ' + this.limits.itemRequired.itemName + '.';
    }
    else
    {
      this.canGetHint = false;

      this.dialogText += ', and as much as I\'d love to ask you to collect some more things for me, I\'m afraid that\'s as high as it goes.';
    }
  }

  doGetHint()
  {
    if(this.givingItem) return;

    this.canGetHint = false;
    this.dialogText = this.limits.itemRequired.hint;
  }

  doNeverMindTheLimits()
  {
    this.state = 'intro';
    this.dialogText = this.canIncreaseLimit
      ? 'Very well.\n\nOh, you\'re still here. Did you need something else?'
      : 'Yes. Life is very unfair.\n\nWas there anything else?';
  }

  doGotWingedKey()
  {
    this.state = 'intro';
    this.dialogText = 'Please, don\'t mention it.\n\nEspecially not to anyone else, if that\'s possible.';
  }

  doGiveItem()
  {
    if(this.givingItem) return;

    this.givingItem = true;

    this.api.post<UserLimitsModel>('/market/limits/increase').subscribe({
      next: r => {
        this.dialogText = [
          'Thank you.',
          'That will do.',
          'Great.',
          'Okay, then.'
        ][Math.floor(Math.random() * 4)];

        this.limits = r.data;

        this.dialogText += ' That brings your limit up to ' + this.limits.moneysLimit + '~~m~~';

        this.appendItemRequiredDialog();

        this.givingItem = false;
      },
      error: () => {
        this.dialogText = 'Uh-huh.';
        this.givingItem = false;
      }
    });
  }

  ngOnDestroy(): void {
    this.marketLimitsAjax.unsubscribe();
  }
}

interface MarketStateResponse
{
  offeringBulkSellUpgrade: boolean;
  knows1800Curries: boolean;
  limits: UserLimitsModel;
}

interface UserLimitsModel
{
  moneysLimit: number;
  itemRequired: { itemName: string, hint: string }|null;
}
