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
import { ActivatedRoute, Router } from '@angular/router';
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";
import { ApiService } from "../../../shared/service/api.service";
import { ApiResponseModel } from "../../../../model/api-response.model";
import { Subscription } from "rxjs";
import { UnlockedAuraSerializationGroup } from "../../../../model/aura/unlocked-aura.serialization-group";
import { UserDataService } from "../../../../service/user-data.service";
import { MyAccountSerializationGroup } from "../../../../model/my-account/my-account.serialization-group";
import { AvailableStylesResponse } from '../../model/available-styles-response';
import { HasSounds, SoundsService } from "../../../shared/service/sounds.service";
import { auraFilter } from "../../../shared/component/pet-aura/pet-aura.component";

@Component({
  templateUrl: './dressing-room.component.html',
  styleUrls: ['./dressing-room.component.scss'],
  standalone: false
})
@HasSounds([ 'chaching' ])
export class DressingRoomComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'The Hattier - Dressing Room' };

  paramSubscription = Subscription.EMPTY;
  petSubscription = Subscription.EMPTY;
  buySubscription = Subscription.EMPTY;
  availableStylesSubscription = Subscription.EMPTY;
  userSubscription = Subscription.EMPTY;

  petId: string|null = null;
  user: MyAccountSerializationGroup;

  selectedPet: MyPetSerializationGroup|null = null;
  previewPet: MyPetSerializationGroup|null = null;

  availableStyles: UnlockedAuraSerializationGroup[]|null = null;
  selectedStyleId: number|null = null;
  initialStyleId: number|null = null;
  selectedStyle: UnlockedAuraSerializationGroup|null = null;
  initialStyle: UnlockedAuraSerializationGroup|null = null;

  sortBy = 'name';

  newHue = 0;
  oldHue = 0;
  newBrightness = 100;
  oldBrightness = 100;

  noStyle: UnlockedAuraSerializationGroup = {
    id: null,
    comment: null,
    unlockedOn: null,
    name: 'None',
    aura: null,
  };

  constructor(
    private api: ApiService,
    private userData: UserDataService,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private sounds: SoundsService
  ) {
  }

  ngOnInit(): void {
    this.userSubscription = this.userData.user.subscribe({
      next: u => this.user = u
    })

    this.loadStyles();

    this.paramSubscription = this.activatedRoute.paramMap.subscribe(params => {
      this.petId = params.get('petId');
      if (this.petId) {
        this.loadPet();
      }
    });
  }

  private loadPet()
  {
    this.petSubscription = this.api.get<MyPetSerializationGroup[]>('/pet/my').subscribe({
      next: (r: ApiResponseModel<MyPetSerializationGroup[]>) => {
        const pet = r.data.find(p => p.id.toString() === this.petId);
        if (!pet) {
          // Pet not found - navigate back to selection
          this.router.navigate(['/hattier']);
          return;
        }
        this.selectedPet = pet;
        this.initializePetStyle();
      }
    });
  }

  private initializePetStyle()
  {
    if(!(this.selectedPet && this.availableStyles))
      return;

    this.selectedStyleId = this.selectedPet.hat.enchantment?.aura?.id ?? null;
    this.selectedStyle = {
      id: null,
      comment: null,
      unlockedOn: null,
      name: '',
      aura: this.selectedPet.hat.enchantment?.aura,
    };

    this.initialStyleId = this.selectedStyleId;
    this.initialStyle = { ...this.selectedStyle };

    this.newHue = this.selectedPet.hat.enchantmentHue ?? 0;
    this.oldHue = this.newHue;

    this.newBrightness = this.selectedPet.hat.enchantmentBrightness ?? 100;
    this.oldBrightness = this.newBrightness;

    this.doUpdateStyle();
  }

  get noChanges(): boolean
  {
    return (this.selectedStyleId === null || this.selectedStyleId === this.initialStyleId) &&
      this.newHue === this.oldHue &&
      this.newBrightness === this.oldBrightness
    ;
  }

  get previewFilter(): string
  {
    return auraFilter(this.newHue, this.newBrightness);
  }

  public doUpdateColor()
  {
    this.previewPet = {
      ...this.previewPet,
      hat: {
        ...this.previewPet.hat,
        enchantmentHue: this.newHue,
        enchantmentBrightness: this.newBrightness
      }
    };
  }

  private loadStyles()
  {
    this.availableStylesSubscription = this.api.get<AvailableStylesResponse>('/hattier/unlockedStyles').subscribe({
      next: r => {
        this.availableStyles = r.data.available;
        this.initializePetStyle();
        this.doSort();
      }
    });
  }

  ngOnDestroy() {
    this.paramSubscription.unsubscribe();
    this.petSubscription.unsubscribe();
    this.buySubscription.unsubscribe();
    this.availableStylesSubscription.unsubscribe();
    this.userSubscription.unsubscribe();
  }

  doBuy(payWith: string)
  {
    const data = {
      pet: this.selectedPet.id,
      aura: this.selectedStyle.id,
      hue: this.newHue,
      brightness: this.newBrightness,
      payWith: payWith
    };

    this.buySubscription = this.api.post('/hattier/buy', data).subscribe({
      next: () => {
        this.sounds.playSound('chaching');
        this.router.navigate(['/hattier']);
      }
    });
  }

  public doSort()
  {
    let sortMethod;

    switch(this.sortBy)
    {
      case 'name':
        sortMethod = (a, b) => a.name.localeCompare(b.name);
        break;

      case 'unlockedOn':
        sortMethod = (a, b) => {
          if(a.unlockedOn === null)
            return 1;
          else if(b.unlockedOn === null)
            return -1;
          else
            return b.unlockedOn.localeCompare(a.unlockedOn);
        };
        break;

      default:
        // user is hacking the front-end??? whatever: just bail
        return;
    }

    this.availableStyles = this.availableStyles.sort(sortMethod);
  }

  doUpdateStyle()
  {
    this.selectedStyle = this.availableStyles.find(s => s.aura && s.aura.id === this.selectedStyleId) ?? this.noStyle;
    this.makePreviewPet();
  }

  private makePreviewPet()
  {
    this.previewPet = {
      ...this.selectedPet,
      hat: {
        ...this.selectedPet.hat,
        enchantment: this.selectedStyle,
        enchantmentHue: this.newHue,
        enchantmentBrightness: this.newBrightness
      }
    };
  }
}
