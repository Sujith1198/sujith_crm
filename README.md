# CRM Social Media Management System

A production-ready **CRM Social Media Management System** built with Angular 20 (NX Monorepo) + Laravel 12.

---

## Technology Stack

| Layer       | Technology |
|-------------|------------|
| Frontend    | Angular 20, NX Monorepo, Bootstrap 5, Angular Material, Chart.js |
| Backend     | Laravel 12, PHP 8.3, JWT Auth, Repository + Service Pattern |
| Database    | MySQL 8.0 |
| Queue/Cache | Redis |
| Scheduler   | Laravel Scheduler (cron) |
| Auth        | JWT (`php-open-source-saver/jwt-auth`) + Spatie RBAC |
| Social APIs | Facebook Graph API v20.0 + Instagram Graph API |
| Exports     | Maatwebsite Excel (xlsx/csv) + DomPDF (pdf) |

---

## Project Structure

```
top_ten_CRM/
├── backend/                  # Laravel 12 API
│   ├── app/
│   │   ├── Console/Kernel.php        # Scheduler
│   │   ├── Exports/                  # Excel/CSV exports
│   │   ├── Http/
│   │   │   ├── Controllers/Api/      # All API controllers
│   │   │   ├── Middleware/           # RoleMiddleware
│   │   │   ├── Requests/             # Form requests
│   │   │   └── Resources/            # API resources
│   │   ├── Jobs/                     # Queue jobs
│   │   ├── Models/                   # Eloquent models
│   │   ├── Providers/                # AppServiceProvider
│   │   ├── Repositories/             # Repository pattern
│   │   └── Services/                 # Business logic layer
│   ├── bootstrap/app.php             # Laravel 12 bootstrap
│   ├── config/                       # cors.php, services.php
│   ├── database/
│   │   ├── migrations/               # 12 migration files
│   │   └── seeders/                  # Roles, Admin user, Settings
│   ├── resources/views/reports/      # PDF Blade templates
│   └── routes/api.php                # All API routes
│
├── frontend/                 # Angular 20 NX Monorepo
│   ├── apps/crm-admin/
│   │   └── src/
│   │       ├── app/
│   │       │   ├── layout/shell/     # Sidebar + Topbar shell
│   │       │   ├── pages/
│   │       │   │   ├── auth/         # Login, Forgot Password
│   │       │   │   ├── dashboard/    # KPIs + Charts
│   │       │   │   ├── posts/        # List + Create/Edit form
│   │       │   │   ├── users/        # List + Create/Edit form
│   │       │   │   ├── analytics/    # Charts + data table
│   │       │   │   ├── reports/      # Export UI
│   │       │   │   ├── social/       # Account management
│   │       │   │   ├── profile/      # Self-profile
│   │       │   │   └── settings/     # Admin settings
│   │       │   └── shared/           # Toast container
│   │       ├── environments/         # API URLs
│   │       ├── index.html
│   │       ├── main.ts
│   │       └── styles.css            # Full design system
│   └── libs/
│       ├── models/src/index.ts       # All TypeScript interfaces
│       ├── services/src/lib/         # HTTP services
│       ├── guards/src/lib/           # Auth, Admin, Guest guards
│       └── interceptors/src/lib/     # JWT interceptor
│
├── Dockerfile                # Multi-stage build
├── docker-compose.yml        # Full stack deployment
└── README.md
```

---

## Quick Start (Local Development)

### Prerequisites
- PHP 8.3, Composer
- Node.js 20+, npm
- MySQL 8.0
- Redis

### 1. Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Copy env
cp .env.example .env

# Generate app key and JWT secret
php artisan key:generate
php artisan jwt:secret

# Configure .env:
#   DB_DATABASE, DB_USERNAME, DB_PASSWORD
#   FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_REDIRECT_URI
#   FRONTEND_URL=http://localhost:4200

# Run migrations and seed
php artisan migrate --seed

# Start Laravel server
php artisan serve --port=8000

# (Separate terminal) Start queue worker
php artisan queue:work

# (Separate terminal) Start scheduler
php artisan schedule:work
```

### 2. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start Angular dev server
npm start
# → http://localhost:4200
```

