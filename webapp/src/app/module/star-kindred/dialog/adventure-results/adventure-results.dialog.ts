/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialog, MatDialogRef } from "@angular/material/dialog";
import { StarKindredAdventureResult } from "../../model/star-kindred.models";

@Component({
  templateUrl: './adventure-results.dialog.html',
  styleUrls: ['./adventure-results.dialog.scss'],
  standalone: false
})
export class AdventureResultsDialog {

  constructor(
    @Inject(MAT_DIALOG_DATA) public result: StarKindredAdventureResult,
    private dialogRef: MatDialogRef<AdventureResultsDialog>
  ) {
  }

  doOk()
  {
    this.dialogRef.close();
  }

  static open(matDialog: MatDialog, result: StarKindredAdventureResult): MatDialogRef<AdventureResultsDialog>
  {
    return matDialog.open(AdventureResultsDialog, {
      data: result
    });
  }
}
