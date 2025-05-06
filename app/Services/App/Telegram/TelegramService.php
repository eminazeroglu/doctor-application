<?php

namespace App\Services\App\Telegram;

use App\Services\App\Telegram\Traits\{HasCallback, HasCommand, HasFile, HasKeyboard, HasMessage};
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    use HasMessage, HasFile, HasCallback, HasCommand, HasKeyboard;

    protected array $config;
    protected int $chatId;

    public function __construct()
    {
        $this->config = Config::get('services.telegram', []);

        $this->chatId = config('services.telegram.chat_id');

        // Əgər konfiqurasiya boşdursa, default dəyərləri təyin edirik
        if (empty($this->config)) {
            $this->config = [
                'token' => null,
                'webhook_url' => null,
                'chat_id' => null,
                'api_url' => 'https://api.telegram.org/bot',
                'file_url' => 'https://api.telegram.org/file/bot',
            ];
        }

    }

    /**
     * Set chat ID for messaging
     */
    public function chat(int $chatId): self
    {
        $this->chatId = $chatId;
        return $this;
    }

    /**
     * Make request to Telegram API
     */
    protected function request(string $method, array $params = []): array
    {

        try {
            $response = Http::post($this->config['api_url'] . $this->config['token'] . '/' . $method, $params);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Telegram API Error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get bot information
     */
    public function getMe(): array
    {
        return $this->request('getMe');
    }

    /**
     * Get webhook information
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $url, array $allowedUpdates = []): array
    {
        return $this->request('setWebhook', [
            'url' => $url,
            'allowed_updates' => $allowedUpdates
        ]);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(): array
    {
        return $this->request('deleteWebhook');
    }

    /**
     * Handle incoming webhook updates
     */
    public function handleWebhook(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }
    }

    /**
     * Register bot commands
     */
    public function registerCommands(array $commands): array
    {
        return $this->request('setMyCommands', [
            'commands' => $commands
        ]);
    }

    /**
     * Get active bot commands
     */
    public function getCommands(): array
    {
        return $this->request('getMyCommands');
    }

    /**
     * Delete bot commands
     */
    public function deleteCommands(): array
    {
        return $this->request('deleteMyCommands');
    }
}
