/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {EncyclopediaRoutingModule} from "./encyclopedia-routing.module";
import {EncyclopediaComponent} from "./page/encyclopedia/encyclopedia.component";
import {ArtistsComponent} from "./page/artists/artists.component";
import {ResidentDirectoryComponent} from "./page/resident-directory/resident-directory.component";
import {PetProfileComponent} from "./page/pet-profile/pet-profile.component";
import {UserProfileComponent} from "./page/user-profile/user-profile.component";
import {ItemEncyclopediaComponent} from "./page/item-encyclopedia/item-encyclopedia.component";
import {ItemEncyclopediaDetailsComponent} from "./page/item-encyclopedia/item-encyclopedia-details/item-encyclopedia-details.component";
import {SpeciesComponent} from "./page/species/species.component";
import {SpeciesDetailsComponent} from "./page/species/species-details/species-details.component";
import {MarkdownModule} from "ngx-markdown";
import {FormsModule} from "@angular/forms";
import {HowItsMadeComponent} from "./page/how-its-made/how-its-made.component";
import {HelpComponent} from "./page/help/help.component";
import {HowToPlayComponent} from "./page/help/how-to-play/how-to-play.component";
import {MaxInventoryComponent} from "./page/help/max-inventory/max-inventory.component";
import {MaslowsHierarchyComponent} from "./page/help/maslows-hierarchy/maslows-hierarchy.component";
import {PetLevelComponent} from "./page/help/pet-level/pet-level.component";
import {PetActivitiesComponent} from "./page/help/pet-activities/pet-activities.component";
import {FoodFlavorsComponent} from "./page/help/food-flavors/food-flavors.component";
import {AffectionComponent} from "./page/help/affection/affection.component";
import {GroupsComponent} from "./page/groups/groups.component";
import {GroupComponent} from "./page/groups/group/group.component";
import { PrivacyPolicyComponent } from './page/privacy-policy/privacy-policy.component';
import {MeritsComponent} from "./page/help/merits/merits.component";
import {RelationshipsComponent} from "./page/help/relationships/relationships.component";
import { ToolsAndHatsComponent } from './page/help/tools-and-hats/tools-and-hats.component';
import { MeritComponent } from './page/merit/merit.component';
import { PetShelterComponent } from './page/user-profile/pet-shelter/pet-shelter.component';
import { FollowUnfollowComponent } from './component/follow-unfollow/follow-unfollow.component';
import { SkillsComponent } from './page/help/skills/skills.component';
import { LockedInventoryComponent } from './page/help/locked-inventory/locked-inventory.component';
import { PetDirectoryComponent } from './page/pet-directory/pet-directory.component';
import { SpiritCompanionDirectoryComponent } from './page/spirit-companion-directory/spirit-companion-directory.component';
import { PetRelationshipsComponent } from './component/pet-relationships/pet-relationships.component';
import {GroupsHelpComponent} from "./page/help/groups/groups-help.component";
import { DesignGoalsComponent } from './page/design-goals/design-goals.component';
import { DesignGoalDetailsComponent } from './page/design-goal-details/design-goal-details.component';
import { StarKindredComponent } from './page/help/star-kindred/star-kindred.component';
import { SocialLinkComponent } from './component/social-link/social-link.component';
import { LunchboxesComponent } from "./page/help/lunchboxes/lunchboxes.component";
import { StatusEffectsComponent } from "./page/status-effects/status-effects.component";
import { GroupSearchComponent } from "./component/group-search/group-search.component";
import { LoadingThrobberComponent } from "../shared/component/loading-throbber/loading-throbber.component";
import { PaginatorComponent } from "../shared/component/paginator/paginator.component";
import { UrlPaginatorComponent } from "../shared/component/url-paginator/url-paginator.component";
import { ListDesignGoalsComponent } from "../shared/component/list-design-goals/list-design-goals.component";
import { PetSearchComponent } from "../shared/component/pet-search/pet-search.component";
import { DateAndTimeComponent } from "../shared/component/date-and-time/date-and-time.component";
import { PetAppearanceComponent } from "../shared/component/pet-appearance/pet-appearance.component";
import { DateOnlyComponent } from "../shared/component/date-only/date-only.component";
import { PetGroupLabelPipe } from "../shared/pipe/pet-group-label.pipe";
import { PetGroupIconPipe } from "../shared/pipe/pet-group-icon.pipe";
import { PlayerNameComponent } from "../shared/component/player-name/player-name.component";
import { InventoryItemComponent } from "../shared/component/inventory-item/inventory-item.component";
import { ImageComponent } from "../shared/component/image/image.component";
import { PetChangesComponent } from "../shared/component/pet-changes/pet-changes.component";
import { PetActivityLogTagComponent } from "../shared/component/pet-activity-log-tag/pet-activity-log-tag.component";
import { PetActivityLogCalendarComponent } from "../shared/component/pet-activity-log-calendar/pet-activity-log-calendar.component";
import { DonutChartComponent } from "../shared/component/donut-chart/donut-chart.component";
import { HelpLinkComponent } from "../shared/component/help-link/help-link.component";
import { ItemSearchComponent } from "../shared/component/item-search/item-search.component";
import { HasUnlockedFeaturePipe } from "../shared/pipe/has-unlocked-feature.pipe";
import { ItemDetailsComponent } from "../shared/component/item-details/item-details.component";
import { PetGroupProductsLabelPipe } from "../shared/pipe/pet-group-products-label.pipe";
import { PetFriendComponent } from "../shared/component/pet-friend/pet-friend.component";
import {PlayerSearchComponent} from "../shared/component/player-search/player-search.component";
import { PetActivityStatsDetailsComponent } from "./component/pet-activity-stats-details/pet-activity-stats-details.component";
import {
    ItemPriceHistoryFromApiComponent
} from "../shared/component/item-price-history-from-api/item-price-history-from-api.component";
import {PspTimeComponent} from "../shared/component/psp-time/psp-time.component";
import {PetBadgeListComponent} from "../shared/pet-badge-list/pet-badge-list.component";
import { CeilPipe } from "../shared/pipe/ceil.pipe";
import { BeehiveHelpComponent } from "./page/help/beehive/beehive-help.component";
import {NpcDialogComponent} from "../shared/component/npc-dialog/npc-dialog.component";
import { InputYesNoBothComponent } from "../filters/components/input-yes-no-both/input-yes-no-both.component";

