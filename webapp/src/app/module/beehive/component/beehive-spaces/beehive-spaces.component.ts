/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, computed, input, output } from '@angular/core';
import { BeehiveSpace, BeehiveSpaceType } from "../../../../model/my-beehive.serialization-group";

interface IndexedSpace
{
  index: number;
  space: BeehiveSpace;
}

@Component({
  selector: 'app-beehive-spaces',
  templateUrl: './beehive-spaces.component.html',
  styleUrls: ['./beehive-spaces.component.scss'],
  imports: [],
})
export class BeehiveSpacesComponent {
  spaces = input.required<BeehiveSpace[]>();
  selectable = input<boolean>(false);

  spaceChosen = output<number>();

  static readonly RowLengths = [ 3, 4, 5, 4, 3 ];

  static readonly Icons: Record<BeehiveSpaceType, string> = {
    jungle: 'fa-tree-palm',
    beach: 'fa-wave',
    grassy: 'fa-flower-tulip',
    rocky: 'fa-mountain',
  };

  // the flat 19-entry array, row-major over rows of 3/4/5/4/3
  rows = computed<IndexedSpace[][]>(() => {
    const spaces = this.spaces();
    const rows: IndexedSpace[][] = [];
    let index = 0;

    for(const length of BeehiveSpacesComponent.RowLengths)
    {
      rows.push(spaces.slice(index, index + length).map((space, i) => ({ index: index + i, space })));
      index += length;
    }

    return rows;
  });

  icon(type: BeehiveSpaceType): string
  {
    return BeehiveSpacesComponent.Icons[type];
  }

  doChoose(index: number)
  {
    if(!this.selectable() || this.spaces()[index].harvested)
      return;

    this.spaceChosen.emit(index);
  }
}
