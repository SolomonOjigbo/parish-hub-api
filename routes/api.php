<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Members\MemberController;

/*
|--------------------------------------------------------------------------
| ParishHub API Routes — v1
| All routes prefixed: /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── PUBLIC ROUTES (no auth required) ──────────────────────────────
    Route::prefix('public')->group(function () {
        Route::post('register', fn() => response()->json(['message' => 'TODO']));
        Route::post('visitor',  fn() => response()->json(['message' => 'TODO']));
        Route::get('events',    fn() => response()->json(['message' => 'TODO']));
    });

    // ── AUTH ──────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login',          [AuthController::class, 'login']);
        Route::post('forgot-password',[AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
            Route::put('me/password', fn() => response()->json(['message' => 'TODO']));
        });
    });

    // ── ALL PROTECTED ROUTES ──────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Members
        Route::apiResource('members', MemberController::class);
        Route::post('members/{id}/photo',            fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/societies',         fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/attendance',        fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/giving',            fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/communications',    fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/audit-log',         fn() => response()->json(['message' => 'TODO']));
        Route::get('members/export',                 fn() => response()->json(['message' => 'TODO']));
        Route::post('members/{id}/sacraments',       fn() => response()->json(['message' => 'TODO']));
        Route::put('members/{id}/sacraments/{sid}',  fn() => response()->json(['message' => 'TODO']));

        // Families
        Route::apiResource('families', \App\Http\Controllers\Controller::class);

        // Societies
        Route::prefix('societies')->group(function () {
            Route::get('/',                                    fn() => response()->json(['message' => 'TODO']));
            Route::post('/',                                   fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',                                 fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',                                 fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',                              fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/members',                         fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/members',                        fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}/members/{mid}',                   fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}/members/{mid}',                fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/meetings',                        fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/meetings',                       fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}/meetings/{mid}',                  fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/meetings/{mid}/minutes',         fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/dues',                            fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/dues',                           fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/dues/matrix',                     fn() => response()->json(['message' => 'TODO']));
        });

        // Events
        Route::prefix('events')->group(function () {
            Route::get('/',                            fn() => response()->json(['message' => 'TODO']));
            Route::post('/',                           fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',                         fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',                         fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',                      fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/register',               fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}/register',             fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/registrations',           fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/attendance',             fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/attendance',              fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/reminders/send',         fn() => response()->json(['message' => 'TODO']));
        });

        // Finance
        Route::prefix('offerings')->group(function () {
            Route::get('/',         fn() => response()->json(['message' => 'TODO']));
            Route::post('/',        fn() => response()->json(['message' => 'TODO']));
            Route::get('summary',   fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',      fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',      fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',   fn() => response()->json(['message' => 'TODO']));
            Route::post('import',   fn() => response()->json(['message' => 'TODO']));
        });

        Route::prefix('tithes')->group(function () {
            Route::get('/',                    fn() => response()->json(['message' => 'TODO']));
            Route::post('/',                   fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',                 fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',                 fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',              fn() => response()->json(['message' => 'TODO']));
            Route::get('member/{memberId}',    fn() => response()->json(['message' => 'TODO']));
        });

        Route::prefix('pledges')->group(function () {
            Route::get('/',                    fn() => response()->json(['message' => 'TODO']));
            Route::post('/',                   fn() => response()->json(['message' => 'TODO']));
            Route::get('overdue',              fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',                 fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',                 fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',              fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/payments',       fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/payments',        fn() => response()->json(['message' => 'TODO']));
        });

        Route::prefix('donations')->group(function () {
            Route::get('/',                        fn() => response()->json(['message' => 'TODO']));
            Route::post('/',                       fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',                     fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',                     fn() => response()->json(['message' => 'TODO']));
            Route::delete('{id}',                  fn() => response()->json(['message' => 'TODO']));
            Route::get('donor/{memberId}',         fn() => response()->json(['message' => 'TODO']));
        });

        // Reports
        Route::get('reports/financial', fn() => response()->json(['message' => 'TODO']));

        // Communications
        Route::prefix('communications')->group(function () {
            Route::post('email',    fn() => response()->json(['message' => 'TODO']));
            Route::post('sms',      fn() => response()->json(['message' => 'TODO']));
            Route::get('logs',      fn() => response()->json(['message' => 'TODO']));
            Route::get('logs/{id}', fn() => response()->json(['message' => 'TODO']));
        });

        Route::prefix('bulletins')->group(function () {
            Route::get('/',          fn() => response()->json(['message' => 'TODO']));
            Route::post('/',         fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}',       fn() => response()->json(['message' => 'TODO']));
            Route::put('{id}',       fn() => response()->json(['message' => 'TODO']));
            Route::get('{id}/preview', fn() => response()->json(['message' => 'TODO']));
            Route::post('{id}/export', fn() => response()->json(['message' => 'TODO']));
        });

        // Staff & Roles
        Route::apiResource('staff', \App\Http\Controllers\Controller::class);
        Route::get('roles',              fn() => response()->json(['message' => 'TODO']));
        Route::post('roles',             fn() => response()->json(['message' => 'TODO']));
        Route::put('roles/{id}',         fn() => response()->json(['message' => 'TODO']));
        Route::get('permissions',        fn() => response()->json(['message' => 'TODO']));
        Route::post('users/{id}/roles',  fn() => response()->json(['message' => 'TODO']));

        // Settings & Audit
        Route::get('settings',              fn() => response()->json(['message' => 'TODO']));
        Route::put('settings',              fn() => response()->json(['message' => 'TODO']));
        Route::post('settings/test-email',  fn() => response()->json(['message' => 'TODO']));
        Route::post('settings/test-sms',    fn() => response()->json(['message' => 'TODO']));
        Route::post('settings/backup',      fn() => response()->json(['message' => 'TODO']));
        Route::get('settings/backups',      fn() => response()->json(['message' => 'TODO']));
        Route::get('audit-logs',            fn() => response()->json(['message' => 'TODO']));

        // Zones
        Route::apiResource('zones', \App\Http\Controllers\Controller::class);
        Route::get('attendance',  fn() => response()->json(['message' => 'TODO']));
        Route::get('sacraments',  fn() => response()->json(['message' => 'TODO']));
        Route::get('committees',  fn() => response()->json(['message' => 'TODO']));

        // Sync (for Electron desktop app)
        Route::prefix('sync')->group(function () {
            Route::post('push',     fn() => response()->json(['message' => 'TODO']));
            Route::post('pull',     fn() => response()->json(['message' => 'TODO']));
            Route::get('status',    fn() => response()->json(['message' => 'TODO']));
            Route::post('resolve',  fn() => response()->json(['message' => 'TODO']));
        });

        // Member Portal
        Route::prefix('portal')->group(function () {
            Route::get('profile',          fn() => response()->json(['message' => 'TODO']));
            Route::put('profile',          fn() => response()->json(['message' => 'TODO']));
            Route::post('profile/photo',   fn() => response()->json(['message' => 'TODO']));
            Route::get('giving',           fn() => response()->json(['message' => 'TODO']));
            Route::get('events',           fn() => response()->json(['message' => 'TODO']));
            Route::post('events/{id}/register', fn() => response()->json(['message' => 'TODO']));
            Route::get('family',           fn() => response()->json(['message' => 'TODO']));
        });

    }); // end auth:sanctum

}); // end v1
