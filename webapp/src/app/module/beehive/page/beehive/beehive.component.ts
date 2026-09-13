/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Component, OnDestroy, OnInit} from '@angular/core';
import {ApiService} from "../../../shared/service/api.service";
import {BeehiveBar, MyBeehiveResponse, MyBeehiveSerializationGroup} from "../../../../model/my-beehive.serialization-group";
import {ApiResponseModel} from "../../../../model/api-response.model";
import {UserDataService} from "../../../../service/user-data.service";
import {MyAccountSerializationGroup} from "../../../../model/my-account/my-account.serialization-group";
import {ItemDetailsDialog} from "../../../../dialog/item-details/item-details.dialog";
import {Subscription} from "rxjs";
import { SelectPetDialog } from "../../../../dialog/select-pet/select-pet.dialog";
import { MessagesService } from "../../../../service/messages.service";
import { InteractWithAwayPetDialog } from "../../../pet-helpers/dialog/interact-with-away-pet/interact-with-away-pet-dialog.component";
import { MatDialog } from "@angular/material/dialog";
import { FeedBeehiveDialog } from "../../dialog/feed-beehive/feed-beehive.dialog";
import { AreYouSureDialog } from "../../../../dialog/are-you-sure/are-you-sure.dialog";

// one row under the "Progress" heading: a bar, plus either a Harvest button or a status word
interface BeehiveBarRow
{
  bar: BeehiveBar;
  label: string;

  // how the bar is named in the screen-reader prompt for choosing a space
  deployLabel: string;

  percent: number;

  // whether app:buzz-buzz (api/src/Command/BuzzBuzzCommand.php) will advance this bar on its next
  // run. worker & hard-worker bees always advance; royalty bees only advance while the colony is
  // working (i.e. it has flower power to spend), and the helper's bar only exists while a helper does
  growing: boolean;
}

