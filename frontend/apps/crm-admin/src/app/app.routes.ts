import { Routes } from '@angular/router';
import { authGuard, adminGuard, guestGuard } from '@crm/guards';

/**
 * Application Routes
 * - Auth routes (login/forgot) guarded by guestGuard
 * - All app routes require authGuard
 * - Admin-only routes additionally require adminGuard
 */
export const routes: Routes = [
  // Default redirect
  { path: '', redirectTo: '/dashboard', pathMatch: 'full' },

  // ── Auth (public) ──────────────────────────────────
  {
    path: 'auth',
    canActivate: [guestGuard],
    loadChildren: () =>
      import('./pages/auth/auth.routes').then(m => m.authRoutes),
  },

  // ── Main Shell (authenticated) ─────────────────────
  {
    path: '',
    canActivate: [authGuard],
    loadComponent: () =>
      import('./layout/shell/shell.component').then(m => m.ShellComponent),
    children: [
      // Dashboard
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./pages/dashboard/dashboard.component').then(m => m.DashboardComponent),
        title: 'Dashboard — CRM Social Media',
      },

      // Users (Admin only)
      {
        path: 'users',
        canActivate: [adminGuard],
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./pages/users/users-list/users-list.component').then(m => m.UsersListComponent),
            title: 'Users — CRM Social Media',
          },
          {
            path: 'create',
            loadComponent: () =>
              import('./pages/users/user-form/user-form.component').then(m => m.UserFormComponent),
            title: 'Create User',
          },
          {
            path: ':id/edit',
            loadComponent: () =>
              import('./pages/users/user-form/user-form.component').then(m => m.UserFormComponent),
            title: 'Edit User',
          },
        ],
      },

      // Posts
      {
        path: 'posts',
        children: [
          {
            path: '',
            loadComponent: () =>
              import('./pages/posts/posts-list/posts-list.component').then(m => m.PostsListComponent),
            title: 'Posts — CRM Social Media',
          },
          {
            path: 'create',
            loadComponent: () =>
              import('./pages/posts/post-form/post-form.component').then(m => m.PostFormComponent),
            title: 'Create Post',
          },
          {
            path: ':id/edit',
            loadComponent: () =>
              import('./pages/posts/post-form/post-form.component').then(m => m.PostFormComponent),
            title: 'Edit Post',
          },
        ],
      },

      // Social Accounts
      {
        path: 'social',
        loadComponent: () =>
          import('./pages/social/social.component').then(m => m.SocialComponent),
        title: 'Social Accounts',
      },

      // Analytics
      {
        path: 'analytics',
        loadComponent: () =>
          import('./pages/analytics/analytics.component').then(m => m.AnalyticsComponent),
        title: 'Analytics — CRM Social Media',
      },

      // Reports
      {
        path: 'reports',
        loadComponent: () =>
          import('./pages/reports/reports.component').then(m => m.ReportsComponent),
        title: 'Reports',
      },

      // Settings (Admin only)
      {
        path: 'settings',
        canActivate: [adminGuard],
        loadComponent: () =>
          import('./pages/settings/settings.component').then(m => m.SettingsComponent),
        title: 'Settings',
      },

      // Profile (self)
      {
        path: 'profile',
        loadComponent: () =>
          import('./pages/profile/profile.component').then(m => m.ProfileComponent),
        title: 'My Profile',
      },
    ],
  },

  // Wildcard
  {
    path: '**',
    redirectTo: '/dashboard',
  },
];
