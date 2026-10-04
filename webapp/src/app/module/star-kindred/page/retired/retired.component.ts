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
import { FilterResultsSerializationGroup } from "../../../../model/filter-results.serialization-group";
import { StarKindredCharacter } from "../../model/star-kindred.models";

/**
 * The Book of Retired Adventurers: retired characters of every pet the player currently owns.
 */
@Component({
  templateUrl: './retired.component.html',
  styleUrls: ['./retired.component.scss'],
  standalone: false
})
export class RetiredComponent implements OnInit, OnDestroy {
  pageMeta = { title: '★Kindred - Retired Adventurers' };

  page = 0;
  retired: FilterResultsSerializationGroup<StarKindredCharacter>|null = null;

  retiredAjax = Subscription.EMPTY;

  constructor(private api: ApiService) {
  }

  ngOnInit() {
    this.loadRetired();
  }

  ngOnDestroy() {
    this.retiredAjax.unsubscribe();
  }

  doChangePage(page: number)
  {
    this.page = page;
    this.loadRetired();
  }

  private loadRetired()
  {
    this.retiredAjax.unsubscribe();
    this.retiredAjax = this.api.get<FilterResultsSerializationGroup<StarKindredCharacter>>('/starKindred/retired', { page: this.page }).subscribe({
      next: r => {
        this.retired = r.data;
      }
    });
  }
}
