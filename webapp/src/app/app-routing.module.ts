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
import { Routes, RouterModule } from '@angular/router';
import {PortalComponent} from './page/portal/portal.component';
import {RegisterComponent} from './page/register/register.component';
import {MustBeLoggedInGuard} from "./guard/must-be-logged-in.guard";
import {MustBeLoggedOutGuard} from "./guard/must-be-logged-out.guard";
import {AnyGuard} from "./guard/any.guard";
import {StatsComponent} from "./page/stats/stats.component";
import {MustHaveUnlockedFloristGuard} from "./guard/must-have-unlocked-florist.guard";
import {MustHaveUnlockedBookstoreGuard} from "./guard/must-have-unlocked-bookstore.guard";
import {NotFoundComponent} from "./page/not-found/not-found.component";
import {MustHaveUnlockedParkGuard} from "./guard/must-have-unlocked-park.guard";
import {ResetPassphraseComponent} from "./page/reset-passphrase/reset-passphrase.component";
import {MustHaveUnlockedGreenhouseGuard} from "./guard/must-have-unlocked-greenhouse.guard";
import {MustHaveUnlockedBasementGuard} from "./guard/must-have-unlocked-basement.guard";
import {MustHaveUnlockedHollowEarthGuard} from "./guard/must-have-unlocked-hollow-earth.guard";
import {MustHaveUnlockedBeehiveGuard} from "./guard/must-have-unlocked-beehive.guard";
import {MustHaveUnlockedFireplaceGuard} from "./guard/must-have-unlocked-fireplace.guard";
import {MustHaveUnlockedMuseumGuard} from "./guard/must-have-unlocked-museum.guard";
import {MustHaveUnlockedTraderGuard} from "./guard/must-have-unlocked-trader.guard";
import {MustHaveUnlockedMailboxGuard} from "./guard/must-have-unlocked-mailbox.guard";
import {MustHaveUnlockedDragonGuard} from "./guard/must-have-unlocked-dragon.guard";
import { MustHaveUnlockedMarketGuard } from "./guard/must-have-unlocked-market.guard";
import { MustHaveUnlockedHattierGuard } from "./guard/must-have-unlocked-hattier.guard";
import { MustHaveUnlockedFieldGuideGuard } from "./guard/must-have-unlocked-field-guide.guard";
import { MustHaveUnlockedZoologistGuard } from "./guard/must-have-unlocked-zoologist.guard";
import { MustHaveUnlockedLibraryGuard } from "./guard/must-have-unlocked-library.guard";
import { MustHaveUnlockedInfinityVaultGuard } from "./guard/must-have-unlocked-infinity-vault.guard";

