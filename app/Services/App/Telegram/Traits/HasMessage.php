<?php

namespace App\Services\App\Telegram\Traits;

trait HasMessage
{
    /**
     * Send text message
     */
    public function sendMessage(string $text, array $options = []): array
    {
        return $this->request('sendMessage', array_merge([
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options));
    }

    /**
     * Edit message text
     */
    public function editMessage(int $messageId, string $text, array $options = []): array
    {
        return $this->request('editMessageText', array_merge([
            'chat_id' => $this->chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ], $options));
    }

    /**
     * Delete message
     */
    public function deleteMessage(int $messageId): array
    {
        return $this->request('deleteMessage', [
            'chat_id' => $this->chatId,
            'message_id' => $messageId
        ]);
    }

    /**
     * Forward message
     */
    public function forwardMessage(int $fromChatId, int $messageId): array
    {
        return $this->request('forwardMessage', [
            'chat_id' => $this->chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId
        ]);
    }

    /**
     * Copy message
     */
    public function copyMessage(int $fromChatId, int $messageId, array $options = []): array
    {
        return $this->request('copyMessage', array_merge([
            'chat_id' => $this->chatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId
        ], $options));
    }

    /**
     * Handle incoming message
     */
    protected function handleMessage(array $message): void
    {
        // Override this method in your bot implementation
    }
}
