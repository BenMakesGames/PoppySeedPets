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
import {Subscription} from "rxjs";
import {MyAccountSerializationGroup} from "../../../../model/my-account/my-account.serialization-group";
import {FilterResultsSerializationGroup} from "../../../../model/filter-results.serialization-group";
import {MuseumSerializationGroup} from "../../../../model/museum.serialization-group";
import {UserDataService} from "../../../../service/user-data.service";
import {ApiService} from "../../../shared/service/api.service";
import {ApiResponseModel} from "../../../../model/api-response.model";
import {SelectIconDialog} from "../../dialog/select-icon/select-icon.dialog";
import {ActivatedRoute, ParamMap, Router} from "@angular/router";
import {QueryStringService} from "../../../../service/query-string.service";
import { MatDialog } from "@angular/material/dialog";


@Component({
    templateUrl: './museum.component.html',
    styleUrls: ['./museum.component.scss'],
    standalone: false
})
export class MuseumComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'Museum' };

  user: MyAccountSerializationGroup;
  nextMilestoneDescription: string|undefined;

  page: number = 0;
  params = {
    orderBy: 'donatedOn'
  };
  results: FilterResultsSerializationGroup<MuseumSerializationGroup>;
  resultsSubscription = Subscription.EMPTY;
  clearingIconSubscription = Subscription.EMPTY;
  userSubscription = Subscription.EMPTY;

  constructor(
    private userData: UserDataService, private api: ApiService, private matDialog: MatDialog,
    private activatedRoute: ActivatedRoute, private router: Router
  ) {

  }

  ngOnInit()
  {
    this.userSubscription = this.userData.user.subscribe({
      next: u => { this.user = u; }
    });

    this.activatedRoute.queryParamMap.subscribe({
      next: (p: ParamMap) =>
      {
        const params = QueryStringService.parse(p);

        this.params = {
          orderBy: 'donatedOn'
        };

        if('page' in params)
          this.page = QueryStringService.parseInt(params.page, 0);
        else
          this.page = 0;

        if('orderBy' in params)
          this.params.orderBy = params.orderBy;

        this.getPage();
      }
    });
  }

  ngOnDestroy()
  {
    this.resultsSubscription.unsubscribe();
    this.userSubscription.unsubscribe();
  }

  doClearIcon()
  {
    if(this.clearingIconSubscription.closed)
      this.clearingIconSubscription = this.api.patch('/account/clearIcon').subscribe();
  }

  doSelectIcon(item)
  {
    SelectIconDialog.open(this.matDialog, item);
  }

  doChangeSort()
  {
    this.router.navigate([ '/museum' ], { queryParams: { page: 0, ...this.params }});
  }

  getPage()
  {
    this.resultsSubscription.unsubscribe();

    const data = {
      page: this.page,
      ...this.params
    };

    this.resultsSubscription = this.api.get<FilterResultsSerializationGroup<MuseumSerializationGroup>>('/museum/' + this.user.id + '/items', data).subscribe({
      next: (r: ApiResponseModel<FilterResultsSerializationGroup<MuseumSerializationGroup>>) => {
        this.results = r.data;
        this.nextMilestoneDescription = MuseumComponent.getMilestoneDescription(r.data.unfilteredTotal);
      }
    });
  }

  private static getMilestoneDescription(itemsDonated: number): string
  {
    if(itemsDonated < 100)
      return 'After donating 100 items, you can get a new kind of care package from Tess at the Plaza.';
    else if(itemsDonated < 150)
      return 'Hector will expand his Bookstore offerings to you once you\'ve donated 150 items.';
    else if(itemsDonated < 200)
      return 'Hector will expand his Bookstore offerings yet again after you\'ve donated 200 items.';
    else if(itemsDonated < 300)
      return 'Hector will expand his Bookstore offerings even more once you\'ve donated 300 items!';
    else if(itemsDonated < 400)
      return 'Argentelle - the Market manager - will let you make more bids after you\'ve donated 400 items. She has a lot of connections with the fae world, and I hear there\'s some other benefits, too, but I don\'t know the details...';
    else if(itemsDonated < 450)
      return 'After donating 450 items, you can get a new kind of care package from Tess at the Plaza.';
    else if(itemsDonated < 600)
      return 'Hector will expand his Bookstore offerings one final time after you\'ve donated 600 items.';
    else
      return 'There\'s nothing left to "unlock" by donating to the museum. But my Gift Shop will always be open, of course!';
  }

}
