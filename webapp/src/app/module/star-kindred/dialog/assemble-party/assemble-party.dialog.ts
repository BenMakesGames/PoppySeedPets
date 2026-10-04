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

    // one row per distinct kind of check; checks against the undead get their own row, since Banish Undead applies
    const checks = this.adventure.encounters.filter((e, i, all) =>
      all.findIndex(o => o.skill === e.skill && o.againstUndead === e.againstUndead) === i
    );

    // the target scales with party size, so compare the party's total bonus to the total target
    this.skillOdds = checks.map(check => ({
      skill: check.againstUndead ? `${check.skill} vs. undead` : check.skill,
      averageRoll: Math.round(this.selected.reduce((total, c) => total + 10.5 + this.encounterBonus(c, check), 0)),
      target: this.difficulty.baseTarget + this.difficulty.targetPerAdventurer * this.selected.length,
    }));
  }

  // rewards are cumulative: beating a difficulty awards its tier, and every easier tier
  get tier(): number
  {
    return this.status.difficulties.indexOf(this.difficulty);
  }

  rewardFor(difficulty: StarKindredDifficulty): StarKindredReward|undefined
  {
    return this.adventure?.rewards.find(r => r.difficulty === difficulty.name);
  }

  skillValue(character: StarKindredCharacter, skill: string): number
  {
    return character.skills.find(s => s.name === skill)?.value ?? 0;
  }

  // mirrors StarKindredCharacter::getEncounterBonus
  private encounterBonus(character: StarKindredCharacter, encounter: StarKindredAdventure['encounters'][number]): number
  {
    return this.skillValue(character, encounter.skill) +
      (encounter.againstUndead ? character.classFeatures.banishUndead ?? 0 : 0);
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
