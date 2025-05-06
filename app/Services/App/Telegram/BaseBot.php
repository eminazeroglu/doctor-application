<?php

namespace App\Services\App\Telegram;

use Illuminate\Support\Facades\Cache;

abstract class BaseBot
{
    protected TelegramService $telegram;          // Telegram servisinin instance'ı
    protected array $update;                      // Telegram-dan gələn xam update datası
    protected ?array $message = null;             // Gələn mesaj
    protected ?array $callbackQuery = null;       // Callback query
    protected ?int $chatId = null;                // Cari chat ID
    protected ?int $userId = null;                // İstifadəçi ID
    protected ?string $username = null;           // İstifadəçi adı
    protected ?string $firstName = null;          // İstifadəçinin adı
    protected ?string $lastName = null;           // İstifadəçinin soyadı

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
        $this->registerCommands();
    }

    /**
     * Handle incoming update
     */
    public function handle(array $update): void
    {
        $this->update = $update;
        $this->parseUpdate();

        if ($this->callbackQuery) {
            $this->handleCallback($this->callbackQuery);
        } elseif ($this->message) {
            $text = $this->message['text'] ?? '';
            if (str_starts_with($text, '/')) {
                $this->handleCommandMessage($this->message);
            } else {
                $this->handleMessage($this->message);
            }
        }
    }

    /**
     * Parse update data
     */
    protected function parseUpdate(): void
    {
        // Get message or callback query
        $this->message = $this->update['message'] ?? null;
        $this->callbackQuery = $this->update['callback_query'] ?? null;

        // Get chat and user info
        if ($this->message) {
            $this->chatId = $this->message['chat']['id'];
            $this->userId = $this->message['from']['id'];
            $this->username = $this->message['from']['username'] ?? null;
            $this->firstName = $this->message['from']['first_name'] ?? null;
            $this->lastName = $this->message['from']['last_name'] ?? null;
        } elseif ($this->callbackQuery) {
            $this->chatId = $this->callbackQuery['message']['chat']['id'];
            $this->userId = $this->callbackQuery['from']['id'];
            $this->username = $this->callbackQuery['from']['username'] ?? null;
            $this->firstName = $this->callbackQuery['from']['first_name'] ?? null;
            $this->lastName = $this->callbackQuery['from']['last_name'] ?? null;
        }

        // Set chat ID in telegram service
        if ($this->chatId) {
            $this->telegram->chat($this->chatId);
        }
    }

    /**
     * Register bot commands
     */
    abstract protected function registerCommands(): void;

    /**
     * Handle incoming message
     */
    abstract protected function handleMessage(array $message): void;

    /**
     * Handle callback query
     */
    abstract protected function handleCallback(array $callbackQuery): void;

    /**
     * Get user state
     */
    protected function getState(): ?string
    {
        return Cache::get($this->getStateCacheKey());
    }

    /**
     * Set user state
     */
    protected function setState(?string $state): void
    {
        if ($state === null) {
            Cache::forget($this->getStateCacheKey());
        } else {
            Cache::put($this->getStateCacheKey(), $state, now()->addDay());
        }
    }

    /**
     * Get state cache key
     */
    protected function getStateCacheKey(): string
    {
        return "telegram_bot_state_{$this->userId}";
    }
}
