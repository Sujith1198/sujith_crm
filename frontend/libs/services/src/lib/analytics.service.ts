import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { ApiResponse, AnalyticsData, AnalyticsSummary, AnalyticsFilters, DashboardStats } from '@crm/models';

/** AnalyticsService — Fetches aggregated metrics and dashboard data */
@Injectable({ providedIn: 'root' })
export class AnalyticsService {
  private readonly baseUrl = `${environment.apiUrl}/analytics`;

  constructor(private http: HttpClient) {}

  getAggregated(filters: AnalyticsFilters = {}): Observable<ApiResponse<AnalyticsData[]>> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([k, v]) => {
      if (v) params = params.set(k, String(v));
    });
    return this.http.get<ApiResponse<AnalyticsData[]>>(this.baseUrl, { params });
  }

  getSummary(): Observable<ApiResponse<AnalyticsSummary>> {
    return this.http.get<ApiResponse<AnalyticsSummary>>(`${this.baseUrl}/summary`);
  }

  getTopPosts(limit = 10): Observable<ApiResponse<unknown[]>> {
    return this.http.get<ApiResponse<unknown[]>>(`${this.baseUrl}/top-posts`, {
      params: new HttpParams().set('limit', String(limit)),
    });
  }

  getDashboard(): Observable<ApiResponse<DashboardStats>> {
    return this.http.get<ApiResponse<DashboardStats>>(`${environment.apiUrl}/dashboard`);
  }
}