### 3. Default Credentials
| Role  | Email                   | Password      |
|-------|-------------------------|---------------|
| Admin | admin@crm-social.com    | Admin@12345   |
| User  | user@crm-social.com     | User@12345    |

> ⚠️ **Change these passwords immediately after first login!**

---

## Docker Deployment

```bash
# Build and start all services
docker compose up -d --build

# Run migrations inside container
docker exec crm_app php artisan migrate --seed

# View logs
docker compose logs -f app
```

---

## Facebook & Instagram Setup

1. Create a **Facebook App** at [developers.facebook.com](https://developers.facebook.com)
2. Enable **Pages API** and **Instagram Graph API** permissions
3. Configure OAuth redirect URI: `https://your-domain.com/api/facebook/callback`
4. Add to `.env`:
   ```
   FACEBOOK_APP_ID=your_app_id
   FACEBOOK_APP_SECRET=your_app_secret
   FACEBOOK_REDIRECT_URI=https://your-domain.com/api/facebook/callback
   ```

Required permissions:
- `pages_manage_posts`
- `pages_read_engagement`
- `pages_show_list`
- `instagram_basic`
- `instagram_content_publish`
- `instagram_manage_insights`
- `read_insights`

---

## API Reference

| Method | Endpoint                        | Auth  | Role  | Description                    |
|--------|---------------------------------|-------|-------|--------------------------------|
| POST   | `/api/login`                    | ❌    | any   | JWT login                      |
| POST   | `/api/auth/logout`              | ✅    | any   | Logout                         |
| POST   | `/api/auth/refresh`             | ✅    | any   | Refresh JWT token              |
| GET    | `/api/auth/me`                  | ✅    | any   | Current user profile           |
| GET    | `/api/dashboard`                | ✅    | any   | Dashboard stats                |
| GET    | `/api/users`                    | ✅    | admin | List users                     |
| POST   | `/api/users`                    | ✅    | admin | Create user                    |
| PUT    | `/api/users/{id}`               | ✅    | admin | Update user                    |
| DELETE | `/api/users/{id}`               | ✅    | admin | Delete user                    |
| PATCH  | `/api/users/{id}/toggle-status` | ✅    | admin | Enable/disable user            |
| GET    | `/api/posts`                    | ✅    | any   | List posts (scoped by role)    |
| POST   | `/api/posts`                    | ✅    | any   | Create post (multipart)        |
| PUT    | `/api/posts/{id}`               | ✅    | any   | Update post                    |
| DELETE | `/api/posts/{id}`               | ✅    | any   | Delete post                    |
| GET    | `/api/facebook/redirect`        | ✅    | any   | Get OAuth URL                  |
| GET    | `/api/facebook/callback`        | ✅    | any   | Handle OAuth callback          |
| POST   | `/api/instagram/connect`        | ✅    | any   | Link Instagram via FB page     |
| GET    | `/api/analytics`                | ✅    | any   | Analytics data                 |
| GET    | `/api/analytics/summary`        | ✅    | any   | KPI summary                    |
| GET    | `/api/reports/posts`            | ✅    | any   | Download posts report          |
| GET    | `/api/reports/analytics`        | ✅    | any   | Download analytics report      |

---

## Scheduler Tasks

| Task                       | Frequency  | Description                              |
|----------------------------|------------|------------------------------------------|
| Publish scheduled posts    | Every min  | Dispatches `PublishScheduledPostJob`     |
| Sync Facebook insights     | Every hour | Dispatches `SyncFacebookInsightsJob`     |
| Sync Instagram insights    | Every hour | Dispatches `SyncInstagramInsightsJob`    |
| Refresh expiring tokens    | Daily 2am  | Extends tokens expiring within 7 days    |
| Cleanup stuck posts        | Every hour | Marks stuck 'publishing' posts as failed |
| Flush failed jobs          | Weekly     | Clears old failed queue jobs             |

---

## Security Features

- JWT Authentication with auto-refresh on 401
- Spatie Role-Based Access Control (admin/user)
- Rate limiting: 10 login attempts/minute
- CORS restricted to frontend domain
- Encrypted social media tokens at rest
- Input validation via Laravel Form Requests
- File upload type/size validation
- Production error messages sanitized

---

## License

MIT License — Free to use and modify for personal or commercial projects.
