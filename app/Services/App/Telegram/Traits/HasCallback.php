<?php

namespace App\Services\App\Telegram\Traits;

trait HasCallback
{
    /**
     * Answer callback query
     */
    public function answerCallback(string $callbackQueryId, array $options = []): array
    {
        return $this->request('answerCallbackQuery', array_merge([
            'callback_query_id' => $callbackQueryId,
        ], $options));
    }

    /**
     * Handle callback query
     */
    protected function handleCallback(array $callbackQuery): void
    {
        $callbackData = json_decode($callbackQuery['data'], true);

        if (!$callbackData) {
            return;
        }

        // Get action and parameters from callback data
        $action = $callbackData['action'] ?? null;
        $params = $callbackData['params'] ?? [];

        // Answer callback to remove loading state
        $this->answerCallback($callbackQuery['id']);

        // Handle different callback actions
        match($action) {
            'menu' => $this->handleMenuCallback($params),
            'select' => $this->handleSelectCallback($params),
            'page' => $this->handlePageCallback($params),
            default => $this->handleCustomCallback($action, $params)
        };
    }

    /**
     * Create callback data
     */
    protected function createCallbackData(string $action, array $params = []): string
    {
        return json_encode([
            'action' => $action,
            'params' => $params
        ]);
    }

    /**
     * Handle menu callback
     */
    protected function handleMenuCallback(array $params): void
    {
        // Override this method in your implementation
    }

    /**
     * Handle select callback
     */
    protected function handleSelectCallback(array $params): void
    {
        // Override this method in your implementation
    }

    /**
     * Handle page callback
     */
    protected function handlePageCallback(array $params): void
    {
        // Override this method in your implementation
    }

    /**
     * Handle custom callback
     */
    protected function handleCustomCallback(?string $action, array $params): void
    {
        // Override this method in your implementation
    }
}
