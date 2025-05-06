<?php

namespace App\Services\App\Telegram\Bots;

use App\Models\User;
use App\Services\App\Telegram\BaseBot;
use Illuminate\Support\Facades\Cache;

class NotificationBot extends BaseBot
{
    // Bot States
    private const STATE_AWAITING_CATEGORY = 'AWAITING_CATEGORY';
    private const STATE_AWAITING_TIME = 'AWAITING_TIME';

    /**
     * Register bot commands
     */
    protected function registerCommands(): void
    {
        // Start command
        $this->telegram->command('start', function($message) {
            $this->showMainMenu();
        });

        // Settings command
        $this->telegram->command('settings', function($message) {
            $this->showSettings();
        });

        // Help command
        $this->telegram->command('help', function($message) {
            $this->showHelp();
        });

        // Stop command
        $this->telegram->command('stop', function($message) {
            $this->disableNotifications();
        });
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage(array $message): void
    {
        $state = $this->getState();
        $text = $message['text'] ?? '';

        switch ($state) {
            case self::STATE_AWAITING_CATEGORY:
                $this->handleCategorySelection($text);
                break;

            case self::STATE_AWAITING_TIME:
                $this->handleTimeSelection($text);
                break;

            default:
                $this->showMainMenu();
                break;
        }
    }

    /**
     * Handle callback queries
     */
    protected function handleCallback(array $callbackQuery): void
    {
        $data = json_decode($callbackQuery['data'], true);
        $action = $data['action'] ?? '';
        $params = $data['params'] ?? [];

        switch ($action) {
            case 'notifications':
                $this->handleNotificationToggle($params);
                break;

            case 'category':
                $this->handleCategoryCallback($params);
                break;

            case 'time':
                $this->handleTimeCallback($params);
                break;

            case 'settings':
                $this->showSettings();
                break;

            case 'back':
                $this->showMainMenu();
                break;
        }
    }

    /**
     * Show main menu
     */
    private function showMainMenu(): void
    {
        $buttons = [
            [
                'text' => '📢 Bildirişləri idarə et',
                'callback' => 'notifications',
                'params' => ['action' => 'manage']
            ],
            '---',
            [
                'text' => '⚙️ Parametrlər',
                'callback' => 'settings',
                'params' => []
            ],
            [
                'text' => '❓ Kömək',
                'callback' => 'help',
                'params' => []
            ]
        ];

        $this->telegram->sendMessage(
            "🤖 Xoş gəlmisiniz!\n\n" .
            "Bu bot vasitəsilə bildirişlərinizi idarə edə bilərsiniz.",
            ['reply_markup' => $this->telegram->inlineKeyboard($buttons)]
        );

        $this->setState(null);
    }

    /**
     * Show settings menu
     */
    private function showSettings(): void
    {
        $user = $this->getUser();
        $settings = $this->getUserSettings();

        $status = $settings['enabled'] ? '✅ Aktiv' : '❌ Deaktiv';
        $categories = implode(', ', $settings['categories'] ?? ['Heç biri']);
        $time = $settings['notification_time'] ?? 'Təyin edilməyib';

        $buttons = [
            [
                'text' => $settings['enabled'] ? '❌ Deaktiv et' : '✅ Aktiv et',
                'callback' => 'notifications',
                'params' => ['action' => $settings['enabled'] ? 'disable' : 'enable']
            ],
            '---',
            [
                'text' => '📂 Kateqoriyaları seç',
                'callback' => 'category',
                'params' => ['action' => 'select']
            ],
            [
                'text' => '🕒 Bildiriş vaxtını seç',
                'callback' => 'time',
                'params' => ['action' => 'select']
            ],
            '---',
            [
                'text' => '◀️ Geri',
                'callback' => 'back',
                'params' => []
            ]
        ];

        $this->telegram->sendMessage(
            "⚙️ *Parametrlər*\n\n" .
            "Status: $status\n" .
            "Kateqoriyalar: $categories\n" .
            "Bildiriş vaxtı: $time",
            [
                'reply_markup' => $this->telegram->inlineKeyboard($buttons),
                'parse_mode' => 'Markdown'
            ]
        );
    }

    /**
     * Show help message
     */
    private function showHelp(): void
    {
        $message = "❓ *Kömək*\n\n" .
            "Bot əmrləri:\n" .
            "/start - Botu başlat\n" .
            "/settings - Parametrləri göstər\n" .
            "/help - Kömək menyusu\n" .
            "/stop - Bildirişləri dayandır\n\n" .
            "Əlavə suallarınız üçün support@example.com ilə əlaqə saxlaya bilərsiniz.";

        $buttons = [[
            'text' => '◀️ Geri',
            'callback' => 'back',
            'params' => []
        ]];

        $this->telegram->sendMessage($message, [
            'reply_markup' => $this->telegram->inlineKeyboard($buttons),
            'parse_mode' => 'Markdown'
        ]);
    }

    /**
     * Handle notification toggle
     */
    private function handleNotificationToggle(array $params): void
    {
        $action = $params['action'] ?? '';
        $settings = $this->getUserSettings();

        if ($action === 'enable') {
            $settings['enabled'] = true;
            $message = "✅ Bildirişlər aktivləşdirildi!";
        } else {
            $settings['enabled'] = false;
            $message = "❌ Bildirişlər deaktiv edildi!";
        }

        $this->setUserSettings($settings);
        $this->telegram->sendMessage($message);
        $this->showSettings();
    }

    /**
     * Handle category selection
     */
    private function handleCategoryCallback(array $params): void
    {
        $categories = [
            'news' => '📰 Xəbərlər',
            'events' => '📅 Tədbirlər',
            'updates' => '🔄 Yeniliklər',
            'alerts' => '⚠️ Xəbərdarlıqlar'
        ];

        $settings = $this->getUserSettings();
        $selectedCategories = $settings['categories'] ?? [];

        $buttons = [];
        foreach ($categories as $key => $name) {
            $status = in_array($key, $selectedCategories) ? '✅' : '⬜️';
            $buttons[] = [
                'text' => "$status $name",
                'callback' => 'category',
                'params' => ['action' => 'toggle', 'category' => $key]
            ];
            if (count($buttons) % 2 === 0) {
                $buttons[] = '---';
            }
        }

        $buttons[] = [
            'text' => '✅ Təsdiqlə',
            'callback' => 'settings',
            'params' => []
        ];

        $this->telegram->sendMessage(
            "📂 *Bildiriş kateqoriyalarını seçin:*\n\n" .
            "Seçilmiş kateqoriyalar:\n" .
            implode(", ", array_map(fn($cat) => $categories[$cat], $selectedCategories)),
            [
                'reply_markup' => $this->telegram->inlineKeyboard($buttons),
                'parse_mode' => 'Markdown'
            ]
        );
    }

    /**
     * Handle time selection
     */
    private function handleTimeCallback(array $params): void
    {
        $times = [
            '09:00', '12:00', '15:00', '18:00', '21:00'
        ];

        $settings = $this->getUserSettings();
        $selectedTime = $settings['notification_time'] ?? '';

        $buttons = [];
        foreach ($times as $time) {
            $status = $time === $selectedTime ? '✅' : '⬜️';
            $buttons[] = [
                'text' => "$status $time",
                'callback' => 'time',
                'params' => ['action' => 'set', 'time' => $time]
            ];
            if (count($buttons) % 2 === 0) {
                $buttons[] = '---';
            }
        }

        $buttons[] = [
            'text' => '◀️ Geri',
            'callback' => 'settings',
            'params' => []
        ];

        $this->telegram->sendMessage(
            "🕒 *Bildiriş vaxtını seçin:*\n\n" .
            "Cari vaxt: " . ($selectedTime ?: 'Təyin edilməyib'),
            [
                'reply_markup' => $this->telegram->inlineKeyboard($buttons),
                'parse_mode' => 'Markdown'
            ]
        );
    }

    /**
     * Get user settings
     */
    private function getUserSettings(): array
    {
        $key = "notification_settings_{$this->userId}";
        return Cache::get($key, [
            'enabled' => false,
            'categories' => [],
            'notification_time' => null
        ]);
    }

    /**
     * Set user settings
     */
    private function setUserSettings(array $settings): void
    {
        $key = "notification_settings_{$this->userId}";
        Cache::put($key, $settings, now()->addYear());
    }

    /**
     * Get or create user
     */
    private function getUser(): User
    {
        return Cache::remember("telegram_user_{$this->userId}", now()->addDay(), function () {
            return User::firstOrCreate(
                ['telegram_id' => $this->userId],
                [
                    'name' => $this->firstName,
                    'surname' => $this->lastName,
                    'username' => $this->username,
                ]
            );
        });
    }

    /**
     * Disable all notifications
     */
    private function disableNotifications(): void
    {
        $settings = $this->getUserSettings();
        $settings['enabled'] = false;
        $this->setUserSettings($settings);

        $this->telegram->sendMessage(
            "❌ Bütün bildirişlər deaktiv edildi.\n" .
            "Yenidən aktivləşdirmək üçün /start əmrindən istifadə edin."
        );
    }
}
