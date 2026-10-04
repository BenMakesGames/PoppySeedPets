/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { inject } from '@angular/core';
import { HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { WeatherService } from '../module/shared/service/weather.service';

/**
 * Tells WeatherService when the API answers, so it can resume fetching if it gave up.
 */
export const apiReachableInterceptor: HttpInterceptorFn = (req, next) => {
  const weatherService = inject(WeatherService);

  return next(req).pipe(
    tap(event => {
      if(event instanceof HttpResponse)
        weatherService.reportApiReachable(req.url);
    })
  );
};
