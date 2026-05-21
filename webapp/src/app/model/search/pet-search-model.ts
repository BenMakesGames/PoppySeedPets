/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { QueryStringService } from "../../service/query-string.service";

export interface PetSearchModel
{
  name: string;
  nameExactMatch: boolean;
  species: string|null;
  merit: number|null;
  toolOrHat: number|null;
  isPregnant: boolean|null;
  orderBy: string|null;
}

export function CreatePetSearchModel(): PetSearchModel {
  return {
    name: '',
    nameExactMatch: false,
    species: null,
    merit: null,
    toolOrHat: null,
    isPregnant: null,
    orderBy: null,
  };
}

export function CreatePetSearchModelFromQueryObject(query: any): PetSearchModel
{
  let search: PetSearchModel = {
    name: '',
    nameExactMatch: false,
    species: null,
    merit: null,
    toolOrHat: null,
    isPregnant: null,
    orderBy: null,
  };

  if('name' in query) search.name = query.name.toString().trim();
  if('nameExactMatch' in query) search.nameExactMatch = QueryStringService.parseBool(query.nameExactMatch, false);
  if('species' in query) search.species = query.species ? query.species.toString().trim() || null : null;
  if('merit' in query) search.merit = QueryStringService.parseNullableInt(query.merit);
  if('toolOrHat' in query) search.toolOrHat = QueryStringService.parseNullableInt(query.toolOrHat);
  if('isPregnant' in query) search.isPregnant = QueryStringService.parseNullableBool(query.isPregnant);
  if('orderBy' in query) search.orderBy = query.orderBy.toString().trim();

  return search;
}

export function CreateRequestDtoFromPetSearchModel(search: PetSearchModel): any
{
  let filter: any = {};

  if(search.name && search.name.trim().length > 0) filter.name = search.name.trim();
  if(search.nameExactMatch) filter.nameExactMatch = search.nameExactMatch;
  if(search.species && search.species.trim().length > 0) filter.species = search.species.trim();
  if(search.merit && search.merit > 0) filter.merit = search.merit;
  if(search.toolOrHat && search.toolOrHat > 0) filter.toolOrHat = search.toolOrHat;
  if(search.isPregnant !== null) filter.isPregnant = search.isPregnant;
  if(search.orderBy && search.orderBy.trim().length > 0) filter.orderBy = search.orderBy.trim();

  return filter;
}
