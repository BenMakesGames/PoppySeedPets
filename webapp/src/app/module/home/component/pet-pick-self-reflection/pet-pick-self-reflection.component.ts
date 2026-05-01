/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Component, EventEmitter, Input, OnDestroy, OnInit, Output} from '@angular/core';
import {MyPetSerializationGroup} from "../../../../model/my-pet/my-pet.serialization-group";
import {ApiService} from "../../../shared/service/api.service";
import {PetPublicProfileSerializationGroup} from "../../../../model/public-profile/pet-public-profile.serialization-group";
import {Subscription} from "rxjs";
import { SelectPetComponent } from "../../../shared/component/select-pet/select-pet.component";
import { LoadingThrobberComponent } from "../../../shared/component/loading-throbber/loading-throbber.component";
import { PetAppearanceComponent } from "../../../shared/component/pet-appearance/pet-appearance.component";
import { FormsModule } from "@angular/forms";
import { CommonModule } from "@angular/common";

@Component({
    selector: 'app-pet-pick-self-reflection',
    templateUrl: './pet-pick-self-reflection.component.html',
    styleUrls: ['./pet-pick-self-reflection.component.scss'],
    imports: [
        SelectPetComponent,
        LoadingThrobberComponent,
        PetAppearanceComponent,
        FormsModule,
        CommonModule,
    ]
})
export class PetPickSelfReflectionComponent implements OnInit, OnDestroy {

  @Input() pet: MyPetSerializationGroup;
  @Output() selectSelfReflection = new EventEmitter<PetPickSelfReflectionModel>();

  loading = true;
  response: SelfReflectionResponse;
  reconcileWith: number;
  possibleRelationships: string;

  selfReflectionAjax: Subscription;

  constructor(private api: ApiService) { }

  petMapper = (r) => r.pet;

  ngOnInit() {
    this.selfReflectionAjax = this.api.get<SelfReflectionResponse>('/pet/' + this.pet.id + '/selfReflection').subscribe({
      next: (r) => {
        this.response = r.data;
        this.loading = false;
      }
    })
  }

  ngOnDestroy(): void {
    this.selfReflectionAjax.unsubscribe();
  }

  doSelectReconcileWith(relationship: TroubledRelationshipsModel)
  {
    if(relationship === null)
    {
      this.reconcileWith = 0;
      this.possibleRelationships = '';
    }
    else
    {
      this.reconcileWith = relationship.pet.id;
      this.doSetPossibleRelationships(relationship.possibleRelationships);
    }
  }

  doReconcile()
  {
    this.selectSelfReflection.emit({ route: 'reconcile', data: { petId: this.reconcileWith } });
  }

  doSetPossibleRelationships(possibleRelationships: string[])
  {
    this.possibleRelationships = possibleRelationships.map(r => {
      switch(r)
      {
        case 'friend': return 'friends';
        case 'bff': return 'BFFs';
        case 'fwb': return 'FWBs';
        case 'mate': return 'dating';
        default: return r;
      }
    }).join(', ');
  }
}

export interface SelfReflectionResponse
{
  troubledRelationshipsCount: number;
  troubledRelationships: TroubledRelationshipsModel[]|null;
}

export interface TroubledRelationshipsModel {
  pet: PetPublicProfileSerializationGroup;
  possibleRelationships: string[];
}

export interface PetPickSelfReflectionModel
{
  route: string;
  data: any;
}
