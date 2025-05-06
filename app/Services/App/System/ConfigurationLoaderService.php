<?php

namespace App\Services\App\System;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigurationLoaderService
{
    /**
     * Konfiqurasiya yeniləmələrini idarə edən əsas metod.
     * Console əmrləri zamanı işləmir.
     */
    public function load(): void
    {
        if (!app()->runningInConsole()) {
            $this->updateAllConfigurations();
        }
    }

    /**
     * Bütün konfiqurasiyaları yeniləyən əsas metod.
     * Cache mexanizmindən istifadə edir və xətaları idarə edir.
     */
    private function updateAllConfigurations(): void
    {
        try {
            // Cache-dən konfiqləri yoxlayırıq
            if (Cache::has('app_settings')) {
                $this->loadFromCache();
                return;
            }

            // Cache-də yoxdursa, yeni konfiqləri hazırlayırıq
            $settings = $this->prepareAllSettings();

            // Cache-ə yazırıq (1 saat müddətinə)
            Cache::put('app_settings', $settings, now()->addHour());

            // Konfiqləri yeniləyirik
            $this->applySettings($settings);

        } catch (Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Cache-dən konfiqləri yükləyir
     */
    private function loadFromCache(): void
    {
        $settings = Cache::get('app_settings');
        $this->applySettings($settings);
    }

    /**
     * Bütün lazımi konfiqləri hazırlayır
     */
    private function prepareAllSettings(): array
    {
        return array_merge(
            $this->prepareAppSettings(),
            $this->prepareMailSettings(),
            $this->prepareSocialSettings(),
            $this->prepareUploadSettings(),
            $this->prepareCacheSettings(),
            $this->prepareQueueSettings(),
            $this->prepareSecuritySettings(),
            $this->prepareDatabaseSettings(),
            $this->prepareSessionSettings()
        );
    }

    /**
     * Application əsas konfiqləri
     */
    private function prepareAppSettings(): array
    {
        return [
            'app.name' => setting('info.translates.' . app()->getLocale() . '.name', env('APP_NAME')),
            'app.env' => setting('system.environment', env('APP_ENV')),
            'app.debug' => (bool)setting('system.debug_mode', env('APP_DEBUG')),
            'app.timezone' => setting('system.timezone', env('APP_TIMEZONE')),
            'app.locale' => setting('system.default_language', env('APP_LOCALE')),
            'app.fallback_locale' => env('APP_FALLBACK_LOCALE'),
            'app.url' => env('APP_URL')
        ];
    }

    /**
     * Mail sistemi konfiqləri
     */
    private function prepareMailSettings(): array
    {
        return [
            'mail.default' => setting('mail.driver', env('MAIL_MAILER')),
            'mail.mailers.smtp.host' => setting('mail.host', env('MAIL_HOST')),
            'mail.mailers.smtp.port' => (int)setting('mail.port', env('MAIL_PORT')),
            'mail.mailers.smtp.encryption' => setting('mail.encryption', env('MAIL_ENCRYPTION')),
            'mail.mailers.smtp.username' => setting('mail.username', env('MAIL_USERNAME')),
            'mail.mailers.smtp.password' => setting('mail.password', env('MAIL_PASSWORD')),
            'mail.from.address' => setting('mail.from_address', env('MAIL_FROM_ADDRESS')),
            'mail.from.name' => setting('mail.from_name', env('MAIL_FROM_NAME'))
        ];
    }

    /**
     * Social platformalar üçün konfiqlər
     */
    private function prepareSocialSettings(): array
    {
        return [
            // Telegram
            'services.telegram.token' => setting('social_page.telegram.token'),
            'services.telegram.webhook_url' => setting('social_page.telegram.webhook_url'),
            'services.telegram.chat_id' => setting('social_page.telegram.chat_id'),
            'services.telegram.api_url' => 'https://api.telegram.org/bot',
            'services.telegram.file_url' => 'https://api.telegram.org/file/bot',

            // Google
            'services.google.client_id' => setting('social_page.google.client_id'),
            'services.google.client_secret' => setting('social_page.google.client_secret'),
            'services.google.redirect' => setting('social_page.google.redirect'),

            // Facebook
            'services.facebook.client_id' => setting('social_page.facebook.client_id'),
            'services.facebook.client_secret' => setting('social_page.facebook.client_secret'),
            'services.facebook.redirect' => setting('social_page.facebook.redirect'),

            // LinkedIn
            'services.linkedin.client_id' => setting('social_page.linkedin.client_id'),
            'services.linkedin.client_secret' => setting('social_page.linkedin.client_secret'),
            'services.linkedin.redirect' => setting('social_page.linkedin.redirect')
        ];
    }

    /**
     * Fayl yükləmə konfiqləri
     */
    private function prepareUploadSettings(): array
    {
        return [
            'filesystems.default' => setting('upload.storage_driver', env('FILESYSTEM_DISK')),
            'filesystems.disks.local.visibility' => 'public',
            'filesystems.disks.local.throw' => false
        ];
    }

    /**
     * Cache sistemi konfiqləri
     */
    private function prepareCacheSettings(): array
    {
        if (!setting('system.cache.enabled', true)) {
            return [];
        }

        return [
            'cache.default' => setting('system.cache.driver', env('CACHE_DRIVER')),
            'cache.prefix' => setting('system.cache.prefix', env('CACHE_PREFIX')),
            'cache.ttl' => setting('system.cache.ttl', 3600)
        ];
    }

    /**
     * Queue sistemi konfiqləri
     */
    private function prepareQueueSettings(): array
    {
        return [
            'queue.default' => setting('system.queue.default', env('QUEUE_CONNECTION')),
            'queue.failed.retention_days' => setting('system.queue.failed_job_retention_days', 7)
        ];
    }

    /**
     * Təhlükəsizlik konfiqləri
     */
    private function prepareSecuritySettings(): array
    {
        $settings = [
            'session.lifetime' => setting('security.login_lockout_time', env('SESSION_LIFETIME', 120)),
            'auth.passwords.timeout' => setting('security.password_timeout', 10800),
            'auth.password_timeout' => setting('security.password_timeout', 10800),
            'sanctum.token_prefix' => env('SANCTUM_TOKEN_PREFIX', '')
        ];

        // Rate limiting aktiv olduqda əlavə konfiqləri əlavə edirik
        if (setting('security.api_rate_limit.enabled', true)) {
            $settings = array_merge($settings, [
                'rate_limiting.enabled' => true,
                'rate_limiting.max_attempts' => setting('security.api_rate_limit.max_attempts', 60),
                'rate_limiting.decay_minutes' => setting('security.api_rate_limit.decay_minutes', 1)
            ]);
        }

        return $settings;
    }

    /**
     * Database konfiqləri
     */
    private function prepareDatabaseSettings(): array
    {
        return [
            'database.connections.mysql.strict' => false,
            'database.migrations.update_date_on_publish' => true
        ];
    }

    /**
     * Session konfiqləri
     */
    private function prepareSessionSettings(): array
    {
        return [
            'session.driver' => env('SESSION_DRIVER', 'database'),
            'session.lifetime' => setting('security.login_lockout_time', env('SESSION_LIFETIME', 120)),
            'session.expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
            'session.encrypt' => env('SESSION_ENCRYPT', false),
            'session.cookie' => env('SESSION_COOKIE', 'laravel_session'),
            'session.path' => env('SESSION_PATH', '/'),
            'session.domain' => env('SESSION_DOMAIN', null),
            'session.secure' => env('SESSION_SECURE_COOKIE', true),
            'session.same_site' => env('SESSION_SAME_SITE', 'lax')
        ];
    }

    /**
     * Konfiqləri sistemə tətbiq edir
     */
    private function applySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Config::set($key, $value);
        }
    }

    /**
     * Xətaları idarə edir
     */
    private function handleError(Exception $e): void
    {
        if (app()->environment('production')) {
            Log::error('Configuration update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            throw $e;
        }
    }

    /**
     * Cache-i təmizləyir
     */
    public function clearCache(): void
    {
        Cache::forget('app_settings');
    }
}