@Component({
    templateUrl: './beehive.component.html',
    styleUrls: ['./beehive.component.scss'],
    standalone: false
})
export class BeehiveComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'Beehive' };

  dialog = null;
  loading = true;
  beehive: MyBeehiveSerializationGroup;
  canReroll = false;
  deploying: BeehiveBar|null = null;
  user: MyAccountSerializationGroup;
  interacting = false;
  beehiveAjax = Subscription.EMPTY;
  userSubscription = Subscription.EMPTY;

  constructor(
    private api: ApiService, private userDataService: UserDataService, private matDialog: MatDialog,
    private messages: MessagesService
  ) {
    this.userSubscription = userDataService.user.subscribe(u => {
      this.user = u;
    });
  }

  ngOnInit() {
    this.beehiveAjax = this.api.get<MyBeehiveResponse>('/beehive').subscribe({
      next: (r: ApiResponseModel<MyBeehiveResponse>) => {
        this.loadBeehive(r.data);
        this.loading = false;
      }
    });
  }

  ngOnDestroy(): void {
    this.beehiveAjax.unsubscribe();
    this.userSubscription.unsubscribe();
  }

  private loadBeehive(data: MyBeehiveResponse)
  {
    this.beehive = data.beehive;
    this.canReroll = data.canReroll;
  }

  doViewItem(itemName: string)
  {
    ItemDetailsDialog.open(this.matDialog, itemName);
  }

  doGiveItem()
  {
    if(this.interacting || this.beehive.flowerPowerIsMaxed) return;

    FeedBeehiveDialog.open(this.matDialog).afterClosed().subscribe({
      next: (data: MyBeehiveResponse|null|undefined) => {
        if(data)
        {
          this.loadBeehive(data);
        }
      }
    })
  }

  // the bars under "Progress", in display order; the helper's bar only exists while a helper does
  get bars(): BeehiveBarRow[]
  {
    const bars: BeehiveBarRow[] = [
      { bar: 'misc', label: 'Worker bees', deployLabel: 'Worker bees', percent: this.beehive.miscPercent, growing: true },
      { bar: 'honeycomb', label: 'Hard-worker bees', deployLabel: 'Hard-worker bees', percent: this.beehive.honeycombPercent, growing: true },
      { bar: 'royalJelly', label: 'Royalty bees', deployLabel: 'Royalty bees', percent: this.beehive.royalJellyPercent, growing: this.beehive.isWorking },
    ];

    if(this.beehive.helper)
      bars.push({ bar: 'helper', label: 'Helper pet', deployLabel: this.beehive.helper.name, percent: this.beehive.helperPercent, growing: true });

    return bars;
  }

  get deployingLabel(): string
  {
    return this.bars.find(b => b.bar === this.deploying)?.deployLabel ?? '';
  }

  // a second click on the same bar's button cancels
  doToggleDeploy(bar: BeehiveBar)
  {
    if(this.interacting) return;

    this.deploying = this.deploying === bar ? null : bar;
  }

  doDeployOnSpace(space: number)
  {
    if(!this.deploying) return;

    const bar = this.deploying;

    this.deploying = null;

    this.postInteraction('harvest', { bar: bar, space: space });
  }

  doReroll()
  {
    if(this.interacting || this.deploying) return;

    AreYouSureDialog.open(this.matDialog, 'Re-roll the Beehive\'s Spaces?', 'Consume 1 Gold Compass and re-roll all 19 spaces? Harvested spaces stay harvested.', 'Spin the needle!', 'Never mind')
      .afterClosed()
      .subscribe(yes => {
        if(yes)
          this.postInteraction('reroll');
      })
    ;
  }

  private postInteraction(action: string, data: any = {})
  {
    if(this.interacting) return;

    this.interacting = true;

    this.api.post<MyBeehiveResponse>('/beehive/' + action, data).subscribe({
      next: (r: ApiResponseModel<MyBeehiveResponse>) => {
        this.dialog = null;
        this.loadBeehive(r.data);
        this.interacting = false;
      },
      error: () => {
        this.dialog = null;
        this.interacting = false;
      }
    });
  }

  doAssignHelper()
  {
    SelectPetDialog.open(this.matDialog)
      .afterClosed()
      .subscribe(pet => {
        if(pet)
        {
          this.interacting = true;

          const everHadAHelper = this.user.canAssignHelpers;

          this.api.post('/beehive/assignHelper/' + pet.id).subscribe({
            next: (r: ApiResponseModel<MyBeehiveResponse>) => {
              this.dialog = null;
              this.loadBeehive(r.data);
              this.interacting = false;

              if(this.userDataService.user.value.canAssignHelpers && !everHadAHelper)
              {
                this.dialog = 'Many thanks bzzbzz. Your pets are very industrious creatures. They possess the spirit of the bee. Perhaps they can assist you in other ways, as well bzzbzz.';

                this.messages.addGenericMessage('Bzzbzz! Your pets possess the spirit of the bee! (Who knew!) You can now assign them to help out in many of your house add-ons!');
              }
            },
            error: () => {
              this.dialog = null;
              this.interacting = false;
            }
          });
        }
      })
    ;
  }

  doRecallHelper()
  {
    if(this.interacting)
      return;

    this.interacting = true;

    this.api.post('/pet/' + this.beehive.helper.id + '/stopHelping').subscribe({
      next: _ => {
        this.beehive.helper = null;
        this.beehive.helperPercent = 0;
        if(this.deploying === 'helper') this.deploying = null;
        this.interacting = false;
      },
      error: _ => {
        this.interacting = false;
      }
    });
  }

  doViewHelper()
  {
    InteractWithAwayPetDialog.open(this.matDialog, this.beehive.helper.id, this.beehive.helper.name, [])
      .afterClosed()
      .subscribe({
        next: v => {
          if(v && v.newPet)
          {
            this.beehive.helper.name = v.newPet.name;
          }
        }
      })
    ;
  }
}
