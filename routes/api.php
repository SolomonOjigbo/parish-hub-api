<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Members\MemberController;
use App\Http\Controllers\Api\V1\Families\FamilyController;
use App\Http\Controllers\Api\V1\Societies\SocietyController;
use App\Http\Controllers\Api\V1\Societies\SocietyMemberController;
use App\Http\Controllers\Api\V1\Societies\SocietyMeetingController;
use App\Http\Controllers\Api\V1\Societies\SocietyDuesController;
use App\Http\Controllers\Api\V1\Committees\CommitteeController;
use App\Http\Controllers\Api\V1\Committees\CommitteeActionItemController;
use App\Http\Controllers\Api\V1\Events\EventController;
use App\Http\Controllers\Api\V1\Events\EventRegistrationController;
use App\Http\Controllers\Api\V1\Events\EventAttendanceController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\SacramentController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\Finance\OfferingController;
use App\Http\Controllers\Api\V1\Finance\TitheController;
use App\Http\Controllers\Api\V1\Finance\PledgeController;
use App\Http\Controllers\Api\V1\Finance\DonationController;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Http\Controllers\Api\V1\Communications\EmailController;
use App\Http\Controllers\Api\V1\Communications\SmsController;
use App\Http\Controllers\Api\V1\Communications\CommunicationLogController;
use App\Http\Controllers\Api\V1\Communications\BulletinController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use App\Http\Controllers\Api\V1\Staff\RoleController;
use App\Http\Controllers\Api\V1\Staff\UserController;
use App\Http\Controllers\Api\V1\Settings\SettingController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\PublicController;

