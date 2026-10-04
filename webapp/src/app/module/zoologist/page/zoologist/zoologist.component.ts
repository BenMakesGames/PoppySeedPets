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
import { FilterResultsSerializationGroup } from "../../../../model/filter-results.serialization-group";
import { Subscription } from "rxjs";
import { ActivatedRoute, ParamMap, Router } from "@angular/router";
import { QueryStringService } from "../../../../service/query-string.service";
import { ApiResponseModel } from "../../../../model/api-response.model";
import { ApiService } from "../../../shared/service/api.service";
import { SelectPetDialog } from "../../../../dialog/select-pet/select-pet.dialog";
import { MatDialog } from "@angular/material/dialog";

@Component({
    templateUrl: './zoologist.component.html',
    styleUrls: ['./zoologist.component.scss'],
    standalone: false
})
export class ZoologistComponent implements OnDestroy, OnInit {
  pageMeta = { title: 'Zoologist' };

  dialog = 'welcome';

  page = 0;
  params = {
    orderBy: 'discoveredOn'
  };

  results: FilterResultsSerializationGroup<Specimen>|null = null;
  resultsSubscription = Subscription.EMPTY;
  replacing = false;

  constructor(
    private router: Router, private activatedRoute: ActivatedRoute,
    private api: ApiService, private matDialog: MatDialog
  ) {
    if(window.localStorage.getItem('metZoologist') !== 'yes')
    {
      this.dialog = 'intro';
      window.localStorage.setItem('metZoologist', 'yes');
    }
  }

  ngOnInit()
  {
    this.activatedRoute.queryParamMap.subscribe({
      next: (p: ParamMap) =>
      {
        const params = QueryStringService.parse(p);

        this.params = {
          orderBy: 'discoveredOn'
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

  doChangeSort()
  {
    this.router.navigate([ '/zoologist' ], { queryParams: { page: 0, ...this.params }});
  }

  doReplaceEntry(speciesId: string)
  {
    SelectPetDialog.open(this.matDialog, { speciesId: speciesId }).afterClosed().subscribe({
      next: pet => {
        if(!pet)
          return;

        this.replacing = true;

        this.api.post('/zoologist/replaceEntry', { petId: pet.id }).subscribe({
          next: () => {
            this.replacing = false;
            this.getPage();
          },
          error: () => {
            this.replacing = false;
          }
        });
      }
    });
  }

  ngOnDestroy() {
    this.resultsSubscription.unsubscribe();
  }

  getPage()
  {
    this.resultsSubscription.unsubscribe();

    const data = {
      page: this.page,
      ...this.params
    };

    this.resultsSubscription = this.api.get<FilterResultsSerializationGroup<Specimen>>('/zoologist', data).subscribe({
      next: (r: ApiResponseModel<FilterResultsSerializationGroup<Specimen>>) => {
        this.results = r.data;
      }
    });
  }
}

interface Specimen
{
  species: {
    id: string,
    image: string,
    name: string,
    family: string,
    sheds: { name: string },
  };
  discoveredOn: string;
  petName: string;
  colorA: string;
  colorB: string;
  scale: number;
}