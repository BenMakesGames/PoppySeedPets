/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Component, EventEmitter, Inject, OnInit, Output} from '@angular/core';
import { MyInventorySerializationGroup } from "../../model/my-inventory/my-inventory.serialization-group";
import { ApiService } from "../../module/shared/service/api.service";
import { ApiResponseModel } from "../../model/api-response.model";
import { ItemActionResponseSerializationGroup } from "../../model/item-action-response.serialization-group";
import {ItemActionResponseDialog} from "../item-action-response/item-action-response.dialog";
import { Router, RouterLink } from "@angular/router";
import {MessagesService} from "../../service/messages.service";
import {UserDataService} from "../../service/user-data.service";
import {MyAccountSerializationGroup} from "../../model/my-account/my-account.serialization-group";
import {StoryDialog} from "../story/story.dialog";
import {AreYouSureDialog} from "../are-you-sure/are-you-sure.dialog";
import { MAT_DIALOG_DATA, MatDialog, MatDialogRef } from "@angular/material/dialog";
import { LoadingThrobberComponent } from "../../module/shared/component/loading-throbber/loading-throbber.component";
import { MarkdownComponent } from "ngx-markdown";
import { CommonModule } from "@angular/common";
import { ItemNameWithBonusComponent } from "../../module/shared/component/item-name-with-bonus/item-name-with-bonus.component";
import { HasUnlockedFeaturePipe } from "../../module/shared/pipe/has-unlocked-feature.pipe";
import { MoneysComponent } from "../../module/shared/component/moneys/moneys.component";
import { FormsModule } from "@angular/forms";
import { CeilPipe } from "../../module/shared/pipe/ceil.pipe";
import { HelpLinkComponent } from "../../module/shared/component/help-link/help-link.component";
import { ItemTagsComponent } from "../../module/shared/component/item-tags/item-tags.component";
import { ItemPriceHistoryFromApiComponent } from "../../module/shared/component/item-price-history-from-api/item-price-history-from-api.component";
import {ItemOtherPropertiesIcons} from "../../model/item-other-properties-icons";

@Component({
    templateUrl: './inventory-details.dialog.html',
    imports: [
        LoadingThrobberComponent,
        MarkdownComponent,
        CommonModule,
        ItemNameWithBonusComponent,
        HasUnlockedFeaturePipe,
        MoneysComponent,
        FormsModule,
        CeilPipe,
        RouterLink,
        HelpLinkComponent,
        ItemTagsComponent,
        ItemPriceHistoryFromApiComponent
    ],
    styleUrls: ['./inventory-details.dialog.scss']
})
export class InventoryDetailsDialog implements OnInit {

  @Output() itemDeleted = new EventEmitter();

  isOctober = false;
  selling = false;
  sellPrice;
  editSellPrice = false;
  public newSellPrice: number|null = null;
  public sellPriceChanged = false;
  waitingOnAjax = false;
  inventory: MyInventorySerializationGroup;
  user: MyAccountSerializationGroup;

  constructor(
    private matDialog: MatDialog,
    private dialogRef: MatDialogRef<InventoryDetailsDialog>,
    @Inject(MAT_DIALOG_DATA) private data: any,
    private api: ApiService,
    private router: Router,
    private userData: UserDataService,
    private messages: MessagesService
  ) {
    this.inventory = data.inventory;
    this.sellPrice = this.inventory.sellPrice;
    this.newSellPrice = this.sellPrice;
    this.selling = !!this.inventory.sellPrice;
  }

  ngOnInit()
  {
    this.user = this.userData.user.getValue();
    this.isOctober = (new Date()).getUTCMonth() === 9;
  }

  doSell()
  {
    if(this.sellPrice < 1)
    {
      this.messages.addGenericMessage('Selling an item for ' + this.sellPrice + '~~m~~ is nonsense!');
      return;
    }
    else if(this.sellPrice > this.user.maxSellPrice)
    {
      this.messages.addGenericMessage('You cannot sell items for more than ' + this.user.maxSellPrice + '~~m~~.');
      return;
    }

    this.newSellPrice = this.sellPrice;
    this.sellPriceChanged = this.newSellPrice !== this.inventory.sellPrice;
    this.editSellPrice = false;
  }

  doStartSelling()
  {
    this.selling = true;
    this.editSellPrice = true;
  }

  doCancelSelling()
  {
    this.editSellPrice = false;
    this.sellPrice = this.inventory.sellPrice;
    this.selling = !!this.sellPrice;
    this.newSellPrice = this.inventory.sellPrice;
  }

  doStopSelling()
  {
    this.sellPrice = null;
    this.selling = false;
    this.newSellPrice = null;
    this.sellPriceChanged = this.newSellPrice !== this.inventory.sellPrice;
  }

