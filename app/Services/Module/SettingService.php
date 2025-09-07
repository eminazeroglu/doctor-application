<?php

namespace App\Services\Module;

use App\Repositories\Module\SettingRepository;
use App\Services\App\Upload\ImageUploadService;
use App\Services\BaseCrudService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService extends BaseCrudService
{
    private const SETTINGS_CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 86400; // 24 saat

    public function __construct(SettingRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Bütün tənzimləmələri qaytarır
     */
    public function all()
    {
        return Cache::remember(self::SETTINGS_CACHE_KEY, self::CACHE_TTL, function () {
            return $this->repository->findAll();
        });
    }

    /**
     * Konkret tənzimləməni və ya onun alt parametrini qaytarır
     */
    public function get(string $key, ?string $path = null, $default = null)
    {
        $cacheKey = "{$key}" . ($path ? ".{$path}" : '');

        return Cache::remember(
            self::SETTINGS_CACHE_KEY . '.' . $cacheKey,
            self::CACHE_TTL,
            function () use ($key, $path, $default) {
                // Əsas setting məlumatlarını əldə edirik
                $setting = $this->repository->getValue($key, $path, $default);

                // Əgər info settingidirsə və tam obyekt qayıdırsa
                if ($setting && $key === 'info' && !$path) {
                    $imageFields = [
                        'logo',
                        'logo_dark',
                        'mobile_logo',
                        'mobile_logo_dark',
                        'favicon',
                        'wallpaper',
                        'join_us_wallpaper',
                        'watermark'
                    ];

                    $imageService = app(ImageUploadService::class);
                    $imageService->setSettingsMode(true);

                    // Hər bir şəkil sahəsi üçün path əlavə edirik
                    foreach ($imageFields as $field) {
                        if (isset($setting[$field])) {
                            // Original adı saxlayırıq
                            $originalValue = $setting[$field];

                            // Tam URL-ləri ayrı sahədə əlavə edirik
                            $setting[$field.'_path'] = optional($imageService->getPhoto(
                                'setting',
                                $originalValue,
                                'default_photo.webp'
                            ))['original'];
                        }
                    }
                }

                return $setting;
            }
        );
    }

    /**
     * Tənzimləmə dəyərini yeniləyir
     */
    public function set(string $key, $value, ?string $path = null)
    {
        $result = $path
            ? $this->repository->setValue($key, $path, $value)
            : $this->repository->updateOrCreate($key, $value);

        // Cache-i təmizləyirik
        $this->clearSettingsCache();

        return $result;
    }

    /**
     * Settings cache-ni təmizləyir
     */
    public function clearSettingsCache(): void
    {
        Artisan::call('repo:clear');
    }

    public function export(): array
    {
        return $this->repository->all()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'values' => $setting->values
            ];
        })->toArray();
    }

    public function import(array $settings): void
    {
        DB::transaction(function () use ($settings) {
            foreach ($settings as $setting) {
                $this->set($setting['key'], $setting['values']);
            }
        });
    }

}
