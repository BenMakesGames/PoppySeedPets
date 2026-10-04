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
import { MatDialog, MatDialogRef } from "@angular/material/dialog";
import { Subscription } from "rxjs";
import { ApiService } from "../../../shared/service/api.service";
import { UserDataService } from "../../../../service/user-data.service";
import { FilterResultsSerializationGroup } from "../../../../model/filter-results.serialization-group";
import { PetPublicProfileSerializationGroup } from "../../../../model/public-profile/pet-public-profile.serialization-group";
import { StarKindredCharacter } from "../../model/star-kindred.models";

/**
 * Characters are rolled fully at random; the player's only choice is which pet gets one.
 */
@Component({
  templateUrl: './roll-character.dialog.html',
  styleUrls: ['./roll-character.dialog.scss'],
  standalone: false
})
export class RollCharacterDialog implements OnInit, OnDestroy {

  page = 0;
  pets: FilterResultsSerializationGroup<PetPublicProfileSerializationGroup>|null = null;
  rolling = false;

  petsAjax = Subscription.EMPTY;

  constructor(
    private dialogRef: MatDialogRef<RollCharacterDialog>,
    private api: ApiService,
    private userData: UserDataService,
  ) {
  }

  ngOnInit() {
    this.loadPets();
  }

  ngOnDestroy() {
    this.petsAjax.unsubscribe();
  }

  private loadPets()
  {
    const data = {
      page: this.page,
      orderBy: 'name',
      filter: {
        owner: this.userData.user.value.id,
        hasStarKindredCharacter: false,
      }
    };

    this.petsAjax.unsubscribe();
    this.petsAjax = this.api.get<FilterResultsSerializationGroup<PetPublicProfileSerializationGroup>>('/pet', data).subscribe({
      next: r => {
        this.pets = r.data;
      }
    });
  }

  doChangePage(page: number)
  {
    this.page = page;
    this.loadPets();
  }

  doRoll(pet: PetPublicProfileSerializationGroup)
  {
    if(this.rolling) return;

    this.rolling = true;
    this.dialogRef.disableClose = true;

    this.api.post<StarKindredCharacter>('/starKindred/rollCharacter', { petId: pet.id }).subscribe({
      next: r => {
        this.dialogRef.close(r.data);
      },
      error: () => {
        this.rolling = false;
        this.dialogRef.disableClose = false;
      }
    });
  }

  doCancel()
  {
    this.dialogRef.close();
  }

  static open(matDialog: MatDialog): MatDialogRef<RollCharacterDialog, StarKindredCharacter>
  {
    return matDialog.open(RollCharacterDialog);
  }
}
