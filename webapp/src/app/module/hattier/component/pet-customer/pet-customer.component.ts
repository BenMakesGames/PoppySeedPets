/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";

@Component({
    selector: 'app-pet-customer',
    templateUrl: './pet-customer.component.html',
    styleUrls: ['./pet-customer.component.scss'],
    standalone: false
})
export class PetCustomerComponent {

  @Input() pet: MyPetSerializationGroup;
  @Output() zhuzhUp = new EventEmitter<void>();
  @Output() changeFit = new EventEmitter<void>();
}