  doRemoveBonus()
  {
    if(this.waitingOnAjax) return;

    const dialog = AreYouSureDialog.open(this.matDialog, 'Are you sure???', 'Really remove the "' + this.inventory.enchantment.name + '" bonus? If an item was used to grant this bonus, you won\'t get it back! (This just lets you re-bonusify the ' + this.inventory.item.name + '.)');

    dialog.afterClosed().subscribe({
      next: (confirmed) => {
        if(confirmed)
        {
          this.waitingOnAjax = true;

          this.api.patch('/inventory/' + this.inventory.id + '/removeBonus').subscribe({
            next: () => {
              this.inventory.enchantment = null;
              this.waitingOnAjax = false;
            },
            error: () => {
              this.waitingOnAjax = false;
            }
          });
        }
      }
    });
  }

  doItemAction(actions: string[])
  {
    if(this.waitingOnAjax) return;

    this.waitingOnAjax = true;

    const link = actions[1];
    const linkType = actions.length > 2 ? actions[2] : 'item';

    if(linkType === 'page')
    {
      let closeDialog = true;

      switch(link)
      {
        case 'magicMirror':
        case 'pandemirrorum':
        case 'birdBathBlueprint':
        case 'forgeBlueprint':
        case 'fishStatue':
        case 'moondialBlueprint':
        case 'basementBlueprint':
        case 'greenhouseBlueprint':
        case 'hyperchromaticPrism':
        case 'magicBrush':
        case 'greenhouseDeed':
        case 'beehiveBlueprint':
        case 'tinyTea':
        case 'tremendousTea':
        case 'totallyTea':
        case 'installComposter':
        case 'werebane':
        case 'brawlSkillScroll':
        case 'craftsSkillScroll':
        case 'musicSkillScroll':
        case 'natureSkillScroll':
        case 'scienceSkillScroll':
        case 'stealthSkillScroll':
        case 'arcanaSkillScroll':
        case 'lassoscope':
        case 'molly':
        case 'nightAndDay':
        case 'proboscis':
        case 'yggdrasilBranch':
        case 'pocketDimension':
        case 'changeWereform':
        case 'blushOfLife':
        case 'extractHyperchromaticPrism':
        case 'infinityVaultBlueprint':
          this.router.navigate([ 'home/choosePet/' + link + '/' + this.inventory.id ]);
          break;

        case 'renamingScroll':
        case 'renameYourself':
        case 'renameSpiritCompanion':
        case 'feedBug':
        case 'behattingScroll':
        case 'iridescentHandCannon':
        case 'spiritPolymorphPotion':
        case 'transmigrationSerum':
        case 'forgettingScroll':
        case 'cursedScissors':
        case 'dragonVase':
        case 'hotPot':
        case 'lengthyScrollOfSkill':
        case 'rijndael':
        case 'wunderbuss':
        case 'philosophersStone':
        case 'releaseMoths':
        case 'betaBug':
        case 'takePicture':
        case 'lunchboxPaint':
        case 'scrollOfIllusions':
        case 'dragonTongue':
        case 'magicCrystalBall':
        case 'smilingWand':
        case 'resonatingBow':
        case 'telephone':
          this.router.navigate([ 'home/' + link + '/' + this.inventory.id ]);
          break;

        default:
          alert('Oh! An error occurred! You may be running an old version of the game. Reload and try again. If the error persists, please let Ben know! He may have messed up!');
          this.waitingOnAjax = false;
          closeDialog = false;
          break;
      }

      if(closeDialog)
        this.dialogRef.close();
    }
    else if(linkType === 'story')
    {
      const path = '/item/' + link.replace(/#/, this.inventory.id.toString());

      StoryDialog.open(this.matDialog, path);

      this.waitingOnAjax = false;
    }
    else if(linkType === 'item')
    {
      const path = '/item/' + link.replace(/#/, this.inventory.id.toString());

      this.api.post<ItemActionResponseSerializationGroup>(path).subscribe({
        next: (r: ApiResponseModel<ItemActionResponseSerializationGroup>) => {
          if(r.data.itemDeleted)
          {
            this.itemDeleted.emit();
            this.dialogRef.close();
          }

          if(r.data.text)
            ItemActionResponseDialog.open(this.matDialog, r.data, this.inventory);

          this.waitingOnAjax = false;
        },
        error: () => {
          this.waitingOnAjax = false;
        }
      });
    }
  }

  doClose()
  {
    this.dialogRef.close();
  }

  public static open(matDialog: MatDialog, inventory: MyInventorySerializationGroup): MatDialogRef<InventoryDetailsDialog>
  {
    return matDialog.open(InventoryDetailsDialog, {
      data: {
        inventory: inventory
      }
    });
  }

    protected readonly itemOtherPropertiesIcons = ItemOtherPropertiesIcons;
}
