<?php

namespace App\Services\Module;

use App\Helpers\Helper;
use App\Http\Resources\Admin\ReferenceResource;

class CommonService
{
    /**
     * Start
    */
    public function start (): array
    {
        $languages = app(TranslationService::class)->getAllLanguages();
        $settingSocialMedia = collect(setting('socialMedia'))->where('active', true)->toArray();
        $settingGeneral = collect(setting('info'))->toArray();
        $socialMedia = [];
        foreach ($settingSocialMedia as $key => $value) {
            $socialMedia[$key] = $value['url'];
        }
        return [
            'socialMedia' => $socialMedia,
            'language' => setting('system.default_language'),
            'languages' => ReferenceResource::collection($languages),
            'photos' => [
                'logo_path' => $settingGeneral['logo_path'] ?? null,
                'logo_dark_path' => $settingGeneral['logo_dark_path'] ?? null,
                'mobile_logo_path' => $settingGeneral['mobile_logo_path'] ?? null,
                'mobile_logo_dark_path' => $settingGeneral['mobile_logo_dark_path'] ?? null,
                'favicon_path' => $settingGeneral['favicon_path'] ?? null,
                'wallpaper_path' => $settingGeneral['wallpaper_path'] ?? null,
                'join_us_wallpaper_path' => $settingGeneral['join_us_wallpaper_path'] ?? null,
                'app_qr_path' => $settingGeneral['app_qr_path'] ?? null,
            ],
            'general' => [
                ...$settingGeneral,
                'address' => @$settingGeneral['translates'][Helper::language()]['address'],
                'name' => @$settingGeneral['translates'][Helper::language()]['name'],
                'description' => @$settingGeneral['translates'][Helper::language()]['description'],
            ],
            'image_formats' => collect(setting('upload.allowed_file_types.image'))->map(function ($i) {
                $format = 'image/' . $i;
                if ($i === 'svg') $format = $format . '+xml';
                return $format;
            }),
            'file_formats' => setting('upload.allowed_file_types.document')
        ];
    }
}
