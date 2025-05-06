<?php

namespace App\Services\App\Telegram\Traits;

trait HasKeyboard
{
    /**
     * Create inline keyboard markup
     */
    protected function inlineKeyboard(array $buttons): array
    {
        return [
            'inline_keyboard' => $this->buildInlineKeyboard($buttons)
        ];
    }

    /**
     * Create reply keyboard markup
     */
    protected function replyKeyboard(array $buttons, array $options = []): array
    {
        return array_merge([
            'keyboard' => $this->buildReplyKeyboard($buttons),
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ], $options);
    }

    /**
     * Remove reply keyboard
     */
    protected function removeKeyboard(bool $selective = false): array
    {
        return [
            'remove_keyboard' => true,
            'selective' => $selective
        ];
    }

    /**
     * Build inline keyboard buttons
     */
    protected function buildInlineKeyboard(array $buttons): array
    {
        $keyboard = [];
        $row = [];

        foreach ($buttons as $button) {
            // New row
            if ($button === '---') {
                if (!empty($row)) {
                    $keyboard[] = $row;
                    $row = [];
                }
                continue;
            }

            // Add button to row
            $row[] = $this->buildInlineButton($button);

            // Add row if specified number of columns reached
            if (isset($button['columns']) && count($row) >= $button['columns']) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        // Add remaining buttons
        if (!empty($row)) {
            $keyboard[] = $row;
        }

        return $keyboard;
    }

    /**
     * Build reply keyboard buttons
     */
    protected function buildReplyKeyboard(array $buttons): array
    {
        $keyboard = [];
        $row = [];

        foreach ($buttons as $button) {
            // New row
            if ($button === '---') {
                if (!empty($row)) {
                    $keyboard[] = $row;
                    $row = [];
                }
                continue;
            }

            // Add button to row
            $row[] = $this->buildReplyButton($button);

            // Add row if specified number of columns reached
            if (isset($button['columns']) && count($row) >= $button['columns']) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        // Add remaining buttons
        if (!empty($row)) {
            $keyboard[] = $row;
        }

        return $keyboard;
    }

    /**
     * Build single inline button
     */
    protected function buildInlineButton(array $button): array
    {
        $built = ['text' => $button['text']];

        if (isset($button['url'])) {
            $built['url'] = $button['url'];
        }
        elseif (isset($button['callback'])) {
            $built['callback_data'] = $this->createCallbackData(
                $button['callback'],
                $button['params'] ?? []
            );
        }
        elseif (isset($button['switch_inline_query'])) {
            $built['switch_inline_query'] = $button['switch_inline_query'];
        }
        elseif (isset($button['switch_inline_query_current_chat'])) {
            $built['switch_inline_query_current_chat'] = $button['switch_inline_query_current_chat'];
        }
        elseif (isset($button['pay'])) {
            $built['pay'] = true;
        }

        return $built;
    }

    /**
     * Build single reply button
     */
    protected function buildReplyButton(array $button): array
    {
        $built = ['text' => $button['text']];

        if (isset($button['request_contact'])) {
            $built['request_contact'] = true;
        }
        elseif (isset($button['request_location'])) {
            $built['request_location'] = true;
        }
        elseif (isset($button['request_poll'])) {
            $built['request_poll'] = ['type' => $button['request_poll']];
        }

        return $built;
    }
}
