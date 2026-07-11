import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import {
  ApiResponse, PaginatedResponse, User,
  CreateUserPayload, UpdateUserPayload, UserFilters,
} from '@crm/models';

/** UserService — Admin CRUD operations on user accounts */
@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly baseUrl = `${environment.apiUrl}/users`;

  constructor(private http: HttpClient) {}

  list(filters: UserFilters = {}): Observable<PaginatedResponse<User>> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') params = params.set(k, String(v));
    });
    return this.http.get<PaginatedResponse<User>>(this.baseUrl, { params });
  }

  getById(id: number): Observable<ApiResponse<User>> {
    return this.http.get<ApiResponse<User>>(`${this.baseUrl}/${id}`);
  }

  create(data: CreateUserPayload): Observable<ApiResponse<User>> {
    return this.http.post<ApiResponse<User>>(this.baseUrl, data);
  }

  update(id: number, data: UpdateUserPayload): Observable<ApiResponse<User>> {
    return this.http.put<ApiResponse<User>>(`${this.baseUrl}/${id}`, data);
  }

  delete(id: number): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(`${this.baseUrl}/${id}`);
  }

  toggleStatus(id: number): Observable<ApiResponse<User>> {
    return this.http.patch<ApiResponse<User>>(`${this.baseUrl}/${id}/toggle-status`, {});
  }

  resetPassword(id: number, password: string, passwordConfirmation: string): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.baseUrl}/${id}/reset-password`, {
      password,
      password_confirmation: passwordConfirmation,
    });
  }

  updateProfile(data: Partial<UpdateUserPayload>): Observable<ApiResponse<User>> {
    return this.http.put<ApiResponse<User>>(`${this.baseUrl}/profile`, data);
  }
}
