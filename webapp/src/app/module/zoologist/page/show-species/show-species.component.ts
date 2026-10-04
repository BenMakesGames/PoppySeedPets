/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component } from '@angular/core';
import { FilterResultsSerializationGroup } from "../../../../model/filter-results.serialization-group";
import { Subscription } from "rxjs";
import { ApiService } from "../../../shared/service/api.service";
import { ApiResponseModel } from "../../../../model/api-response.model";

@Component({
    templateUrl: './show-species.component.html',
    styleUrls: ['./show-species.component.scss'],
    standalone: false
})
export class ShowSpeciesComponent {
  pageMeta = { title: 'Zoologist - Show Species' };

  page = 0;

  loading = true;
  results: FilterResultsSerializationGroup<Pet>;
  resultsSubscription: Subscription;

  anySelected = false;

  constructor(
    private api: ApiService,
  ) {

  }

  ngOnInit()
  {
    this.doSearch();
  }

  doSearch()
  {
    this.loading = true;
    this.anySelected = false;

    this.resultsSubscription = this.api.get<FilterResultsSerializationGroup<Pet>>('/zoologist/showable', { page: this.page }).subscribe({
      next: (r: ApiResponseModel<FilterResultsSerializationGroup<Pet>>) => {
        this.results = r.data;
        this.loading = false;
      }
    })
  }

  doShow()
  {
    const petIds = this.results.results.filter(r => r.selected).map(r => r.id);

    this.loading = true;

    this.api.post('/zoologist/showPets', { petIds: petIds }).subscribe({
      next: () => {
        this.doSearch();
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  doClickPet(pet: Pet)
  {
    pet.selected = !pet.selected;

    if(pet.selected)
    {
      for(let i = 0; i < this.results.results.length; i++)
      {
        if(this.results.results[i].id === pet.id)
          continue;

        if(this.results.results[i].species.id === pet.species.id)
          this.results.results[i].selected = false;
      }
    }

    this.anySelected = this.results.results.some(p => p.selected);
  }

  ngOnDestroy()
  {
    this.resultsSubscription.unsubscribe();
  }

}

interface Pet
{
  selected: boolean|undefined;
  id: number;
  name: string;
  colorA: string;
  colorB: string;
  species: { id: string, name: string, image: string };
}