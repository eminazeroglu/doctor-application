<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
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
            // Model::observe(ModelObserver::class);
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
