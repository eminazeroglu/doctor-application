<?php

namespace App\Providers;

use App\Services\App\System\ConfigurationLoaderService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Bu metod service container-ə xidmətləri qeydiyyatdan keçirir
     */
    public function register(): void
    {
        // ConfigurationLoaderService-i singleton olaraq qeydiyyatdan keçiririk
        // Bu o deməkdir ki, tətbiq boyunca bu servisin yalnız bir nümunəsi olacaq
        $this->app->singleton(ConfigurationLoaderService::class);
    }

    /**
     * Bootstrap any application services.
     * Bu metod application boot olduqdan sonra işləyir
     * @throws Exception
     */
    public function boot(): void
    {
        // Console əmrləri zamanı və ya miqrasiya prosesi zamanı konfiqurasiyaları yeniləməyə ehtiyac yoxdur
        if ($this->app->runningInConsole() || $this->isRunningMigration()) {
            return;
        }

        try {
            // ConfigurationLoaderService-i yaradıb konfiqləri yükləyirik
            $this->loadConfigurations();
        } catch (Exception $e) {
            // Xətaları idarə edirik
            $this->handleError($e);
        }
    }

    /**
     * Yoxlayır ki, mövcud request miqrasiya əməliyyatı ilə əlaqəlidirmi
     */
    private function isRunningMigration(): bool
    {
        if (!$this->app->runningInConsole()) {
            return false;
        }

        $command = request()->server('argv')[1] ?? '';
        return str_contains($command, 'migrate');
    }

    /**
     * Konfiqurasiyaları yükləyir
     */
    private function loadConfigurations(): void
    {
        if (config('cache.default') === 'database') {
            try {
                if (!Schema::hasTable('cache')) {
                    Config::set('cache.default', 'file');
                    Log::info('Cache driver switched to file because cache table does not exist yet');
                }
            } catch (Exception $e) {
                // Veritabanı xətası halında file cache-ə keçirik
                Config::set('cache.default', 'file');
                Log::info('Cache driver switched to file due to database error: ' . $e->getMessage());
            }
        }

        // ConfigurationLoaderService-i container-dən alırıq
        $loader = $this->app->make(ConfigurationLoaderService::class);

        // Əgər cache-də konfiq varsa və debug mode aktiv deyilsə, cache-dən istifadə edirik
        if (Cache::has('app_settings') && !config('app.debug')) {
            $settings = Cache::get('app_settings');
            foreach ($settings as $key => $value) {
                Config::set($key, $value);
            }
            return;
        }

        // Konfiqləri yükləyirik
        $loader->load();
    }

    /**
     * Xətaları idarə edir
     */
    private function handleError(Exception $e): void
    {
        // Production mühitində xətaları loglayırıq
        if ($this->app->environment('production')) {
            Log::error('Settings could not be loaded: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Default konfiqləri istifadə edirik
            $this->loadDefaultConfigurations();
        } else {
            // Development mühitində xətanı göstəririk
            throw $e;
        }
    }

    /**
     * Default konfiqləri yükləyir (xəta baş verdikdə)
     */
    private function loadDefaultConfigurations(): void
    {
        Config::set([
            'app.name' => env('APP_NAME', 'Laravel'),
            'app.env' => env('APP_ENV', 'production'),
            'app.debug' => env('APP_DEBUG', false),
            'app.url' => env('APP_URL', 'http://localhost'),
            'app.timezone' => env('APP_TIMEZONE', 'UTC'),
            'app.locale' => env('APP_LOCALE', 'en'),
            'app.fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

            'mail.default' => env('MAIL_MAILER', 'smtp'),
            'mail.mailers.smtp.host' => env('MAIL_HOST'),
            'mail.mailers.smtp.port' => env('MAIL_PORT'),
            'mail.from.address' => env('MAIL_FROM_ADDRESS'),
            'mail.from.name' => env('MAIL_FROM_NAME'),

            'session.driver' => env('SESSION_DRIVER', 'file'),
            'session.lifetime' => env('SESSION_LIFETIME', 120),

            'database.default' => env('DB_CONNECTION', 'mysql'),

            'cache.default' => env('CACHE_DRIVER', 'file'),

            'queue.default' => env('QUEUE_CONNECTION', 'sync'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     * Laravel-ə bu provider-in hansı xidmətləri təmin etdiyini bildirir
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [ConfigurationLoaderService::class];
    }

    /**
     * Determine if provider is deferred.
     * Provider-in təxirə salınıb-salınmadığını müəyyən edir
     */
    public function isDeferred(): bool
    {
        return false;
    }
}
