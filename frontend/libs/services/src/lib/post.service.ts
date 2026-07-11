import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import {
  ApiResponse,
  PaginatedResponse,
  Post,
  CreatePostPayload,
  UpdateUserPayload,
  PostFilters,
} from '@crm/models';

/**
 * PostService
 * CRUD operations for posts, including media upload and scheduling.
 */
@Injectable({ providedIn: 'root' })
export class PostService {
  private readonly baseUrl = `${environment.apiUrl}/posts`;

  constructor(private http: HttpClient) {}

  list(filters: PostFilters = {}): Observable<PaginatedResponse<Post>> {
    let params = new HttpParams();
    Object.entries(filters).forEach(([key, val]) => {
      if (val !== undefined && val !== null && val !== '') {
        params = params.set(key, String(val));
      }
    });
    return this.http.get<PaginatedResponse<Post>>(this.baseUrl, { params });
  }

  getById(id: number): Observable<ApiResponse<Post>> {
    return this.http.get<ApiResponse<Post>>(`${this.baseUrl}/${id}`);
  }

  create(data: CreatePostPayload, mediaFiles: File[] = []): Observable<ApiResponse<Post>> {
    const formData = this.buildFormData(data, mediaFiles);
    return this.http.post<ApiResponse<Post>>(this.baseUrl, formData);
  }

  update(id: number, data: Partial<CreatePostPayload>, mediaFiles: File[] = []): Observable<ApiResponse<Post>> {
    const formData = this.buildFormData(data, mediaFiles);
    formData.append('_method', 'PUT'); // Laravel method spoofing for multipart
    return this.http.post<ApiResponse<Post>>(`${this.baseUrl}/${id}`, formData);
  }

  delete(id: number): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(`${this.baseUrl}/${id}`);
  }

  deleteMedia(mediaId: number): Observable<ApiResponse> {
    return this.http.delete<ApiResponse>(`${this.baseUrl}/media/${mediaId}`);
  }

  getStats(): Observable<ApiResponse<Record<string, number>>> {
    return this.http.get<ApiResponse<Record<string, number>>>(`${this.baseUrl}/stats`);
  }

  private buildFormData(data: any, files: File[]): FormData {
    const form = new FormData();

    const appendRecursive = (obj: any, prefix = ''): void => {
      Object.entries(obj).forEach(([key, val]) => {
        const fieldKey = prefix ? `${prefix}[${key}]` : key;
        if (val !== null && val !== undefined) {
          if (typeof val === 'object' && !Array.isArray(val)) {
            appendRecursive(val, fieldKey);
          } else if (Array.isArray(val)) {
            val.forEach((v, i) => form.append(`${fieldKey}[${i}]`, String(v)));
          } else {
            form.append(fieldKey, String(val));
          }
        }
      });
    };

    appendRecursive(data);
    files.forEach(file => form.append('media[]', file, file.name));

    return form;
  }
}
