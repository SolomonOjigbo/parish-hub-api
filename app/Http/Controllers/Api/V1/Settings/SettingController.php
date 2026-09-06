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
     * Keys the API will persist — everything else is ignored so arbitrary
     * keys cannot be injected into the settings store.
     */
    private const ALLOWED_KEYS = [
        'parish_name', 'diocese', 'deanery', 'parish_address', 'parish_phone',
        'parish_email', 'motto', 'logo_path', 'membership_prefix',
        'mass_schedule_sunday', 'mass_schedule_weekday', 'mass_schedule_saturday',
        'paystack_enabled', 'flutterwave_enabled', 'termii_enabled', 'smtp_enabled',
        'sms_sender_id', 'zone_label',
    ];

    /**
     * Update settings.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('settings.manage');

        foreach ($request->all() as $key => $value) {
            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }
            // Never store masked values
            if ($value === '***masked***') {
                continue;
            }
            Setting::set($key, is_scalar($value) || $value === null ? (string) $value : json_encode($value));
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
     * List database backups (stored on the private local disk).
     */
    public function backups(): JsonResponse
    {
        $this->authorize('settings.view');

        $files = Storage::disk('local')->files('backups');

        $backups = collect($files)->map(function ($file) {
            $filePath = Storage::disk('local')->path($file);
            return [
                'filename' => basename($file),
                'size_mb' => round(filesize($filePath) / 1024 / 1024, 2),
                'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
            ];
        })->sortByDesc('created_at')->values();

        return $this->success($backups);
    }

    /**
     * Download one backup file — authenticated, settings.manage only.
     */
    public function downloadBackup(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $this->authorize('settings.manage');

        $safe = basename($filename); // no path traversal
        if (!Storage::disk('local')->exists('backups/' . $safe)) {
            return $this->error('Backup not found', 404);
        }

        return response()->download(Storage::disk('local')->path('backups/' . $safe));
    }
}
