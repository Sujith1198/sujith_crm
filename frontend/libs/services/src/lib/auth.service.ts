import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import {
  AuthResponse,
  LoginRequest,
  RefreshResponse,
  User,
  ApiResponse,
} from '@crm/models';

/**
 * AuthService
 * Handles JWT authentication: login, logout, refresh, profile.
 * Stores the JWT token in localStorage.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly TOKEN_KEY = 'crm_access_token';
  private readonly USER_KEY  = 'crm_user';

  constructor(private http: HttpClient) {}

  // ──────────────────────────────────────────────
  // API Calls
  // ──────────────────────────────────────────────

  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${environment.apiUrl}/login`, credentials);
  }

  logout(): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${environment.apiUrl}/auth/logout`, {});
  }

  refresh(): Observable<RefreshResponse> {
    return this.http.post<RefreshResponse>(`${environment.apiUrl}/auth/refresh`, {});
  }

  getProfile(): Observable<ApiResponse<User>> {
    return this.http.get<ApiResponse<User>>(`${environment.apiUrl}/auth/me`);
  }

  // ──────────────────────────────────────────────
  // Token Management
  // ──────────────────────────────────────────────

  saveToken(token: string): void {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  removeToken(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
  }

  saveUser(user: User): void {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  }

  getUser(): User | null {
    const stored = localStorage.getItem(this.USER_KEY);
    return stored ? JSON.parse(stored) : null;
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  isAdmin(): boolean {
    return this.getUser()?.roles?.includes('admin') ?? false;
  }

  hasPermission(permission: string): boolean {
    return this.getUser()?.permissions?.includes(permission) ?? false;
  }
}
