<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Setting;
use App\Jobs\DatabaseBackupJob;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingController extends BaseApiController
{
    /**
     * Display all settings.
     */
    public function index(): JsonResponse
    {
        $this->authorize('settings.view');

        $settings = Setting::all()->pluck('value', 'key');

        // Mask sensitive values
        $sensitiveKeys = ['termii_api_key', 'smtp_password', 'smtp_username'];
        foreach ($sensitiveKeys as $key) {
            if (isset($settings[$key]) && !empty($settings[$key])) {
                $settings[$key] = '***masked***';
            }
        }

        return $this->success($settings);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        foreach ($request->all() as $key => $value) {
            // Never store masked values
            if ($value === '***masked***') {
                continue;
            }
            Setting::set($key, $value);
        }

        return $this->success(null, 'Settings updated successfully');
    }

    /**
     * Send test email.
     */
    public function testEmail(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        $email = $request->user()->email;

        try {
            Mail::raw('This is a test email from St. Ferdinand Catholic Church ParishHub system.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - ParishHub');
            });

            return $this->success(null, 'Test email sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send test email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send test SMS.
     */
    public function testSms(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        $request->validate([
            'phone_number' => ['required', 'string'],
        ]);

        try {
            $smsService = new SmsService();
            $result = $smsService->send([$request->phone_number], 'This is a test SMS from St. Ferdinand Catholic Church ParishHub system.');

            return $this->success($result, 'Test SMS sent successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to send test SMS: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Trigger database backup.
     */
    public function backup(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        dispatch(new DatabaseBackupJob());

        return $this->success(null, 'Database backup job dispatched');
    }

    /**
     * List database backups.
     */
    public function backups(): JsonResponse
    {
        $this->authorize('settings.view');

        $files = Storage::disk('public')->files('backups');

        $backups = collect($files)->map(function ($file) {
            $filePath = Storage::disk('public')->path($file);
            return [
                'filename' => basename($file),
                'size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                'download_url' => Storage::disk('public')->url($file),
            ];
        })->sortByDesc('created_at')->values();

        return $this->success($backups);
    }
}