/*
|--------------------------------------------------------------------------
| ParishHub API Routes — v1
| All routes prefixed: /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── PUBLIC ROUTES (no auth required) ──────────────────────────────
    Route::prefix('public')->group(function () {
        Route::post('register', [PublicController::class, 'register']);
        Route::post('visitor',  [PublicController::class, 'visitor']);
        Route::get('events',    [PublicController::class, 'events']);
    });

    // ── AUTH ──────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login',          [AuthController::class, 'login']);
        Route::post('forgot-password',[AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
            Route::put('me/password', [AuthController::class, 'changePassword']);

            Route::middleware('password.changed')->group(function () {
                // All other auth:sanctum routes go here
            });
        });
    });

    // ── ALL PROTECTED ROUTES ──────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Members — static routes BEFORE apiResource so /members/export
        // is not captured by /members/{id}.
        Route::get('members/export',                 [MemberController::class, 'export']);
        Route::post('members/{id}/photo',            [MemberController::class, 'uploadPhoto']);
        Route::get('members/{id}/societies',         fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/attendance',        fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/giving',            [MemberController::class, 'giving']);
        Route::get('members/{id}/communications',    fn() => response()->json(['message' => 'TODO']));
        Route::get('members/{id}/audit-log',         [MemberController::class, 'auditLog']);
        Route::post('members/{id}/sacraments',       fn() => response()->json(['message' => 'TODO']));
        Route::put('members/{id}/sacraments/{sid}',  fn() => response()->json(['message' => 'TODO']));
        Route::apiResource('members', MemberController::class);

        // Families
        Route::get('families/{id}/giving',                  [FamilyController::class, 'giving']);
        Route::post('families/{id}/members',                [FamilyController::class, 'assignMember']);
        Route::delete('families/{id}/members/{memberId}',   [FamilyController::class, 'removeMember']);
        Route::apiResource('families', FamilyController::class);

        // Societies
        Route::prefix('societies')->group(function () {
            Route::get('/',                            [SocietyController::class, 'index']);
            Route::post('/',                           [SocietyController::class, 'store']);
            Route::get('{id}',                         [SocietyController::class, 'show'])->whereNumber('id');
            Route::put('{id}',                         [SocietyController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',                      [SocietyController::class, 'destroy'])->whereNumber('id');

            Route::get('{id}/members',                 [SocietyMemberController::class, 'index']);
            Route::post('{id}/members',                [SocietyMemberController::class, 'store']);
            Route::put('{id}/members/{mid}',           [SocietyMemberController::class, 'update']);
            Route::delete('{id}/members/{mid}',        [SocietyMemberController::class, 'destroy']);

            Route::get('{id}/meetings',                [SocietyMeetingController::class, 'index']);
            Route::post('{id}/meetings',               [SocietyMeetingController::class, 'store']);
            Route::put('{id}/meetings/{mid}',          [SocietyMeetingController::class, 'update']);
            Route::post('{id}/meetings/{mid}/minutes', [SocietyMeetingController::class, 'uploadMinutes']);

            Route::get('{id}/dues/matrix',             [SocietyDuesController::class, 'matrix']);
            Route::get('{id}/dues',                    [SocietyDuesController::class, 'index']);
            Route::post('{id}/dues',                   [SocietyDuesController::class, 'store']);
        });

        // Events
        Route::prefix('events')->group(function () {
            Route::get('/',                    [EventController::class, 'index']);
            Route::post('/',                   [EventController::class, 'store']);
            Route::get('{id}',                 [EventController::class, 'show'])->whereNumber('id');
            Route::put('{id}',                 [EventController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',              [EventController::class, 'destroy'])->whereNumber('id');

            Route::post('{id}/register',       [EventRegistrationController::class, 'register']);
            Route::delete('{id}/register',     [EventRegistrationController::class, 'cancel']);
            Route::get('{id}/registrations',   [EventRegistrationController::class, 'index']);

            Route::post('{id}/attendance',     [EventAttendanceController::class, 'mark']);
            Route::get('{id}/attendance',      [EventAttendanceController::class, 'index']);

            Route::post('{id}/reminders/send', fn() => response()->json(['message' => 'TODO']));
        });

        // Finance
        Route::prefix('offerings')->group(function () {
            Route::get('/',         [OfferingController::class, 'index']);
            Route::post('/',        [OfferingController::class, 'store']);
            Route::get('summary',   [OfferingController::class, 'summary']);
            Route::get('{id}',      [OfferingController::class, 'show'])->whereNumber('id');
            Route::put('{id}',      [OfferingController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',   [OfferingController::class, 'destroy'])->whereNumber('id');
            Route::post('import',   [OfferingController::class, 'import']);
        });

        Route::prefix('tithes')->group(function () {
            Route::get('/',                    [TitheController::class, 'index']);
            Route::post('/',                   [TitheController::class, 'store']);
            Route::get('{id}',                 [TitheController::class, 'show'])->whereNumber('id');
            Route::put('{id}',                 [TitheController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',              [TitheController::class, 'destroy'])->whereNumber('id');
            Route::get('member/{memberId}',    [TitheController::class, 'member']);
        });

        Route::prefix('pledges')->group(function () {
            Route::get('/',                    [PledgeController::class, 'index']);
            Route::post('/',                   [PledgeController::class, 'store']);
            Route::get('overdue',              [PledgeController::class, 'overdue']);
            Route::get('{id}',                 [PledgeController::class, 'show'])->whereNumber('id');
            Route::put('{id}',                 [PledgeController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',              [PledgeController::class, 'destroy'])->whereNumber('id');
            Route::post('{id}/payments',       [PledgeController::class, 'addPayment']);
            Route::get('{id}/payments',        [PledgeController::class, 'payments']);
        });

        Route::prefix('donations')->group(function () {
            Route::get('/',                        [DonationController::class, 'index']);
            Route::post('/',                       [DonationController::class, 'store']);
            Route::get('{id}',                     [DonationController::class, 'show'])->whereNumber('id');
            Route::put('{id}',                     [DonationController::class, 'update'])->whereNumber('id');
            Route::delete('{id}',                  [DonationController::class, 'destroy'])->whereNumber('id');
            Route::get('donor/{memberId}',         [DonationController::class, 'donor']);
        });

        // Reports
        Route::get('reports/financial', [ReportController::class, 'financial']);

        // Communications
        Route::prefix('communications')->group(function () {
            Route::post('email',    [EmailController::class, 'send']);
            Route::post('sms',      [SmsController::class, 'send']);
            Route::get('logs',      [CommunicationLogController::class, 'index']);
            Route::get('logs/{id}', [CommunicationLogController::class, 'show']);
        });

        Route::prefix('bulletins')->group(function () {
            Route::get('/',            [BulletinController::class, 'index']);
            Route::post('/',           [BulletinController::class, 'store']);
            Route::get('{id}',         [BulletinController::class, 'show'])->whereNumber('id');
            Route::put('{id}',         [BulletinController::class, 'update'])->whereNumber('id');
            Route::get('{id}/preview', [BulletinController::class, 'preview']);
            Route::post('{id}/export', [BulletinController::class, 'export']);
        });

        // Staff & Roles
        Route::apiResource('staff', StaffController::class);
        Route::get('roles',              [RoleController::class, 'index']);
        Route::post('roles',             [RoleController::class, 'store']);
        Route::put('roles/{id}',         [RoleController::class, 'update']);
        Route::get('permissions',        [RoleController::class, 'permissions']);
        Route::post('users',             [UserController::class, 'store']);
        Route::put('users/{id}',         [UserController::class, 'update']);
        Route::delete('users/{id}',      [UserController::class, 'destroy']);
        Route::post('users/{id}/roles',  [UserController::class, 'assignRole']);
        Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // Settings & Audit
        Route::get('settings',              [SettingController::class, 'index']);
        Route::put('settings',              [SettingController::class, 'update']);
        Route::post('settings/test-email',  [SettingController::class, 'testEmail']);
        Route::post('settings/test-sms',    [SettingController::class, 'testSms']);
        Route::post('settings/backup',      [SettingController::class, 'backup']);
        Route::get('settings/backups',      [SettingController::class, 'backups']);
        Route::get('audit-logs',            [AuditLogController::class, 'index']);

        // Zones
        Route::apiResource('zones', ZoneController::class);
        Route::get('attendance',  [AttendanceController::class, 'index']);
        Route::get('sacraments',  [SacramentController::class, 'index']);

        // Committees
        Route::get('committees/{id}/action-items',                  [CommitteeActionItemController::class, 'index']);
        Route::post('committees/{id}/action-items',                 [CommitteeActionItemController::class, 'store']);
        Route::put('committees/{id}/action-items/{itemId}',         [CommitteeActionItemController::class, 'update']);
        Route::delete('committees/{id}/action-items/{itemId}',      [CommitteeActionItemController::class, 'destroy']);
        Route::apiResource('committees', CommitteeController::class);

        // Sync (for Electron desktop app)
        Route::prefix('sync')->group(function () {
            Route::post('push',     [SyncController::class, 'push']);
            Route::post('pull',     [SyncController::class, 'pull']);
            Route::get('status',    [SyncController::class, 'status']);
            Route::post('resolve',  [SyncController::class, 'resolve']);
        });

        // Member Portal
        Route::prefix('portal')->group(function () {
            Route::get('profile',          [PortalController::class, 'profile']);
            Route::put('profile',          [PortalController::class, 'updateProfile']);
            Route::post('profile/photo',   [PortalController::class, 'uploadPhoto']);
            Route::get('giving',           [PortalController::class, 'giving']);
            Route::get('events',           [PortalController::class, 'events']);
            Route::post('events/{id}/register', [PortalController::class, 'registerEvent']);
            Route::get('family',           [PortalController::class, 'family']);
        });

    }); // end auth:sanctum

}); // end v1
