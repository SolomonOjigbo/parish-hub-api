<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.termii.api_key');
        $this->senderId = config('services.termii.sender_id');
    }

    /**
     * Send SMS to multiple phone numbers.
     */
    public function send(array $phoneNumbers, string $message): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($phoneNumbers as $phoneNumber) {
            try {
                $response = Http::post('https://api.ng.termii.com/api/sms/send', [
                    'api_key' => $this->apiKey,
                    'to' => $phoneNumber,
                    'from' => $this->senderId,
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => 'generic',
                ]);

                if ($response->successful()) {
                    $sent++;
                } else {
                    $failed++;
                    $errors[] = [
                        'phone' => $phoneNumber,
                        'error' => $response->body(),
                    ];
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage(),
                ];
                Log::error('SMS sending failed', [
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Get SMS credit balance.
     */
    public function getBalance(): array
    {
        try {
            $response = Http::get('https://api.ng.termii.com/api/get-balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json()['balance'] ?? 0,
                    'currency' => $response->json()['currency'] ?? 'NGN',
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get SMS balance', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
