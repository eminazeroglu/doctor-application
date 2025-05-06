<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $message;
    protected ?array $users;
    protected ?array $categories;
    protected ?array $keyboard;
    protected array $extra;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $message,
        ?array $users = null,
        ?array $categories = null,
        ?array $keyboard = null,
        array $extra = []
    ) {
        $this->message = $message;
        $this->users = $users;
        $this->categories = $categories;
        $this->keyboard = $keyboard;
        $this->extra = $extra;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get users to notify
        $query = User::query()
            ->whereNotNull('telegram_id')
            ->where('is_active', true);

        // Filter by specific users if provided
        if ($this->users) {
            $query->whereIn('id', $this->users);
        }

        // Get users in chunks to avoid memory issues
        $query->chunk(100, function (Collection $users) {
            foreach ($users as $user) {
                // Check user notification settings
                $settings = cache()->get("notification_settings_{$user->telegram_id}", []);

                // Skip if notifications are disabled
                if (!($settings['enabled'] ?? false)) {
                    continue;
                }

                // Check if user subscribed to notification category
                if ($this->categories && !empty($settings['categories'])) {
                    $hasCategory = false;
                    foreach ($this->categories as $category) {
                        if (in_array($category, $settings['categories'])) {
                            $hasCategory = true;
                            break;
                        }
                    }
                    if (!$hasCategory) {
                        continue;
                    }
                }

                // Send notification
                $user->notify(new TelegramNotification(
                    $this->message,
                    'HTML',
                    $this->keyboard,
                    $this->extra
                ));
            }
        });
    }
}
