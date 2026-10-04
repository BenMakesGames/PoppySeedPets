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
import { ActivatedRoute } from "@angular/router";
import { Subscription } from "rxjs";
import { ApiService } from "../../../shared/service/api.service";
import { StarKindredCharacter } from "../../model/star-kindred.models";

@Component({
  templateUrl: './character-sheet.component.html',
  styleUrls: ['./character-sheet.component.scss'],
  standalone: false
})
export class CharacterSheetComponent implements OnInit, OnDestroy {
  pageMeta = { title: '★Kindred - Character Sheet' };

  character: StarKindredCharacter|null = null;

  paramSubscription = Subscription.EMPTY;
  characterAjax = Subscription.EMPTY;

  constructor(private api: ApiService, private activatedRoute: ActivatedRoute) {
  }

  ngOnInit() {
    this.paramSubscription = this.activatedRoute.paramMap.subscribe(params => {
      this.loadCharacter(params.get('characterId'));
    });
  }

  ngOnDestroy() {
    this.paramSubscription.unsubscribe();
    this.characterAjax.unsubscribe();
  }

  private loadCharacter(id: string|null)
  {
    this.character = null;
    this.characterAjax.unsubscribe();
    this.characterAjax = this.api.get<StarKindredCharacter>('/starKindred/character/' + id).subscribe({
      next: r => {
        this.character = r.data;
      }
    });
  }
}
