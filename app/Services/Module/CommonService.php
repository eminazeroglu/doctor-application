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
