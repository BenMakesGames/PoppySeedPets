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
import { MatDialog } from "@angular/material/dialog";
import { Router } from "@angular/router";
import { ApiService } from "../../../shared/service/api.service";
import { StarKindredAdventure, StarKindredStatus } from "../../model/star-kindred.models";
import { AssemblePartyDialog } from "../../dialog/assemble-party/assemble-party.dialog";
import { AdventureResultsDialog } from "../../dialog/adventure-results/adventure-results.dialog";
import { RollCharacterDialog } from "../../dialog/roll-character/roll-character.dialog";

@Component({
  templateUrl: './star-kindred.component.html',
  styleUrls: ['./star-kindred.component.scss'],
  standalone: false
})
export class StarKindredComponent implements OnInit, OnDestroy {
  pageMeta = { title: '★Kindred' };

  status: StarKindredStatus|null = null;
  hasRetirees = false;
  hasAdventurers = false;

  statusAjax = Subscription.EMPTY;

  constructor(private api: ApiService, private matDialog: MatDialog, private router: Router) {
  }

  ngOnInit() {
    this.loadStatus();
  }

  ngOnDestroy() {
    this.statusAjax.unsubscribe();
  }

  private loadStatus()
  {
    this.statusAjax.unsubscribe();
    this.statusAjax = this.api.get<StarKindredStatus>('/starKindred').subscribe({
      next: r => {
        this.status = r.data;
        this.hasRetirees = r.data.characters.some(c => c.level >= r.data.maxLevel);
        this.hasAdventurers = r.data.characters.some(c => c.level < r.data.maxLevel);
      }
    });
  }

  doChooseParty(adventure: StarKindredAdventure|null)
  {
    if(!this.status) return;

    AssemblePartyDialog.open(this.matDialog, this.status, adventure).afterClosed().subscribe({
      next: result => {
        if(!result) return;

        AdventureResultsDialog.open(this.matDialog, result);
        this.loadStatus();
      }
    });
  }

  doRollCharacter()
  {
    RollCharacterDialog.open(this.matDialog).afterClosed().subscribe({
      next: character => {
        if(!character) return;

        this.router.navigate([ '/starKindred/character', character.id ]);
      }
    });
  }
}
