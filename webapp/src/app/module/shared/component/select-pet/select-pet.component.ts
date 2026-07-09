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
  Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild
} from '@angular/core';
import {MyPetSerializationGroup} from "../../../../model/my-pet/my-pet.serialization-group";
import { fromEvent, Observable, Subscription } from "rxjs";
import {ApiService} from "../../service/api.service";
import {debounceTime, distinctUntilChanged, filter, map, switchMap, tap} from "rxjs/operators";
import {ApiResponseModel} from "../../../../model/api-response.model";
import {FilterResultsSerializationGroup} from "../../../../model/filter-results.serialization-group";
import { UserDataService } from "../../../../service/user-data.service";
import { SvgIconComponent } from "../svg-icon/svg-icon.component";
import { PetAppearanceComponent } from "../pet-appearance/pet-appearance.component";
import { LocationSpritePipe } from "../../pipe/location-sprite.pipe";
import { LoadingThrobberComponent } from "../loading-throbber/loading-throbber.component";
import { PaginatorComponent } from "../paginator/paginator.component";
import { CommonModule } from "@angular/common";

@Component({
    selector: 'app-select-pet',
    templateUrl: './select-pet.component.html',
    imports: [
        SvgIconComponent,
        PetAppearanceComponent,
        LocationSpritePipe,
        LoadingThrobberComponent,
        PaginatorComponent,
        CommonModule
    ],
    styleUrls: ['./select-pet.component.scss']
})
export class SelectPetComponent implements OnInit {

  @Input() showLunchbox: boolean|null = null;
  @Input() highlightSelected: boolean = false;
  @Input() apiEndpoint: string = '/pet/typeahead';
  @Input() petMapper: (value)=>(MyPetSerializationGroup) = null;
  @Input() additionalFilters: any = null;
  @Input('disableCondition') isDisabled: (MyPetSerializationGroup)=>(boolean) = _ => false;
  @Output() selected = new EventEmitter<any>();

  @ViewChild('search', { 'static': true }) search: ElementRef;

  keyUpSubscription: Subscription;

  searching = false;
  results: any[];
  pets: MyPetSerializationGroup[];
  selectedPet: MyPetSerializationGroup;
  showLocations: boolean;

  page = 0;
  pageCount = 0;

  constructor(private api: ApiService, private userService: UserDataService) {
    this.showLocations = this.userService.user.getValue().canAssignHelpers;
  }

  public reload()
  {
    this.suggest(this.search.nativeElement.value).subscribe({
      next: r => this.applyResults(r.data),
      error: () => {
        this.searching = false;
      }
    });
  }

  ngOnInit() {
    this.keyUpSubscription = fromEvent(this.search.nativeElement, 'keyup')
      .pipe(
        map(_ => this.search.nativeElement.value),
        debounceTime(400),
        distinctUntilChanged(),
        filter(q => q.length > 0),
        // typing a new search starts over from the first page
        tap(() => this.page = 0),
        switchMap(q => this.suggest(q))
      )
      .subscribe({
        next: r => this.applyResults(r.data),
        error: () => {
          this.searching = false;
        }
      })
    ;
  }

  ngOnDestroy()
  {
    this.keyUpSubscription.unsubscribe();
  }

  suggest(search: string): Observable<ApiResponseModel<FilterResultsSerializationGroup<MyPetSerializationGroup>>>
  {
    this.doSelect(null);
    this.results = null;
    this.searching = true;

    const data = this.additionalFilters
      ? { ...this.additionalFilters, search: search, page: this.page }
      : { search: search, page: this.page }
    ;

    return this.api.get<FilterResultsSerializationGroup<MyPetSerializationGroup>>(this.apiEndpoint, data);
  }

  // a page click re-fetches immediately for the current search text, bypassing the debounced keyup stream
  goToPage(page: number)
  {
    this.page = page;
    this.reload();
  }

  private applyResults(data: FilterResultsSerializationGroup<MyPetSerializationGroup>)
  {
    this.results = data.results;
    this.pets = this.petMapper === null ? data.results : data.results.map(this.petMapper);
    this.page = data.page;
    this.pageCount = data.pageCount;
    this.searching = false;
  }

  doSelect(pet: MyPetSerializationGroup)
  {
    this.selectedPet = pet;

    if(pet === null)
      this.selected.emit(null);
    else if(this.petMapper)
      this.selected.emit(this.results.find(r => this.petMapper(r).id === pet.id));
    else
      this.selected.emit(pet);
  }
}
