/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {
  Component,
  computed,
  effect, ElementRef,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  signal, SimpleChanges, viewChild
} from '@angular/core';
import { fromEvent, Subscription } from "rxjs";
import {ThemeService} from "../../service/theme.service";
import { LUNCHBOXES } from "../../../../model/lunchboxes.model";
import { ToolItemGripSerializationGroup } from "../../../../model/public-profile/tool-item-grip.serialization-group";
import { ImageComponent } from "../image/image.component";
import { CommonModule } from "@angular/common";
import { PetAuraComponent } from "../pet-aura/pet-aura.component";
import { EmojiOrFaComponent } from "../emoji-or-fa/emoji-or-fa.component";
import { EmoteFontAwesomeClasses } from "../../../../model/emote-font-awesome-classes";
import { CurrentMoonPhaseComponent } from "../current-moon-phase/current-moon-phase.component";

@Component({
    selector: 'app-pet-appearance',
    templateUrl: './pet-appearance.component.html',
    imports: [
        ImageComponent,
        CommonModule,
        PetAuraComponent,
        EmojiOrFaComponent
    ],
    styleUrls: ['./pet-appearance.component.scss']
})
export class PetAppearanceComponent implements OnChanges, OnInit, OnDestroy {

  @Input() pet;
  @Input() showTool = true;
  @Input() showHat = true;
  @Input() size = '1in';
  emote = signal<{ letter: string }|null>(null);
  isAnimatingEmote = signal(false);
  emoteFontAwesomeClass = computed(() => this.emote() === null ? null : EmoteFontAwesomeClasses[this.emote().letter]);
  @Input() disableAura = false;
  @Input() disableMoonbound = false;
  @Input() disableSpiritCompanion = false;
  @Input() disableEgg = false;
  @Input() showLunchbox: boolean|null = null;
  @Input() overrideLunchboxIndex: number|null = null;
  @Input('flipped') forceFlip: boolean|null = null;

  lunchboxImage;
  animationDelay = Math.random() * -12;
  spiritSpeed = Math.random() * 5 + 10;
  isSpectral = false;
  isDancing = false;
  isInverted = false;
  isVeryInverted = false;
  moonPhase: string|null = null;
  toolRotationStyle: any = {};
  toolPositionStyle: any = {};
  hatRotationStyle: any = {};
  hatPositionStyle: any = {};
  flipped = false;
  upsideDown = false;
  glittery = false;
  petColors: any = {};
  eggColors: any = {};
  itemTool: ToolAppearance|null = null;
  petSpecies: any = {};

  spiritCompanionAnimations: string;
  spiritCompanionAnimationsSubscription: Subscription;

  private emojiContainer = viewChild<ElementRef<HTMLElement>>('emojiContainer');

  auras = [];

  constructor(private themeService: ThemeService) {
    effect(() => {
      this.isAnimatingEmote.set(this.emote() !== null);
    });
    
    effect(onCleanup => {
      const node = this.emojiContainer()?.nativeElement;
      if (!node) return;

      const sub = fromEvent(node, 'animationend')
        .subscribe(() => this.isAnimatingEmote.set(false));

      onCleanup(() => sub.unsubscribe());
    });
  }

  ngOnInit(): void {
    this.spiritCompanionAnimationsSubscription = this.themeService.spiritCompanionAnimations.subscribe({
      next: v => {
        this.spiritCompanionAnimations = v;
      }
    })
  }

  ngOnDestroy(): void {
    this.spiritCompanionAnimationsSubscription.unsubscribe();
  }

