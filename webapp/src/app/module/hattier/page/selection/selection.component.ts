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
import { Router } from '@angular/router';
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";
import { ApiService } from "../../../shared/service/api.service";
import { ApiResponseModel } from "../../../../model/api-response.model";
import { Subscription } from "rxjs";
import { UserDataService } from "../../../../service/user-data.service";
import { MyAccountSerializationGroup } from "../../../../model/my-account/my-account.serialization-group";

@Component({
  templateUrl: './selection.component.html',
  styleUrls: ['./selection.component.scss'],
  standalone: false
})
export class SelectionComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'The Hattier' };

  myPetsAjax = Subscription.EMPTY;
  userSubscription = Subscription.EMPTY;

  user: MyAccountSerializationGroup;
  pets: MyPetSerializationGroup[] = [];

  hattierDialog = 'Welcome to the Hattier! I specialize in rare and exotic hat stylings.';

  isOctober = false;
  npcName = 'Myles';
  npcImage = 'hattier';

  constructor(private api: ApiService, private userData: UserDataService, private router: Router) {
  }

  ngOnInit(): void {
    this.loadPets();

    this.userSubscription = this.userData.user.subscribe({
      next: u => this.user = u
    })

    this.isOctober = (new Date()).getMonth() === 9;

    if(this.isOctober)
    {
      this.npcName = 'Lysander';
      this.npcImage = 'lysander';
    }

    this.setDefaultHattierDialog();
  }

  private loadPets()
  {
    this.myPetsAjax = this.api.get<MyPetSerializationGroup[]>('/pet/my').subscribe({
      next: (r: ApiResponseModel<MyPetSerializationGroup[]>) => {
        this.pets = r.data;
      }
    });
  }

  ngOnDestroy() {
    this.myPetsAjax.unsubscribe();
    this.userSubscription.unsubscribe();
  }

  doZhuzhUp(pet: MyPetSerializationGroup)
  {
    this.router.navigate(['/hattier', pet.id]);
  }

  doChangeFit(pet: MyPetSerializationGroup)
  {
    this.router.navigate(['/hattier', 'fit', pet.id]);
  }

  setDefaultHattierDialog()
  {
    if(this.isOctober)
      this.hattierDialog = 'Welcome. My name is Lysander. Myles is away until November, however he\'s left me instructions on how to assist you with your hat styling needs. Additionally, I\'m selling some specialty items, if you\'re interested.';
    else
      this.hattierDialog = 'Welcome to the Hattier! I can zhuzh up your pets\' hats, or fit any hats that don\'t seem to be sitting right.';
  }
}
