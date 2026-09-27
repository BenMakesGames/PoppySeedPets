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
import { ApiService } from "../../../shared/service/api.service";
import {
  StarKindredAdventure,
  StarKindredAdventureResult,
  StarKindredCharacter,
  StarKindredDifficulty,
  StarKindredReward,
  StarKindredStatus
} from "../../model/star-kindred.models";

/**
 * Picks the party for one of the day's adventures, or - when adventure is null - for the retirement
 * adventure, which only (and all) level-20 adventurers may go on.
 */
@Component({
  templateUrl: './assemble-party.dialog.html',
  styleUrls: ['./assemble-party.dialog.scss'],
  standalone: false
})
export class AssemblePartyDialog {

  status: StarKindredStatus;
  adventure: StarKindredAdventure|null;
  available: StarKindredCharacter[];
  selected: StarKindredCharacter[] = [];
  difficulty: StarKindredDifficulty;
  skillOdds: { skill: string, averageRoll: number, target: number }[] = [];
  rewards: StarKindredReward[] = [];
  embarking = false;

  constructor(
    private dialogRef: MatDialogRef<AssemblePartyDialog>,
    private api: ApiService,
    @Inject(MAT_DIALOG_DATA) data: { status: StarKindredStatus, adventure: StarKindredAdventure|null },
  ) {
    this.status = data.status;
    this.adventure = data.adventure;
    this.difficulty = this.status.difficulties[0];

    this.available = this.status.characters.filter(c => this.adventure
      ? c.level < this.status.maxLevel
      : c.level >= this.status.maxLevel
    );

    this.computeOdds();
  }

  isSelected = (character: StarKindredCharacter) => this.selected.some(c => c.id === character.id);

  doToggle(character: StarKindredCharacter)
  {
    if(this.embarking) return;

    if(this.isSelected(character))
      this.selected = this.selected.filter(c => c.id !== character.id);
    else if(this.selected.length < this.status.maxPartySize)
      this.selected = [ ...this.selected, character ];

    this.computeOdds();
  }

  doSetDifficulty(difficulty: StarKindredDifficulty)
  {
    this.difficulty = difficulty;
    this.computeOdds();
  }

  private computeOdds()
  {
    if(!this.adventure) return;

    // rewards are cumulative: beating a difficulty awards its tier, and every easier tier
    const tier = this.status.difficulties.indexOf(this.difficulty);
    this.rewards = this.adventure.rewards.filter(r => this.status.difficulties.findIndex(d => d.name === r.difficulty) <= tier);

    // the target scales with party size, so compare the party's total bonus to the total target
    this.skillOdds = this.adventure.skillsTested.map(skill => ({
      skill: skill,
      averageRoll: Math.round(this.selected.reduce((total, c) => total + 10.5 + this.skillValue(c, skill), 0)),
      target: this.difficulty.targetPerAdventurer * this.selected.length,
    }));
  }

  skillValue(character: StarKindredCharacter, skill: string): number
  {
    return character.skills.find(s => s.name === skill)?.value ?? 0;
  }

  doCancel()
  {
    this.dialogRef.close();
  }

  doEmbark()
  {
    if(this.embarking || this.selected.length === 0) return;

    this.embarking = true;
    this.dialogRef.disableClose = true;

    const characters = this.selected.map(c => c.id);

    const request = this.adventure
      ? this.api.post<StarKindredAdventureResult>('/starKindred/adventure', { adventureId: this.adventure.id, difficulty: this.difficulty.name, characters })
      : this.api.post<StarKindredAdventureResult>('/starKindred/retire', { characters })
    ;

    request.subscribe({
      next: r => {
        this.dialogRef.close(r.data);
      },
      error: () => {
        this.embarking = false;
        this.dialogRef.disableClose = false;
      }
    });
  }

  static open(matDialog: MatDialog, status: StarKindredStatus, adventure: StarKindredAdventure|null): MatDialogRef<AssemblePartyDialog, StarKindredAdventureResult>
  {
    return matDialog.open(AssemblePartyDialog, {
      data: { status, adventure }
    });
  }
}