  ngOnChanges(changes: SimpleChanges)
  {
    if(changes.pet)
    {
      this.emote.set(this.pet.emoji
        ? { letter: this.pet.emoji }
        : null
      );
    }

    this.petSpecies = this.pet.statuses && this.pet.statuses.some(s => s === 'Wereform')
      ? {
        ...WEREFORM_SPECIES[this.pet.wereform],
        pregnancyStyle: this.pet.species.pregnancyStyle,
        eggImage: this.pet.species.eggImage
      }
      : this.pet.species
    ;
    this.petColors = { colorA: this.pet.colorA, colorB: this.pet.colorB };
    this.eggColors = this.pet.pregnancy ? { colorA: this.pet.pregnancy.eggColor } : {};

    this.toolPositionStyle = {
      width: this.size,
      height: this.size,
    };

    this.toolRotationStyle = {
      width: this.size,
      height: this.size,
    };

    this.hatPositionStyle = {
      width: this.size,
      height: this.size,
    };

    this.hatRotationStyle = {
      width: this.size,
      height: this.size,
    };

    if(!this.disableMoonbound && this.pet.merits && this.pet.merits.some(m => m.name === 'Moon-bound'))
      this.moonPhase = CurrentMoonPhaseComponent.getMoonPhase(new Date());
    else
      this.moonPhase = null;

    this.isSpectral = this.pet.merits && this.pet.merits.some(m => m.name === 'Spectral');
    this.isInverted = this.pet.merits && this.pet.merits.some(m => m.name === 'Inverted');
    this.isVeryInverted = this.pet.merits && this.pet.merits.some(m => m.name === 'Very Inverted');

    if(this.forceFlip !== null)
      this.flipped = this.forceFlip;
    else
      this.flipped = Math.random() < 0.02;

    this.upsideDown = this.pet.statuses && this.pet.statuses.some(s => s === 'Anti-grav\'d');
    this.isDancing = this.pet.statuses && this.pet.statuses.some(s => s === 'Dancing Like a Fool');
    this.glittery = this.pet.statuses && this.pet.statuses.some(s => s === 'Glitter-bombed');

    if(this.pet.merits && this.pet.merits.some(m => m.name === 'Mirrored'))
      this.flipped = !this.flipped;

    if(this.pet.tool)
    {
      this.itemTool = getToolAppearance(this.pet.tool);

      this.toolPositionStyle.transform = '';

      const handX = (this.petSpecies.handX - 0.5) * (this.pet.scale / 100) + 0.5;
      const handY = (this.petSpecies.handY - 0.5) * (this.pet.scale / 100) + 0.5;

      this.toolPositionStyle.transform +=
        'translate(' +
          ((handX - this.itemTool.gripX) * 100) + '%, ' +
          ((handY - this.itemTool.gripY) * 100) + '%' +
        ')'
      ;

      this.toolRotationStyle.transform = 'scale(' + this.itemTool.gripScale + ') ';

      let handAngle = this.itemTool.gripAngleFixed ? 0 : this.petSpecies.handAngle;

      if(this.petSpecies.flipX)
      {
        this.toolRotationStyle.transform += 'scaleX(-1) ';
        handAngle = -handAngle;
      }

      this.toolRotationStyle.transform += 'rotate(' + (handAngle + this.itemTool.gripAngle) + 'deg)';

      this.toolRotationStyle.transformOrigin = (this.itemTool.gripX * 100) + '% ' + (this.itemTool.gripY * 100) + '%';
    }
    else
    {
      this.itemTool = null;
    }

    if(this.pet.hat)
    {
      this.hatPositionStyle.transform = '';

      const hatX = (this.petSpecies.hatX - 0.5) * (this.pet.scale / 100) + 0.5;
      const hatY = (this.petSpecies.hatY - 0.5) * (this.pet.scale / 100) + 0.5;

      this.hatPositionStyle.transform +=
        'translate(' +
          ((hatX - this.pet.hat.item.hat.headX) * 100) + '%, ' +
          ((hatY - this.pet.hat.item.hat.headY) * 100) + '%' +
        ')'
      ;

      this.hatRotationStyle.transform = 'scale(' + this.pet.hat.item.hat.headScale + ') ';

      let hatAngle = this.pet.hat.item.hat.headAngleFixed ? 0 : this.petSpecies.hatAngle;

      if(this.petSpecies.flipX)
      {
        this.hatRotationStyle.transform += 'scaleX(-1) ';
        hatAngle = -hatAngle;
      }

      this.hatRotationStyle.transform += 'rotate(' + (hatAngle + this.pet.hat.item.hat.headAngle) + 'deg)';

      this.hatRotationStyle.transformOrigin = (this.pet.hat.item.hat.headX * 100) + '% ' + (this.pet.hat.item.hat.headY * 100) + '%';
    }

    this.auras = [];

    if(!this.disableAura)
    {
      if(this.pet.hat?.enchantment?.aura)
        this.auras.push({ ...this.pet.hat.enchantment.aura, hue: this.pet.hat.enchantmentHue });

      if(this.pet.tool?.enchantment?.aura)
        this.auras.push({ ...this.pet.tool.enchantment.aura, hue:this.pet.tool.enchantmentHue });
    }

    if(this.overrideLunchboxIndex !== null)
      this.lunchboxImage = LUNCHBOXES[this.overrideLunchboxIndex];
    else
      this.lunchboxImage = LUNCHBOXES[this.pet.lunchboxIndex];
  }
}