const routes: Routes = [
  { path: '', component: PortalComponent, canActivate: [ AnyGuard ] },
  { path: 'register', component: RegisterComponent, canActivate: [ MustBeLoggedOutGuard ] },
  { path: 'stats', component: StatsComponent, canActivate: [ MustBeLoggedInGuard ] },
  { path: 'resetPassphrase/:code', component: ResetPassphraseComponent, canActivate: [ AnyGuard ] },

  // modules
  { path: 'settings', loadChildren: () => import('./module/settings/settings.module').then(m => m.SettingsModule) },
  { path: 'florist', loadChildren: () => import('./module/florist/florist.module').then(m => m.FloristModule), canActivate: [ MustHaveUnlockedFloristGuard ] },
  { path: 'museum', loadChildren: () => import('./module/museum/museum.module').then(m => m.MuseumModule), canActivate: [ MustHaveUnlockedMuseumGuard ] },
  { path: 'fireplace', loadChildren: () => import('./module/fireplace/fireplace.module').then(m => m.FireplaceModule), canActivate: [ MustHaveUnlockedFireplaceGuard ] },
  { path: 'dragon', loadChildren: () => import('./module/dragon/dragon.module').then(m => m.DragonModule), canActivate: [ MustHaveUnlockedDragonGuard ] },
  { path: 'beehive', loadChildren: () => import('./module/beehive/beehive.module').then(m => m.BeehiveModule), canActivate: [ MustHaveUnlockedBeehiveGuard ] },
  { path: 'poppyopedia', loadChildren: () => import('./module/encyclopedia/encyclopedia.module').then(m => m.EncyclopediaModule), canActivate: [ AnyGuard ] },
  { path: 'halloween', loadChildren: () => import('./module/halloween/halloween.module').then(m => m.HalloweenModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'market', loadChildren: () => import('./module/market/market.module').then(m => m.MarketModule), canActivate: [ MustHaveUnlockedMarketGuard ] },
  { path: 'grocer', loadChildren: () => import('./module/grocer/grocer.module').then(m => m.GrocerModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'hollowEarth', loadChildren: () => import('./module/hollow-earth/hollow-earth.module').then(m => m.HollowEarthModule), canActivate: [ MustHaveUnlockedHollowEarthGuard ] },
  { path: 'greenhouse', loadChildren: () => import('./module/greenhouse/greenhouse.module').then(m => m.GreenhouseModule), canActivate: [ MustHaveUnlockedGreenhouseGuard ] },
  { path: 'trader', loadChildren: () => import('./module/trader/trader.module').then(m => m.TraderModule), canActivate: [ MustHaveUnlockedTraderGuard ] },
  { path: 'home', loadChildren: () => import('./module/home/home.module').then(m => m.HomeModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'cookingBuddy', loadChildren: () => import('./module/cooking-buddy/cooking-buddy.module').then(m => m.CookingBuddyModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'mailbox', loadChildren: () => import('./module/mailbox/mailbox.module').then(m => m.MailboxModule), canActivate: [ MustHaveUnlockedMailboxGuard ] },
  { path: 'basement', loadChildren: () => import('./module/basement/basement.module').then(m => m.BasementModule), canActivate: [ MustHaveUnlockedBasementGuard ] },
  { path: 'infinityVault', loadChildren: () => import('./module/vault/vault.module').then(m => m.VaultModule), canActivate: [ MustHaveUnlockedInfinityVaultGuard ] },
  { path: 'bookstore', loadChildren: () => import('./module/bookstore/bookstore.module').then(m => m.BookstoreModule), canActivate: [ MustHaveUnlockedBookstoreGuard ] },
  { path: 'petShelter', loadChildren: () => import('./module/pet-shelter/pet-shelter.module').then(m => m.PetShelterModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'park', loadChildren: () => import('./module/park/park.module').then(m => m.ParkModule), canActivate: [ MustHaveUnlockedParkGuard ] },
  { path: 'journal', loadChildren: () => import('./module/pet-logs/pet-logs.module').then(m => m.PetLogsModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'plaza', loadChildren: () => import('./module/plaza/plaza.module').then(m => m.PlazaModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'painter', loadChildren: () => import('./module/styler/styler.module').then(m => m.StylerModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'hattier', loadChildren: () => import('./module/hattier/hattier.module').then(m => m.HattierModule), canActivate: [ MustHaveUnlockedHattierGuard ] },
  { path: 'fieldGuide', loadChildren: () => import('./module/field-guide/field-guide.module').then(m => m.FieldGuideModule), canActivate: [ MustHaveUnlockedFieldGuideGuard ] },
  { path: 'survey', loadChildren: () => import('./module/survey/survey.module').then(m => m.SurveyModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'news', loadChildren: () => import('./module/news/news.module').then(m => m.NewsModule), canActivate: [ AnyGuard ] },
  { path: 'starKindred', loadChildren: () => import('./module/star-kindred/star-kindred.module').then(m => m.StarKindredModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'achievements', loadChildren: () => import('./module/badges/badges.module').then(m => m.BadgesModule), canActivate: [ MustBeLoggedInGuard ] },
  { path: 'zoologist', loadChildren: () => import('./module/zoologist/zoologist.module').then(m => m.ZoologistModule), canActivate: [ MustHaveUnlockedZoologistGuard ] },
  { path: 'library', loadChildren: () => import('./module/library/library.module').then(m => m.LibraryModule), canActivate: [ MustHaveUnlockedLibraryGuard ] },

  // 404
  { path: '**', component: NotFoundComponent },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule { }
