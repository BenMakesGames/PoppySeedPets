/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, Input } from '@angular/core';
import { StarKindredReward } from "../../model/star-kindred.models";

@Component({
  selector: 'app-star-kindred-reward',
  templateUrl: './reward.component.html',
  styleUrls: ['./reward.component.scss'],
  standalone: false
})
export class RewardComponent {
  @Input({ required: true }) reward!: StarKindredReward;

  // lets a layout put the icon and the label in different places (ex: separate table rows)
  @Input() part: 'icon'|'label'|'both' = 'both';

  // alt text, for when the icon is shown without its label
  get label(): string
  {
    if(this.reward.item)
      return (this.reward.item.quantity > 1 ? this.reward.item.quantity + '× ' : '') + this.reward.item.name;

    return '"' + this.reward.aura?.name + '" hat styling' + (this.reward.aura?.alreadyUnlocked ? ' (you already have this)' : '');
  }
}
