/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  standalone: true,
  name: 'petGroupProductLabel'
})
export class PetGroupProductLabelPipe implements PipeTransform {

  readonly GROUP_PRODUCT_LABELS = [
    '',
    'Album',
    'Survey',
    '',
    '',
    'Harvest'
  ];

  transform(value: number): any {
    if(value < 1 || value >= this.GROUP_PRODUCT_LABELS.length)
      return '<unknown type>';

    return this.GROUP_PRODUCT_LABELS[value];
  }

}
