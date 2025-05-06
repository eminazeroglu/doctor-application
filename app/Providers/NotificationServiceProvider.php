<?php

namespace App\Providers;

use App\Services\Module\NotificationService;
use App\Services\Notification\Channels\{EmailChannel, PushChannel};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->isDatabaseConnected()) {
            $service = $this->app->make(NotificationService::class);

            // Kanalları qeydiyyatdan keçiririk
            $service->registerChannel('email', new EmailChannel());
            // $service->registerChannel('telegram', new TelegramChannel(
            //     $this->app->make(TelegramService::class)
            // ));
            $service->registerChannel('push', new PushChannel());
        }
    }

    /**
     * Verilənlər bazası ilə əlaqə qurulub-qurulmadığını yoxlayır
     *
     * @return bool
     */
    protected function isDatabaseConnected(): bool
    {
        try {
            // Sadə bir sorğu ilə əlaqəni yoxlayırıq
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            // Əlaqə zamanı xəta yaranarsa, false qaytarırıq
            return false;
        }
    }
}
