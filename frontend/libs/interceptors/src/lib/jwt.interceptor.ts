import {
  HttpErrorResponse,
  HttpInterceptorFn,
  HttpRequest,
  HttpHandlerFn,
  HttpEvent,
} from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { Observable, throwError, BehaviorSubject } from 'rxjs';
import { catchError, filter, switchMap, take } from 'rxjs/operators';
import { AuthService } from '@crm/services';
import { ToastService } from '@crm/services';

let isRefreshing = false;
const refreshTokenSubject = new BehaviorSubject<string | null>(null);

/**
 * JWT Interceptor (Functional)
 * 1. Attaches Bearer token to every outgoing request.
 * 2. On 401, attempts a single token refresh, replays the failed request.
 * 3. If refresh fails, clears storage and redirects to login.
 */
export const jwtInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn
): Observable<HttpEvent<unknown>> => {
  const authService = inject(AuthService);
  const router      = inject(Router);
  const toast       = inject(ToastService);

  const token = authService.getToken();
  const authReq = token ? addToken(req, token) : req;

  return next(authReq).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !authReq.url.includes('/login')) {
        return handle401Error(authReq, next, authService, router, toast);
      }

      // Show error toast for server errors
      if (error.status >= 500) {
        toast.error('Server Error', error.error?.message ?? 'An unexpected server error occurred.');
      }

      return throwError(() => error);
    })
  );
};

function addToken(request: HttpRequest<unknown>, token: string): HttpRequest<unknown> {
  return request.clone({
    setHeaders: { Authorization: `Bearer ${token}` },
  });
}

function handle401Error(
  request: HttpRequest<unknown>,
  next: HttpHandlerFn,
  authService: AuthService,
  router: Router,
  toast: ToastService,
): Observable<HttpEvent<unknown>> {
  if (isRefreshing) {
    return refreshTokenSubject.pipe(
      filter(token => token !== null),
      take(1),
      switchMap(token => next(addToken(request, token!))),
    );
  }

  isRefreshing = true;
  refreshTokenSubject.next(null);

  return authService.refresh().pipe(
    switchMap(response => {
      isRefreshing = false;
      const newToken = response.data.token;
      authService.saveToken(newToken);
      refreshTokenSubject.next(newToken);
      return next(addToken(request, newToken));
    }),
    catchError(err => {
      isRefreshing = false;
      authService.removeToken();
      toast.error('Session Expired', 'Please log in again.');
      router.navigate(['/auth/login']);
      return throwError(() => err);
    })
  );
}
