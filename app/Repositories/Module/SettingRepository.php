<?php

namespace App\Repositories\Module;

use App\Models\Setting;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Artisan;

class SettingRepository extends BaseRepository
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    // Konkret setting-i key ilə tapmaq
    public function findByKey(string $key)
    {
        return $this->findOneWhere(['key' => $key]);
    }

    // Konkret dəyəri path ilə tapmaq
    public function getValue(string $key, string $path = null, $default = null)
    {
        // BaseRepository-nin cache mexanizmindən istifadə edirik
        return $this->executeWithCache("getValue.{$key}.{$path}", function () use ($key, $path, $default) {
            $setting = $this->findByKey($key);

            if (!$setting) {
                return $default;
            }

            return $setting->getValue($path, $default);
        });
    }

    // Setting-i yeniləmək və ya yaratmaq
    public function updateOrCreate(string $key, array $values)
    {
        $result = $this->model->updateOrCreate(
            ['key' => $key],
            ['values' => $values]
        );

        Artisan::call("repo:clear");

        // Cache-i təmizləmək üçün BaseRepository metodunu istifadə edirik
        if ($this->useCache) {
            $this->clearCache();
        }

        return $result;
    }

    // Konkret path üçün dəyəri yeniləmək
    public function setValue(string $key, string $path, $value)
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            $setting = $this->model->create([
                'key' => $key,
                'values' => []
            ]);
        }

        $setting->setValue($path, $value)->save();

        // Cache-i təmizləmək üçün BaseRepository metodunu istifadə edirik
        if ($this->useCache) {
            $this->clearCache();
        }

        return $setting;
    }

}