@NgModule({
  declarations: [
    EncyclopediaComponent,
    ArtistsComponent,
    ResidentDirectoryComponent,
    PetProfileComponent,
    UserProfileComponent,
    ItemEncyclopediaComponent,
    ItemEncyclopediaDetailsComponent,
    SpeciesComponent,
    SpeciesDetailsComponent,
    HowItsMadeComponent,
    HelpComponent,
    HowToPlayComponent,
    MaxInventoryComponent,
    MaslowsHierarchyComponent,
    PetLevelComponent,
    PetActivitiesComponent,
    FoodFlavorsComponent,
    AffectionComponent,
    GroupsComponent,
    GroupComponent,
    PrivacyPolicyComponent,
    MeritsComponent,
    RelationshipsComponent,
    ToolsAndHatsComponent,
    MeritComponent,
    PetShelterComponent,
    FollowUnfollowComponent,
    SkillsComponent,
    LockedInventoryComponent,
    PetDirectoryComponent,
    SpiritCompanionDirectoryComponent,
    PetRelationshipsComponent,
    GroupsHelpComponent,
    DesignGoalsComponent,
    DesignGoalDetailsComponent,
    StarKindredComponent,
    SocialLinkComponent,
    LunchboxesComponent,
    StatusEffectsComponent,
    GroupSearchComponent,
    PetActivityStatsDetailsComponent,
    BeehiveHelpComponent,
  ],
  imports: [
    CommonModule,
    EncyclopediaRoutingModule,
    MarkdownModule.forChild(),
    FormsModule,
    LoadingThrobberComponent,
    PaginatorComponent,
    UrlPaginatorComponent,
    ListDesignGoalsComponent,
    PetSearchComponent,
    DateAndTimeComponent,
    PetAppearanceComponent,
    DateOnlyComponent,
    PetGroupLabelPipe,
    PetGroupIconPipe,
    PlayerNameComponent,
    InventoryItemComponent,
    ImageComponent,
    PetChangesComponent,
    PetActivityLogTagComponent,
    PetActivityLogCalendarComponent,
    DonutChartComponent,
    HelpLinkComponent,
    ItemSearchComponent,
    HasUnlockedFeaturePipe,
    ItemDetailsComponent,
    PetGroupProductsLabelPipe,
    PetFriendComponent,
    PlayerSearchComponent,
    ItemPriceHistoryFromApiComponent,
    PspTimeComponent,
    PetBadgeListComponent,
    CeilPipe,
    NpcDialogComponent,
    InputYesNoBothComponent,
  ],
    exports: [
        EncyclopediaRoutingModule,
        LockedInventoryComponent,
        SocialLinkComponent
    ]
})
export class EncyclopediaModule { }
