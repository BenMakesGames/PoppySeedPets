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
import { WeatherDataModel } from "../../../../model/weather.model";
import { UserDataService } from "../../../../service/user-data.service";
import { WeatherService } from "../../../shared/service/weather.service";
import { Subscription } from "rxjs";

@Component({
    selector: 'app-weather-forecast',
    templateUrl: './weather-forecast.component.html',
    styleUrls: ['./weather-forecast.component.scss'],
    standalone: false
})
export class WeatherForecastComponent implements OnInit, OnDestroy {

  forecast: WeatherDataModel[] = [];
  allowanceDayOfWeek: string;
  weatherSubscription = Subscription.EMPTY;
  today: WeatherDataModel|null = null;
  currentDate = new Date();

  constructor(
    private userData: UserDataService, private weatherService: WeatherService
  ) {

  }

  ngOnInit(): void
  {
    this.weatherSubscription = this.weatherService.weather.subscribe({
      next: w => {
        const todayKey = new Date().toISOString().substring(0, 10);

        // `date` is a YYYY-MM-DD string, so lexicographic comparison is chronological:
        // pluck today's entry, and keep only strictly-future days (dropping any stale past-days).
        this.today = w?.find(entry => entry.date === todayKey) || null;
        this.forecast = w?.filter(entry => entry.date > todayKey) || [];
      }
    });

    const user = this.userData.user.getValue();
    const lastAllowanceCollected = new Date(user.lastAllowanceCollected);
    this.allowanceDayOfWeek = [
      'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
    ][lastAllowanceCollected.getUTCDay()];
  }

  ngOnDestroy() {
    this.weatherSubscription.unsubscribe();
  }
}
