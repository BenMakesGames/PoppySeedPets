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
import { BehaviorSubject, filter, fromEvent, Subscription, timer } from "rxjs";
import { WeatherDataModel } from "../../../model/weather.model";
import { ApiService } from "./api.service";

const RetryDelayMs = 5000;
const MaxAttempts = 3;

@Injectable({
  providedIn: 'root'
})
export class WeatherService {
  weather = new BehaviorSubject<WeatherDataModel[]|null>(null);

  #weatherAjax = Subscription.EMPTY;
  #nextFetch = Subscription.EMPTY;
  #nextFetchAt = Infinity;
  #failures = 0;

  constructor(private readonly apiService: ApiService) {
    // timers don't tick while the computer sleeps, so a fetch scheduled for midnight can fire hours late;
    // catch up when the player comes back
    fromEvent(document, 'visibilitychange')
      .pipe(filter(() => document.visibilityState === 'visible' && Date.now() >= this.#nextFetchAt))
      .subscribe(() => this.#fetchWeather());

    this.#fetchWeather();
  }

  /**
   * Called by apiReachableInterceptor for every successful API response.
   */
  reportApiReachable(url: string)
  {
    // any other successful API call means the API is reachable again; if we gave up, try again
    if(this.#failures >= MaxAttempts && !url.endsWith('/weather'))
    {
      this.#failures = 0;
      this.#fetchWeather();
    }
  }

  #fetchWeather()
  {
    this.#nextFetch.unsubscribe();
    this.#nextFetchAt = Infinity;

    this.#weatherAjax.unsubscribe();
    this.#weatherAjax = this.apiService.get<{ forecast: WeatherDataModel[], secondsUntilNextDay: number }>('/weather').subscribe({
      next: r => {
        if(r.data?.forecast?.length > 0)
        {
          this.#failures = 0;
          this.weather.next(r.data.forecast);
          this.#scheduleFetch(r.data.secondsUntilNextDay * 1000);
        }
        else
          this.#recordFailure();
      },
      error: () => {
        this.#recordFailure();
      }
    });
  }

  #recordFailure()
  {
    this.#failures++;

    if(this.#failures < MaxAttempts)
      this.#scheduleFetch(RetryDelayMs);
  }

  #scheduleFetch(delayMs: number)
  {
    this.#nextFetchAt = Date.now() + delayMs;
    this.#nextFetch = timer(delayMs).subscribe(() => this.#fetchWeather());
  }
}
