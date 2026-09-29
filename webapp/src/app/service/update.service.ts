/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { ApplicationRef, inject, Injectable } from '@angular/core';
import { SwUpdate } from "@angular/service-worker";
import {AskToRestartDialog} from "../dialog/ask-to-restart/ask-to-restart.dialog";
import {concat, fromEvent, interval, merge, Subject, timer} from 'rxjs';
import {filter, first, switchMap, takeWhile} from 'rxjs/operators';
import { MatDialog } from "@angular/material/dialog";

/**
 * Keeps players on the latest webapp build.
 *
 * The API sends an X-PSP-Build header which changes on every deploy. When it changes, we immediately ask the
 * service worker to look for a new webapp build (and retry for a few minutes, in case the webapp's files finish
 * deploying after the API's). Once a new build is ready, the player is made to reload.
 *
 * As a fallback, we also check when the tab regains focus, and periodically.
 */
@Injectable({
  providedIn: 'root'
})
export class UpdateService
{
  private appRef = inject(ApplicationRef);
  private updates = inject(SwUpdate);
  private matDialog = inject(MatDialog);

  private static readonly FallbackCheckInterval = 60 * 60 * 1000;
  private static readonly MinimumTimeBetweenFocusChecks = 5 * 60 * 1000;
  private static readonly ApiBuildChangedCheckMinutes = [ 0, 1, 2, 5, 10 ];

  private lastSeenApiBuild: string|null = null;
  private apiBuildChanged = new Subject<void>();
  private lastCheck = 0;
  private updateIsReady = false;

  constructor()
  {
    if(!this.updates.isEnabled)
      return;

    // the service worker also checks for updates on its own (ex: on page load); this catches those, too
    this.updates.versionUpdates
      .pipe(filter(e => e.type === 'VERSION_READY'))
      .subscribe(() => this.forceRestart());

    this.updates.unrecoverable.subscribe(() => document.location.reload());

    const appIsStable = this.appRef.isStable.pipe(first((isStable) => isStable === true));

    concat(appIsStable, interval(UpdateService.FallbackCheckInterval))
      .subscribe(() => this.checkForUpdate());

    fromEvent(document, 'visibilitychange')
      .pipe(filter(() => document.visibilityState === 'visible' && Date.now() - this.lastCheck >= UpdateService.MinimumTimeBetweenFocusChecks))
      .subscribe(() => this.checkForUpdate());

    // check now, then a few more times, in case the webapp finishes deploying after the API
    this.apiBuildChanged
      .pipe(switchMap(() => merge(...UpdateService.ApiBuildChangedCheckMinutes.map(m => timer(m * 60 * 1000))).pipe(
        takeWhile(() => !this.updateIsReady),
      )))
      .subscribe(() => this.checkForUpdate());
  }

  /**
   * Called with the X-PSP-Build header of every API response.
   */
  reportApiBuild(build: string|null)
  {
    if(!build || build === this.lastSeenApiBuild)
      return;

    const isFirstSighting = this.lastSeenApiBuild === null;

    this.lastSeenApiBuild = build;

    // the first sighting tells us nothing; the startup check already covers a webapp that loaded stale
    if(!isFirstSighting)
      this.apiBuildChanged.next();
  }

  private async checkForUpdate()
  {
    if(this.updateIsReady)
      return;

    this.lastCheck = Date.now();

    try
    {
      // resolves true once a new version has been downloaded; VERSION_READY is also emitted then
      if(await this.updates.checkForUpdate())
        this.forceRestart();
    }
    catch (err)
    {
      console.error('Failed to check for updates:', err);
    }
  }

  private forceRestart()
  {
    if(this.updateIsReady)
      return;

    this.updateIsReady = true;

    AskToRestartDialog.open(this.matDialog);
  }
}
