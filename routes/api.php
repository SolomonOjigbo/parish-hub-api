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
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
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
    Route::prefix('public')->middleware('throttle:20,1')->group(function () {
        Route::post('register', [PublicController::class, 'register']);
        Route::post('visitor',  [PublicController::class, 'visitor']);
        Route::get('events',    [PublicController::class, 'events']);
    });

    // ── AUTH ──────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('login',          [AuthController::class, 'login']);
            Route::post('forgot-password',[AuthController::class, 'forgotPassword']);
            Route::post('reset-password', [AuthController::class, 'resetPassword']);
        });

        // Reachable even while must_change_password is set, so the user
        // can identify themselves, change the password, or log out.
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
            Route::put('me/password', [AuthController::class, 'changePassword']);
        });
    });

    // ── ALL PROTECTED ROUTES ──────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'password.changed'])->group(function () {

        // Dashboard & notifications
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('notifications',     [NotificationController::class, 'index']);

        // Members — static routes BEFORE apiResource so /members/export
        // is not captured by /members/{member}.
        Route::get('members/export',                        [MemberController::class, 'export']);
        Route::post('members/import',                       [MemberController::class, 'import']);
        Route::post('members/{id}/photo',                   [MemberController::class, 'uploadPhoto']);
        Route::get('members/{id}/societies',                [MemberController::class, 'societies']);
        Route::get('members/{id}/attendance',               [MemberController::class, 'attendance']);
        Route::get('members/{id}/giving',                   [MemberController::class, 'giving']);
        Route::get('members/{id}/communications',           [MemberController::class, 'communications']);
        Route::get('members/{id}/audit-log',                [MemberController::class, 'auditLog']);
        Route::post('members/{id}/sacraments',              [MemberController::class, 'storeSacrament']);
        Route::put('members/{id}/sacraments/{sacrament}',   [MemberController::class, 'updateSacrament']);
        Route::get('members/{id}/sacraments/{sacrament}/certificate', [MemberController::class, 'sacramentCertificate']);
        Route::get('members/{id}/giving/statement',         [MemberController::class, 'givingStatement']);
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

            Route::post('{id}/reminders/send', [EventController::class, 'sendReminders']);
        });

        // Finance
        Route::prefix('offerings')->group(function () {
            Route::get('/',              [OfferingController::class, 'index']);
            Route::post('/',             [OfferingController::class, 'store']);
            Route::get('summary',        [OfferingController::class, 'summary']);
            Route::post('import',        [OfferingController::class, 'import']);
            Route::get('{offering}',     [OfferingController::class, 'show'])->whereNumber('offering');
            Route::put('{offering}',     [OfferingController::class, 'update'])->whereNumber('offering');
            Route::delete('{offering}',  [OfferingController::class, 'destroy'])->whereNumber('offering');
        });

        Route::prefix('tithes')->group(function () {
            Route::get('/',                    [TitheController::class, 'index']);
            Route::post('/',                   [TitheController::class, 'store']);
            Route::get('member/{memberId}',    [TitheController::class, 'member']);
            Route::get('{tithe}',              [TitheController::class, 'show'])->whereNumber('tithe');
            Route::put('{tithe}',              [TitheController::class, 'update'])->whereNumber('tithe');
            Route::delete('{tithe}',           [TitheController::class, 'destroy'])->whereNumber('tithe');
        });

        Route::prefix('pledges')->group(function () {
            Route::get('/',                    [PledgeController::class, 'index']);
            Route::post('/',                   [PledgeController::class, 'store']);
            Route::get('overdue',              [PledgeController::class, 'overdue']);
            Route::get('{pledge}',             [PledgeController::class, 'show'])->whereNumber('pledge');
            Route::put('{pledge}',             [PledgeController::class, 'update'])->whereNumber('pledge');
            Route::delete('{pledge}',          [PledgeController::class, 'destroy'])->whereNumber('pledge');
            Route::post('{pledge}/payments',   [PledgeController::class, 'addPayment']);
            Route::get('{pledge}/payments',    [PledgeController::class, 'payments']);
        });

        Route::prefix('donations')->group(function () {
            Route::get('/',                    [DonationController::class, 'index']);
            Route::post('/',                   [DonationController::class, 'store']);
            Route::get('donor/{memberId}',     [DonationController::class, 'donor']);
            Route::get('{donation}/receipt',   [DonationController::class, 'receipt'])->whereNumber('donation');
            Route::get('{donation}',           [DonationController::class, 'show'])->whereNumber('donation');
            Route::put('{donation}',           [DonationController::class, 'update'])->whereNumber('donation');
            Route::delete('{donation}',        [DonationController::class, 'destroy'])->whereNumber('donation');
        });

        // Reports
        Route::get('reports/financial', [ReportController::class, 'financial']);

        // Communications
        Route::prefix('communications')->group(function () {
            Route::post('email',     [EmailController::class, 'send']);
            Route::post('sms',       [SmsController::class, 'send']);
            Route::get('logs',       [CommunicationLogController::class, 'index']);
            Route::get('logs/{log}', [CommunicationLogController::class, 'show'])->whereNumber('log');
        });

        Route::prefix('bulletins')->group(function () {
            Route::get('/',                  [BulletinController::class, 'index']);
            Route::post('/',                 [BulletinController::class, 'store']);
            Route::get('{bulletin}',         [BulletinController::class, 'show'])->whereNumber('bulletin');
            Route::put('{bulletin}',         [BulletinController::class, 'update'])->whereNumber('bulletin');
            Route::get('{bulletin}/preview', [BulletinController::class, 'preview']);
            Route::get('{bulletin}/export',  [BulletinController::class, 'export']);
            Route::post('{bulletin}/export', [BulletinController::class, 'export']);
        });

        // Staff & Roles
        Route::apiResource('staff', StaffController::class);
        Route::get('roles',              [RoleController::class, 'index']);
        Route::post('roles',             [RoleController::class, 'store']);
        Route::put('roles/{role}',       [RoleController::class, 'update']);
        Route::get('permissions',        [RoleController::class, 'permissions']);
        Route::post('users',             [UserController::class, 'store']);
        Route::put('users/{user}',       [UserController::class, 'update']);
        Route::delete('users/{user}',    [UserController::class, 'destroy']);
        Route::post('users/{user}/roles',  [UserController::class, 'assignRole']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);

        // Settings & Audit
        Route::get('settings',              [SettingController::class, 'index']);
        Route::put('settings',              [SettingController::class, 'update']);
        Route::post('settings/test-email',  [SettingController::class, 'testEmail']);
        Route::post('settings/test-sms',    [SettingController::class, 'testSms']);
        Route::post('settings/backup',      [SettingController::class, 'backup']);
        Route::get('settings/backups',      [SettingController::class, 'backups']);
        Route::get('settings/backups/{filename}', [SettingController::class, 'downloadBackup']);
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

        // Member Portal
        Route::prefix('portal')->group(function () {
            Route::get('profile',          [PortalController::class, 'profile']);
            Route::put('profile',          [PortalController::class, 'updateProfile']);
            Route::post('profile/photo',   [PortalController::class, 'uploadPhoto']);
            Route::get('giving',           [PortalController::class, 'giving']);
            Route::get('giving/statement', [PortalController::class, 'givingStatement']);
            Route::get('events',           [PortalController::class, 'events']);
            Route::post('events/{id}/register', [PortalController::class, 'registerEvent']);
            Route::get('family',           [PortalController::class, 'family']);
        });

    }); // end auth:sanctum + password.changed

}); // end v1
