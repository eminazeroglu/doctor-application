<?php

namespace App\Rules;

use App\Services\Module\SettingService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileImageControlRule implements ValidationRule
{
    /**
     * @var SettingService
     */
    protected SettingService $settingService;

    public function __construct()
    {
        $this->settingService = app(SettingService::class);
    }

    /**
     * Şəkil validasiyası
     * - Settings-dən format və həcm məlumatlarını alır
     * - Base64 şəklinin formatını və həcmini yoxlayır
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail(t('validation.invalidFileType'));
            return;
        }

        // Settings-dən lazımi məlumatları alırıq
        $allowedFormats = $this->settingService->get('upload', 'allowed_file_types.image', []);
        $maxFileSize = $this->settingService->get('upload', 'max_image_size', 1024*5); // KB

        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $allowedFormats)) {
            $fail(str(t('validation.invalidImageType'))
                ->replace(':format', implode(', ', $allowedFormats)));
            return;
        }

        $allowedMimeTypes = collect($allowedFormats)
            ->map(fn($format) => 'image/' . $format)
            ->toArray();

        $mimeType = $value->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $fail(str(t('validation.invalidImageType'))
                ->replace(':format', implode(', ', $allowedFormats)));
            return;
        }

        // Həcm yoxlaması (KB-a çeviririk)
        $sizeInKB = $value->getSize() / 1024;
        if ($sizeInKB > $maxFileSize) {
            $fail(str(t('validation.invalidImageSize'))
                ->replace(':size', ceil($maxFileSize / 1024) . 'MB'));
        }
    }
}
