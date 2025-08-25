<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'local');
        $this->config = config('services.sms.' . $this->provider, []);
    }

    /**
     * SMS göndərmək
     */
    public function send(string $phone, string $message): array
    {
        try {
            // Telefon nömrəsini formatlamaq
            $phone = $this->formatPhoneNumber($phone);

            switch ($this->provider) {
                case 'asan_sms':
                    return $this->sendViaAsanSms($phone, $message);
                case 'azercell':
                    return $this->sendViaAzercell($phone, $message);
                case 'local':
                    return $this->sendViaLocal($phone, $message);
                default:
                    throw new \Exception('Unsupported SMS provider: ' . $this->provider);
            }

        } catch (\Exception $e) {
            Log::error('SMS send failed', [
                'phone' => $phone,
                'provider' => $this->provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Asan SMS vasitəsilə göndərmək
     */
    protected function sendViaAsanSms(string $phone, string $message): array
    {
        try {
            $response = Http::timeout(30)->post($this->config['api_url'], [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'msisdn' => $phone,
                'message' => $message,
                'source' => $this->config['sender_name'],
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
                return [
                    'success' => true,
                    'message_id' => $responseData['message_id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'SMS göndərmə xətası',
                    'response' => $responseData
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Asan SMS API xətası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Azercell vasitəsilə göndərmək
     */
    protected function sendViaAzercell(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
            ])->post($this->config['api_url'], [
                'to' => $phone,
                'text' => $message,
                'from' => $this->config['sender_name'],
            ]);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $responseData['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['message'] ?? 'SMS göndərmə xətası',
                    'response' => $responseData
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Azercell API xətası: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Local test üçün (development environment)
     */
    protected function sendViaLocal(string $phone, string $message): array
    {
        Log::info('SMS would be sent (local mode)', [
            'phone' => $phone,
            'message' => $message
        ]);

        return [
            'success' => true,
            'message_id' => 'local_' . uniqid(),
            'note' => 'SMS sent via local provider (test mode)'
        ];
    }

    /**
     * Telefon nömrəsini formatlamaq
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Boşluqları və xüsusi simvolları silmək
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Əgər + ilə başlamırsa və 994 ilə başlamırsa əlavə etmək
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '994')) {
                $phone = '+' . $phone;
            } elseif (str_starts_with($phone, '0')) {
                $phone = '+994' . substr($phone, 1);
            } else {
                $phone = '+994' . $phone;
            }
        }

        return $phone;
    }

    /**
     * SMS göndərmə statusunu yoxlamaq
     */
    public function checkStatus(string $messageId): array
    {
        try {
            switch ($this->provider) {
                case 'asan_sms':
                    return $this->checkAsanSmsStatus($messageId);
                case 'azercell':
                    return $this->checkAzercellStatus($messageId);
                default:
                    return [
                        'success' => false,
                        'error' => 'Status check not supported for provider: ' . $this->provider
                    ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Asan SMS status yoxlaması
     */
    protected function checkAsanSmsStatus(string $messageId): array
    {
        try {
            $response = Http::get($this->config['status_url'], [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
                'message_id' => $messageId,
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Azercell status yoxlaması
     */
    protected function checkAzercellStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
            ])->get($this->config['status_url'] . '/' . $messageId);

            return [
                'success' => $response->successful(),
                'status' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kütləvi SMS göndərmək
     */
    public function sendBulk(array $phones, string $message): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($phones as $phone) {
            $result = $this->send($phone, $message);

            if ($result['success']) {
                $results['success'][] = [
                    'phone' => $phone,
                    'message_id' => $result['message_id'] ?? null
                ];
            } else {
                $results['failed'][] = [
                    'phone' => $phone,
                    'error' => $result['error']
                ];
            }
        }

        return [
            'total' => count($phones),
            'successful' => count($results['success']),
            'failed' => count($results['failed']),
            'details' => $results
        ];
    }
}
