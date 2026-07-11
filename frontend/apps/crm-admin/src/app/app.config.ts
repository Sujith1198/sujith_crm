import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter, withComponentInputBinding, withViewTransitions } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { routes } from './app.routes';
import { jwtInterceptor } from '@crm/interceptors';

/**
 * Application-level providers configuration.
 * - Zone-based change detection (coalesced for performance)
 * - HTTP client with JWT interceptor
 * - Angular Router with view transitions
 * - Angular Material animations (async loaded)
 */
export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(
      routes,
      withComponentInputBinding(),
      withViewTransitions(),
    ),
    provideHttpClient(
      withInterceptors([jwtInterceptor]),
    ),
    provideAnimationsAsync(),
  ],
};
