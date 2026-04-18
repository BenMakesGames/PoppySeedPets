/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {RouterModule, Routes} from "@angular/router";
import {NgModule} from "@angular/core";
import {EncyclopediaComponent} from "./page/encyclopedia/encyclopedia.component";
import {PetProfileComponent} from "./page/pet-profile/pet-profile.component";
import {AnyGuard} from "../../guard/any.guard";
import {ResidentDirectoryComponent} from "./page/resident-directory/resident-directory.component";
import {UserProfileComponent} from "./page/user-profile/user-profile.component";
import {ItemEncyclopediaComponent} from "./page/item-encyclopedia/item-encyclopedia.component";
import {ItemEncyclopediaDetailsComponent} from "./page/item-encyclopedia/item-encyclopedia-details/item-encyclopedia-details.component";
import {SpeciesComponent} from "./page/species/species.component";
import {SpeciesDetailsComponent} from "./page/species/species-details/species-details.component";
import {ArtistsComponent} from "./page/artists/artists.component";
import {HowItsMadeComponent} from "./page/how-its-made/how-its-made.component";
import {HelpComponent} from "./page/help/help.component";
import {HowToPlayComponent} from "./page/help/how-to-play/how-to-play.component";
import {MaxInventoryComponent} from "./page/help/max-inventory/max-inventory.component";
import {MaslowsHierarchyComponent} from "./page/help/maslows-hierarchy/maslows-hierarchy.component";
import {PetLevelComponent} from "./page/help/pet-level/pet-level.component";
import {PetActivitiesComponent} from "./page/help/pet-activities/pet-activities.component";
import {FoodFlavorsComponent} from "./page/help/food-flavors/food-flavors.component";
import {AffectionComponent} from "./page/help/affection/affection.component";
import {GroupComponent} from "./page/groups/group/group.component";
import {GroupsComponent} from "./page/groups/groups.component";
import {PrivacyPolicyComponent} from "./page/privacy-policy/privacy-policy.component";
import {MeritsComponent} from "./page/help/merits/merits.component";
import {RelationshipsComponent} from "./page/help/relationships/relationships.component";
import {ToolsAndHatsComponent} from "./page/help/tools-and-hats/tools-and-hats.component";
import {MeritComponent} from "./page/merit/merit.component";
import {PetShelterComponent} from "./page/user-profile/pet-shelter/pet-shelter.component";
import {SkillsComponent} from "./page/help/skills/skills.component";
import {LockedInventoryComponent} from "./page/help/locked-inventory/locked-inventory.component";
import {PetDirectoryComponent} from "./page/pet-directory/pet-directory.component";
import {SpiritCompanionDirectoryComponent} from "./page/spirit-companion-directory/spirit-companion-directory.component";
import {GroupsHelpComponent} from "./page/help/groups/groups-help.component";
import { DesignGoalsComponent } from "./page/design-goals/design-goals.component";
import { DesignGoalDetailsComponent } from "./page/design-goal-details/design-goal-details.component";
import { StarKindredComponent } from "./page/help/star-kindred/star-kindred.component";
import { LunchboxesComponent } from "./page/help/lunchboxes/lunchboxes.component";
import { StatusEffectsComponent } from "./page/status-effects/status-effects.component";
import { BeehiveHelpComponent } from "./page/help/beehive/beehive-help.component";

const routes: Routes = [
  { path: '', component: EncyclopediaComponent, canActivate: [ AnyGuard ] },
  { path: 'pet', component: PetDirectoryComponent, canActivate: [ AnyGuard ] },
  { path: 'pet/:pet', component: PetProfileComponent, canActivate: [ AnyGuard ] },
  { path: 'pet/:pet/:tab', component: PetProfileComponent, canActivate: [ AnyGuard ] },
  { path: 'resident', component: ResidentDirectoryComponent, canActivate: [ AnyGuard ] },
  { path: 'resident/:user', component: UserProfileComponent, canActivate: [ AnyGuard ] },
  { path: 'resident/:user/petShelter', component: PetShelterComponent, canActivate: [ AnyGuard ] },
  { path: 'spiritCompanion', component: SpiritCompanionDirectoryComponent, canActivate: [ AnyGuard ] },
  { path: 'item', component: ItemEncyclopediaComponent, canActivate: [ AnyGuard ] },
  { path: 'item/:name', component: ItemEncyclopediaDetailsComponent, canActivate: [ AnyGuard ] },
  { path: 'species', component: SpeciesComponent, canActivate: [ AnyGuard ] },
  { path: 'species/:name', component: SpeciesDetailsComponent, canActivate: [ AnyGuard ] },
  { path: 'artists', component: ArtistsComponent, canActivate: [ AnyGuard ] },
  { path: 'group', component: GroupsComponent, canActivate: [ AnyGuard ] },
  { path: 'group/:group', component: GroupComponent, canActivate: [ AnyGuard ] },
  { path: 'merit', component: MeritComponent, canActivate: [ AnyGuard ] },
  { path: 'statusEffects', component: StatusEffectsComponent, canActivate: [ AnyGuard ] },
  { path: 'designGoal', component: DesignGoalsComponent, canActivate: [ AnyGuard ] },
  { path: 'designGoal/:designGoal', component: DesignGoalDetailsComponent, canActivate: [ AnyGuard ] },

  { path: 'howItsMade', component: HowItsMadeComponent, canActivate: [ AnyGuard ] },
  { path: 'privacyPolicy', component: PrivacyPolicyComponent, canActivate: [ AnyGuard ] },
  { path: 'help', component: HelpComponent, canActivate: [ AnyGuard ] },
  { path: 'help/affection', component: AffectionComponent, canActivate: [ AnyGuard ] },
  { path: 'help/beehive', component: BeehiveHelpComponent, canActivate: [ AnyGuard ] },
  { path: 'help/flavors', component: FoodFlavorsComponent, canActivate: [ AnyGuard ] },
  { path: 'help/groups', component: GroupsHelpComponent, canActivate: [ AnyGuard ] },
  { path: 'help/howToPlay', component: HowToPlayComponent, canActivate: [ AnyGuard ] },
  { path: 'help/lockedInventory', component: LockedInventoryComponent, canActivate: [ AnyGuard ] },
  { path: 'help/lunchboxes', component: LunchboxesComponent, canActivate: [ AnyGuard ] },
  { path: 'help/maslowsHierarchy', component: MaslowsHierarchyComponent, canActivate: [ AnyGuard ] },
  { path: 'help/maxInventory', component: MaxInventoryComponent, canActivate: [ AnyGuard ] },
  { path: 'help/merits', component: MeritsComponent, canActivate: [ AnyGuard ] },
  { path: 'help/petActivity', component: PetActivitiesComponent, canActivate: [ AnyGuard ] },
  { path: 'help/petLevel', component: PetLevelComponent, canActivate: [ AnyGuard ] },
  { path: 'help/relationships', component: RelationshipsComponent, canActivate: [ AnyGuard ] },
  { path: 'help/toolsAndHats', component: ToolsAndHatsComponent, canActivate: [ AnyGuard ] },
  { path: 'help/skillsAndAttributes', component: SkillsComponent, canActivate: [ AnyGuard ] },
  { path: 'help/starKindred', component: StarKindredComponent, canActivate: [ AnyGuard ] },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class EncyclopediaRoutingModule { }
