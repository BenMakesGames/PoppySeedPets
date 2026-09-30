/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, computed, input } from '@angular/core';
import { MyAuraSerializationGroup } from "../../../../model/aura/my-aura.serialization-group";

@Component({
  standalone: true,
  selector: 'app-pet-aura',
  templateUrl: './pet-aura.component.html',
  styleUrls: ['./pet-aura.component.scss']
})
export class PetAuraComponent {
  petScale = input<number>(1);
  aura = input.required<AuraInput>();

  auraFilter = computed(() => auraFilter(this.aura().hue, this.aura().brightness));
}

export function auraFilter(hue: number|null, brightness: number|null): string
{
  const filters: string[] = [];

  if(hue)
    filters.push('hue-rotate(' + hue + 'deg)');

  if(brightness !== null && brightness !== 100)
    filters.push('brightness(' + brightness + '%)');

  return filters.join(' ');
}

export interface AuraInput extends MyAuraSerializationGroup
{
  id: number;
  name: string;
  image: string;
  size: number;
  centerX: number;
  centerY: number;
  hue: number|null;
  brightness: number|null;
}
