<?php

namespace App\Services\App\Telegram\Traits;

trait HasCommand
{
    protected array $commands = [];

    /**
     * Add command handler
     */
    public function command(string $command, callable $handler): self
    {
        $this->commands[$command] = $handler;
        return $this;
    }

    /**
     * Handle command message
     */
    protected function handleCommandMessage(array $message): void
    {
        $text = $message['text'] ?? '';

        if (!str_starts_with($text, '/')) {
            return;
        }

        // Extract command and arguments
        $parts = explode(' ', $text);
        $commandParts = explode('@', $parts[0]);
        $command = substr($commandParts[0], 1);
        $arguments = array_slice($parts, 1);

        // Check if command exists and call handler
        if (isset($this->commands[$command])) {
            call_user_func($this->commands[$command], $message, $arguments);
        }
    }

    /**
     * Register bot command
     */
    public function registerCommand(string $command, string $description, ?string $scope = null): array
    {
        $params = [
            'command' => $command,
            'description' => $description
        ];

        if ($scope) {
            $params['scope'] = ['type' => $scope];
        }

        return $this->request('setMyCommands', [
            'commands' => [$params]
        ]);
    }

    /**
     * Delete bot command
     */
    public function deleteCommand(string $command, ?string $scope = null): array
    {
        $params = ['command' => $command];

        if ($scope) {
            $params['scope'] = ['type' => $scope];
        }

        return $this->request('deleteMyCommands', $params);
    }

    /**
     * Get bot commands
     */
    public function getCommands(?string $scope = null): array
    {
        $params = [];

        if ($scope) {
            $params['scope'] = ['type' => $scope];
        }

        return $this->request('getMyCommands', $params);
    }
}
