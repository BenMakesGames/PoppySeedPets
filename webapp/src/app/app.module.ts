/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { PortalComponent } from './page/portal/portal.component';
import { RegisterComponent } from './page/register/register.component';
import { MenuComponent } from './component/menu/menu.component';
import { NavComponent } from './component/nav/nav.component';
import { LoginComponent } from './component/login/login.component';
import {FormsModule} from "@angular/forms";
import { provideHttpClient, withInterceptors, withInterceptorsFromDi } from "@angular/common/http";
import { apiBuildInterceptor } from "./service/api-build.interceptor";
import { apiReachableInterceptor } from "./service/api-reachable.interceptor";
import { LoginChoicesComponent } from './component/login-choices/login-choices.component';
import {NoopAnimationsModule} from "@angular/platform-browser/animations";
import { IconComponent } from './component/icon/icon.component';
import { MessagesComponent } from './component/messages/messages.component';
import { NotFoundComponent } from './page/not-found/not-found.component';
import {ServiceWorkerModule, SwRegistrationOptions} from '@angular/service-worker';
import { environment } from '../environments/environment';
import { AskToRestartDialog } from './dialog/ask-to-restart/ask-to-restart.dialog';
import { EnterPassphraseDialog } from './dialog/enter-passphrase/enter-passphrase.dialog';
import { ResetPassphraseComponent } from './page/reset-passphrase/reset-passphrase.component';
import { RequestPassphraseResetComponent } from './component/request-passphrase-reset/request-passphrase-reset.component';
import {MarkdownModule} from "ngx-markdown";
import { NavMenuDimmerComponent } from './component/nav-menu-dimmer/nav-menu-dimmer.component';
import { WeatherReportComponent } from './component/weather-report/weather-report.component';
import {HotKeysModule} from "./module/hot-keys/hot-keys.module";
import { MatDialogModule } from "@angular/material/dialog";
import { LoadingThrobberComponent } from "./module/shared/component/loading-throbber/loading-throbber.component";
import { SvgIconComponent } from "./module/shared/component/svg-icon/svg-icon.component";
import { DateAndTimeComponent } from "./module/shared/component/date-and-time/date-and-time.component";
import { NpcDialogComponent } from "./module/shared/component/npc-dialog/npc-dialog.component";
import { ChangeThemeComponent } from "./module/shared/component/change-theme/change-theme.component";
import { ChooseTwoColorsComponent } from "./module/shared/component/choose-two-colors/choose-two-colors.component";
import { ImageComponent } from "./module/shared/component/image/image.component";
import { ListDesignGoalsComponent } from "./module/shared/component/list-design-goals/list-design-goals.component";
import { RecyclingPointsComponent } from "./module/shared/component/recycling-points/recycling-points.component";
import { MoneysComponent } from "./module/shared/component/moneys/moneys.component";
import { CurrentWeatherComponent } from "./module/shared/component/current-weather/current-weather.component";
import { HelpLinkComponent } from "./module/shared/component/help-link/help-link.component";
import { PspTimeComponent } from "./module/shared/component/psp-time/psp-time.component";
import { CurrentMoonPhaseComponent } from "./module/shared/component/current-moon-phase/current-moon-phase.component";

export{}
declare global {
  interface Array<T>  {
    listNice(separator?: string, lastSeparator?: string): string;
  }
}

Array.prototype.listNice = function(separator: string = ', ', lastSeparator = ', and ')
{
  let niceList = '';

  for(let i = 0; i < this.length; i++)
  {
    if(i > 0)
    {
      if(i === this.length - 1)
        niceList += lastSeparator;
      else
        niceList += separator;
    }

    niceList += this[i];
  }

  return niceList;
};

@NgModule({
  declarations: [
    AppComponent,
    PortalComponent,
    RegisterComponent,
    MenuComponent,
    NavComponent,
    LoginComponent,
    LoginChoicesComponent,
    IconComponent,
    MessagesComponent,
    NotFoundComponent,
    AskToRestartDialog,
    EnterPassphraseDialog,
    ResetPassphraseComponent,
    RequestPassphraseResetComponent,
    NavMenuDimmerComponent,
    WeatherReportComponent,
  ],
  bootstrap: [AppComponent],
  imports: [ BrowserModule,
    AppRoutingModule,
    FormsModule,
    MatDialogModule,
    NoopAnimationsModule,
    MarkdownModule.forRoot(),
    ServiceWorkerModule.register('ngsw-worker.js', <SwRegistrationOptions>{ enabled: environment.production }),
    HotKeysModule,
    LoadingThrobberComponent,
    SvgIconComponent,
    DateAndTimeComponent,
    NpcDialogComponent,
    ChangeThemeComponent,
    ChooseTwoColorsComponent,
    ImageComponent,
    ListDesignGoalsComponent,
    RecyclingPointsComponent,
    MoneysComponent,
    CurrentWeatherComponent,
    HelpLinkComponent,
    PspTimeComponent,
    CurrentMoonPhaseComponent,
  ],
  providers: [
    provideHttpClient(withInterceptorsFromDi(), withInterceptors([ apiBuildInterceptor, apiReachableInterceptor ])),
  ]
})
export class AppModule { }