export const DEFAULT_ITEM_POSITION = {
  gripX: 0.5,
  gripY: 0.5,
  gripAngle: 0,
  gripAngleFixed: false,
  gripScale: 0.5,
  alwaysInFront: false,
};

export interface ToolAppearance
{
  image: string,
  gripX: number,
  gripY: number,
  gripAngle: number,
  gripAngleFixed: boolean,
  gripScale: number,
  alwaysInFront: boolean
}

export interface PetTool
{
  id: number;
  item: { id: number, name: string, image: string, tool: ToolItemGripSerializationGroup, hat: any };
  sellPrice: number;
  enchantment: any;
  spice: any;
  illusion: { id: number, name: string, image: string, tool: ToolItemGripSerializationGroup, hat: any };
}

export function getToolAppearance(tool: PetTool): ToolAppearance
{
  if(tool.illusion)
  {
    if(tool.illusion.tool)
      return { ...tool.illusion.tool, image: tool.illusion.image };
    else
      return { ...DEFAULT_ITEM_POSITION, image: tool.illusion.image };
  }

  if(tool.item.tool)
    return { ...tool.item.tool, image: tool.item.image };

  return { ...DEFAULT_ITEM_POSITION, image: tool.item.image };
}

const WEREFORM_SPECIES = [
  {
    name: 'Werecreature',
    image: 'lycanthrope/1',
    handX: 0.47,
    handY: 0.765,
    handAngle: 75,
    flipX: false,
    handBehind: false,
    availableFromPetShelter: false,
    hatX: 0.565,
    hatY: 0.35,
    hatAngle: -11,
    family: 'lycanthrope'
  },
  {
    name: 'Werecreature',
    image: 'lycanthrope/2',
    handX: 0.795,
    handY: 0.67,
    handAngle: 31,
    flipX: false,
    handBehind: false,
    availableFromPetShelter: false,
    hatX: 0.52,
    hatY: 0.485,
    hatAngle: 5,
    family: 'lycanthrope'
  },
  {
    name: 'Werecreature',
    image: 'lycanthrope/3',
    handX: 0.6,
    handY: 0.54,
    handAngle: 128,
    flipX: false,
    handBehind: false,
    availableFromPetShelter: false,
    hatX: 0.5,
    hatY: 0.2,
    hatAngle: 5,
    family: 'lycanthrope'
  },
  {
    name: 'Werecreature',
    image: 'lycanthrope/4',
    handX: 0.45,
    handY: 0.625,
    handAngle: -31,
    flipX: false,
    handBehind: true,
    availableFromPetShelter: false,
    hatX: 0.77,
    hatY: 0.365,
    hatAngle: 0,
    family: 'lycanthrope'
  },
  {
    name: 'Werecreature',
    image: 'lycanthrope/5',
    handX: 0.5,
    handY: 0.53,
    handAngle: 143,
    flipX: false,
    handBehind: false,
    availableFromPetShelter: false,
    hatX: 0.485,
    hatY: 0.325,
    hatAngle: 0,
    family: 'lycanthrope'
  },
  {
    name: 'Werecreature',
    image: 'lycanthrope/6',
    handX: 0.59,
    handY: 0.76,
    handAngle: 29,
    flipX: false,
    handBehind: false,
    availableFromPetShelter: false,
    hatX: 0,
    hatY: 1,
    hatAngle: 0,
    family: 'lycanthrope'
  }
];
