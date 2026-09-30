/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {MyInventoryItemSerializationGroup} from "./my-inventory-item.serialization-group";
import {ToolItemGripSerializationGroup} from "../public-profile/tool-item-grip.serialization-group";
import {FoodSerializationGroup} from "../food.serialization-group";
import { MyAuraSerializationGroup } from "../aura/my-aura.serialization-group";

export interface MyInventorySerializationGroup
{
  id: number;
  comments: string[];
  item: MyInventoryItemSerializationGroup;
  illusion: { image: string, name: string }|null;
  createdBy: { id: number, name: string };
  createdOn: string;
  modifiedOn: string;
  selected?: boolean;
  sellPrice: number|null;
  lockedToOwner: boolean;
  enchantment: { effects: ToolItemGripSerializationGroup, name: string, isSuffix: boolean, aura: MyAuraSerializationGroup|null }|null;
  enchantmentHue: number|null;
  enchantmentBrightness: number|null;
  spice: { effects: FoodSerializationGroup, name: string, isSuffix: boolean }|null;
  isUpgrade?: boolean;
}
