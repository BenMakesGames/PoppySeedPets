/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Component, OnDestroy, OnInit } from '@angular/core';
import { ApiService } from "../../../shared/service/api.service";
import { MyPetSerializationGroup } from "../../../../model/my-pet/my-pet.serialization-group";
import { MyInventorySerializationGroup } from "../../../../model/my-inventory/my-inventory.serialization-group";
import { Subscription } from "rxjs";
import { ThemeService } from "../../../shared/service/theme.service";

enum VariableType
{
  Color,
}

interface VariableModel
{
  name: string,
  apiName: string;
  label: string,
  format: VariableType,
}

@Component({
    templateUrl: './styler.component.html',
    styleUrls: ['./styler.component.scss'],
    standalone: false
})
export class StylerComponent implements OnInit, OnDestroy {
  pageMeta = { title: 'The Painter' };

  saveThemeSubscription = Subscription.EMPTY;
  Variables = StylerComponent.VARIABLES;
  RGBs: { [key: string]: string } = {};
  revertTo: { [key: string]: number[] } = {};
  showingPreview = false;

  constructor(private api: ApiService, private theme: ThemeService) { }

  ngOnDestroy()
  {
    this.doRevert();
  }

  doRevert()
  {
    StylerComponent.VARIABLES.forEach(v => {
      const rgb = this.revertTo[v.name];
      document.documentElement.style.setProperty('--' + v.name, rgb.join(', '));
      this.RGBs[v.name] = ThemeService.rgbToHex(rgb[0], rgb[1], rgb[2]);
    });
  }

  doSave()
  {
    const data = {};

    StylerComponent.VARIABLES.forEach(v => {
      const rgb = getComputedStyle(document.documentElement).getPropertyValue('--' + v.name)
        .trim()
        .split(/ *, */)
        .map(v => parseInt(v))
      ;

      data[v.apiName] = ThemeService.rgbToHex(rgb[0], rgb[1], rgb[2]);
      this.revertTo[v.name] = rgb;
    });

    this.saveThemeSubscription = this.api.patch('/style/current', data).subscribe();
  }

  ngOnInit(): void {
    StylerComponent.VARIABLES.forEach(v => {
      const cssVarValue = getComputedStyle(document.documentElement).getPropertyValue('--' + v.name).trim();

      const rgb = cssVarValue.split(/ *, */).map(v => parseInt(v));

      //console.log(v.name + ' = ' + cssVarValue + ' = ' + rgb.join(' '));

      this.revertTo[v.name] = rgb;
      this.RGBs[v.name] = ThemeService.rgbToHex(rgb[0], rgb[1], rgb[2]);
    });
  }

  doUpdateColor(name: string, value: string)
  {
    this.theme.setStyleColor(name, value);
  }

  // data

  static readonly VARIABLES: VariableModel[] = [
    {
      name: 'color-content-background',
      apiName: 'backgroundColor',
      label: 'Background',
      format: VariableType.Color,
    },
    {
      name: 'color-text-on-content-background',
      apiName: 'textColor',
      label: 'Text',
      format: VariableType.Color,
    },
    {
      name: 'color-primary',
      apiName: 'primaryColor',
      label: 'Header & Selected Item Background',
      format: VariableType.Color,
    },
    {
      name: 'color-text-on-primary',
      apiName: 'textOnPrimaryColor',
      label: 'Header & Selected Item Text',
      format: VariableType.Color,
    },
    {
      name: 'color-speech-bubble-background',
      apiName: 'speechBubbleBackgroundColor',
      label: 'Pet & Speech Bubble Background',
      format: VariableType.Color,
    },
    {
      name: 'color-tab-bar-background',
      apiName: 'tabBarBackgroundColor',
      label: 'Tab Bar & Character Name Background',
      format: VariableType.Color,
    },
    {
      name: 'color-link-and-button',
      apiName: 'linkAndButtonColor',
      label: 'Link & Button Color',
      format: VariableType.Color,
    },
    {
      name: 'color-text-on-button',
      apiName: 'buttonTextColor',
      label: 'Button Text',
      format: VariableType.Color,
    },
    {
      name: 'color-link-on-speech-bubble',
      apiName: 'dialogLinkColor',
      label: 'Link in Speech Bubble',
      format: VariableType.Color,
    },
    {
      name: 'color-warning',
      apiName: 'warningColor',
      label: 'Warning/Loss',
      format: VariableType.Color,
    },
    {
      name: 'color-gain',
      apiName: 'gainColor',
      label: 'Success/Gain',
      format: VariableType.Color,
    },
    {
      name: 'color-tool-bonus',
      apiName: 'bonusAndSpiceColor',
      label: 'Tool Bonus',
      format: VariableType.Color,
    },
    {
      name: 'color-tool-bonus-on-primary',
      apiName: 'bonusAndSpiceSelectedColor',
      label: 'Tool Bonus (selected)',
      format: VariableType.Color,
    },
    {
      name: 'color-input-background',
      apiName: 'inputBackgroundColor',
      label: 'Input Field Background',
      format: VariableType.Color,
    },
    {
      name: 'color-input-foreground',
      apiName: 'inputTextColor',
      label: 'Input Field Text',
      format: VariableType.Color,
    },
  ];

