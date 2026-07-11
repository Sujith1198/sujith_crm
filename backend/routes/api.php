<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacebookController;
use App\Http\Controllers\Api\InstagramController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CRM Social Media Management System — API Routes
|--------------------------------------------------------------------------
|
| JWT-authenticated REST API.
| All routes return JSON. Rate limiting applied to auth endpoints.
| RBAC enforced via RoleMiddleware ('role:admin').
|
*/

// ──────────────────────────────────────────────────────────────────────────
// Public Auth Endpoints (no authentication required)
// Rate limited: 10 attempts per minute per IP
// ──────────────────────────────────────────────────────────────────────────
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

// ──────────────────────────────────────────────────────────────────────────
// Authenticated Endpoints (JWT required)
// ──────────────────────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::get('/me',       [AuthController::class, 'me'])->name('auth.me');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ──────────────────────────────────────────────
    // Users (Admin-only management, self-profile for all)
    // ──────────────────────────────────────────────
    Route::prefix('users')->group(function () {
        Route::get('/',                              [UserController::class, 'index'])->middleware('role:admin');
        Route::post('/',                             [UserController::class, 'store'])->middleware('role:admin');
        Route::get('/profile',                       [UserController::class, 'updateProfile']);
        Route::put('/profile',                       [UserController::class, 'updateProfile']);
        Route::get('/{id}',                          [UserController::class, 'show']);
        Route::put('/{id}',                          [UserController::class, 'update'])->middleware('role:admin');
        Route::delete('/{id}',                       [UserController::class, 'destroy'])->middleware('role:admin');
        Route::patch('/{id}/toggle-status',          [UserController::class, 'toggleStatus'])->middleware('role:admin');
        Route::post('/{id}/reset-password',          [UserController::class, 'resetPassword'])->middleware('role:admin');
    });

    // ──────────────────────────────────────────────
    // Posts (scoped to user role inside controller)
    // ──────────────────────────────────────────────
    Route::prefix('posts')->group(function () {
        Route::get('/',                [PostController::class, 'index']);
        Route::post('/',               [PostController::class, 'store']);
        Route::get('/stats',           [PostController::class, 'stats']);
        Route::get('/{id}',            [PostController::class, 'show']);
        Route::put('/{id}',            [PostController::class, 'update']);
        Route::delete('/{id}',         [PostController::class, 'destroy']);
        Route::delete('/media/{id}',   [PostController::class, 'deleteMedia']);
    });

    // ──────────────────────────────────────────────
    // Analytics
    // ──────────────────────────────────────────────
    Route::prefix('analytics')->group(function () {
        Route::get('/',          [AnalyticsController::class, 'index']);
        Route::get('/summary',   [AnalyticsController::class, 'summary']);
        Route::get('/top-posts', [AnalyticsController::class, 'topPosts']);
    });

    // ──────────────────────────────────────────────
    // Reports (export endpoints return file downloads)
    // ──────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('/posts',     [ReportController::class, 'exportPosts']);
        Route::get('/analytics', [ReportController::class, 'exportAnalytics']);
    });

    // ──────────────────────────────────────────────
    // Facebook Integration
    // ──────────────────────────────────────────────
    Route::prefix('facebook')->group(function () {
        Route::get('/redirect',      [FacebookController::class, 'redirectUrl']);
        Route::get('/callback',      [FacebookController::class, 'callback']);
        Route::post('/connect',      [FacebookController::class, 'callback']);
        Route::get('/accounts',      [FacebookController::class, 'accounts']);
        Route::delete('/{id}',       [FacebookController::class, 'disconnect']);
        Route::post('/{id}/reconnect', [FacebookController::class, 'reconnect']);
    });

    // ──────────────────────────────────────────────
    // Instagram Integration
    // ──────────────────────────────────────────────
    Route::prefix('instagram')->group(function () {
        Route::post('/connect',  [InstagramController::class, 'connect']);
        Route::get('/accounts',  [InstagramController::class, 'accounts']);
        Route::delete('/{id}',   [InstagramController::class, 'disconnect']);
    });
});
