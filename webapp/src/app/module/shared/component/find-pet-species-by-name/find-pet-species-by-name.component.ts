/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {
  Component,
  ElementRef,
  EventEmitter,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild
} from '@angular/core';
import {fromEvent, Observable, Subscription} from "rxjs";
import {ApiService} from "../../service/api.service";
import {concat, debounceTime, distinctUntilChanged, filter, map, switchMap} from "rxjs/operators";
import {ApiResponseModel} from "../../../../model/api-response.model";
import { LoadingThrobberComponent } from "../loading-throbber/loading-throbber.component";
import { CommonModule, NgOptimizedImage } from "@angular/common";
import { FormsModule } from "@angular/forms";

@Component({
    selector: 'app-find-pet-species-by-name',
    templateUrl: './find-pet-species-by-name.component.html',
    imports: [
        LoadingThrobberComponent,
        CommonModule,
        NgOptimizedImage,
        FormsModule,
    ],
    styleUrls: ['./find-pet-species-by-name.component.scss']
})
export class FindPetSpeciesByNameComponent implements OnInit, OnChanges {

  @Input() label: string = 'Species';
  @Input() value: string|null = null;
  @Output() valueChange = new EventEmitter<string|null>();

  @ViewChild('search', { 'static': true }) search: ElementRef;

  keyUpSubscription: Subscription;

  searching = false;
  results: PetSpeciesTypeaheadModel[]|null = null;
  selected: PetSpeciesTypeaheadModel|null = null;

  constructor(private api: ApiService) {

  }

  ngOnInit() {
    this.keyUpSubscription = fromEvent(this.search.nativeElement, 'keyup')
      .pipe(
        filter((e: KeyboardEvent) => e.keyCode !== 13),
        map((e: any) => e.target.value),
        debounceTime(400),
        concat(),
        distinctUntilChanged(),
        filter(q => q.length > 0),
        switchMap(q => this.suggest(q))
      )
      .subscribe({
        next: (r: ApiResponseModel<PetSpeciesTypeaheadModel[]>) => {
          this.results = r.data;
          FindPetSpeciesByNameComponent.addResultsToCache(this.results);
          this.searching = false;
        },
        error: () => {
          this.searching = false;
        }
      })
    ;
  }

  ngOnChanges(changes: SimpleChanges) {
    if('value' in changes)
    {
      const species = FindPetSpeciesByNameComponent.speciesCache.find(x => x.id === this.value);
      if(species)
      {
        this.selected = species;
        this.search.nativeElement.value = species.name;
      }
    }
  }

  ngOnDestroy()
  {
    this.keyUpSubscription.unsubscribe();
  }

  suggest(search: string): Observable<ApiResponseModel<PetSpeciesTypeaheadModel[]>>
  {
    this.results = null;
    this.searching = true;
    return this.api.get<PetSpeciesTypeaheadModel[]>('/petSpecies/typeahead', { search: search });
  }

  doSelectFirstResult()
  {
    if(this.results && this.results.length > 0)
      this.doSelect(this.results[0]);
  }

  doClear()
  {
    this.selected = null;
    this.valueChange.emit(null);
    this.search.nativeElement.value = '';
    this.results = null;
  }

  doSelect(result: PetSpeciesTypeaheadModel)
  {
    this.selected = result;
    this.valueChange.emit(result.id);
    this.search.nativeElement.value = '';
    this.results = null;
  }

  static speciesCache: PetSpeciesTypeaheadModel[] = [];

  static addResultsToCache(results: PetSpeciesTypeaheadModel[])
  {
    results.forEach(result => {
      if(!FindPetSpeciesByNameComponent.speciesCache.find(x => x.id === result.id))
        FindPetSpeciesByNameComponent.speciesCache.push(result);
    });
  }
}

export interface PetSpeciesTypeaheadModel
{
  id: string;
  name: string;
  image: string;
}
