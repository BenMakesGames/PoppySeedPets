/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { PetPublicProfileSerializationGroup } from "../../../model/public-profile/pet-public-profile.serialization-group";

export interface StarKindredCharacter
{
  id: number;
  pet: PetPublicProfileSerializationGroup;
  name: string;
  race: string;
  class: string;
  level: number;
  experience: number;
  experienceToNextLevel: number|null;
  adventuresCompleted: number;
  adventuresWon: number;
  createdOn: string;
  retiredOn: string|null;
  epilogue: string|null;
  stats: { name: string, value: number, modifier: number, growth: 'primary'|'secondary'|null }[];
  skills: { name: string, stat: string, value: number, isClassSkill: boolean }[];
}

export interface StarKindredAdventure
{
  index: number;
  theme: string;
  title: string;
  summary: string;
  encounters: { title: string, skill: string }[];
  skillsTested: string[];
}

export interface StarKindredDifficulty
{
  name: string;
  targetPerAdventurer: number;
  victoryExperience: number;
}

export interface StarKindredStatus
{
  canPlayToday: boolean;
  maxPartySize: number;
  maxLevel: number;
  adventures: StarKindredAdventure[];
  difficulties: StarKindredDifficulty[];
  characters: StarKindredCharacter[];
}

export interface StarKindredAdventureResult
{
  victory: boolean;
  text: string;
  loot: string[];
  progress: {
    characterId: number;
    characterName: string;
    petName: string;
    experienceGained: number;
    levelsGained: number;
    level: number;
    retired: boolean;
  }[];
}
