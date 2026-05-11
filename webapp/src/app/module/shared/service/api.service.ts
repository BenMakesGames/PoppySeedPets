/*
 * This file is part of the Poppy Seed Pets Webapp.
 *
 * The Poppy Seed Pets Webapp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * The Poppy Seed Pets Webapp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with The Poppy Seed Pets Webapp. If not, see <https://www.gnu.org/licenses/>.
 */
import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpHeaders } from "@angular/common/http";
import {Observable, throwError} from "rxjs";
import {ApiResponseModel} from "../../../model/api-response.model";
import {catchError, map, mergeMap} from "rxjs/operators";
import {UserDataService} from "../../../service/user-data.service";
import {environment} from "../../../../environments/environment";
import {MessagesService} from "../../../service/messages.service";
import * as qs from 'qs/dist/qs.js';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  // Path prefixes served by the C# API. Anything else goes to the PHP API.
  // As endpoints migrate from PHP to C#, add their prefixes here.
  private readonly csharpPathPrefixes = [
    '/globalStats',
  ];

  constructor(
    private http: HttpClient, private userData: UserDataService, private messages: MessagesService
  )
  {
  }

  private resolveBase(path: string): string
  {
    const isCSharp = this.csharpPathPrefixes.some(p => path === p || path.startsWith(p + '/'));
    return isCSharp ? environment.csharpApiEndpoint : environment.apiEndpoint;
  }

  get<T>(path: string, data: any = null): Observable<ApiResponseModel<T>>
  {
    const url = this.resolveBase(path) + path + (data ? '?' + qs.stringify(data) : '');
    let options = this.options();

    return this.http.get<ApiResponseModel<T>>(url, <object>options)
      .pipe(
        catchError(r => this.commonErrorHandler<T>(r, () => {
          return this.http.get<ApiResponseModel<T>>(url, <object>options);
        })),
        map(r => this.commonDataHandler<T>(r)),
      )
    ;
  }

  post<T>(path: string, data: any = {}): Observable<ApiResponseModel<T>>
  {
    const url = this.resolveBase(path) + path;
    return this.http.post<ApiResponseModel<T>>(url, data, this.options())
      .pipe(
        catchError(r => this.commonErrorHandler<T>(r, () => {
          return this.http.post<ApiResponseModel<T>>(url, data, this.options());
        })),
        map(r => this.commonDataHandler<T>(r)),
      )
    ;
  }

  put<T>(path: string, data: any = {}): Observable<ApiResponseModel<T>>
  {
    const url = this.resolveBase(path) + path;
    return this.http.put<ApiResponseModel<T>>(url, data, this.options())
      .pipe(
        catchError(r => this.commonErrorHandler<T>(r, () => {
          return this.http.put<ApiResponseModel<T>>(url, data, this.options());
        })),
        map(r => this.commonDataHandler<T>(r)),
      )
    ;
  }

  patch<T>(path: string, data: any = {}): Observable<ApiResponseModel<T>>
  {
    const url = this.resolveBase(path) + path;
    return this.http.patch<ApiResponseModel<T>>(url, data, this.options())
      .pipe(
        catchError(r => this.commonErrorHandler<T>(r, () => {
          return this.http.patch<ApiResponseModel<T>>(url, data, this.options());
        })),
        map(r => this.commonDataHandler<T>(r)),
      )
    ;
  }

  del<T>(path: string): Observable<ApiResponseModel<T>>
  {
    const url = this.resolveBase(path) + path;
    return this.http.delete<ApiResponseModel<T>>(url, this.options())
      .pipe(
        catchError(r => this.commonErrorHandler<T>(r, () => {
          return this.http.delete<ApiResponseModel<T>>(url, this.options());
        })),
        map(r => this.commonDataHandler<T>(r)),
      )
    ;
  }

  private commonErrorHandler<T>(r: HttpErrorResponse, originalRequest: Function): Observable<ApiResponseModel<T>>
  {
    if(r.status == 470)
    {
      return <any>this.post<T>('/house/runHours').pipe(
        mergeMap(r => {
          if('inventory' in (<any>r).data)
            this.userData.userInventoryChanged.next((<any>r.data).inventory);
          if('pets' in (<any>r).data)
            this.userData.userPetsChanged.next((<any>r.data).pets);

          return originalRequest();
        })
      );
    }
    else
    {
      const data = <ApiResponseModel<T>>r.error;

      if(r.status === 401)
        this.userData.updateUser(null);
      else
        this.processCommonData(data);

      return throwError(() => data);
    }
  }

  private commonDataHandler<T>(r: ApiResponseModel<T>): ApiResponseModel<T>
  {
    this.processCommonData(r);

    return r;
  }

  private processCommonData(data: ApiResponseModel<any>)
  {
    // this must happen before we call updateUser
    // why? updateUser pushes new data to a subject which is being observed; there may be listeners that
    // follow up with an API call, and we need sessionId to be accurate for that
    if(data.hasOwnProperty('user'))
      this.userData.updateUser(data.user);

    if(data.activity)
      this.messages.addMessages(data.activity);

    if(data.errors)
    {
      data.errors.forEach(e => {
        this.messages.addGenericMessage(e);
      });
    }

    if(data.reloadPets)
      this.userData.userPetsChanged.next(null);

    if(data.reloadInventory)
      this.userData.userInventoryChanged.next(null);
  }

  private options(): object
  {
    return {
      headers: new HttpHeaders(),
      withCredentials: true,
      responseType: 'json',
    };
  }
}
