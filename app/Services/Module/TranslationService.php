<?php

namespace App\Services\Module;

use App\Models\Language;
use App\Models\Translate;
use App\Helpers\Helper;
use App\Repositories\Module\TranslationRepository;
use App\Services\BaseCrudService;
use Illuminate\Support\Facades\Cache;

class TranslationService extends BaseCrudService
{
    public function __construct(TranslationRepository $repository)
    {
        parent::__construct($repository);
    }

    public function save($data): true
    {
        foreach ($data['translation'] as $locale => $value) {
            $this->setTranslation($data['key'], $value, $locale);
        }
        return true;
    }

    public function destroy($key)
    {
        return Translate::query()->where('key', $key)->where('is_system', false)->forceDelete();
    }

    public function getTranslation($key, $locale = null, $default = null, $notCached = false)
    {
        $locale = $locale ?: Helper::language();
        $cacheKey = $this->getCacheKey($key, $locale);

        if ($notCached) {
            $translation = Translate::getTranslation($key, $locale);
            return $translation !== $key ? $translation : ($default ?: $key);
        }

        return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $locale, $default) {
            $translation = Translate::getTranslation($key, $locale);
            return $translation !== $key ? $translation : ($default ?: $key);
        });
    }

    public function setTranslation($key, $value, $locale = null)
    {
        $locale = $locale ?: Helper::language();
        $translation = Translate::setTranslation($key, $value, $locale);
        $this->clearTranslationCache($key, $locale);
        return $translation;
    }

    public function clearTranslationCache($key, $locale = null): void
    {
        $locale = $locale ?: Helper::language();
        $cacheKey = $this->getCacheKey($key, $locale);
        Cache::forget($cacheKey);
    }

    public function clearAllTranslationCache(): void
    {
        Cache::flush();
    }

    protected function getCacheKey($key, $locale): string
    {
        return "translation:{$locale}:{$key}";
    }

    public function getAllLanguages(): \Illuminate\Database\Eloquent\Collection
    {
        return Language::all();
    }

    public function getActiveLanguages()
    {
        return Language::active()->get();
    }

    public function getActiveLanguagesWithTranslate()
    {
        return Language::active()->with('translates')->get();
    }

    public function setDefaultLanguage($locale)
    {
        return Language::setDefault($locale);
    }

    public function importTranslations(array $translations, $locale = null): void
    {
        $locale = $locale ?: Helper::language();
        foreach ($translations as $key => $value) {
            $this->setTranslation($key, $value, $locale);
        }
    }

    public function exportTranslations($locale = null)
    {
        $locale = $locale ?: Helper::language();
        return Translate::where('locale', $locale)->pluck('value', 'key')->toArray();
    }

    public function getCurrentLanguage(): string
    {
        return Helper::language();
    }
}
