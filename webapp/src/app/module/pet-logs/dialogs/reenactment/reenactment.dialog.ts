/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, ElementRef, Inject, OnInit, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogRef } from "@angular/material/dialog";
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";
import { PetAppearanceComponent } from "../../../shared/component/pet-appearance/pet-appearance.component";
import { ActivityLogTagSerializationGroup } from "../../../../model/activity-log-tag.serialization-group";
import { ToolItemSerializationGroup } from "../../../../model/public-profile/tool-item.serialization-group";
import { RouterLink } from "@angular/router";

@Component({
    imports: [
        PetAppearanceComponent,
        RouterLink,
    ],
    templateUrl: './reenactment.dialog.html',
    styleUrl: './reenactment.dialog.scss'
})
export class ReenactmentDialog implements OnInit
{
  public static readonly locationPictures = {
    'Location: Neighborhood': {
      image: 'neighborhood',
      petPosition: { x: 36, y: 76, scale: 12 },
      interestingness: 0,
    },
    'Location: Abandoned Quarry': {
      image: 'abandoned-quarry',
      petPosition: { x: 48, y: 34, scale: 8 },
      interestingness: 0,
    },
    'Location: Micro-jungle': {
      image: 'micro-jungle',
      petPosition: { x: 60, y: 88, scale: 15 },
      interestingness: 0,
    },
    'Location: Stream': {
      image: 'stream',
      petPosition: { x: 52, y: 65, scale: 20 },
      interestingness: 0,
    },
    'Location: Noetala\'s Cocoon': {
      image: 'noetala',
      petPosition: { x: 30, y: 95, scale: 13 },
      interestingness: 1000
    },
    'Location: Small Lake': {
      image: 'lake',
      petPosition: { x: 68, y: 71, scale: 11 },
      interestingness: 0,
    },
    'Location: Hollow Log': {
      image: 'hollow-log',
      petPosition: { x: 31, y: 29, scale: 22 },
      interestingness: 0,
    },
    /*'Location: Under a Bridge': {
      image: 'under-a-bridge',
      petPosition: { x: 10, y: 10, scale: 20 },
      interestingness: 0,
    },
    'Location: Roadside Creek': {
      image: 'roadside-creek',
      petPosition: { x: 10, y: 10, scale: 20 },
      interestingness: 0,
    },*/
    'Location: Beach': {
      image: 'beach',
      petPosition: { x: 10, y: 10, scale: 4 },
      interestingness: 0,
    },
    'Location: At Home': {
      image: 'living-room',
      petPosition: { x: 46, y: 89, scale: 24 },
      interestingness: 0,
    },
    'Location: Icy Moon': {
      image: 'icy-moon',
      petPosition: { x: 30, y: 86, scale: 20 },
      interestingness: 0,
    },
    'Location: Escaping Icy Moon': {
      image: 'escaping-icy-moon-explosion',
      petPosition: { x: 99, y: 104, scale: 30 },
      interestingness: 9999,
    },
    'Location: Hedge Maze': {
      image: 'hedge-maze',
      petPosition: { x: 17, y: 67, scale: 15 },
      interestingness: 0,
    },
    'Location: Hedge Maze Sphinx': {
      image: 'hedge-maze-sphinx',
      petPosition: { x: 19, y: 56, scale: 12 },
      interestingness: 1000,
    },
    'Location: Hedge Maze Light Puzzle': {
      image: 'hedge-maze-light-puzzle',
      petPosition: { x: 74, y: 64, scale: 14, flipped: true },
      interestingness: 1000,
    },
    'Isekai Location: Bug Army': {
      image: 'bug-army',
      petPosition: { x: 85, y: 103, scale: 35 },
      interestingness: 1000,
    },
    'Isekai Location: Celestial Temple': {
      image: 'celestial-temple',
      petPosition: { x: 61, y: 89, scale: 4 },
      interestingness: 1000,
    },
  };

  reenactments: NormalizedReenactment[];
  slideCount = 0;
  howManyMore: number;

  constructor(
    @Inject(MAT_DIALOG_DATA) private data: any,
    private dialogRef: MatDialogRef<ReenactmentDialog>
  )
  {
    this.reenactments = data.reenactments.map((r: Reenactment) => {
      const tagNames = r.tags.map(t => t.title);

      const locationKey = Object.keys(ReenactmentDialog.locationPictures)
        .sort((k1, k2) => ReenactmentDialog.locationPictures[k2].interestingness - ReenactmentDialog.locationPictures[k1].interestingness)
        .find(key => tagNames.includes(key));

      let image: string|null = null;
      let petPosition = { x: -9999, y: 0, scale: 0 };

      if(locationKey)
      {
        const location = ReenactmentDialog.locationPictures[locationKey];

        image = '/assets/images/camera/backgrounds/' + location.image + '.svg';
        petPosition = location.petPosition;
      }

      let data: NormalizedReenactment = {
        pet: r.pet,
        item: r.createdItems.length > 0 ? r.createdItems[0].item : null,
        caption: r.caption,
        image: image,
        petPosition: petPosition,
      }

      if(r.tool)
      {
        data.pet.tool = {
          id: 0,
          item: r.tool,
          illusion: null,
          enchantment: null,
        };
      }

      return data;
    });

    this.howManyMore = data.howManyMore;

    this.slideCount = this.reenactments.length + (this.howManyMore > 0 ? 1 : 0);
  }

  ngOnInit()
  {
    setTimeout(() => this.reenactmentsContainer.nativeElement.scrollLeft = 0, 0);
  }

  @ViewChild('reenactmentsContainer') reenactmentsContainer: ElementRef;

  doPrevious()
  {
    let scrollPosition = Math.ceil(this.reenactmentsContainer.nativeElement.scrollLeft / this.reenactmentsContainer.nativeElement.clientWidth);

    document.getElementById('reenactment' + Math.max(0, scrollPosition - 1)).scrollIntoView();
  }

  doNext()
  {
    let scrollPosition = Math.floor(this.reenactmentsContainer.nativeElement.scrollLeft / this.reenactmentsContainer.nativeElement.clientWidth);

    document.getElementById('reenactment' + Math.min(this.slideCount - 1, scrollPosition + 1)).scrollIntoView();
  }

  doCloseDialog()
  {
    this.dialogRef.close();
  }

  public static open(matDialog: MatDialog, reenactments: Reenactment[], howManyMore: number)
  {
    return matDialog.open(ReenactmentDialog, {
      data: {
        reenactments: reenactments,
        howManyMore: howManyMore
      },
      panelClass: 'reenactment-dialog',
      width: 'min(480px, 80vw, calc(80vh - 4rem))'
    });
  }
}

interface Reenactment
{
  pet: MyPetSerializationGroup;
  tool: ToolItemSerializationGroup|null|undefined;
  tags: ActivityLogTagSerializationGroup[];
  createdItems: { item: { name: string, image: string } }[];
  caption: string;
}

interface NormalizedReenactment
{
  pet: MyPetSerializationGroup;
  item: { name: string, image: string };
  caption: string;
  image: string|null;
  petPosition: PetPosition;
}

interface PetPosition {
  x: number;
  y: number;
  scale: number;
  flipped?: boolean;
}
