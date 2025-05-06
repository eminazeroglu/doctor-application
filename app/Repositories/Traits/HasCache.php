<?php

namespace App\Repositories\Traits;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

trait HasCache
{

    protected function isCacheTableExists(): bool
    {
        try {
            // Veritabanı bağlantısını ve cache tablosunun varlığını kontrol et
            return Schema::hasTable('cache');
        } catch (\Exception $e) {
            // Herhangi bir sorun olursa (veritabanı bağlantı sorunu dahil) false döndür
            return false;
        }
    }

    public function remember(string $key, \Closure $callback)
    {
        if (!$this->useCache || !$this->isCacheTableExists()) {
            return $callback();
        }

        // Burada cache key yaratma məntiqi eyni olmalıdır
        $cacheKey = $this->getCacheKey($key);

        if ($this->supportsTags()) {
            return Cache::tags($this->model->getTable())
                ->remember($cacheKey, $this->cacheTtl, $callback);
        }

        return Cache::remember($cacheKey, $this->cacheTtl, $callback);
    }

    public function invalidateCache(): void
    {
        if (!$this->useCache || !$this->isCacheTableExists()) {
            return;
        }

        if ($this->supportsTags()) {
            Cache::tags($this->model->getTable())->flush();
            return;
        }

        // Bütün cache-i təmizləyirik
        Cache::flush();
    }

    public function clearCache(?string $key = null): void
    {
        if (!$this->useCache || !$this->isCacheTableExists()) {
            return;
        }

        if ($key) {
            // Konkret key üçün cache təmizləmə
            $cacheKey = $this->getCacheKey($key);

            if ($this->supportsTags()) {
                Cache::tags($this->model->getTable())->forget($cacheKey);
            } else {
                Cache::forget($cacheKey);
            }
            return;
        }

        // Key verilməyibsə bütün cache-i təmizləyirik
        $this->invalidateCache();
    }

    public function getCacheKey(string $key, array $params = []): string
    {
        $baseKey = sprintf('%s_%s', $this->model->getTable(), $key);

        // Əgər parametrlər varsa, onları da key-ə əlavə edirik
        if (!empty($params)) {
            ksort($params);
            $paramsKey = md5(json_encode($params));
            $baseKey .= '_' . $paramsKey;
        }

        return $baseKey;
    }

    public function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }

    /**
     * Execute a callback function with caching
     *
     * @param string $key
     * @param \Closure $callback
     * @param array $params
     * @return mixed
     */
    public function executeWithCache(string $key, \Closure $callback, array $params = []): mixed
    {
        if (!$this->useCache || !$this->isCacheTableExists()) {
            return $callback();
        }

        $cacheKey = $this->getCacheKey($key, $params);

        return $this->remember($cacheKey, $callback);
    }
}
