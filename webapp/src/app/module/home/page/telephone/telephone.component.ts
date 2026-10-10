/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, OnInit } from '@angular/core';
import { ApiService } from "../../../shared/service/api.service";
import { ActivatedRoute, Router } from "@angular/router";
import { MatDialog } from "@angular/material/dialog";
import { ItemActionResponseSerializationGroup } from "../../../../model/item-action-response.serialization-group";
import { ItemActionResponseDialog } from "../../../../dialog/item-action-response/item-action-response.dialog";

@Component({
    templateUrl: './telephone.component.html',
    styleUrls: ['./telephone.component.scss'],
    standalone: false
})
export class TelephoneComponent implements OnInit {
  inventoryId: number;

  knownNumbers: KnownNumber[]|null = null;
  number = '';
  calling = false;

  constructor(
    private api: ApiService, private activatedRoute: ActivatedRoute, private router: Router,
    private matDialog: MatDialog
  )
  {
  }

  ngOnInit()
  {
    this.inventoryId = parseInt(this.activatedRoute.snapshot.paramMap.get('id'));

    this.api.get<KnownNumber[]>('/item/telephone/' + this.inventoryId + '/knownNumbers').subscribe({
      next: r => {
        this.knownNumbers = r.data;
      }
    });
  }

  doCall(number: string)
  {
    if(this.calling || number.trim() === '') return;

    this.calling = true;

    this.api.post<ItemActionResponseSerializationGroup>('/item/telephone/' + this.inventoryId + '/call', { number: number }).subscribe({
      next: r => {
        ItemActionResponseDialog.open(this.matDialog, r.data, null);

        this.router.navigate([ '/home' ]);
      },
      error: () => {
        this.calling = false;
      }
    });
  }
}

interface KnownNumber
{
  label: string;
  cost: number;
}
