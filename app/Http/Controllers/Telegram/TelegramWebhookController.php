<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\App\Telegram\TelegramService;
use App\Services\App\Telegram\Bots\NotificationBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming webhook
     */
    public function handle(Request $request)
    {
        try {
            $update = $request->all();

            // Log incoming update
            Log::channel('telegram')->debug('Incoming webhook', $update);

            // Initialize bot and handle update
            $bot = new NotificationBot($this->telegram);
            $bot->handle($update);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::channel('telegram')->error('Webhook error: ' . $e->getMessage(), [
                'exception' => $e,
                'update' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set webhook URL
     */
    public function setWebhook()
    {
        try {
            $url = config('services.telegram.webhook_url');
            $response = $this->telegram->setWebhook($url);

            if ($response['ok']) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook set successfully'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response['description'] ?? 'Unknown error'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook()
    {
        try {
            $response = $this->telegram->deleteWebhook();

            if ($response['ok']) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook removed successfully'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response['description'] ?? 'Unknown error'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo()
    {
        try {
            $response = $this->telegram->getWebhookInfo();

            return response()->json([
                'status' => 'success',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
