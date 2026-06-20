/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import {Injectable} from '@angular/core';
import { BehaviorSubject, Subscription, timer } from "rxjs";
import { WeatherDataModel } from "../../../model/weather.model";
import { ApiService } from "./api.service";

@Injectable({
  providedIn: 'root'
})
export class WeatherService {
  weather = new BehaviorSubject<WeatherDataModel[]|null>(null);

  #weatherAjax = Subscription.EMPTY;
  #lastUpdated: string|null = null;

  constructor(private readonly apiService: ApiService) {
    // every 1 second, check if it's a new (UTC) day; if so, update the weather
    timer(0, 1000).subscribe({
      next: () => {
        if(this.#weatherAjax.closed)
        {
          const today = new Date().toISOString().substring(0, 10);

          if(this.weather.getValue() === null || this.#lastUpdated === null || this.#lastUpdated !== today)
          {
            this.#lastUpdated = today;
            this.updateWeather();
          }
        }
      }
    });

  }

  updateWeather()
  {
    this.#weatherAjax.unsubscribe();
    this.#weatherAjax = this.apiService.get<{ forecast: WeatherDataModel[] }>('/weather').subscribe({
      next: r => {
        if(r.data?.forecast?.length > 0)
          this.weather.next(r.data.forecast);
        else
          this.#lastUpdated = null;
      },
      error: () => {
        this.#lastUpdated = null;
      }
    });
  }
}
