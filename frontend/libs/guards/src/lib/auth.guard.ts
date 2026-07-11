import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '@crm/services';

/**
 * authGuard — Protects routes that require authentication.
 * Redirects to /auth/login if no token is found.
 */
export const authGuard: CanActivateFn = (route, state) => {
  const auth   = inject(AuthService);
  const router = inject(Router);

  if (auth.isLoggedIn()) {
    return true;
  }

  router.navigate(['/auth/login'], { queryParams: { returnUrl: state.url } });
  return false;
};

/**
 * adminGuard — Protects routes that require the 'admin' role.
 * Redirects to /dashboard if user doesn't have admin role.
 */
export const adminGuard: CanActivateFn = () => {
  const auth   = inject(AuthService);
  const router = inject(Router);

  if (auth.isLoggedIn() && auth.isAdmin()) {
    return true;
  }

  router.navigate(['/dashboard']);
  return false;
};

/**
 * guestGuard — Prevents logged-in users from accessing login/register pages.
 */
export const guestGuard: CanActivateFn = () => {
  const auth   = inject(AuthService);
  const router = inject(Router);

  if (!auth.isLoggedIn()) {
    return true;
  }

  router.navigate(['/dashboard']);
  return false;
};
