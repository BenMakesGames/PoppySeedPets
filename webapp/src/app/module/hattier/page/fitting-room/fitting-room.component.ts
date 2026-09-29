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
import { Subscription } from "rxjs";
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";
import { ApiService } from "../../../shared/service/api.service";
import { UserDataService } from "../../../../service/user-data.service";
import { MyAccountSerializationGroup } from "../../../../model/my-account/my-account.serialization-group";
import { HasSounds, SoundsService } from "../../../shared/service/sounds.service";

interface HatFit {
  headX: number;
  headY: number;
  headAngle: number;
  headScale: number;
}

interface FitAdjustment {
  label: string;
  lessLabel: string;
  moreLabel: string;
  min: number;
  max: number;
  step: number;
  get: () => number;
  set: (value: number) => void;
}

// keep in sync with PetHatFit on the API
const MIN_HEAD_XY = -0.5;
const MAX_HEAD_XY = 1.5;
const MAX_HEAD_ANGLE = 180;
const MIN_HEAD_SCALE = 0.1;
const MAX_HEAD_SCALE = 1.5;

@Component({
  templateUrl: './fitting-room.component.html',
  styleUrls: ['./fitting-room.component.scss'],
  standalone: false
})
@HasSounds([ 'chaching' ])
export class FittingRoomComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'The Hattier - Fitting Room' };

  // keep in sync with FitHatController on the API
  readonly moneysCost = 50;
  readonly recyclingCost = 25;

  paramSubscription = Subscription.EMPTY;
  petSubscription = Subscription.EMPTY;
  buySubscription = Subscription.EMPTY;
  userSubscription = Subscription.EMPTY;

  petId: string|null = null;
  user: MyAccountSerializationGroup;

  selectedPet: MyPetSerializationGroup|null = null;
  previewPet: MyPetSerializationGroup|null = null;

  fit: HatFit;
  initialFit: HatFit;
  adjustments: FitAdjustment[] = [];

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
    });

    this.paramSubscription = this.activatedRoute.paramMap.subscribe(params => {
      this.petId = params.get('petId');
      if (this.petId) {
        this.loadPet();
      }
    });
  }

  ngOnDestroy() {
    this.paramSubscription.unsubscribe();
    this.petSubscription.unsubscribe();
    this.buySubscription.unsubscribe();
    this.userSubscription.unsubscribe();
  }

  private loadPet()
  {
    this.petSubscription = this.api.get<MyPetSerializationGroup[]>('/pet/my').subscribe({
      next: r => {
        const pet = r.data.find(p => p.id.toString() === this.petId);

        if(!pet?.hat)
        {
          this.router.navigate(['/hattier']);
          return;
        }

        this.selectedPet = pet;

        const hat = pet.hat.item.hat;
        this.initialFit = { headX: hat.headX, headY: hat.headY, headAngle: hat.headAngle, headScale: hat.headScale };
        this.fit = { ...this.initialFit };

        this.adjustments = this.createAdjustments(pet);
        this.makePreviewPet();
      }
    });
  }

  // the hat image is drawn mirrored for some species, and for Mirrored pets; the controls should
  // still move the hat in the direction the player sees
  private createAdjustments(pet: MyPetSerializationGroup): FitAdjustment[]
  {
    const isMirrored = pet.merits?.some(m => m.name === 'Mirrored') ?? false;
    const drawnMirrored = pet.species.flipX !== isMirrored;

    // increasing headX moves an un-mirrored hat left
    const horizontalSign = drawnMirrored ? 1 : -1;
    const tiltSign = drawnMirrored ? -1 : 1;

    return [
      {
        label: 'Left/Right', lessLabel: 'Move left', moreLabel: 'Move right',
        min: horizontalSign === 1 ? MIN_HEAD_XY : -MAX_HEAD_XY,
        max: horizontalSign === 1 ? MAX_HEAD_XY : -MIN_HEAD_XY,
        step: 0.005,
        get: () => this.fit.headX * horizontalSign,
        set: v => this.fit.headX = round(v * horizontalSign, 3),
      },
      {
        label: 'Up/Down', lessLabel: 'Move down', moreLabel: 'Move up',
        min: MIN_HEAD_XY,
        max: MAX_HEAD_XY,
        step: 0.005,
        get: () => this.fit.headY,
        set: v => this.fit.headY = round(v, 3),
      },
      {
        label: 'Tilt', lessLabel: 'Tilt counter-clockwise', moreLabel: 'Tilt clockwise',
        min: -MAX_HEAD_ANGLE,
        max: MAX_HEAD_ANGLE,
        step: 1,
        get: () => this.fit.headAngle * tiltSign,
        set: v => this.fit.headAngle = round(v * tiltSign, 0),
      },
      {
        label: 'Size', lessLabel: 'Shrink', moreLabel: 'Grow',
        min: MIN_HEAD_SCALE,
        max: MAX_HEAD_SCALE,
        step: 0.01,
        get: () => this.fit.headScale,
        set: v => this.fit.headScale = round(v, 2),
      },
    ];
  }

  doSet(adjustment: FitAdjustment, value: number)
  {
    adjustment.set(Math.min(adjustment.max, Math.max(adjustment.min, value)));
    this.makePreviewPet();
  }

  doNudge(adjustment: FitAdjustment, direction: 1|-1)
  {
    this.doSet(adjustment, adjustment.get() + direction * adjustment.step);
  }

  get isUnchanged(): boolean
  {
    return this.fit.headX === this.initialFit.headX &&
      this.fit.headY === this.initialFit.headY &&
      this.fit.headAngle === this.initialFit.headAngle &&
      this.fit.headScale === this.initialFit.headScale
    ;
  }

  private makePreviewPet()
  {
    this.previewPet = {
      ...this.selectedPet,
      hat: {
        ...this.selectedPet.hat,
        item: {
          ...this.selectedPet.hat.item,
          hat: {
            ...this.selectedPet.hat.item.hat,
            ...this.fit,
          }
        }
      }
    };
  }

  doBuy(payWith: 'moneys'|'recycling')
  {
    const data = {
      pet: this.selectedPet.id,
      ...this.fit,
      payWith: payWith
    };

    this.buySubscription = this.api.post('/hattier/fit', data).subscribe({
      next: () => {
        this.sounds.playSound('chaching');
        this.router.navigate(['/hattier']);
      }
    });
  }
}

function round(value: number, decimals: number): number
{
  const factor = Math.pow(10, decimals);
  return Math.round(value * factor) / factor;
}
