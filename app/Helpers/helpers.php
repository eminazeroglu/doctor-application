<?php

use App\Services\Module\TranslationService;
use App\Helpers\Helper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (!function_exists('t')) {
    function t($key, $locale = null, $default = null, $notCached = false)
    {
        $translationService = app(TranslationService::class);
        $locale = $locale ?: Helper::language();
        return $translationService->getTranslation($key, $locale, $default, $notCached);
    }
}

if (!function_exists('t_replace')) {
    function t_replace($key, $replacement, $locale = null, $default = null)
    {
        $locale = $locale ?: Helper::language();
        $translation = t($key, $locale, $default);
        if (count($replacement) > 0) {
            foreach ($replacement as $key => $value) {
                $translation = str_replace($key, $value, $translation);
            }
        }
        return $translation;
    }
}

if (!function_exists('currentLang')) {
    function currentLang(): string
    {
        return Helper::language();
    }
}

if (!function_exists('setting')) {
    function setting($key = null, $default = null)
    {
        $service = app(App\Services\Module\SettingService::class);

        if ($key === null) {
            return $service;
        }

        if (str_contains($key, '.')) {
            [$group, $path] = explode('.', $key, 2);
            return $service->get($group, $path, $default);
        }

        return $service->get($key, null, $default);
    }
}
