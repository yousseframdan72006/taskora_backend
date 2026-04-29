<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

// OTP endpoints: max 3 requests per minute per IP to prevent abuse
RateLimiter::for('otp', function (Request $request) {
    return Limit::perMinute(3)->by($request->ip());
});

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Device Token Management (no workspace required)
    Route::prefix('devices')->group(function () {
        Route::post('/token', [DeviceTokenController::class, 'register']);
        Route::delete('/token', [DeviceTokenController::class, 'remove']);
    });

    // Workspace & Invites Management (Hybrid User operations)
    Route::prefix('workspaces')->group(function () {
        Route::post('/', [WorkspaceController::class, 'store']);
    });

    Route::prefix('invites')->group(function () {
        Route::get('/pending', [InviteController::class, 'pending']);
        Route::post('/{id}/accept', [InviteController::class, 'accept']);
        Route::post('/{id}/decline', [InviteController::class, 'decline']);
    });

    // Multi-tenant core operations (Requires active workspace)
    Route::middleware([\App\Http\Middleware\EnsureWorkspace::class])->group(function () {

        Route::prefix('dashboard')->group(function () {
            Route::get('/task-overview', [\App\Http\Controllers\DashboardController::class, 'taskOverview']);
        });

        Route::prefix('workspaces/employees')->group(function () {
            Route::get('/task-stats', [\App\Http\Controllers\AnalyticsController::class, 'employeeStats']);
        });

        Route::get('/users/{id}/task-stats', [\App\Http\Controllers\AnalyticsController::class, 'userStats']);

        Route::prefix('workspaces')->group(function () {
            Route::get('/details', [WorkspaceController::class, 'show']);
            Route::put('/update', [WorkspaceController::class, 'update']);
            Route::get('/dashboard', [WorkspaceController::class, 'stats']);
            Route::get('/members', [WorkspaceController::class, 'members']);
            Route::get('/activities', [WorkspaceController::class, 'activities']);
            Route::delete('/members/{user}', [WorkspaceController::class, 'removeMember']);
            Route::put('/members/{user}/role', [WorkspaceController::class, 'updateRole']);
            Route::post('/leave', [WorkspaceController::class, 'leave']);
        });

        Route::prefix('invites')->group(function () {
            Route::post('/', [InviteController::class, 'store']); // Equivalent to generate invite
        });

        Route::prefix('projects')->group(function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::get('/{project}', [ProjectController::class, 'show']);
            Route::put('/{project}', [ProjectController::class, 'update']);
            Route::delete('/{project}', [ProjectController::class, 'destroy']);
            Route::post('/{project}/assign', [ProjectController::class, 'assignMembers']);
            Route::delete('/{project}/members/{user}', [ProjectController::class, 'removeMember']);
        });

        Route::prefix('tasks')->group(function () {
            Route::get('/', [TaskController::class, 'index']);
            Route::post('/', [TaskController::class, 'store']);
            Route::get('/{task}', [TaskController::class, 'show']);
            Route::put('/{task}', [TaskController::class, 'update']);
            Route::delete('/{task}', [TaskController::class, 'destroy']);
            Route::post('/{task}/assign', [TaskController::class, 'assign']);
            Route::delete('/{task}/assignees/{user}', [TaskController::class, 'removeAssignee']);
            Route::post('/{task}/comments', [CommentController::class, 'store']);
        });

        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
        });
    });
});

// ── Password Reset via OTP (public — no auth required) ─────────────────────
Route::middleware('throttle:otp')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-otp',      [PasswordResetController::class, 'verifyOtp']);
    Route::post('/reset-password',  [PasswordResetController::class, 'resetPassword']);
});
