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
import { HttpErrorResponse, HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { tap } from 'rxjs/operators';
import { UpdateService } from './update.service';

/**
 * Hands the API's X-PSP-Build header to UpdateService, so it can tell when the API has been redeployed.
 */
export const apiBuildInterceptor: HttpInterceptorFn = (req, next) => {
  const updateService = inject(UpdateService);

  return next(req).pipe(
    tap({
      next: event => {
        if(event instanceof HttpResponse)
          updateService.reportApiBuild(event.headers.get('X-PSP-Build'));
      },
      error: error => {
        if(error instanceof HttpErrorResponse)
          updateService.reportApiBuild(error.headers.get('X-PSP-Build'));
      }
    })
  );
};
