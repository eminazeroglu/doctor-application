<?php

namespace App\Services\Notification;

use App\Models\NotificationDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected string $fcmServerKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->fcmServerKey = config('services.firebase.server_key');
    }

    /**
     * Cihaza push notification göndərmək
     */
    public function sendToDevice(
        NotificationDevice $device,
        string $title,
        string $body,
        array $data = []
    ): array {
        try {
            switch ($device->device_type) {
                case 'ios':
                    return $this->sendToIOS($device->device_token, $title, $body, $data);
                case 'android':
                    return $this->sendToAndroid($device->device_token, $title, $body, $data);
                case 'web':
                    return $this->sendToWeb($device->device_token, $title, $body, $data);
                default:
                    throw new \Exception('Unsupported device type: ' . $device->device_type);
            }
        } catch (\Exception $e) {
            Log::error('Push notification send failed', [
                'device_id' => $device->id,
                'device_type' => $device->device_type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * iOS cihazına göndərmək
     */
    protected function sendToIOS(string $deviceToken, string $title, string $body, array $data): array
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                    ]
                ]
            ]
        ];

        return $this->sendFCMRequest($payload);
    }

    /**
     * Android cihazına göndərmək
     */
    protected function sendToAndroid(string $deviceToken, string $title, string $body, array $data): array
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => 'ic_notification',
                'color' => '#667eea',
                'sound' => 'default',
            ],
            'data' => $data,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => 'ic_notification',
                    'color' => '#667eea',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            ]
        ];

        return $this->sendFCMRequest($payload);
    }

    /**
     * Web cihazına göndərmək
     */
    protected function sendToWeb(string $deviceToken, string $title, string $body, array $data): array
    {
        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/icon-192x192.png',
                'click_action' => $data['action_url'] ?? config('app.url'),
            ],
            'data' => $data,
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high'
                ],
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => '/icon-192x192.png',
                    'badge' => '/badge-72x72.png',
                    'vibrate' => [200, 100, 200],
                    'requireInteraction' => true,
                ]
            ]
        ];

        return $this->sendFCMRequest($payload);
    }

    /**
     * Firebase Cloud Messaging API-yə sorğu göndərmək
     */
    protected function sendFCMRequest(array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success'] > 0) {
                return [
                    'success' => true,
                    'response' => $responseData
                ];
            } else {
                $errorMessage = $responseData['results'][0]['error'] ?? 'Unknown FCM error';

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'response' => $responseData
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'FCM request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Çoxlu cihaza göndərmək
     */
    public function sendToMultipleDevices(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $payload = [
            'registration_ids' => $deviceTokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
        ];

        return $this->sendFCMRequest($payload);
    }

    /**
     * Topic-ə subscription
     */
    public function subscribeToTopic(array $deviceTokens, string $topic): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://iid.googleapis.com/iid/v1:batchAdd', [
                'to' => '/topics/' . $topic,
                'registration_tokens' => $deviceTokens,
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
