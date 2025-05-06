<?php

namespace App\Providers;

use App\Models\MailTemplate;
use App\Observers\MailTemplateObserver;
use Exception;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Http\Routing\ApiResourceRegister;
use App\Repositories\BaseRepository;
use App\Repositories\Interface\BaseRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Servislərin qeydiyyatı
    }

    /**
     * Bootstrap any application services.
     * @throws Exception
     */
    public function boot(): void
    {
        // String sütunlar üçün default uzunluq təyin edirik
        Schema::defaultStringLength(191);

        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);

        $this->app->bind(ResourceRegistrar::class, function ($app) {
            return new ApiResourceRegister($app['router']);
        });

        // Production mühitində HTTPS-i məcburi edirik
        if (app()->environment() !== 'local') {
            url()->forceScheme('https');
        }

        // Console əmrləri zamanı konfiqurasiyaları yeniləməyə ehtiyac yoxdur
        if (!$this->app->runningInConsole()) {
            $this->updateAllConfigurations();
        }
    }

    /**
     * Bütün konfiqurasiyaları yeniləyir.
     * Bu metod runtime-da konfiq dəyərlərini setting-lərdən gələn dəyərlərlə əvəz edir.
     * @throws Exception
     */
    private function updateAllConfigurations(): void
    {
        try {
            // Site məlumatları və əsas konfiqurasiyalar
            $this->updateAppConfiguration();

            // Email sistemi konfiqurasiyaları
            $this->updateMailConfiguration();

            // Sistem səviyyəli konfiqurasiyalar
            $this->updateSystemConfiguration();

            // Upload və fayl sistemi konfiqurasiyaları
            $this->updateUploadConfiguration();

            // Təhlükəsizlik tənzimləmələri
            $this->updateSecurityConfiguration();

            // Cache sistemi konfiqurasiyaları
            $this->updateCacheConfiguration();

        } catch (Exception $e) {
            // Production mühitində xətaları loglayırıq
            if (app()->environment('production')) {
                logger()->error('Configuration update failed: ' . $e->getMessage());
            } else {
                // Development mühitində xətanı göstəririk
                throw $e;
            }
        }
    }

    /**
     * Sayt və tətbiq əsas konfiqurasiyalarını yeniləyir
     */
    private function updateAppConfiguration(): void
    {
        Config::set([
            'app.name' => setting('site_info.name.' . app()->getLocale(), env('APP_NAME')),
            'app.env' => setting('system.environment', env('APP_ENV')),
            'app.debug' => (bool)setting('system.debug_mode', env('APP_DEBUG')),
            'app.timezone' => setting('system.timezone', env('APP_TIMEZONE')),
            'app.locale' => setting('system.default_language', env('APP_LOCALE')),
            'app.fallback_locale' => env('APP_FALLBACK_LOCALE'),
            'app.url' => env('APP_URL')
        ]);
    }

    /**
     * Email sistemi konfiqurasiyalarını yeniləyir
     */
    private function updateMailConfiguration(): void
    {
        Config::set([
            'mail.default' => setting('mail.driver', env('MAIL_MAILER')),
            'mail.mailers.smtp.host' => setting('mail.host', env('MAIL_HOST')),
            'mail.mailers.smtp.port' => (int)setting('mail.port', env('MAIL_PORT')),
            'mail.mailers.smtp.encryption' => setting('mail.encryption', env('MAIL_ENCRYPTION')),
            'mail.mailers.smtp.username' => setting('mail.username', env('MAIL_USERNAME')),
            'mail.mailers.smtp.password' => setting('mail.password', env('MAIL_PASSWORD')),
            'mail.from.address' => setting('mail.from_address', env('MAIL_FROM_ADDRESS')),
            'mail.from.name' => setting('mail.from_name', env('MAIL_FROM_NAME'))
        ]);
    }

    /**
     * Sistem səviyyəli konfiqurasiyaları yeniləyir
     */
    private function updateSystemConfiguration(): void
    {
        // Sistem parametrləri
        Config::set([
            'app.maintenance_mode' => setting('system.maintenance_mode', false),
            'app.date_format' => setting('system.date_format', 'Y-m-d'),
            'app.time_format' => setting('system.time_format', 'H:i:s')
        ]);

        // Queue sistemi
        Config::set([
            'queue.default' => setting('system.queue.default', env('QUEUE_CONNECTION')),
            'queue.failed.retention_days' => setting('system.queue.failed_job_retention_days', 7)
        ]);
    }

    /**
     * Upload və fayl sistemi konfiqurasiyalarını yeniləyir
     */
    private function updateUploadConfiguration(): void
    {
        Config::set([
            'filesystems.default' => setting('upload.storage_driver', env('FILESYSTEM_DISK')),
            'filesystems.disks.local.visibility' => 'public'
        ]);
    }

    /**
     * Təhlükəsizlik tənzimləmələrini yeniləyir
     */
    private function updateSecurityConfiguration(): void
    {
        Config::set([
            'session.lifetime' => setting('security.session_lifetime', env('SESSION_LIFETIME', 120)),
            'auth.passwords.timeout' => setting('security.password_timeout', 10800),
            'auth.password_timeout' => setting('security.password_timeout', 10800)
        ]);
    }

    /**
     * Cache sistemi konfiqurasiyalarını yeniləyir
     */
    private function updateCacheConfiguration(): void
    {
        if (setting('system.cache.enabled', true)) {
            Config::set([
                'cache.default' => setting('system.cache.driver', env('CACHE_DRIVER')),
                'cache.prefix' => setting('system.cache.prefix', env('CACHE_PREFIX', ''))
            ]);
        }
    }
}