  samplePet: MyPetSerializationGroup = {
    id: 0,
    name: 'Roy',
    colorA: '#336699',
    colorB: '#99ccff',
    tool: null,
    hat: null,
    skills: null,
    species: {
      image: 'monotreme/desikh',
      flipX: false,
      family: 'monotreme',
      handAngle: 0,
      handBehind: false,
      handX: 0,
      handY: 0,
      hatAngle: 0,
      hatX: 0,
      hatY: 0,
      name: 'Desikh',
      pregnancyStyle: 1,
      eggImage: null,
    },
    level: 0,
    note: '',
    costume: '',
    affectionLevel: 0,
    affectionRewardsClaimed: 0,
    merits: [],
    spiritCompanion: null,
    hasRelationships: false,
    lastParkEvent: null,
    parkEventType: null,
    canInteract: false,
    canParticipateInParkEvents: false,
    needs: {
      food: { description: 'food' },
      safety: { description: 'safety' },
      love: { description: 'love' },
      esteem: { description: 'esteem' },
    },
    statuses: [],
    pregnancy: null,
    poisonLevel: 'none',
    alcoholLevel: 'none',
    hallucinogenLevel: 'none',
    isFertile: false,
    canPickTalent: null,
    flavor: 'Planty',
    maximumFriends: 15,
    lunchboxItems: [],
    selfReflectionPoint: 0,
    renamingCharges: 0,
    birthDate: '',
    scale: 100,
    craving: null,
    lunchboxIndex: 0,
    houseTime: { activityTime: 0 },
    badges: []
  };

  sampleInventoryItem: MyInventorySerializationGroup = {
    id: 0,
    comments: [],
    item: {
      id: 0,
      food: null,
      image: 'tool/hammer/heavy',
      name: 'Hammer',
      description: 'Hey, wait: this item isn\'t real!',
      useActions: null,
      tool: null,
      hat: null,
      isFertilizer: false,
      isFlammable: false,
      isTreasure: false,
      recycleValue: 1,
      enchants: null,
      spice: null,
      hollowEarthTileCard: null,
      itemGroups: [],
    },
    illusion: null,
    createdBy: null,
    createdOn: '',
    modifiedOn: '',
    selected: false,
    sellPrice: null,
    lockedToOwner: false,
    enchantment: {
      name: 'Imaginary',
      isSuffix: false,
      effects: null,
      aura: null,
    },
    enchantmentHue: 0,
    enchantmentBrightness: 100,
    spice: null,
  };

  sampleInventoryItem2 = {
    ...this.sampleInventoryItem,
    enchantment: {
      name: 'of Dreams',
      isSuffix: true,
      effects: null,
      aura: null
    },
    item: {
      ...this.sampleInventoryItem.item,
      name: 'Fruit',
      image: 'fruit/orange'
    }
  };

}
